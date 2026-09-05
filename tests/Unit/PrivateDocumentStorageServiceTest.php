<?php

namespace Tests\Unit;

use App\Services\PrivateDocumentStorageService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateDocumentStorageServiceTest extends TestCase
{
    private string $testRoot;


    protected function setUp(): void
    {
        parent::setUp();

        $this->testRoot =
            storage_path(
                'framework/testing/smartfind-private-storage'
            );

        if (! is_dir($this->testRoot)) {
            mkdir(
                $this->testRoot,
                0777,
                true
            );
        }

        config([
            'filesystems.disks.bmc_documents' => [
                'driver' =>
                    'local',

                'root' =>
                    $this->testRoot,

                'throw' =>
                    false,
            ],
        ]);

        /*
        | Pastikan filesystem manager tidak memakai disk instance
        | dari test sebelumnya.
        */

        Storage::purge(
            'bmc_documents'
        );
    }


    protected function tearDown(): void
    {
        Storage::purge(
            'bmc_documents'
        );

        $this->deleteDirectory(
            $this->testRoot
        );

        parent::tearDown();
    }


    public function test_generated_relative_path_is_accepted(): void
    {
        $service =
            app(
                PrivateDocumentStorageService::class
            );

        $this->assertTrue(
            $service->isSafeRelativePath(
                'quality-control/final-inspection/document-10/revisions/rev-001/file.xlsx'
            )
        );
    }


    public function test_path_traversal_is_rejected(): void
    {
        $service =
            app(
                PrivateDocumentStorageService::class
            );

        $unsafePaths = [
            '../outside.pdf',
            '../../outside.xlsx',
            'folder/../outside.pdf',
            'folder/./file.pdf',
            '..\\outside.pdf',
            'folder\\..\\outside.pdf',
        ];

        foreach (
            $unsafePaths
            as $path
        ) {
            $this->assertFalse(
                $service->isSafeRelativePath(
                    $path
                ),
                'Path seharusnya ditolak: '
                . $path
            );
        }
    }


    public function test_absolute_and_scheme_paths_are_rejected(): void
    {
        $service =
            app(
                PrivateDocumentStorageService::class
            );

        $unsafePaths = [
            '/etc/passwd',
            'C:\\Windows\\secret.txt',
            'D:/outside/file.xlsx',
            '\\\\server\\share\\file.pdf',
            'file://secret.txt',
            'php://filter/resource=secret.txt',
            'folder/file.txt:evil',
            'folder/%2e%2e/secret.txt',
        ];

        foreach (
            $unsafePaths
            as $path
        ) {
            $this->assertFalse(
                $service->isSafeRelativePath(
                    $path
                ),
                'Path seharusnya ditolak: '
                . $path
            );
        }
    }


    public function test_existing_file_inside_private_root_is_resolved(): void
    {
        $service =
            app(
                PrivateDocumentStorageService::class
            );

        $relativePath =
            'quality-control/test/document-1/file.txt';

        Storage::disk(
            'bmc_documents'
        )->put(
            $relativePath,
            'BMC SmartFind private test file'
        );

        $resolved =
            $service->resolveExistingFile(
                $relativePath
            );

        $this->assertNotNull(
            $resolved
        );

        $this->assertFileExists(
            $resolved
        );

        $this->assertStringStartsWith(
            $this->normalizePath(
                realpath(
                    $this->testRoot
                )
            )
            . '/',
            $this->normalizePath(
                $resolved
            )
        );
    }


    public function test_missing_file_is_not_resolved(): void
    {
        $service =
            app(
                PrivateDocumentStorageService::class
            );

        $this->assertNull(
            $service->resolveExistingFile(
                'quality-control/test/missing.pdf'
            )
        );
    }


    public function test_traversal_is_never_resolved_even_if_target_exists(): void
    {
        $service =
            app(
                PrivateDocumentStorageService::class
            );

        $outsideDirectory =
            dirname(
                $this->testRoot
            );

        $outsideFile =
            $outsideDirectory
            . DIRECTORY_SEPARATOR
            . 'smartfind-outside-test.txt';

        file_put_contents(
            $outsideFile,
            'outside private storage'
        );

        try {

            $this->assertNull(
                $service->resolveExistingFile(
                    '../smartfind-outside-test.txt'
                )
            );

        } finally {

            if (is_file($outsideFile)) {
                unlink(
                    $outsideFile
                );
            }
        }
    }


    private function normalizePath(
        string|false $path
    ): string {

        if ($path === false) {
            return '';
        }

        $normalized =
            rtrim(
                str_replace(
                    '\\',
                    '/',
                    $path
                ),
                '/'
            );

        if (
            PHP_OS_FAMILY === 'Windows'
        ) {
            $normalized =
                mb_strtolower(
                    $normalized
                );
        }

        return $normalized;
    }


    private function deleteDirectory(
        string $directory
    ): void {

        if (! is_dir($directory)) {
            return;
        }

        $items =
            scandir(
                $directory
            );

        if ($items === false) {
            return;
        }

        foreach (
            $items
            as $item
        ) {
            if (
                $item === '.'
                || $item === '..'
            ) {
                continue;
            }

            $path =
                $directory
                . DIRECTORY_SEPARATOR
                . $item;

            if (is_dir($path)) {

                $this->deleteDirectory(
                    $path
                );

            } elseif (
                is_file($path)
                || is_link($path)
            ) {

                unlink(
                    $path
                );
            }
        }

        rmdir(
            $directory
        );
    }
}
