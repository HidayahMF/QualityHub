<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ScanImageSecurityService
{
    /**
     * Pemeriksaan khusus file gambar Scan / Capture QC.
     *
     * Lapisan pemeriksaan:
     * - extension whitelist
     * - ukuran file
     * - server-side MIME
     * - magic bytes / signature
     * - struktur gambar melalui getimagesize()
     * - kecocokan extension dengan format gambar sebenarnya
     * - batas dimensi/pixel untuk mengurangi risiko image bomb
     */
    public function validate(UploadedFile $file): array
    {
        if (! $file->isValid()) {
            $this->reject('Foto/berkas gambar yang dipilih tidak valid.');
        }

        $clientExtension = strtolower(
            trim((string) $file->getClientOriginalExtension())
        );

        $allowedExtensions = array_map(
            'strtolower',
            config(
                'smartfind_scan_security.allowed_extensions',
                ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'tif', 'tiff']
            )
        );

        if (
            $clientExtension === ''
            || ! in_array($clientExtension, $allowedExtensions, true)
        ) {
            $this->reject('Format gambar Scan / Capture tidak didukung.');
        }

        $size = (int) ($file->getSize() ?: 0);
        $maxBytes = max(
            1,
            (int) config(
                'smartfind_scan_security.max_file_size_bytes',
                20 * 1024 * 1024
            )
        );

        if ($size <= 0 || $size > $maxBytes) {
            $this->reject('Ukuran gambar tidak valid atau melebihi batas upload Scan / Capture.');
        }

        $realPath = $file->getRealPath();

        if (
            ! is_string($realPath)
            || $realPath === ''
            || ! is_file($realPath)
            || ! is_readable($realPath)
        ) {
            $this->reject('File gambar tidak dapat dibaca untuk pemeriksaan keamanan.');
        }

        $imageInfo = @getimagesize($realPath);

        if (
            ! is_array($imageInfo)
            || ! isset($imageInfo[0], $imageInfo[1], $imageInfo[2])
        ) {
            $this->reject('Isi file tidak dikenali sebagai gambar yang valid.');
        }

        $width = (int) $imageInfo[0];
        $height = (int) $imageInfo[1];
        $imageType = (int) $imageInfo[2];

        $format = $this->formatDefinition($imageType);

        if ($format === null) {
            $this->reject('Format gambar terdeteksi tidak diizinkan untuk Scan / Capture.');
        }

        if (! in_array($clientExtension, $format['extensions'], true)) {
            $this->reject(
                'Extension file tidak sesuai dengan format gambar sebenarnya.'
            );
        }

        $serverMime = strtolower(
            trim((string) ($file->getMimeType() ?: 'application/octet-stream'))
        );

        if (! in_array($serverMime, $format['mimes'], true)) {
            $this->reject(
                'MIME file tidak sesuai dengan format gambar yang diizinkan.'
            );
        }

        $this->validateMagicBytes(
            $realPath,
            $format['signature']
        );

        $maxWidth = max(
            1,
            (int) config('smartfind_scan_security.max_width', 12000)
        );
        $maxHeight = max(
            1,
            (int) config('smartfind_scan_security.max_height', 12000)
        );
        $maxPixels = max(
            1,
            (int) config('smartfind_scan_security.max_pixels', 80000000)
        );

        $pixels = $width * $height;

        if (
            $width <= 0
            || $height <= 0
            || $width > $maxWidth
            || $height > $maxHeight
            || $pixels > $maxPixels
        ) {
            $this->reject(
                'Dimensi gambar terlalu besar atau tidak valid untuk Scan / Capture.'
            );
        }

        return [
            'extension' => $format['canonical_extension'],
            'mime_type' => $format['canonical_mime'],
            'server_mime' => $serverMime,
            'file_size' => $size,
            'width' => $width,
            'height' => $height,
            'pixels' => $pixels,
            'signature' => $format['signature'],
            'original_name' => $this->sanitizeOriginalName(
                $file->getClientOriginalName(),
                $format['canonical_extension']
            ),
        ];
    }

    private function formatDefinition(int $imageType): ?array
    {
        return match ($imageType) {
            IMAGETYPE_JPEG => [
                'extensions' => ['jpg', 'jpeg'],
                'canonical_extension' => 'jpg',
                'mimes' => ['image/jpeg', 'image/pjpeg'],
                'canonical_mime' => 'image/jpeg',
                'signature' => 'jpeg',
            ],
            IMAGETYPE_PNG => [
                'extensions' => ['png'],
                'canonical_extension' => 'png',
                'mimes' => ['image/png', 'image/x-png'],
                'canonical_mime' => 'image/png',
                'signature' => 'png',
            ],
            IMAGETYPE_GIF => [
                'extensions' => ['gif'],
                'canonical_extension' => 'gif',
                'mimes' => ['image/gif'],
                'canonical_mime' => 'image/gif',
                'signature' => 'gif',
            ],
            IMAGETYPE_BMP => [
                'extensions' => ['bmp'],
                'canonical_extension' => 'bmp',
                'mimes' => ['image/bmp', 'image/x-ms-bmp', 'image/x-bmp'],
                'canonical_mime' => 'image/bmp',
                'signature' => 'bmp',
            ],
            IMAGETYPE_WEBP => [
                'extensions' => ['webp'],
                'canonical_extension' => 'webp',
                'mimes' => ['image/webp'],
                'canonical_mime' => 'image/webp',
                'signature' => 'webp',
            ],
            IMAGETYPE_TIFF_II,
            IMAGETYPE_TIFF_MM => [
                'extensions' => ['tif', 'tiff'],
                'canonical_extension' => 'tiff',
                'mimes' => ['image/tiff', 'image/x-tiff'],
                'canonical_mime' => 'image/tiff',
                'signature' => 'tiff',
            ],
            default => null,
        };
    }

    private function validateMagicBytes(
        string $path,
        string $signature
    ): void {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            $this->reject('Signature gambar tidak dapat dibaca.');
        }

        try {
            $bytes = fread($handle, 16);
        } finally {
            fclose($handle);
        }

        if ($bytes === false) {
            $this->reject('Signature gambar tidak dapat dibaca.');
        }

        $valid = match ($signature) {
            'jpeg' => str_starts_with($bytes, "\xFF\xD8\xFF"),
            'png' => str_starts_with($bytes, "\x89PNG\r\n\x1A\n"),
            'gif' => str_starts_with($bytes, 'GIF87a')
                || str_starts_with($bytes, 'GIF89a'),
            'bmp' => str_starts_with($bytes, 'BM'),
            'webp' => strlen($bytes) >= 12
                && substr($bytes, 0, 4) === 'RIFF'
                && substr($bytes, 8, 4) === 'WEBP',
            'tiff' => str_starts_with($bytes, "II*\x00")
                || str_starts_with($bytes, "MM\x00*"),
            default => false,
        };

        if (! $valid) {
            $this->reject(
                'Magic bytes/signature file tidak sesuai dengan format gambar.'
            );
        }
    }

    private function sanitizeOriginalName(
        string $fileName,
        string $extension
    ): string {
        $fileName = basename(
            str_replace('\\', '/', $fileName)
        );

        $fileName = preg_replace(
            '/[\x00-\x1F\x7F\x{202A}-\x{202E}\x{2066}-\x{2069}]/u',
            '',
            $fileName
        ) ?? '';

        $baseName = pathinfo(trim($fileName), PATHINFO_FILENAME);
        $baseName = trim($baseName);

        if ($baseName === '') {
            $baseName = 'scan-image';
        }

        $maxBaseLength = 220;

        if (mb_strlen($baseName) > $maxBaseLength) {
            $baseName = mb_substr($baseName, 0, $maxBaseLength);
        }

        return $baseName . '.' . $extension;
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages([
            'file' => $message,
        ]);
    }
}
