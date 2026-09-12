<?php

namespace Tests\Unit;

use App\Services\ScanImageSecurityService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ScanImageSecurityServiceTest extends TestCase
{
    private string $testDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDirectory = storage_path(
            'framework/testing/scan-security-' . bin2hex(random_bytes(6))
        );

        mkdir($this->testDirectory, 0777, true);

        config([
            'smartfind_scan_security.allowed_extensions' => [
                'jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'tif', 'tiff',
            ],
            'smartfind_scan_security.max_file_size_bytes' => 20 * 1024 * 1024,
            'smartfind_scan_security.max_width' => 12000,
            'smartfind_scan_security.max_height' => 12000,
            'smartfind_scan_security.max_pixels' => 80000000,
        ]);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->testDirectory);

        parent::tearDown();
    }

    public function test_valid_png_is_accepted(): void
    {
        $path = $this->writePng('valid.png');

        $file = new UploadedFile(
            $path,
            'valid.png',
            'image/png',
            null,
            true
        );

        $result = app(ScanImageSecurityService::class)->validate($file);

        $this->assertSame('png', $result['extension']);
        $this->assertSame('image/png', $result['mime_type']);
        $this->assertSame(1, $result['width']);
        $this->assertSame(1, $result['height']);
    }

    public function test_fake_png_payload_is_rejected(): void
    {
        $path = $this->testDirectory . DIRECTORY_SEPARATOR . 'fake.png';
        file_put_contents($path, '<?php echo "not an image";');

        $file = new UploadedFile(
            $path,
            'fake.png',
            'image/png',
            null,
            true
        );

        $this->expectException(ValidationException::class);

        app(ScanImageSecurityService::class)->validate($file);
    }

    public function test_extension_mismatch_is_rejected(): void
    {
        $path = $this->writePng('mismatch.jpg');

        $file = new UploadedFile(
            $path,
            'mismatch.jpg',
            'image/jpeg',
            null,
            true
        );

        $this->expectException(ValidationException::class);

        app(ScanImageSecurityService::class)->validate($file);
    }

    private function writePng(string $name): string
    {
        $path = $this->testDirectory . DIRECTORY_SEPARATOR . $name;

        // PNG 1x1 yang valid.
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Z2uEAAAAASUVORK5CYII=',
            true
        );

        file_put_contents($path, $png);

        return $path;
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory) ?: [];

        foreach ($items as $item) {
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
