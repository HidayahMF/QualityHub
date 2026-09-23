<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class DocumentPurgeService
{
    public function __construct(
        private readonly PrivateDocumentStorageService $privateStorage
    ) {}

    /** @return array{failed_file_count: int, preserved_shared_file_count: int} */
    public function purge(
        int $documentId,
        User $actor,
        ?string $ipAddress,
        ?string $userAgent
    ): array {
        $result = DB::transaction(function () use (
            $documentId,
            $actor,
            $ipAddress,
            $userAgent
        ): array {
            $document = Document::query()
                ->with('category:id,name')
                ->lockForUpdate()
                ->findOrFail($documentId);

            if ($document->is_active) {
                throw ValidationException::withMessages([
                    'document' => 'Dokumen aktif harus diarsipkan sebelum dapat dihapus permanen.',
                ]);
            }

            $versionPaths = DocumentVersion::query()
                ->where('document_id', $documentId)
                ->lockForUpdate()
                ->pluck('file_path')
                ->all();

            $paths = array_values(array_unique([
                (string) $document->file_path,
                ...array_map('strval', $versionPaths),
            ]));

            $disk = Storage::disk('bmc_documents');

            foreach ($paths as $path) {
                if (! $this->privateStorage->isSafeRelativePath($path)) {
                    throw ValidationException::withMessages([
                        'document' => 'Referensi file dokumen tidak aman. Hubungi administrator.',
                    ]);
                }

                if (
                    $disk->exists($path)
                    && $this->privateStorage->resolveExistingFile($path) === null
                ) {
                    throw ValidationException::withMessages([
                        'document' => 'Lokasi file dokumen tidak dapat diverifikasi. Hubungi administrator.',
                    ]);
                }
            }

            $exclusivePaths = [];
            $sharedCount = 0;

            foreach ($paths as $path) {
                if ($this->isReferencedByAnotherDocument($path, $documentId)) {
                    $sharedCount++;
                } else {
                    $exclusivePaths[] = $path;
                }
            }

            $analyticsIds = DB::table('document_analytics')
                ->where('document_id', $documentId)
                ->lockForUpdate()
                ->pluck('id');

            if ($analyticsIds->isNotEmpty()) {
                DB::table('document_analytic_sheets')
                    ->whereIn('document_analytics_id', $analyticsIds)
                    ->delete();
            }

            DB::table('document_analytics')
                ->where('document_id', $documentId)
                ->delete();

            DB::table('document_versions')
                ->where('document_id', $documentId)
                ->delete();

            foreach ([
                'document_retoolings',
                'document_axle_productions',
                'document_brake_productions',
                'document_scan_metadata',
                'favorites',
            ] as $table) {
                DB::table($table)
                    ->where('document_id', $documentId)
                    ->delete();
            }

            $audit = ActivityLog::create([
                'user_id' => $actor->id,
                'document_id' => null,
                'action' => 'permanent_delete_document',
                'description' => $actor->name
                    .' menghapus permanen dokumen "'
                    .$document->title
                    .'".',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'metadata' => [
                    'document_id' => $document->id,
                    'document_title' => $document->title,
                    'document_code' => $document->document_code,
                    'category_id' => $document->category_id,
                    'category_name' => $document->category?->name,
                    'original_file_name' => $document->file_name,
                    'actor_user_id' => $actor->id,
                    'actor_user_name' => $actor->name,
                    'actor_user_email' => $actor->email,
                    'version_count' => count($versionPaths),
                    'reason' => 'permanent_delete',
                    'deleted_at' => now()->toIso8601String(),
                    'file_cleanup_status' => 'pending',
                    'preserved_shared_file_count' => $sharedCount,
                ],
            ]);

            $document->delete();

            return [
                'audit_id' => $audit->id,
                'paths' => $exclusivePaths,
                'shared_count' => $sharedCount,
            ];
        });

        $failedPaths = [];
        $preservedSharedCount = $result['shared_count'];

        try {
            $disk = Storage::disk('bmc_documents');
        } catch (Throwable $exception) {
            report($exception);
            $disk = null;
            $failedPaths = $result['paths'];
        }

        foreach ($disk === null ? [] : $result['paths'] as $path) {
            try {
                // A file may also be referenced by another record after commit.
                if ($this->isReferencedByAnotherDocument($path, $documentId)) {
                    $preservedSharedCount++;

                    continue;
                }

                if (! $disk->exists($path)) {
                    continue;
                }

                if (
                    $this->privateStorage->resolveExistingFile($path) === null
                    || ! $disk->delete($path)
                    || $disk->exists($path)
                ) {
                    $failedPaths[] = $path;
                }
            } catch (Throwable $exception) {
                $failedPaths[] = $path;
                report($exception);
            }
        }

        if ($failedPaths !== []) {
            Log::error('Pembersihan file setelah hapus permanen dokumen gagal.', [
                'document_id' => $documentId,
                'failed_paths' => $failedPaths,
            ]);
        }

        try {
            $audit = ActivityLog::query()->findOrFail($result['audit_id']);
            $audit->metadata = array_merge($audit->metadata ?? [], [
                'file_cleanup_status' => $failedPaths === [] ? 'complete' : 'failed',
                'failed_file_count' => count($failedPaths),
                'preserved_shared_file_count' => $preservedSharedCount,
            ]);
            $audit->save();
        } catch (Throwable $exception) {
            report($exception);
        }

        return [
            'failed_file_count' => count($failedPaths),
            'preserved_shared_file_count' => $preservedSharedCount,
        ];
    }

    private function isReferencedByAnotherDocument(
        string $path,
        int $documentId
    ): bool {
        if (Document::query()
            ->whereKeyNot($documentId)
            ->where('file_path', $path)
            ->exists()
            || DocumentVersion::query()
                ->where('document_id', '!=', $documentId)
                ->where('file_path', $path)
                ->exists()) {
            return true;
        }

        $canonicalPath = $this->privateStorage->resolveExistingFile($path);

        if ($canonicalPath === null) {
            return false;
        }

        foreach (['documents', 'document_versions'] as $table) {
            $referenced = false;
            $query = DB::table($table)->select(['id', 'file_path']);

            if ($table === 'documents') {
                $query->where('id', '!=', $documentId);
            } else {
                $query->where('document_id', '!=', $documentId);
            }

            $query->chunkById(250, function ($rows) use ($canonicalPath, &$referenced) {
                foreach ($rows as $row) {
                    $otherPath = $this->privateStorage->resolveExistingFile($row->file_path);

                    if ($otherPath !== null && $this->sameCanonicalPath($canonicalPath, $otherPath)) {
                        $referenced = true;

                        return false;
                    }
                }
            });

            if ($referenced) {
                return true;
            }
        }

        return false;
    }

    private function sameCanonicalPath(string $first, string $second): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return mb_strtolower($first) === mb_strtolower($second);
        }

        return $first === $second;
    }
}
