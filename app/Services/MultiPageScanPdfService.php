<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class MultiPageScanPdfService
{
    /**
     * Gabungkan beberapa foto Scan / Capture menjadi satu PDF multi-halaman.
     *
     * Setiap file HARUS sudah lolos ScanImageSecurityService sebelum masuk ke sini.
     * Service ini tidak menerima file arbitrary selain gambar yang sudah diverifikasi.
     *
     * @param  array<int, UploadedFile>  $files
     * @param  array<int, array<string, mixed>>  $securityResults
     * @return array{path:string,page_count:int,file_size:int,mime_type:string,signature:string}
     */
    public function build(
        array $files,
        array $securityResults,
        ?string $title = null
    ): array {
        if (! class_exists(\FPDF::class)) {
            throw new RuntimeException(
                'Library PDF belum tersedia. Jalankan: composer require setasign/fpdf'
            );
        }

        if (count($files) < 2 || count($files) !== count($securityResults)) {
            throw ValidationException::withMessages([
                'files' => 'Minimal dua foto valid diperlukan untuk membuat PDF multi-halaman.',
            ]);
        }

        $maxFiles = max(
            2,
            (int) config('smartfind_scan_security.max_files', 10)
        );

        if (count($files) > $maxFiles) {
            throw ValidationException::withMessages([
                'files' => "Maksimal {$maxFiles} foto dapat digabung menjadi satu berkas.",
            ]);
        }

        $workDirectory = storage_path('framework/cache/qualityhub-scan-pdf');

        if (! is_dir($workDirectory) && ! mkdir($workDirectory, 0775, true) && ! is_dir($workDirectory)) {
            throw new RuntimeException('Folder temporary PDF tidak dapat dibuat.');
        }

        $pdfPath = $workDirectory
            . DIRECTORY_SEPARATOR
            . 'scan-' . bin2hex(random_bytes(12)) . '.pdf';

        /** @var array<int, string> $temporaryImages */
        $temporaryImages = [];

        try {
            $pdf = new \FPDF('P', 'mm', 'A4');
            $pdf->SetAutoPageBreak(false);
            $pdf->SetMargins(0, 0, 0);

            if (is_string($title) && trim($title) !== '') {
                $pdf->SetTitle(trim($title), true);
            }

            foreach ($files as $index => $file) {
                $security = $securityResults[$index] ?? null;

                if (! is_array($security)) {
                    throw new RuntimeException('Metadata keamanan gambar tidak lengkap.');
                }

                $width = max(1, (int) ($security['width'] ?? 0));
                $height = max(1, (int) ($security['height'] ?? 0));
                $extension = strtolower((string) ($security['extension'] ?? ''));

                $sourcePath = $this->prepareImageForPdf(
                    $file,
                    $extension,
                    $workDirectory,
                    $temporaryImages
                );

                $orientation = $width > $height ? 'L' : 'P';
                $pdf->AddPage($orientation, 'A4');

                $pageWidth = $pdf->GetPageWidth();
                $pageHeight = $pdf->GetPageHeight();
                $margin = 7.0;
                $availableWidth = $pageWidth - ($margin * 2);
                $availableHeight = $pageHeight - ($margin * 2);

                $scale = min(
                    $availableWidth / $width,
                    $availableHeight / $height
                );

                $renderWidth = $width * $scale;
                $renderHeight = $height * $scale;
                $x = ($pageWidth - $renderWidth) / 2;
                $y = ($pageHeight - $renderHeight) / 2;

                $fpdfImageType = (
                    $extension === 'png'
                    && $sourcePath === $file->getRealPath()
                ) ? 'PNG' : 'JPG';

                $pdf->Image(
                    $sourcePath,
                    $x,
                    $y,
                    $renderWidth,
                    $renderHeight,
                    $fpdfImageType
                );
            }

            $pdf->Output('F', $pdfPath);

            if (! is_file($pdfPath) || ! is_readable($pdfPath)) {
                throw new RuntimeException('PDF gabungan gagal dibuat.');
            }

            $fileSize = (int) (filesize($pdfPath) ?: 0);
            $maxPdfBytes = max(
                1,
                (int) config(
                    'smartfind_scan_security.max_combined_pdf_size_bytes',
                    50 * 1024 * 1024
                )
            );

            if ($fileSize <= 0 || $fileSize > $maxPdfBytes) {
                throw ValidationException::withMessages([
                    'files' => 'PDF gabungan terlalu besar untuk disimpan. Kurangi jumlah atau ukuran foto.',
                ]);
            }

            $header = (string) file_get_contents(
                $pdfPath,
                false,
                null,
                0,
                8
            );

            if (! str_starts_with($header, '%PDF-')) {
                throw new RuntimeException('Hasil gabungan tidak memiliki signature PDF yang valid.');
            }

            return [
                'path' => $pdfPath,
                'page_count' => count($files),
                'file_size' => $fileSize,
                'mime_type' => 'application/pdf',
                'signature' => 'generated_multi_photo_pdf',
            ];
        } catch (ValidationException $exception) {
            $this->deleteIfExists($pdfPath);
            throw $exception;
        } catch (Throwable $exception) {
            $this->deleteIfExists($pdfPath);

            throw ValidationException::withMessages([
                'files' => 'Beberapa foto tidak dapat digabung menjadi satu PDF. ' . $exception->getMessage(),
            ]);
        } finally {
            foreach ($temporaryImages as $temporaryImage) {
                $this->deleteIfExists($temporaryImage);
            }
        }
    }

    /**
     * FPDF paling stabil untuk JPG/PNG. Format lain dikonversi ke JPG temporary.
     *
     * @param  array<int, string>  $temporaryImages
     */
    private function prepareImageForPdf(
        UploadedFile $file,
        string $extension,
        string $workDirectory,
        array &$temporaryImages
    ): string {
        $realPath = $file->getRealPath();

        if (! is_string($realPath) || $realPath === '' || ! is_readable($realPath)) {
            throw new RuntimeException('Salah satu gambar tidak dapat dibaca.');
        }

        // JPEG dapat langsung ditanam oleh FPDF tanpa decode penuh.
        if (in_array($extension, ['jpg', 'jpeg'], true)) {
            return $realPath;
        }

        $target = $workDirectory
            . DIRECTORY_SEPARATOR
            . 'img-' . bin2hex(random_bytes(10)) . '.jpg';

        if ($this->convertWithImagick($realPath, $target)) {
            $temporaryImages[] = $target;
            return $target;
        }

        if ($this->convertWithGd($realPath, $target)) {
            $temporaryImages[] = $target;
            return $target;
        }

        // FPDF dapat membaca PNG secara native. Ini menjadi fallback bila server
        // belum memiliki GD/Imagick. Format lain tetap memerlukan converter.
        if ($extension === 'png') {
            return $realPath;
        }

        throw new RuntimeException(
            'Format ' . strtoupper($extension) . ' tidak dapat dikonversi untuk PDF pada server ini. Gunakan JPG/PNG.'
        );
    }

    private function convertWithImagick(string $source, string $target): bool
    {
        if (! class_exists(\Imagick::class)) {
            return false;
        }

        try {
            $image = new \Imagick($source);
            $image->setIteratorIndex(0);
            $image->setImageBackgroundColor('white');
            $flattened = $image->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
            $flattened->setImageFormat('jpeg');
            $flattened->setImageCompressionQuality(90);
            $result = $flattened->writeImage($target);
            $flattened->clear();
            $image->clear();

            return $result && is_file($target) && filesize($target) > 0;
        } catch (Throwable) {
            $this->deleteIfExists($target);
            return false;
        }
    }

    private function convertWithGd(string $source, string $target): bool
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            return false;
        }

        $binary = @file_get_contents($source);

        if (! is_string($binary) || $binary === '') {
            return false;
        }

        $image = @imagecreatefromstring($binary);

        if ($image === false) {
            return false;
        }

        try {
            $width = imagesx($image);
            $height = imagesy($image);
            $canvas = imagecreatetruecolor($width, $height);

            if ($canvas === false) {
                return false;
            }

            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $white);
            imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);

            $result = imagejpeg($canvas, $target, 90);
            imagedestroy($canvas);

            return $result && is_file($target) && filesize($target) > 0;
        } finally {
            imagedestroy($image);
        }
    }

    public function cleanup(?string $path): void
    {
        if (is_string($path) && $path !== '') {
            $this->deleteIfExists($path);
        }
    }

    private function deleteIfExists(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
