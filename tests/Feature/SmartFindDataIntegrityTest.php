<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SmartFindDataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private string $testRoot;


    protected function setUp(): void
    {
        parent::setUp();

        $this->testRoot =
            storage_path(
                'framework/testing/smartfind-integrity-'
                . bin2hex(
                    random_bytes(
                        6
                    )
                )
            );

        mkdir(
            $this->testRoot,
            0777,
            true
        );

        config([
            'filesystems.disks.bmc_documents' => [
                'driver' =>
                    'local',

                'root' =>
                    $this->testRoot,

                'throw' =>
                    false,
            ],

            'smartfind_backup.document_disk' =>
                'bmc_documents',
        ]);

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


    public function test_integrity_audit_passes_when_database_and_storage_match(): void
    {
        $this->createValidDocumentFixture();

        $this->artisan(
            'smartfind:audit-integrity'
        )
            ->expectsOutputToContain(
                'DATA INTEGRITY AUDIT: PASS'
            )
            ->assertExitCode(
                0
            );
    }


    public function test_integrity_audit_fails_when_referenced_file_is_missing(): void
    {
        $fixture =
            $this->createValidDocumentFixture();

        Storage::disk(
            'bmc_documents'
        )->delete(
            $fixture[
                'path'
            ]
        );

        $this->artisan(
            'smartfind:audit-integrity'
        )
            ->expectsOutputToContain(
                'DATA INTEGRITY AUDIT: FAIL'
            )
            ->assertExitCode(
                1
            );
    }


    public function test_integrity_audit_reports_orphan_without_deleting_it(): void
    {
        $this->createValidDocumentFixture();

        $orphan =
            'quality-control/orphan/untracked.pdf';

        Storage::disk(
            'bmc_documents'
        )->put(
            $orphan,
            'orphan'
        );

        $this->artisan(
            'smartfind:audit-integrity'
        )
            ->expectsOutputToContain(
                'DATA INTEGRITY AUDIT: PASS'
            )
            ->expectsOutputToContain(
                'Orphan file tidak dihapus otomatis.'
            )
            ->assertExitCode(
                0
            );

        $this->assertTrue(
            Storage::disk(
                'bmc_documents'
            )->exists(
                $orphan
            )
        );
    }


    public function test_integrity_audit_fails_when_current_revision_record_is_missing(): void
    {
        $fixture =
            $this->createValidDocumentFixture();

        DB::table(
            'document_versions'
        )
            ->where(
                'document_id',
                $fixture[
                    'document_id'
                ]
            )
            ->delete();

        $this->artisan(
            'smartfind:audit-integrity'
        )
            ->expectsOutputToContain(
                'DATA INTEGRITY AUDIT: FAIL'
            )
            ->assertExitCode(
                1
            );
    }


    private function createValidDocumentFixture(): array
    {
        $departmentId =
            DB::table(
                'departments'
            )->insertGetId([
                'name' =>
                    'Quality Control',

                'code' =>
                    'QC-'
                    . substr(
                        bin2hex(
                            random_bytes(
                                3
                            )
                        ),
                        0,
                        6
                    ),

                'description' =>
                    'Integrity test.',

                'is_active' =>
                    true,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        $categoryId =
            DB::table(
                'categories'
            )->insertGetId([
                'department_id' =>
                    $departmentId,

                'name' =>
                    'Integrity Test',

                'slug' =>
                    'integrity-test-'
                    . substr(
                        bin2hex(
                            random_bytes(
                                3
                            )
                        ),
                        0,
                        6
                    ),

                'description' =>
                    'Integrity test category.',

                'is_active' =>
                    true,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        $path =
            'quality-control/integrity/document-test/rev-000/test.pdf';

        $content =
            'BMC SmartFind integrity test';

        Storage::disk(
            'bmc_documents'
        )->put(
            $path,
            $content
        );

        $size =
            strlen(
                $content
            );

        $documentId =
            DB::table(
                'documents'
            )->insertGetId([
                'category_id' =>
                    $categoryId,

                'uploaded_by' =>
                    null,

                'title' =>
                    'Integrity Test Document',

                'document_code' =>
                    'INT-001',

                'part_number' =>
                    null,

                'file_name' =>
                    'test.pdf',

                'file_path' =>
                    $path,

                'file_extension' =>
                    'pdf',

                'mime_type' =>
                    'application/pdf',

                'file_size' =>
                    $size,

                'document_date' =>
                    now()->toDateString(),

                'description' =>
                    'Integrity test.',

                'keywords' =>
                    'integrity',

                'is_active' =>
                    true,

                'current_revision' =>
                    0,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        DB::table(
            'document_versions'
        )->insert([
            'document_id' =>
                $documentId,

            'revision_number' =>
                0,

            'uploaded_by' =>
                null,

            'file_name' =>
                'test.pdf',

            'file_path' =>
                $path,

            'file_extension' =>
                'pdf',

            'mime_type' =>
                'application/pdf',

            'file_size' =>
                $size,

            'revision_note' =>
                'Initial test revision.',

            'metadata_snapshot' =>
                json_encode([
                    'title' =>
                        'Integrity Test Document',
                ]),

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ]);

        return [
            'document_id' =>
                $documentId,

            'path' =>
                $path,
        ];
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

            if (
                is_dir(
                    $path
                )
                && ! is_link(
                    $path
                )
            ) {
                $this->deleteDirectory(
                    $path
                );
            } else {
                @unlink(
                    $path
                );
            }
        }

        @rmdir(
            $directory
        );
    }
}
