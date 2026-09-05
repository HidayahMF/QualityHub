<?php

namespace App\Console\Commands;

use App\Services\OperationalHealthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AuditProductionReadiness extends Command
{
    protected $signature =
        'smartfind:audit-production
        {--strict : Perlakukan seluruh requirement production sebagai critical failure}';

    protected $description =
        'Audit read-only production, HTTPS, session, build, database, NAS/private storage, backup, dan runtime BMC QualityHub.';


    public function handle(
        OperationalHealthService $healthService
    ): int {

        $strict =
            (bool) $this->option(
                'strict'
            );

        $checks = [];

        $this->checkApplicationEnvironment(
            $checks,
            $strict
        );

        $this->checkSecurityRuntime(
            $checks
        );

        $databaseAvailable =
            $this->checkDatabaseAndMigrations(
                $checks
            );

        if ($databaseAvailable) {

            $this->checkRequiredTables(
                $checks
            );
        }

        $this->checkPrivateStorageAndNas(
            $checks,
            $strict
        );

        $this->checkBackupSeparation(
            $checks
        );

        $this->checkBuildAndOptimization(
            $checks,
            $strict
        );

        $this->checkOperationalHealth(
            $checks,
            $healthService
        );

        $this->table(
            [
                'Status',
                'Area',
                'Check',
                'Catatan',
            ],
            array_map(
                fn (array $check) => [
                    $check[
                        'status'
                    ],
                    $check[
                        'area'
                    ],
                    $check[
                        'name'
                    ],
                    $check[
                        'note'
                    ],
                ],
                $checks
            )
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

        $this->newLine();

        if ($criticalFailures === 0) {

            $this->info(
                'PRODUCTION READINESS AUDIT: PASS'
            );

        } else {

            $this->error(
                'PRODUCTION READINESS AUDIT: FAIL'
            );
        }

        $this->line(
            'Mode: '
            . (
                $strict
                    ? 'STRICT PRODUCTION'
                    : 'PREPARATION'
            )
        );

        $this->line(
            'Critical Fail: '
            . $criticalFailures
            . ' | Warning: '
            . $warnings
        );

        if (! $strict) {

            $this->newLine();

            $this->warn(
                'Preparation mode tidak membuktikan server production siap. '
                . 'Pada server final jalankan: php artisan smartfind:audit-production --strict'
            );
        }

        return $criticalFailures === 0
            ? self::SUCCESS
            : self::FAILURE;
    }


    private function checkApplicationEnvironment(
        array &$checks,
        bool $strict
    ): void {

        $environment =
            strtolower(
                (string) config(
                    'app.env',
                    ''
                )
            );

        $isProduction =
            $environment === 'production';

        $this->addExpectation(
            checks:
                $checks,

            area:
                'Application',

            name:
                'APP_ENV production',

            passed:
                $isProduction,

            strict:
                $strict,

            note:
                'APP_ENV='
                . (
                    $environment !== ''
                        ? $environment
                        : '[EMPTY]'
                )
        );

        $debug =
            (bool) config(
                'app.debug',
                false
            );

        $this->addExpectation(
            checks:
                $checks,

            area:
                'Application',

            name:
                'APP_DEBUG disabled',

            passed:
                ! $debug,

            strict:
                $strict,

            note:
                'APP_DEBUG='
                . (
                    $debug
                        ? 'true'
                        : 'false'
                )
        );

        $key =
            trim(
                (string) config(
                    'app.key',
                    ''
                )
            );

        $this->addCheck(
            checks:
                $checks,

            area:
                'Application',

            name:
                'APP_KEY configured',

            status:
                $key !== ''
                    ? 'PASS'
                    : 'FAIL',

            note:
                $key !== ''
                    ? 'Application key tersedia.'
                    : 'APP_KEY kosong.'
        );

        $appUrl =
            trim(
                (string) config(
                    'app.url',
                    ''
                )
            );

        $requiresHttps =
            (bool) config(
                'smartfind_production.require_https',
                true
            );

        $httpsOk =
            ! $requiresHttps
            || str_starts_with(
                strtolower(
                    $appUrl
                ),
                'https://'
            );

        $this->addExpectation(
            checks:
                $checks,

            area:
                'HTTPS',

            name:
                'APP_URL HTTPS',

            passed:
                $httpsOk,

            strict:
                $strict,

            note:
                'APP_URL='
                . (
                    $appUrl !== ''
                        ? $appUrl
                        : '[EMPTY]'
                )
        );

        $sessionDriver =
            strtolower(
                (string) config(
                    'session.driver',
                    ''
                )
            );

        $requiresDatabaseSession =
            (bool) config(
                'smartfind_production.require_database_session',
                true
            );

        $this->addExpectation(
            checks:
                $checks,

            area:
                'Session',

            name:
                'Database session driver',

            passed:
                ! $requiresDatabaseSession
                || $sessionDriver === 'database',

            strict:
                $strict,

            note:
                'SESSION_DRIVER='
                . (
                    $sessionDriver !== ''
                        ? $sessionDriver
                        : '[EMPTY]'
                )
        );

        $sessionEncrypted =
            (bool) config(
                'session.encrypt',
                false
            );

        $requiresEncryptedSession =
            (bool) config(
                'smartfind_production.require_encrypted_session',
                true
            );

        $this->addExpectation(
            checks:
                $checks,

            area:
                'Session',

            name:
                'Session encryption',

            passed:
                ! $requiresEncryptedSession
                || $sessionEncrypted,

            strict:
                $strict,

            note:
                'SESSION_ENCRYPT='
                . (
                    $sessionEncrypted
                        ? 'true'
                        : 'false'
                )
        );

        $secureCookie =
            (bool) config(
                'session.secure',
                false
            );

        $this->addExpectation(
            checks:
                $checks,

            area:
                'Session',

            name:
                'Secure session cookie',

            passed:
                ! $requiresHttps
                || $secureCookie,

            strict:
                $strict,

            note:
                'SESSION_SECURE_COOKIE='
                . (
                    $secureCookie
                        ? 'true'
                        : 'false'
                )
        );

        $httpOnly =
            (bool) config(
                'session.http_only',
                true
            );

        $this->addCheck(
            checks:
                $checks,

            area:
                'Session',

            name:
                'HTTP-only session cookie',

            status:
                $httpOnly
                    ? 'PASS'
                    : 'FAIL',

            note:
                'SESSION_HTTP_ONLY='
                . (
                    $httpOnly
                        ? 'true'
                        : 'false'
                )
        );

        $sameSite =
            strtolower(
                (string) config(
                    'session.same_site',
                    ''
                )
            );

        $this->addCheck(
            checks:
                $checks,

            area:
                'Session',

            name:
                'SameSite session cookie',

            status:
                in_array(
                    $sameSite,
                    [
                        'lax',
                        'strict',
                    ],
                    true
                )
                    ? 'PASS'
                    : 'WARN',

            note:
                'SESSION_SAME_SITE='
                . (
                    $sameSite !== ''
                        ? $sameSite
                        : '[EMPTY]'
                )
        );

        $queueConnection =
            strtolower(
                (string) config(
                    'queue.default',
                    ''
                )
            );

        $requiresDatabaseQueue =
            (bool) config(
                'smartfind_production.require_database_queue',
                true
            );

        $this->addExpectation(
            checks:
                $checks,

            area:
                'Queue',

            name:
                'Database queue connection',

            passed:
                ! $requiresDatabaseQueue
                || $queueConnection === 'database',

            strict:
                $strict,

            note:
                'QUEUE_CONNECTION='
                . (
                    $queueConnection !== ''
                        ? $queueConnection
                        : '[EMPTY]'
                )
        );
    }


    private function checkSecurityRuntime(
        array &$checks
    ): void {

        $middlewareClasses =
            config(
                'smartfind_production.required_middleware_classes',
                []
            );

        foreach (
            $middlewareClasses
            as $middlewareClass
        ) {

            $this->addCheck(
                checks:
                    $checks,

                area:
                    'Security',

                name:
                    class_basename(
                        $middlewareClass
                    ),

                status:
                    class_exists(
                        $middlewareClass
                    )
                        ? 'PASS'
                        : 'FAIL',

                note:
                    class_exists(
                        $middlewareClass
                    )
                        ? 'Security middleware tersedia.'
                        : 'Security middleware tidak ditemukan.'
            );
        }
    }


    private function checkDatabaseAndMigrations(
        array &$checks
    ): bool {

        try {

            DB::select(
                'SELECT 1'
            );

            $this->addCheck(
                checks:
                    $checks,

                area:
                    'Database',

                name:
                    'Database connection',

                status:
                    'PASS',

                note:
                    'Database dapat diakses.'
            );

        } catch (Throwable $exception) {

            $this->addCheck(
                checks:
                    $checks,

                area:
                    'Database',

                name:
                    'Database connection',

                status:
                    'FAIL',

                note:
                    $this->shortMessage(
                        $exception->getMessage()
                    )
            );

            return false;
        }

        try {

            $migrator =
                app(
                    'migrator'
                );

            $migrationFiles =
                $migrator
                    ->getMigrationFiles(
                        database_path(
                            'migrations'
                        )
                    );

            $ran =
                $migrator
                    ->getRepository()
                    ->getRan();

            $pending =
                array_values(
                    array_diff(
                        array_keys(
                            $migrationFiles
                        ),
                        $ran
                    )
                );

            $this->addCheck(
                checks:
                    $checks,

                area:
                    'Database',

                name:
                    'Pending migrations',

                status:
                    $pending === []
                        ? 'PASS'
                        : 'FAIL',

                note:
                    $pending === []
                        ? 'Tidak ada migration yang pending.'
                        : count(
                            $pending
                        )
                        . ' migration masih pending.'
            );

        } catch (Throwable $exception) {

            $this->addCheck(
                checks:
                    $checks,

                area:
                    'Database',

                name:
                    'Migration state',

                status:
                    'FAIL',

                note:
                    $this->shortMessage(
                        $exception->getMessage()
                    )
            );
        }

        return true;
    }


    private function checkRequiredTables(
        array &$checks
    ): void {

        $requiredTables =
            config(
                'smartfind_production.required_tables',
                []
            );

        foreach (
            $requiredTables
            as $table
        ) {

            $exists =
                Schema::hasTable(
                    $table
                );

            $this->addCheck(
                checks:
                    $checks,

                area:
                    'Database',

                name:
                    'Table '
                    . $table,

                status:
                    $exists
                        ? 'PASS'
                        : 'FAIL',

                note:
                    $exists
                        ? 'Table tersedia.'
                        : 'Table tidak ditemukan.'
            );
        }
    }


    private function checkPrivateStorageAndNas(
        array &$checks,
        bool $strict
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

                    area:
                        'Storage',

                    name:
                        'Private storage driver',

                    status:
                        'FAIL',

                    note:
                        'Disk '
                        . $diskName
                        . ' bukan local/mapped drive.'
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

                    area:
                        'NAS',

                    name:
                        'Private storage/NAS availability',

                    status:
                        'FAIL',

                    note:
                        'Storage root tidak tersedia: '
                        . $declaredRoot
                );

                return;
            }

            $this->addCheck(
                checks:
                    $checks,

                area:
                    'NAS',

                name:
                    'Private storage/NAS availability',

                status:
                    'PASS',

                note:
                    'Storage root dapat diakses: '
                    . $canonicalRoot
            );

            $readable =
                is_readable(
                    $canonicalRoot
                );

            $writable =
                is_writable(
                    $canonicalRoot
                );

            $this->addCheck(
                checks:
                    $checks,

                area:
                    'NAS',

                name:
                    'Private storage permissions',

                status:
                    (
                        $readable
                        && $writable
                    )
                        ? 'PASS'
                        : 'FAIL',

                note:
                    'Readable='
                    . (
                        $readable
                            ? 'yes'
                            : 'no'
                    )
                    . ' | Writable='
                    . (
                        $writable
                            ? 'yes'
                            : 'no'
                    )
            );

            $requireExternal =
                (bool) config(
                    'smartfind_production.require_external_private_storage',
                    true
                );

            $expectedDrive =
                strtoupper(
                    trim(
                        (string) config(
                            'smartfind_production.expected_nas_drive',
                            'H:'
                        )
                    )
                );

            $allowUnc =
                (bool) config(
                    'smartfind_production.allow_unc_storage_root',
                    true
                );

            $normalizedRoot =
                str_replace(
                    '\\',
                    '/',
                    $canonicalRoot
                );

            $isExpectedDrive =
                $expectedDrive !== ''
                && str_starts_with(
                    strtoupper(
                        $normalizedRoot
                    ),
                    $expectedDrive
                        . '/'
                );

            $isUnc =
                str_starts_with(
                    $normalizedRoot,
                    '//'
                );

            $externalOk =
                ! $requireExternal
                || $isExpectedDrive
                || (
                    $allowUnc
                    && $isUnc
                );

            $this->addExpectation(
                checks:
                    $checks,

                area:
                    'NAS',

                name:
                    'Production NAS target',

                passed:
                    $externalOk,

                strict:
                    $strict,

                note:
                    $externalOk
                        ? 'Private storage menggunakan target external/NAS.'
                        : 'Current root masih '
                            . $canonicalRoot
                            . '; target production direncanakan '
                            . $expectedDrive
                            . ' atau UNC.'
            );

            $publicRoot =
                realpath(
                    public_path()
                );

            if ($publicRoot !== false) {

                $privateNormalized =
                    $this->normalizePath(
                        $canonicalRoot
                    );

                $publicNormalized =
                    $this->normalizePath(
                        $publicRoot
                    );

                $insidePublic =
                    $privateNormalized
                        === $publicNormalized
                    || str_starts_with(
                        $privateNormalized
                            . '/',
                        $publicNormalized
                            . '/'
                    );

                $this->addCheck(
                    checks:
                        $checks,

                    area:
                        'Storage',

                    name:
                        'Private storage outside public',

                    status:
                        ! $insidePublic
                            ? 'PASS'
                            : 'FAIL',

                    note:
                        ! $insidePublic
                            ? 'Private document root berada di luar public/.'
                            : 'Private document root berada di dalam public/.'
                );
            }

            $freeBytes =
                @disk_free_space(
                    $canonicalRoot
                );

            $minimumFree =
                max(
                    0,
                    (int) config(
                        'smartfind_production.minimum_private_storage_free_bytes',
                        5368709120
                    )
                );

            if (
                is_int(
                    $freeBytes
                )
                || is_float(
                    $freeBytes
                )
            ) {

                $freeBytes =
                    max(
                        0,
                        (int) $freeBytes
                    );

                $this->addCheck(
                    checks:
                        $checks,

                    area:
                        'NAS',

                    name:
                        'Private storage free capacity',

                    status:
                        $freeBytes >= $minimumFree
                            ? 'PASS'
                            : 'FAIL',

                    note:
                        'Free '
                        . $this->formatBytes(
                            $freeBytes
                        )
                        . ' | Minimum '
                        . $this->formatBytes(
                            $minimumFree
                        )
                );

            } else {

                $this->addCheck(
                    checks:
                        $checks,

                    area:
                        'NAS',

                    name:
                        'Private storage free capacity',

                    status:
                        'WARN',

                    note:
                        'Free capacity tidak dapat dibaca.'
                );
            }

        } catch (Throwable $exception) {

            $this->addCheck(
                checks:
                    $checks,

                area:
                    'NAS',

                name:
                    'Private storage/NAS runtime',

                status:
                    'FAIL',

                note:
                    $this->shortMessage(
                        $exception->getMessage()
                    )
            );
        }
    }


    private function checkBackupSeparation(
        array &$checks
    ): void {

        $backupRoot =
            trim(
                (string) config(
                    'smartfind_backup.root',
                    ''
                )
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

                area:
                    'Backup',

                name:
                    'Backup root',

                status:
                    'FAIL',

                note:
                    'Backup root tidak tersedia.'
            );

            return;
        }

        $canonicalBackup =
            realpath(
                $backupRoot
            );

        if ($canonicalBackup === false) {

            $this->addCheck(
                checks:
                    $checks,

                area:
                    'Backup',

                name:
                    'Backup root',

                status:
                    'FAIL',

                note:
                    'Backup root tidak dapat di-resolve.'
            );

            return;
        }

        $this->addCheck(
            checks:
                $checks,

            area:
                'Backup',

            name:
                'Backup root access',

            status:
                (
                    is_readable(
                        $canonicalBackup
                    )
                    && is_writable(
                        $canonicalBackup
                    )
                )
                    ? 'PASS'
                    : 'FAIL',

            note:
                'Backup root: '
                . $canonicalBackup
        );

        try {

            $diskName =
                (string) config(
                    'smartfind_backup.document_disk',
                    'bmc_documents'
                );

            $privateRoot =
                realpath(
                    Storage::disk(
                        $diskName
                    )->path('')
                );

            if ($privateRoot === false) {
                return;
            }

            $backupNormalized =
                $this->normalizePath(
                    $canonicalBackup
                );

            $privateNormalized =
                $this->normalizePath(
                    $privateRoot
                );

            $separated =
                $backupNormalized
                    !== $privateNormalized
                && ! str_starts_with(
                    $backupNormalized
                        . '/',
                    $privateNormalized
                        . '/'
                )
                && ! str_starts_with(
                    $privateNormalized
                        . '/',
                    $backupNormalized
                        . '/'
                );

            $this->addCheck(
                checks:
                    $checks,

                area:
                    'Backup',

                name:
                    'Backup separated from document storage',

                status:
                    $separated
                        ? 'PASS'
                        : 'FAIL',

                note:
                    $separated
                        ? 'Backup root terpisah dari private document root.'
                        : 'Backup root dan private document root overlap.'
            );

        } catch (Throwable $exception) {

            $this->addCheck(
                checks:
                    $checks,

                area:
                    'Backup',

                name:
                    'Backup/storage separation',

                status:
                    'FAIL',

                note:
                    $this->shortMessage(
                        $exception->getMessage()
                    )
            );
        }
    }


    private function checkBuildAndOptimization(
        array &$checks,
        bool $strict
    ): void {

        $requireBuild =
            (bool) config(
                'smartfind_production.require_vite_build',
                true
            );

        $manifestPath =
            public_path(
                'build/manifest.json'
            );

        $buildExists =
            is_file(
                $manifestPath
            );

        $this->addExpectation(
            checks:
                $checks,

            area:
                'Frontend',

            name:
                'Vite production build',

            passed:
                ! $requireBuild
                || $buildExists,

            strict:
                $strict,

            note:
                $buildExists
                    ? 'public/build/manifest.json tersedia.'
                    : 'Jalankan npm ci && npm run build.'
        );

        $configCached =
            app()
                ->configurationIsCached();

        $this->addExpectation(
            checks:
                $checks,

            area:
                'Optimization',

            name:
                'Configuration cache',

            passed:
                $configCached,

            strict:
                $strict,

            note:
                $configCached
                    ? 'Configuration cache aktif.'
                    : 'Jalankan php artisan config:cache pada deployment final.'
        );

        $routesCached =
            app()
                ->routesAreCached();

        $this->addExpectation(
            checks:
                $checks,

            area:
                'Optimization',

            name:
                'Route cache',

            passed:
                $routesCached,

            strict:
                $strict,

            note:
                $routesCached
                    ? 'Route cache aktif.'
                    : 'Jalankan php artisan route:cache pada deployment final.'
        );

        $runtimeDirectories = [
            storage_path() =>
                'storage/',

            base_path(
                'bootstrap/cache'
            ) =>
                'bootstrap/cache',
        ];

        foreach (
            $runtimeDirectories
            as $path => $label
        ) {

            $this->addCheck(
                checks:
                    $checks,

                area:
                    'Permissions',

                name:
                    $label
                    . ' writable',

                status:
                    (
                        is_dir(
                            $path
                        )
                        && is_writable(
                            $path
                        )
                    )
                        ? 'PASS'
                        : 'FAIL',

                note:
                    $path
            );
        }
    }


    private function checkOperationalHealth(
        array &$checks,
        OperationalHealthService $healthService
    ): void {

        try {

            $report =
                $healthService
                    ->audit();

            $critical =
                (int) (
                    $report[
                        'critical_failures'
                    ]
                    ?? 1
                );

            $warnings =
                (int) (
                    $report[
                        'warnings'
                    ]
                    ?? 0
                );

            $this->addCheck(
                checks:
                    $checks,

                area:
                    'Reliability',

                name:
                    'Operational health',

                status:
                    $critical === 0
                        ? (
                            $warnings === 0
                                ? 'PASS'
                                : 'WARN'
                        )
                        : 'FAIL',

                note:
                    'Critical Fail: '
                    . $critical
                    . ' | Warning: '
                    . $warnings
            );

        } catch (Throwable $exception) {

            $this->addCheck(
                checks:
                    $checks,

                area:
                    'Reliability',

                name:
                    'Operational health',

                status:
                    'FAIL',

                note:
                    $this->shortMessage(
                        $exception->getMessage()
                    )
            );
        }
    }


    private function addExpectation(
        array &$checks,
        string $area,
        string $name,
        bool $passed,
        bool $strict,
        string $note
    ): void {

        $this->addCheck(
            checks:
                $checks,

            area:
                $area,

            name:
                $name,

            status:
                $passed
                    ? 'PASS'
                    : (
                        $strict
                            ? 'FAIL'
                            : 'WARN'
                    ),

            note:
                $note
        );
    }


    private function addCheck(
        array &$checks,
        string $area,
        string $name,
        string $status,
        string $note
    ): void {

        $checks[] = [
            'status' =>
                $status,

            'area' =>
                $area,

            'name' =>
                $name,

            'note' =>
                $note,
        ];
    }


    private function normalizePath(
        string $path
    ): string {

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
            PHP_OS_FAMILY
            === 'Windows'
        ) {

            $normalized =
                mb_strtolower(
                    $normalized
                );
        }

        return $normalized;
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

        $index =
            0;

        while (
            $value >= 1024
            && $index
                < count(
                    $units
                )
                - 1
        ) {

            $value /=
                1024;

            $index++;
        }

        return number_format(
            $value,
            $index === 0
                ? 0
                : 2,
            ',',
            '.'
        )
        . ' '
        . $units[
            $index
        ];
    }


    private function shortMessage(
        string $message
    ): string {

        return mb_strimwidth(
            trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    $message
                )
                ?? $message
            ),
            0,
            320,
            '...'
        );
    }
}
