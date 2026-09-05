<?php

namespace App\Services;

use App\Models\DocumentAnalytics;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Throwable;

class OperationalHealthService
{
    /**
     * Audit read-only kondisi operasional BMC QualityHub.
     *
     * Tidak melakukan:
     * - delete file
     * - repair database
     * - retry analytics
     * - clear failed jobs
     * - restore backup
     */
    public function audit(): array
    {
        $checks = [];

        $databaseAvailable =
            $this->checkDatabase(
                $checks
            );

        $this->checkRuntimeDirectories(
            $checks
        );

        $this->checkPrivateStorage(
            $checks
        );

        if ($databaseAvailable) {

            $this->checkQueue(
                $checks
            );

            $this->checkAnalytics(
                $checks
            );

        } else {

            $this->addCheck(
                checks:
                    $checks,

                category:
                    'Queue',

                name:
                    'Queue database checks',

                status:
                    'WARN',

                note:
                    'Dilewati karena database tidak dapat diakses.'
            );

            $this->addCheck(
                checks:
                    $checks,

                category:
                    'Analytics',

                name:
                    'Analytics database checks',

                status:
                    'WARN',

                note:
                    'Dilewati karena database tidak dapat diakses.'
            );
        }

        $this->checkBackup(
            $checks
        );

        $this->checkMaintenanceMode(
            $checks
        );

        $criticalFailures =
            collect(
                $checks
            )
                ->where(
                    'status',
                    'FAIL'
                )
                ->count();

        $warnings =
            collect(
                $checks
            )
                ->where(
                    'status',
                    'WARN'
                )
                ->count();

        return [
            'status' =>
                $criticalFailures === 0
                    ? 'PASS'
                    : 'FAIL',

            'critical_failures' =>
                $criticalFailures,

            'warnings' =>
                $warnings,

            'checks' =>
                $checks,
        ];
    }


    /**
     * Database merupakan dependency utama.
     */
    private function checkDatabase(
        array &$checks
    ): bool {

        try {

            DB::select(
                'SELECT 1'
            );

            $this->addCheck(
                checks:
                    $checks,

                category:
                    'Database',

                name:
                    'Database connection',

                status:
                    'PASS',

                note:
                    'Koneksi database aktif.'
            );

            return true;

        } catch (Throwable $exception) {

            $this->addCheck(
                checks:
                    $checks,

                category:
                    'Database',

                name:
                    'Database connection',

                status:
                    'FAIL',

                note:
                    'Database tidak dapat diakses: '
                    . $this->shortMessage(
                        $exception->getMessage()
                    )
            );

            return false;
        }
    }


    /**
     * Directory yang harus writable agar Laravel dapat bekerja normal.
     */
    private function checkRuntimeDirectories(
        array &$checks
    ): void {

        $directories = [
            'Application logs' =>
                storage_path(
                    'logs'
                ),

            'Framework runtime' =>
                storage_path(
                    'framework'
                ),

            'Bootstrap cache' =>
                base_path(
                    'bootstrap/cache'
                ),
        ];

        foreach (
            $directories
            as $name => $path
        ) {

            if (! is_dir($path)) {

                $this->addCheck(
                    checks:
                        $checks,

                    category:
                        'Runtime',

                    name:
                        $name,

                    status:
                        'FAIL',

                    note:
                        'Directory tidak ditemukan: '
                        . $path
                );

                continue;
            }

            $this->addCheck(
                checks:
                    $checks,

                category:
                    'Runtime',

                name:
                    $name,

                status:
                    is_writable(
                        $path
                    )
                        ? 'PASS'
                        : 'FAIL',

                note:
                    is_writable(
                        $path
                    )
                        ? 'Directory tersedia dan writable.'
                        : 'Directory tersedia tetapi tidak writable.'
            );
        }
    }


    /**
     * Memastikan private storage / mapped NAS tersedia.
     */
    private function checkPrivateStorage(
        array &$checks
    ): void {

        $diskName =
            (string) config(
                'smartfind_backup.document_disk',
                'bmc_documents'
            );

        try {

            $driver =
                strtolower(
                    (string) config(
                        'filesystems.disks.'
                        . $diskName
                        . '.driver',
                        ''
                    )
                );

            if ($driver !== 'local') {

                $this->addCheck(
                    checks:
                        $checks,

                    category:
                        'Storage',

                    name:
                        'Private storage driver',

                    status:
                        'FAIL',

                    note:
                        'Disk '
                        . $diskName
                        . ' harus menggunakan local / mapped drive.'
                );

                return;
            }

            $declaredRoot =
                Storage::disk(
                    $diskName
                )->path('');

            $canonicalRoot =
                realpath(
                    $declaredRoot
                );

            if (
                $canonicalRoot === false
                || ! is_dir(
                    $canonicalRoot
                )
            ) {

                $this->addCheck(
                    checks:
                        $checks,

                    category:
                        'Storage',

                    name:
                        'Private storage availability',

                    status:
                        'FAIL',

                    note:
                        'Private storage tidak tersedia: '
                        . $declaredRoot
                );

                return;
            }

            $this->addCheck(
                checks:
                    $checks,

                category:
                    'Storage',

                name:
                    'Private storage availability',

                status:
                    'PASS',

                note:
                    'Private storage dapat diakses.'
            );

            $this->addCheck(
                checks:
                    $checks,

                category:
                    'Storage',

                name:
                    'Private storage readable',

                status:
                    is_readable(
                        $canonicalRoot
                    )
                        ? 'PASS'
                        : 'FAIL',

                note:
                    is_readable(
                        $canonicalRoot
                    )
                        ? 'Storage memiliki akses baca.'
                        : 'Storage tidak memiliki akses baca.'
            );

            $this->addCheck(
                checks:
                    $checks,

                category:
                    'Storage',

                name:
                    'Private storage writable',

                status:
                    is_writable(
                        $canonicalRoot
                    )
                        ? 'PASS'
                        : 'FAIL',

                note:
                    is_writable(
                        $canonicalRoot
                    )
                        ? 'Storage memiliki akses tulis.'
                        : 'Storage tidak memiliki akses tulis.'
            );

            $freeBytes =
                @disk_free_space(
                    $canonicalRoot
                );

            if (
                ! is_int(
                    $freeBytes
                )
                && ! is_float(
                    $freeBytes
                )
            ) {

                $this->addCheck(
                    checks:
                        $checks,

                    category:
                        'Storage',

                    name:
                        'Private storage free space',

                    status:
                        'WARN',

                    note:
                        'Sisa ruang storage tidak dapat dibaca.'
                );

                return;
            }

            $freeBytes =
                max(
                    0,
                    (int) $freeBytes
                );

            $criticalThreshold =
                max(
                    0,
                    (int) config(
                        'smartfind_reliability.private_storage_critical_free_bytes',
                        536870912
                    )
                );

            $warningThreshold =
                max(
                    $criticalThreshold,
                    (int) config(
                        'smartfind_reliability.private_storage_warning_free_bytes',
                        2147483648
                    )
                );

            $status =
                $freeBytes
                    < $criticalThreshold
                    ? 'FAIL'
                    : (
                        $freeBytes
                            < $warningThreshold
                            ? 'WARN'
                            : 'PASS'
                    );

            $this->addCheck(
                checks:
                    $checks,

                category:
                    'Storage',

                name:
                    'Private storage free space',

                status:
                    $status,

                note:
                    'Sisa ruang: '
                    . $this->formatBytes(
                        $freeBytes
                    )
            );

        } catch (Throwable $exception) {

            $this->addCheck(
                checks:
                    $checks,

                category:
                    'Storage',

                name:
                    'Private storage availability',

                status:
                    'FAIL',

                note:
                    'Pemeriksaan private storage gagal: '
                    . $this->shortMessage(
                        $exception->getMessage()
                    )
            );
        }
    }


    /**
     * Queue bukan dependency analytics Tahap 16,
     * tetapi tetap diaudit untuk reliability Laravel.
     */
    private function checkQueue(
        array &$checks
    ): void {

        $connection =
            (string) config(
                'queue.default',
                'sync'
            );

        $this->addCheck(
            checks:
                $checks,

            category:
                'Queue',

            name:
                'Queue connection',

            status:
                'PASS',

            note:
                'Connection aktif: '
                . $connection
                . '.'
        );

        try {

            if (
                Schema::hasTable(
                    'jobs'
                )
            ) {

                $pendingJobs =
                    (int) DB::table(
                        'jobs'
                    )->count();

                $warningCount =
                    max(
                        1,
                        (int) config(
                            'smartfind_reliability.queue_pending_warning_count',
                            100
                        )
                    );

                $this->addCheck(
                    checks:
                        $checks,

                    category:
                        'Queue',

                    name:
                        'Pending queue jobs',

                    status:
                        $pendingJobs
                            >= $warningCount
                            ? 'WARN'
                            : 'PASS',

                    note:
                        number_format(
                            $pendingJobs
                        )
                        . ' job pending.'
                );
            }

            if (
                Schema::hasTable(
                    'failed_jobs'
                )
            ) {

                $failedJobs =
                    (int) DB::table(
                        'failed_jobs'
                    )->count();

                $this->addCheck(
                    checks:
                        $checks,

                    category:
                        'Queue',

                    name:
                        'Failed queue jobs',

                    status:
                        $failedJobs > 0
                            ? 'WARN'
                            : 'PASS',

                    note:
                        number_format(
                            $failedJobs
                        )
                        . ' failed job.'
                );
            }

        } catch (Throwable $exception) {

            $this->addCheck(
                checks:
                    $checks,

                category:
                    'Queue',

                name:
                    'Queue table health',

                status:
                    'WARN',

                note:
                    'Queue table tidak dapat diperiksa: '
                    . $this->shortMessage(
                        $exception->getMessage()
                    )
            );
        }
    }


    /**
     * Deteksi analytics gagal atau macet.
     */
    private function checkAnalytics(
        array &$checks
    ): void {

        try {

            if (
                ! Schema::hasTable(
                    'document_analytics'
                )
            ) {

                $this->addCheck(
                    checks:
                        $checks,

                    category:
                        'Analytics',

                    name:
                        'Analytics table',

                    status:
                        'FAIL',

                    note:
                        'Tabel document_analytics tidak ditemukan.'
                );

                return;
            }

            $failedCount =
                DocumentAnalytics::query()
                    ->where(
                        'status',
                        'failed'
                    )
                    ->count();

            $this->addCheck(
                checks:
                    $checks,

                category:
                    'Analytics',

                name:
                    'Failed analytics',

                status:
                    $failedCount > 0
                        ? 'WARN'
                        : 'PASS',

                note:
                    number_format(
                        $failedCount
                    )
                    . ' analytics berstatus failed.'
            );

            $pendingMinutes =
                max(
                    1,
                    (int) config(
                        'smartfind_reliability.analytics_pending_stale_minutes',
                        15
                    )
                );

            $stalePending =
                DocumentAnalytics::query()
                    ->where(
                        'status',
                        'pending'
                    )
                    ->where(
                        'updated_at',
                        '<',
                        now()
                            ->subMinutes(
                                $pendingMinutes
                            )
                    )
                    ->count();

            $this->addCheck(
                checks:
                    $checks,

                category:
                    'Analytics',

                name:
                    'Stale pending analytics',

                status:
                    $stalePending > 0
                        ? 'WARN'
                        : 'PASS',

                note:
                    number_format(
                        $stalePending
                    )
                    . ' pending lebih dari '
                    . $pendingMinutes
                    . ' menit.'
            );

            $processingMinutes =
                max(
                    1,
                    (int) config(
                        'smartfind_reliability.analytics_processing_stale_minutes',
                        30
                    )
                );

            $staleProcessing =
                DocumentAnalytics::query()
                    ->where(
                        'status',
                        'processing'
                    )
                    ->where(
                        'updated_at',
                        '<',
                        now()
                            ->subMinutes(
                                $processingMinutes
                            )
                    )
                    ->count();

            $this->addCheck(
                checks:
                    $checks,

                category:
                    'Analytics',

                name:
                    'Stale processing analytics',

                status:
                    $staleProcessing > 0
                        ? 'WARN'
                        : 'PASS',

                note:
                    number_format(
                        $staleProcessing
                    )
                    . ' processing lebih dari '
                    . $processingMinutes
                    . ' menit.'
            );

        } catch (Throwable $exception) {

            $this->addCheck(
                checks:
                    $checks,

                category:
                    'Analytics',

                name:
                    'Analytics health',

                status:
                    'FAIL',

                note:
                    'Analytics tidak dapat diperiksa: '
                    . $this->shortMessage(
                        $exception->getMessage()
                    )
            );
        }
    }


    /**
     * Health backup hanya mengecek adanya backup final dan usianya.
     *
     * SHA-256/full verification tetap menggunakan:
     * php artisan smartfind:backup-verify NAMA_BACKUP
     */
    private function checkBackup(
        array &$checks
    ): void {

        $backupRoot =
            trim(
                (string) config(
                    'smartfind_backup.root',
                    ''
                )
            );

        $required =
            app()->environment(
                'production'
            )
            && (bool) config(
                'smartfind_reliability.backup_required_in_production',
                true
            );

        if (
            $backupRoot === ''
            || ! is_dir(
                $backupRoot
            )
        ) {

            $this->addCheck(
                checks:
                    $checks,

                category:
                    'Backup',

                name:
                    'Backup root',

                status:
                    $required
                        ? 'FAIL'
                        : 'WARN',

                note:
                    'Backup root belum tersedia.'
            );

            return;
        }

        $rootReadable =
            is_readable(
                $backupRoot
            );

        $rootWritable =
            is_writable(
                $backupRoot
            );

        $this->addCheck(
            checks:
                $checks,

            category:
                'Backup',

            name:
                'Backup root access',

            status:
                (
                    $rootReadable
                    && $rootWritable
                )
                    ? 'PASS'
                    : 'FAIL',

            note:
                (
                    $rootReadable
                    && $rootWritable
                )
                    ? 'Backup root dapat dibaca dan ditulis.'
                    : 'Backup root permission tidak mencukupi.'
        );

        $directories =
            glob(
                rtrim(
                    $backupRoot,
                    '\\/'
                )
                . DIRECTORY_SEPARATOR
                . 'smartfind-*',
                GLOB_ONLYDIR
            );

        if (
            $directories === false
            || $directories === []
        ) {

            $this->addCheck(
                checks:
                    $checks,

                category:
                    'Backup',

                name:
                    'Latest full backup',

                status:
                    $required
                        ? 'FAIL'
                        : 'WARN',

                note:
                    'Belum ditemukan full backup SmartFind/QualityHub.'
            );

            return;
        }

        $latest =
            null;

        foreach (
            $directories
            as $directory
        ) {

            $backupName =
                basename(
                    $directory
                );

            if (
                preg_match(
                    '/^smartfind-\d{8}-\d{6}-[a-f0-9]{8}$/',
                    $backupName
                ) !== 1
            ) {
                continue;
            }

            $manifestPath =
                $directory
                . DIRECTORY_SEPARATOR
                . 'manifest.json';

            if (
                ! is_file(
                    $manifestPath
                )
            ) {
                continue;
            }

            try {

                $manifest =
                    json_decode(
                        (string) file_get_contents(
                            $manifestPath
                        ),
                        true,
                        512,
                        JSON_THROW_ON_ERROR
                    );

                if (
                    ! is_array(
                        $manifest
                    )
                    || (
                        $manifest[
                            'format'
                        ]
                        ?? null
                    ) !== 'bmc-smartfind-backup'
                    || (
                        $manifest[
                            'backup_name'
                        ]
                        ?? null
                    ) !== $backupName
                ) {
                    continue;
                }

                $createdAtRaw =
                    $manifest[
                        'created_at'
                    ]
                    ?? null;

                if (
                    ! is_string(
                        $createdAtRaw
                    )
                    || trim(
                        $createdAtRaw
                    ) === ''
                ) {
                    continue;
                }

                $createdAt =
                    Carbon::parse(
                        $createdAtRaw
                    );

                if (
                    $latest === null
                    || $createdAt->greaterThan(
                        $latest[
                            'created_at'
                        ]
                    )
                ) {

                    $latest = [
                        'name' =>
                            $backupName,

                        'created_at' =>
                            $createdAt,
                    ];
                }

            } catch (
                JsonException
                | Throwable $exception
            ) {
                continue;
            }
        }

        if ($latest === null) {

            $this->addCheck(
                checks:
                    $checks,

                category:
                    'Backup',

                name:
                    'Latest full backup',

                status:
                    $required
                        ? 'FAIL'
                        : 'WARN',

                note:
                    'Tidak ditemukan backup final dengan manifest yang valid secara struktur dasar.'
            );

            return;
        }

        $warningAge =
            max(
                1,
                (int) config(
                    'smartfind_reliability.backup_warning_age_hours',
                    48
                )
            );

        $ageHours =
            (float) $latest[
                'created_at'
            ]->diffInHours(
                now(),
                true
            );

        $this->addCheck(
            checks:
                $checks,

            category:
                'Backup',

            name:
                'Latest backup age',

            status:
                $ageHours > $warningAge
                    ? 'WARN'
                    : 'PASS',

            note:
                $latest[
                    'name'
                ]
                . ' — sekitar '
                . number_format(
                    $ageHours,
                    1,
                    ',',
                    '.'
                )
                . ' jam lalu.'
        );
    }


    private function checkMaintenanceMode(
        array &$checks
    ): void {

        $maintenance =
            app()->isDownForMaintenance();

        $this->addCheck(
            checks:
                $checks,

            category:
                'Application',

            name:
                'Maintenance mode',

            status:
                $maintenance
                    ? 'WARN'
                    : 'PASS',

            note:
                $maintenance
                    ? 'Aplikasi sedang berada dalam maintenance mode.'
                    : 'Aplikasi sedang melayani request secara normal.'
        );
    }


    private function addCheck(
        array &$checks,
        string $category,
        string $name,
        string $status,
        string $note
    ): void {

        $checks[] = [
            'category' =>
                $category,

            'name' =>
                $name,

            'status' =>
                $status,

            'note' =>
                $note,
        ];
    }


    private function formatBytes(
        int $bytes
    ): string {

        $bytes =
            max(
                0,
                $bytes
            );

        $units = [
            'B',
            'KB',
            'MB',
            'GB',
            'TB',
        ];

        $value =
            (float) $bytes;

        $unitIndex =
            0;

        while (
            $value >= 1024
            && $unitIndex
                < count(
                    $units
                )
                - 1
        ) {

            $value /=
                1024;

            $unitIndex++;
        }

        return number_format(
            $value,
            $unitIndex === 0
                ? 0
                : 2,
            ',',
            '.'
        )
        . ' '
        . $units[
            $unitIndex
        ];
    }


    private function shortMessage(
        string $message
    ): string {

        $message =
            trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    $message
                )
                ?? $message
            );

        return mb_strimwidth(
            $message,
            0,
            320,
            '...'
        );
    }
}
