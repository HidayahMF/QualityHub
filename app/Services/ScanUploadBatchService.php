<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ScanUploadBatchService
{
    private const PREFIX = 'scan-upload-batches';

    private const CHUNK_LIMIT = 10;

    private const MAX_AGE_SECONDS = 12 * 3600;

    private function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk('local');
    }

    private function directory(string $batchId): string
    {
        if (! Str::isUuid($batchId)) {
            throw ValidationException::withMessages([
                'batch_id' => 'Sesi upload tidak valid. Ulangi proses upload foto.',
            ]);
        }

        return self::PREFIX.'/'.$batchId;
    }

    private function withLock(string $batchId, \Closure $callback): mixed
    {
        return Cache::lock('qualityhub-scan-batch-'.$batchId, 180)
            ->block(15, $callback);
    }

    private function readManifest(string $batchId, int $ownerId): array
    {
        $path = $this->directory($batchId).'/manifest.json';
        if (! $this->disk()->exists($path)) {
            throw ValidationException::withMessages([
                'batch_id' => 'Sesi upload tidak ditemukan atau sudah kedaluwarsa.',
            ]);
        }
        $raw = $this->disk()->get($path);
        $manifest = is_string($raw) ? json_decode($raw, true) : null;

        if (! is_array($manifest)
            || (int) ($manifest['owner_id'] ?? 0) !== $ownerId
            || (int) ($manifest['created_at'] ?? 0) < time() - self::MAX_AGE_SECONDS) {
            throw ValidationException::withMessages([
                'batch_id' => 'Sesi upload berakhir atau tidak tersedia. Mulai ulang upload.',
            ]);
        }

        return $manifest;
    }

    public function start(int $ownerId, int $expectedCount): array
    {
        $maxCount = max(1, (int) config('smartfind_scan_security.max_files', 100));

        if ($expectedCount < 1 || $expectedCount > $maxCount) {
            throw ValidationException::withMessages([
                'expected_photo_count' => "Jumlah halaman harus antara 1 dan {$maxCount}.",
            ]);
        }

        $this->disk()->makeDirectory(self::PREFIX);
        $this->pruneExpired();

        // Restrict private temporary storage use to two simultaneous batches.
        $active = 0;
        foreach ($this->disk()->directories(self::PREFIX) as $folder) {
            $raw = $this->disk()->get($folder.'/manifest.json');
            $manifest = is_string($raw) ? json_decode($raw, true) : null;

            if (is_array($manifest)
                && (int) ($manifest['owner_id'] ?? 0) === $ownerId
                && (int) ($manifest['created_at'] ?? 0) >= time() - self::MAX_AGE_SECONDS) {
                $active++;
            }
        }

        if ($active >= 2) {
            throw ValidationException::withMessages([
                'files' => 'Maksimal dua proses upload aktif. Selesaikan atau batalkan upload sebelumnya.',
            ]);
        }

        $id = (string) Str::uuid();
        $folder = $this->directory($id);
        $this->disk()->makeDirectory($folder);

        $manifest = [
            'owner_id' => $ownerId,
            'created_at' => time(),
            'expected_count' => $expectedCount,
            'count' => 0,
            'bytes' => 0,
            'files' => [],
        ];

        if (! $this->disk()->put($folder.'/manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR))) {
            $this->disk()->deleteDirectory($folder);
            throw new RuntimeException('Tidak bisa menyiapkan sesi upload foto.');
        }

        return ['batch_id' => $id, 'expected_count' => $expectedCount];
    }

    /**
     * @param array<int, UploadedFile> $files
     */
    public function append(string $id, int $ownerId, int $offset, array $files, ScanImageSecurityService $securityService): array
    {
        if (count($files) < 1 || count($files) > self::CHUNK_LIMIT) {
            throw ValidationException::withMessages([
                'files' => 'Unggah antara 1 dan 10 foto per kelompok.',
            ]);
        }

        return $this->withLock($id, function () use ($id, $ownerId, $offset, $files, $securityService) {
            $manifest = $this->readManifest($id, $ownerId);

            if ($offset !== (int) $manifest['count']) {
                throw ValidationException::withMessages([
                    'offset' => 'Urutan upload berubah. Jangan menjalankan beberapa upload bersamaan.',
                ]);
            }

            if ($offset + count($files) > (int) $manifest['expected_count']) {
                throw ValidationException::withMessages([
                    'files' => 'Jumlah foto melebihi total yang dipilih.',
                ]);
            }

            $maxBytes = max(1, (int) config('smartfind_scan_security.max_total_source_bytes'));
            $pending = [];
            $pendingBytes = 0;
            $folder = $this->directory($id);

            try {
                foreach ($files as $index => $file) {
                    if (! $file instanceof UploadedFile || ! $file->isValid()) {
                        throw ValidationException::withMessages([
                            'files' => 'Salah satu foto tidak valid.',
                        ]);
                    }

                    // Revalidate each chunk on the server before writing it to
                    // the private staging disk. Never trust browser MIME/name.
                    $verified = $securityService->validate($file);
                    $size = (int) $verified['file_size'];
                    $pendingBytes += $size;

                    if ((int) $manifest['bytes'] + $pendingBytes > $maxBytes) {
                        throw ValidationException::withMessages([
                            'files' => 'Total foto melebihi batas ukuran dokumen scan. Kompres foto atau kurangi halaman.',
                        ]);
                    }

                    $number = $offset + $index + 1;
                    $safeName = sprintf('photo-%04d.%s', $number, $verified['extension']);
                    $path = $file->storeAs($folder, $safeName, 'local');

                    if (! is_string($path) || $path === '') {
                        throw new RuntimeException('Gagal menyimpan sebagian foto ke penyimpanan sementara.');
                    }

                    $pending[] = [
                        'filename' => $safeName,
                        'size' => $size,
                    ];
                }

                $manifest['files'] = [...$manifest['files'], ...$pending];
                $manifest['count'] += count($pending);
                $manifest['bytes'] += $pendingBytes;

                if (! $this->disk()->put($folder.'/manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR))) {
                    throw new RuntimeException('Gagal menyimpan status upload foto.');
                }
            } catch (\Throwable $exception) {
                foreach ($pending as $entry) {
                    $this->disk()->delete($folder.'/'.$entry['filename']);
                }
                throw $exception;
            }

            return [
                'batch_id' => $id,
                'received' => $manifest['count'],
                'expected' => $manifest['expected_count'],
                'total_bytes' => $manifest['bytes'],
            ];
        });
    }

    /**
     * Only server-generated filenames are materialized into UploadedFile.
     *
     * @return array<int, UploadedFile>
     */
    public function filesForCompletion(string $id, int $ownerId, int $expectedCount): array
    {
        return $this->withLock($id, function () use ($id, $ownerId, $expectedCount) {
            $manifest = $this->readManifest($id, $ownerId);

            if ($expectedCount !== (int) $manifest['expected_count']
                || (int) $manifest['count'] !== (int) $manifest['expected_count']
                || count($manifest['files'] ?? []) !== $expectedCount) {
                throw ValidationException::withMessages([
                    'files' => 'Upload belum lengkap. Semua foto harus terunggah sebelum PDF dibuat.',
                ]);
            }

            $files = [];
            $folder = $this->directory($id);
            foreach ($manifest['files'] as $index => $entry) {
                $name = $entry['filename'] ?? '';
                if (! is_string($name)
                    || ! preg_match('/^photo-\d{4}\.(jpg|jpeg|png|gif|bmp|webp|tif|tiff)$/', $name)
                    || $name !== sprintf('photo-%04d.%s', $index + 1, pathinfo($name, PATHINFO_EXTENSION))) {
                    throw ValidationException::withMessages([
                        'files' => 'Status upload foto tidak valid.',
                    ]);
                }

                $filePath = $this->disk()->path($folder.'/'.$name);

                if (! is_file($filePath)
                    || ! is_readable($filePath)
                    || filesize($filePath) !== (int) $entry['size']) {
                    throw ValidationException::withMessages([
                        'files' => 'Sebagian foto sementara tidak tersedia. Ulangi upload.',
                    ]);
                }

                $files[] = new UploadedFile(
                    $filePath,
                    $name,
                    null,
                    null,
                    true
                );
            }

            return $files;
        });
    }

    public function discard(string $id, int $ownerId): void
    {
        $this->withLock($id, function () use ($id, $ownerId) {
            $this->readManifest($id, $ownerId);
            $this->disk()->deleteDirectory($this->directory($id));
        });
    }

    private function pruneExpired(): void
    {
        foreach ($this->disk()->directories(self::PREFIX) as $folder) {
            $raw = $this->disk()->get($folder.'/manifest.json');
            $manifest = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($manifest)
                && (int) ($manifest['created_at'] ?? 0) >= time() - self::MAX_AGE_SECONDS) {
                continue;
            }

            // Only delete paths under our own private staging prefix.
            if (str_starts_with($folder, self::PREFIX.'/')) {
                $this->disk()->deleteDirectory($folder);
            }
        }
    }
}
