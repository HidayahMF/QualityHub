<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentPurgeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class BulkDocumentDeletionController extends Controller
{
    /**
     * Purge up to one page of explicitly selected active test documents.
     * Never infer "dummy" from names or delete anything without typed consent.
     */
    public function __invoke(Request $request, DocumentPurgeService $purgeService): RedirectResponse
    {
        abort_unless(Auth::check() && Auth::user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'document_ids' => ['required', 'array', 'min:1', 'max:15'],
            'document_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'selected_count' => ['required', 'integer', 'min:1', 'max:15'],
            'acknowledge_dummy' => ['required', 'accepted'],
            'confirmation' => ['required', 'string', Rule::in(['HAPUS'])],
        ], [
            'document_ids.required' => 'Pilih dokumen yang benar-benar data dummy terlebih dahulu.',
            'document_ids.max' => 'Maksimal 15 dokumen (satu halaman) per penghapusan.',
            'acknowledge_dummy.accepted' => 'Pastikan dahulu seluruh pilihan adalah data dummy.',
            'confirmation.in' => 'Ketik HAPUS persis untuk mengonfirmasi.',
        ]);

        $ids = array_map('intval', $validated['document_ids']);

        if (count($ids) !== (int) $validated['selected_count']) {
            throw ValidationException::withMessages([
                'document_ids' => 'Jumlah dokumen berubah. Pilih ulang dokumen untuk mencegah salah hapus.',
            ]);
        }

        // Reject a mixed, stale, missing, or archived selection before
        // deleting any files. Each item is then row-locked by the purge service.
        $activeCount = Document::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->count();

        if ($activeCount !== count($ids)) {
            throw ValidationException::withMessages([
                'document_ids' => 'Sebagian dokumen tidak lagi aktif/tersedia. Muat ulang dan pilih ulang.',
            ]);
        }

        $batchId = (string) Str::uuid();
        $deleted = 0;
        $cleanupFailures = 0;

        foreach ($ids as $id) {
            try {
                $result = $purgeService->purgeActiveSelection(
                    $id,
                    Auth::user(),
                    $request->ip(),
                    $request->userAgent(),
                    $batchId
                );

                $deleted++;
                $cleanupFailures += $result['failed_file_count'];
            } catch (Throwable $exception) {
                report($exception);

                return redirect()
                    ->route('documents.index')
                    ->with(
                        'warning',
                        $deleted.' dokumen terhapus, tetapi proses terhenti pada dokumen berikutnya. '
                        .'Periksa Activity Log dan muat ulang sebelum melanjutkan.'
                    );
            }
        }

        if ($cleanupFailures > 0) {
            return redirect()
                ->route('documents.index')
                ->with(
                    'warning',
                    $deleted.' dokumen dihapus dari database, tetapi '
                    .$cleanupFailures.' file belum bersih. Hubungi administrator dan periksa log server.'
                );
        }

        return redirect()
            ->route('documents.index')
            ->with('success', $deleted.' dokumen dummy terpilih berhasil dihapus permanen.');
    }
}
