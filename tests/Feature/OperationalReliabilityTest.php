<?php

namespace Tests\Feature;

use App\Models\DocumentAnalytics;
use App\Models\DocumentVersion;
use App\Services\OperationalHealthService;
use App\Services\SpreadsheetAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class OperationalReliabilityTest extends TestCase
{
    use RefreshDatabase;

    private string $storageRoot;

    private string $backupRoot;


    protected function setUp(): void
    {
        parent::setUp();

        $suffix =
            bin2hex(
                random_bytes(
                    6
                )
            );

        $this->storageRoot =
            storage_path(
                'framework/testing/qualityhub-reliability-storage-'
                . $suffix
            );

        $this->backupRoot =
            storage_path(
                'framework/testing/qualityhub-reliability-backup-'
                . $suffix
            );

        $this->makeDirectory(
            $this->storageRoot
        );

        $this->makeDirectory(
            $this->backupRoot
        );

        config([
            'filesystems.disks.bmc_documents' => [
                'driver' =>
                    'local',

                'root' =>
                    $this->storageRoot,

                'throw' =>
                    false,
            ],

            'smartfind_backup.document_disk' =>
                'bmc_documents',

            'smartfind_backup.root' =>
                $this->backupRoot,

            'smartfind_reliability.backup_required_in_production' =>
                false,

            'smartfind_reliability.backup_warning_age_hours' =>
                48,

            'smartfind_reliability.analytics_pending_stale_minutes' =>
                15,

            'smartfind_reliability.analytics_processing_stale_minutes' =>
                30,

            'smartfind_reliability.analytics_retry_default_limit' =>
                25,

            'smartfind_reliability.analytics_retry_max_limit' =>
                100,
        ]);

        Storage::purge(
            'bmc_documents'
        );

        $this->createValidBackupFixture();
    }


    protected function tearDown(): void
    {
        Storage::purge(
            'bmc_documents'
        );

        $this->deleteDirectory(
            $this->storageRoot
        );

        $this->deleteDirectory(
            $this->backupRoot
        );

        parent::tearDown();
    }


    public function test_operational_health_is_clean_when_core_dependencies_are_available(): void
    {
        $report =
            app(
                OperationalHealthService::class
            )->audit();

        $this->assertSame(
            'PASS',
            $report[
                'status'
            ]
        );

        $this->assertSame(
            0,
            $report[
                'critical_failures'
            ]
        );

        $this->assertSame(
            0,
            $report[
                'warnings'
            ]
        );

        $this->assertHealthCheck(
            $report,
            'Database connection',
            'PASS'
        );

        $this->assertHealthCheck(
            $report,
            'Private storage availability',
            'PASS'
        );

        $this->assertHealthCheck(
            $report,
            'Latest backup age',
            'PASS'
        );
    }


    public function test_missing_private_storage_is_detected_as_critical_failure(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Simulasi storage/NAS benar-benar tidak dapat digunakan
        |--------------------------------------------------------------------------
        |
        | LocalFilesystemAdapter dapat membuat ulang root directory yang hilang.
        | Karena itu sekadar menghapus folder temporary bukan simulasi outage
        | yang valid: adapter dapat membuat folder tersebut kembali.
        |
        | Untuk mensimulasikan unavailable storage secara deterministic,
        | root diarahkan ke path yang merupakan FILE biasa. Local adapter tidak
        | mungkin menggunakannya sebagai directory, sehingga health service harus
        | mendeteksi storage sebagai critical failure.
        |
        */

        Storage::purge(
            'bmc_documents'
        );

        $blockedRoot =
            storage_path(
                'framework/testing/qualityhub-reliability-blocked-'
                . bin2hex(
                    random_bytes(
                        6
                    )
                )
            );

        file_put_contents(
            $blockedRoot,
            'blocked storage fixture'
        );

        config([
            'filesystems.disks.bmc_documents.root' =>
                $blockedRoot,
        ]);

        Storage::purge(
            'bmc_documents'
        );

        try {

            $report =
                app(
                    OperationalHealthService::class
                )->audit();

            $this->assertSame(
                'FAIL',
                $report[
                    'status'
                ]
            );

            $this->assertGreaterThanOrEqual(
                1,
                $report[
                    'critical_failures'
                ]
            );

            $this->assertHealthCheck(
                $report,
                'Private storage availability',
                'FAIL'
            );

        } finally {

            Storage::purge(
                'bmc_documents'
            );

            @unlink(
                $blockedRoot
            );

            config([
                'filesystems.disks.bmc_documents.root' =>
                    $this->storageRoot,
            ]);

            Storage::purge(
                'bmc_documents'
            );
        }
    }


    public function test_failed_analytics_is_reported_as_warning_without_modifying_record(): void
    {
        $fixture =
            $this->createDocumentFixture();

        $analyticsId =
            DB::table(
                'document_analytics'
            )->insertGetId([
                'document_id' =>
                    $fixture[
                        'document_id'
                    ],

                'document_version_id' =>
                    $fixture[
                        'version_id'
                    ],

                'revision_number' =>
                    0,

                'status' =>
                    'failed',

                'sheet_count' =>
                    0,

                'total_rows' =>
                    0,

                'total_columns' =>
                    0,

                'numeric_cells' =>
                    0,

                'blank_cells' =>
                    0,

                'summary' =>
                    null,

                'schema' =>
                    null,

                'processed_at' =>
                    null,

                'error_message' =>
                    'Simulated analytics failure.',

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        $report =
            app(
                OperationalHealthService::class
            )->audit();

        $this->assertSame(
            'PASS',
            $report[
                'status'
            ]
        );

        $this->assertGreaterThanOrEqual(
            1,
            $report[
                'warnings'
            ]
        );

        $this->assertHealthCheck(
            $report,
            'Failed analytics',
            'WARN'
        );

        $this->assertDatabaseHas(
            'document_analytics',
            [
                'id' =>
                    $analyticsId,

                'status' =>
                    'failed',

                'error_message' =>
                    'Simulated analytics failure.',
            ]
        );
    }


    public function test_failed_analytics_dry_run_never_executes_analyze(): void
    {
        $fixture =
            $this->createFailedAnalyticsFixture();

        $this->mock(
            SpreadsheetAnalyticsService::class,
            function (
                MockInterface $mock
            ): void {

                $mock
                    ->shouldNotReceive(
                        'analyze'
                    );
            }
        );

        $this->artisan(
            'smartfind:retry-failed-analytics',
            [
                '--version-id' =>
                    $fixture[
                        'version_id'
                    ],

                '--dry-run' =>
                    true,
            ]
        )
            ->expectsOutputToContain(
                'DRY RUN'
            )
            ->assertExitCode(
                0
            );

        $this->assertDatabaseHas(
            'document_analytics',
            [
                'id' =>
                    $fixture[
                        'analytics_id'
                    ],

                'status' =>
                    'failed',
            ]
        );
    }


    public function test_failed_analytics_can_be_retried_and_ready_record_is_not_reprocessed(): void
    {
        $fixture =
            $this->createFailedAnalyticsFixture();

        $this->mock(
            SpreadsheetAnalyticsService::class,
            function (
                MockInterface $mock
            ) use (
                $fixture
            ): void {

                $mock
                    ->shouldReceive(
                        'analyze'
                    )
                    ->once()
                    ->withArgs(
                        function (
                            DocumentVersion $version,
                            bool $force
                        ) use (
                            $fixture
                        ): bool {

                            return (
                                (int) $version->id
                                === (int) $fixture[
                                    'version_id'
                                ]
                                && $force === true
                            );
                        }
                    )
                    ->andReturnUsing(
                        function (
                            DocumentVersion $version,
                            bool $force
                        ) use (
                            $fixture
                        ): DocumentAnalytics {

                            DocumentAnalytics::query()
                                ->whereKey(
                                    $fixture[
                                        'analytics_id'
                                    ]
                                )
                                ->update([
                                    'status' =>
                                        'ready',

                                    'processed_at' =>
                                        now(),

                                    'error_message' =>
                                        null,
                                ]);

                            return DocumentAnalytics::query()
                                ->findOrFail(
                                    $fixture[
                                        'analytics_id'
                                    ]
                                );
                        }
                    );
            }
        );

        $this->artisan(
            'smartfind:retry-failed-analytics',
            [
                '--version-id' =>
                    $fixture[
                        'version_id'
                    ],
            ]
        )
            ->expectsOutputToContain(
                'READY'
            )
            ->assertExitCode(
                0
            );

        $this->assertDatabaseHas(
            'document_analytics',
            [
                'id' =>
                    $fixture[
                        'analytics_id'
                    ],

                'status' =>
                    'ready',

                'error_message' =>
                    null,
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Ready analytics tidak boleh diproses ulang oleh command failed retry.
        |
        | Mock yang sama memiliki expectation analyze() tepat satu kali.
        | Jika command salah dan memproses ready analytics lagi, test otomatis gagal.
        |--------------------------------------------------------------------------
        */

        $this->artisan(
            'smartfind:retry-failed-analytics',
            [
                '--version-id' =>
                    $fixture[
                        'version_id'
                    ],
            ]
        )
            ->expectsOutputToContain(
                'Tidak ada analytics failed yang perlu di-retry.'
            )
            ->assertExitCode(
                0
            );
    }


    public function test_invalid_retry_options_are_rejected_without_processing(): void
    {
        $this->mock(
            SpreadsheetAnalyticsService::class,
            function (
                MockInterface $mock
            ): void {

                $mock
                    ->shouldNotReceive(
                        'analyze'
                    );
            }
        );

        $this->artisan(
            'smartfind:retry-failed-analytics',
            [
                '--version-id' =>
                    'abc',
            ]
        )
            ->expectsOutputToContain(
                '--version-id harus berupa document_version_id numerik positif.'
            )
            ->assertExitCode(
                1
            );

        $this->artisan(
            'smartfind:retry-failed-analytics',
            [
                '--limit' =>
                    '999999',
            ]
        )
            ->expectsOutputToContain(
                '--limit harus antara 1 dan'
            )
            ->assertExitCode(
                1
            );
    }


    private function createFailedAnalyticsFixture(): array
    {
        $fixture =
            $this->createDocumentFixture();

        $analyticsId =
            DB::table(
                'document_analytics'
            )->insertGetId([
                'document_id' =>
                    $fixture[
                        'document_id'
                    ],

                'document_version_id' =>
                    $fixture[
                        'version_id'
                    ],

                'revision_number' =>
                    0,

                'status' =>
                    'failed',

                'sheet_count' =>
                    0,

                'total_rows' =>
                    0,

                'total_columns' =>
                    0,

                'numeric_cells' =>
                    0,

                'blank_cells' =>
                    0,

                'summary' =>
                    null,

                'schema' =>
                    null,

                'processed_at' =>
                    null,

                'error_message' =>
                    'Simulated retry failure.',

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        return [
            ...$fixture,

            'analytics_id' =>
                $analyticsId,
        ];
    }


    private function createDocumentFixture(): array
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
                    'Operational reliability test.',

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
                    'Reliability Test',

                'slug' =>
                    'reliability-test-'
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
                    'Operational reliability test category.',

                'is_active' =>
                    true,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        $relativePath =
            'quality-control/reliability/test/rev-000/test.xlsx';

        Storage::disk(
            'bmc_documents'
        )->put(
            $relativePath,
            'BMC QualityHub reliability test'
        );

        $fileSize =
            Storage::disk(
                'bmc_documents'
            )->size(
                $relativePath
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
                    'Reliability Test Document',

                'document_code' =>
                    'REL-'
                    . substr(
                        bin2hex(
                            random_bytes(
                                3
                            )
                        ),
                        0,
                        6
                    ),

                'part_number' =>
                    null,

                'file_name' =>
                    'test.xlsx',

                'file_path' =>
                    $relativePath,

                'file_extension' =>
                    'xlsx',

                'mime_type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

                'file_size' =>
                    $fileSize,

                'document_date' =>
                    now()
                        ->toDateString(),

                'description' =>
                    'Operational reliability test.',

                'keywords' =>
                    'reliability',

                'is_active' =>
                    true,

                'current_revision' =>
                    0,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        $versionId =
            DB::table(
                'document_versions'
            )->insertGetId([
                'document_id' =>
                    $documentId,

                'revision_number' =>
                    0,

                'uploaded_by' =>
                    null,

                'file_name' =>
                    'test.xlsx',

                'file_path' =>
                    $relativePath,

                'file_extension' =>
                    'xlsx',

                'mime_type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

                'file_size' =>
                    $fileSize,

                'revision_note' =>
                    'Reliability test revision.',

                'metadata_snapshot' =>
                    json_encode([
                        'title' =>
                            'Reliability Test Document',
                    ]),

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        return [
            'document_id' =>
                $documentId,

            'version_id' =>
                $versionId,

            'path' =>
                $relativePath,
        ];
    }


    private function createValidBackupFixture(): void
    {
        $backupName =
            'smartfind-'
            . now()->format(
                'Ymd-His'
            )
            . '-a1b2c3d4';

        $backupDirectory =
            $this->backupRoot
            . DIRECTORY_SEPARATOR
            . $backupName;

        $this->makeDirectory(
            $backupDirectory
        );

        file_put_contents(
            $backupDirectory
            . DIRECTORY_SEPARATOR
            . 'manifest.json',
            json_encode(
                [
                    'format' =>
                        'bmc-smartfind-backup',

                    'version' =>
                        1,

                    'backup_name' =>
                        $backupName,

                    'created_at' =>
                        now()
                            ->toIso8601String(),
                ],
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
            )
        );
    }


    private function assertHealthCheck(
        array $report,
        string $name,
        string $status
    ): void {

        $check =
            collect(
                $report[
                    'checks'
                ]
            )
                ->firstWhere(
                    'name',
                    $name
                );

        $this->assertNotNull(
            $check,
            'Health check tidak ditemukan: '
            . $name
        );

        $this->assertSame(
            $status,
            $check[
                'status'
            ],
            'Status health check tidak sesuai: '
            . $name
        );
    }



    private function makeDirectory(
        string $directory
    ): void {

        if (is_dir($directory)) {
            return;
        }

        mkdir(
            $directory,
            0777,
            true
        );
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
