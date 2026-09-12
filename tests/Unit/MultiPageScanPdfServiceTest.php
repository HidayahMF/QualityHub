<?php

namespace Tests\Unit;

use App\Services\MultiPageScanPdfService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MultiPageScanPdfServiceTest extends TestCase
{
    private string $testDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDirectory = storage_path(
            'framework/testing/multi-scan-pdf-' . bin2hex(random_bytes(6))
        );

        mkdir($this->testDirectory, 0777, true);

        config([
            'smartfind_scan_security.max_files' => 10,
            'smartfind_scan_security.max_combined_pdf_size_bytes' => 10 * 1024 * 1024,
        ]);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->testDirectory);
        parent::tearDown();
    }

    public function test_two_verified_images_can_be_combined_into_one_pdf(): void
    {
        $first = $this->uploadedPng('page-1.png');
        $second = $this->uploadedPng('page-2.png');

        $security = [
            $this->securityResult('page-1.png'),
            $this->securityResult('page-2.png'),
        ];

        $service = app(MultiPageScanPdfService::class);
        $result = $service->build(
            [$first, $second],
            $security,
            'Test Multi Photo QC'
        );

        try {
            $this->assertSame(2, $result['page_count']);
            $this->assertSame('application/pdf', $result['mime_type']);
            $this->assertSame('generated_multi_photo_pdf', $result['signature']);
            $this->assertFileExists($result['path']);
            $this->assertGreaterThan(0, $result['file_size']);

            $header = file_get_contents(
                $result['path'],
                false,
                null,
                0,
                8
            );

            $this->assertIsString($header);
            $this->assertStringStartsWith('%PDF-', $header);
        } finally {
            $service->cleanup($result['path'] ?? null);
        }
    }

    private function uploadedPng(string $name): UploadedFile
    {
        $path = $this->testDirectory . DIRECTORY_SEPARATOR . $name;

        /*
         * PNG 2x2 RGB yang benar-benar valid.
         * Fixture sebelumnya hanya lolos getimagesize(), tetapi payload IDAT-nya
         * memiliki checksum zlib rusak sehingga FPDF gagal dengan gzuncompress().
         */
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAIAAAD91JpzAAAAFklEQVR4nGP8//8/AwMDEwMDAwMDAwAkBgMB/DXemwAAAABJRU5ErkJggg==',
            true
        );

        file_put_contents($path, $png);

        return new UploadedFile(
            $path,
            $name,
            'image/png',
            null,
            true
        );
    }

    private function securityResult(string $name): array
    {
        return [
            'extension' => 'png',
            'mime_type' => 'image/png',
            'server_mime' => 'image/png',
            'file_size' => filesize($this->testDirectory . DIRECTORY_SEPARATOR . $name),
            'width' => 2,
            'height' => 2,
            'pixels' => 4,
            'signature' => 'png',
            'original_name' => $name,
        ];
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
