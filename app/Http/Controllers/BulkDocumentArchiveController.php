<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Services\InputSanitizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class BulkDocumentArchiveController extends Controller
{
    /**
     * Archive only the explicitly selected documents on the current page.
     * The entire batch is one DB transaction: all documents + audit logs,
     * or no state changes. No stored files or revisions are deleted.
     */
    public function __invoke(
        Request $request,
        InputSanitizationService $input
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor?->isAdmin(), 403);

        $request->merge([
            'archive_reason' => $input->multiline($request->input('archive_reason')),
        ]);

        $validated = $request->validate([
            'document_ids' => ['required', 'array', 'min:1', 'max:15'],
            'document_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'selected_count' => ['required', 'integer', 'min:1', 'max:15'],
            'archive_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'document_ids.required' => 'Pilih dokumen yang akan diarsipkan.',
            'document_ids.max' => 'Maksimal 15 dokumen (satu halaman) per proses arsip.',
            'archive_reason.required' => 'Alasan arsip wajib diisi.',
        ]);

        $ids = array_map('intval', $validated['document_ids']);
        if (count($ids) !== (int) $validated['selected_count']) {
            throw ValidationException::withMessages([
                'document_ids' => 'Jumlah dokumen berubah. Muat ulang dan pilih ulang dokumen.',
            ]);
        }

        $batchId = (string) Str::uuid();
        $reason = $validated['archive_reason'];

        try {
            $archivedCount = DB::transaction(function () use (
                $ids,
                $actor,
                $request,
                $reason,
                $batchId
            ): int {
                // Deterministic row-lock order prevents competing bulk requests
                // from leaving a partially archived selection.
                $documents = Document::query()
                    ->whereIn('id', $ids)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if (
                    $documents->count() !== count($ids)
                    || $documents->contains(
                        static fn (Document $document): bool => ! $document->is_active
                    )
                ) {
                    throw ValidationException::withMessages([
                        'document_ids' => 'Ada dokumen yang tidak tersedia atau sudah diarsipkan. Tidak ada dokumen yang diubah; muat ulang dan pilih ulang.',
                    ]);
                }

                $archivedAt = now();

                foreach ($documents as $document) {
                    $document->update([
                        'is_active' => false,
                        'archived_at' => $archivedAt,
                        'archived_by' => $actor->id,
                        'archive_reason' => $reason,
                    ]);

                    ActivityLog::create([
                        'user_id' => $actor->id,
                        'document_id' => $document->id,
                        'action' => 'archive_document',
                        'description' => $actor->name.' mengarsipkan dokumen "'.$document->title.'".',
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'metadata' => [
                            'document_id' => $document->id,
                            'document_title' => $document->title,
                            'document_code' => $document->document_code,
                            'archive_reason' => $reason,
                            'archived_at' => $archivedAt->toDateTimeString(),
                            'archived_by_user_id' => $actor->id,
                            'physical_file_deleted' => false,
                            'revision_history_deleted' => false,
                            'current_revision_at_archive' => $document->current_revision,
                            'bulk_operation_id' => $batchId,
                            'bulk_action' => 'archive',
                        ],
                    ]);
                }

                return $documents->count();
            }, 3);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'document_ids' => 'Pengarsipan massal gagal. Semua perubahan telah dibatalkan. Coba lagi atau hubungi administrator.',
            ]);
        }

        return redirect()
            ->route('documents.archived')
            ->with(
                'success',
                $archivedCount.' dokumen terpilih berhasil diarsipkan. Seluruh file dan revisi tetap tersimpan.'
            );
    }
}
