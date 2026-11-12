<?php

namespace App\Http\Controllers;

use App\Services\ScanImageSecurityService;
use App\Services\ScanUploadBatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScanUploadBatchController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(Auth::check() && Auth::user()->isAdmin(), 403);
    }

    public function start(Request $request, ScanUploadBatchService $batches): JsonResponse
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'expected_count' => ['required', 'integer', 'min:1', 'max:'.config('smartfind_scan_security.max_files', 1000)],
        ]);

        return response()->json(
            $batches->start((int) Auth::id(), (int) $validated['expected_count']),
            201
        );
    }

    public function upload(
        Request $request,
        ScanUploadBatchService $batches,
        ScanImageSecurityService $imageSecurity
    ): JsonResponse {
        $this->ensureAdmin();

        $fileLimitKb = max(
            1,
            (int) floor(((int) config('smartfind_scan_security.max_file_size_bytes')) / 1024)
        );

        $validated = $request->validate([
            'batch_id' => ['required', 'uuid'],
            'offset' => ['required', 'integer', 'min:0', 'max:999'],
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => [
                'required',
                'file',
                'max:'.$fileLimitKb,
                'mimes:jpg,jpeg,png,webp,gif,bmp,tif,tiff',
            ],
        ]);

        return response()->json(
            $batches->append(
                $validated['batch_id'],
                (int) Auth::id(),
                (int) $validated['offset'],
                array_values($request->file('files')),
                $imageSecurity
            )
        );
    }

    public function discard(string $batchId, ScanUploadBatchService $batches): JsonResponse
    {
        $this->ensureAdmin();
        $batches->discard($batchId, (int) Auth::id());

        return response()->json(['discarded' => true]);
    }
}
