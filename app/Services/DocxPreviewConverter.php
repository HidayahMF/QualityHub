<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Phar;
use PharData;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Menyediakan PDF sementara dari DOCX yang disimpan secara privat.
 * File DOCX asli tidak pernah diganti atau dipindahkan.
 */
final class DocxPreviewConverter
{
    public function convert(string $sourcePath): string
    {
        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            throw new RuntimeException('Dokumen tidak dapat dibaca.');
        }

        $maxSource = (int) config('smartfind_docx_preview.max_source_bytes', 20 * 1024 * 1024);
        $sourceSize = filesize($sourcePath);

        if ($sourceSize === false || $sourceSize < 4 || $sourceSize > $maxSource) {
            throw new RuntimeException('Ukuran DOCX tidak didukung untuk pratinjau.');
        }

        $this->validateDocx($sourcePath);

        $binary = trim((string) config('smartfind_docx_preview.binary', ''));
        if ($binary === '' || ! is_file($binary)) {
            throw new RuntimeException('LibreOffice belum tersedia pada server.');
        }

        // Semua artefak berada di storage privat, bukan public/build atau public/storage.
        $root = storage_path('app/docx-preview');
        $work = $root . DIRECTORY_SEPARATOR . 'work-' . bin2hex(random_bytes(16));
        $sourceDir = $work . DIRECTORY_SEPARATOR . 'source';
        $outputDir = $work . DIRECTORY_SEPARATOR . 'output';
        $profileDir = $work . DIRECTORY_SEPARATOR . 'profile';
        $responsesDir = $root . DIRECTORY_SEPARATOR . 'responses';

        try {
            foreach ([$sourceDir, $outputDir, $profileDir, $responsesDir] as $dir) {
                File::ensureDirectoryExists($dir, 0700, true);
            }

            $input = $sourceDir . DIRECTORY_SEPARATOR . 'document.docx';
            $expectedPdf = $outputDir . DIRECTORY_SEPARATOR . 'document.pdf';

            if (! copy($sourcePath, $input)) {
                throw new RuntimeException('Gagal menyiapkan DOCX.');
            }

            // Symfony Process array menghindari eksekusi perintah shell dari filename.
            // Profil LibreOffice unik untuk setiap request agar tidak saling bentrok.
            $process = new Process(
                [
                    $binary,
                    '-env:UserInstallation=' . $this->fileUri($profileDir),
                    '--headless',
                    '--nologo',
                    '--nodefault',
                    '--nofirststartwizard',
                    '--convert-to',
                    'pdf:writer_pdf_Export',
                    '--outdir',
                    $outputDir,
                    $input,
                ],
                $work
            );
            $process->setTimeout(
                max(10, min(120, (int) config('smartfind_docx_preview.timeout', 60)))
            );
            $process->run();

            if (! $process->isSuccessful() || ! is_file($expectedPdf)) {
                throw new RuntimeException('LibreOffice gagal membuat PDF.');
            }

            $pdfSize = filesize($expectedPdf);
            if (
                $pdfSize === false
                || $pdfSize < 8
                || $pdfSize > (int) config('smartfind_docx_preview.max_pdf_bytes', 40 * 1024 * 1024)
            ) {
                throw new RuntimeException('Ukuran PDF hasil konversi tidak valid.');
            }

            $head = fopen($expectedPdf, 'rb');
            if ($head === false) {
                throw new RuntimeException('PDF tidak dapat dibaca.');
            }

            try {
                $signature = fread($head, 5);
            } finally {
                fclose($head);
            }

            if ($signature !== '%PDF-') {
                throw new RuntimeException('Hasil konversi bukan PDF.');
            }

            $previewFile = $responsesDir . DIRECTORY_SEPARATOR
                . 'preview-' . bin2hex(random_bytes(16)) . '.pdf';

            if (! File::move($expectedPdf, $previewFile)) {
                throw new RuntimeException('Gagal menyiapkan respons PDF.');
            }

            // Pemanggil wajib mengaktifkan deleteFileAfterSend(true).
            return $previewFile;
        } finally {
            // DOCX kerja + profil sementara tidak boleh tertinggal.
            if (is_dir($work)) {
                File::deleteDirectory($work);
            }
        }
    }

    private function validateDocx(string $path): void
    {
        // Phar dapat membaca ZIP tanpa memerlukan ekstensi php_zip tambahan.
        try {
            $archive = new PharData($path);

            if (
                ! $archive->isFileFormat(Phar::ZIP)
                || ! $archive->offsetExists('[Content_Types].xml')
                || ! $archive->offsetExists('word/document.xml')
            ) {
                throw new RuntimeException('Struktur DOCX tidak valid.');
            }

            $entryCount = 0;
            $expandedBytes = 0;

            foreach (new \RecursiveIteratorIterator($archive) as $entry) {
                $entryCount++;
                $expandedBytes += (int) $entry->getSize();

                if ($entryCount > 3000 || $expandedBytes > 120 * 1024 * 1024) {
                    throw new RuntimeException('Konten DOCX terlalu besar.');
                }
            }
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new RuntimeException('Berkas DOCX rusak atau tidak valid.', 0, $exception);
        }
    }

    /** Membentuk file URI untuk profil LibreOffice pada Windows maupun Linux. */
    private function fileUri(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $isWindowsDrive = preg_match('/^[A-Za-z]:\//', $path) === 1;
        $parts = explode('/', ltrim($path, '/'));

        foreach ($parts as $index => &$part) {
            // "C:" tidak perlu di-escape menjadi "C%3A".
            if (! ($isWindowsDrive && $index === 0)) {
                $part = rawurlencode($part);
            }
        }
        unset($part);

        return 'file:///' . implode('/', $parts);
    }
}
