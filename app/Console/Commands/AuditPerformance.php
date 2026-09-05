<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Throwable;

class AuditPerformance extends Command
{
    protected $signature =
        'smartfind:audit-performance';

    protected $description =
        'Audit read-only index database, endpoint throttling, dan baseline performance BMC SmartFind.';


    public function handle(): int
    {
        $checks = [];


        /*
        |--------------------------------------------------------------------------
        | Required Indexes
        |--------------------------------------------------------------------------
        */

        $requiredIndexes = [
            'documents' => [
                'documents_active_created_at_idx',
                'documents_active_extension_idx',
                'documents_active_document_date_idx',
                'documents_category_active_created_idx',
            ],

            'activity_logs' => [
                'activity_logs_action_created_document_idx',
            ],

            'users' => [
                'users_account_status_idx',
            ],

            'categories' => [
                'categories_active_name_idx',
            ],

            'departments' => [
                'departments_active_name_idx',
            ],
        ];

        foreach (
            $requiredIndexes
            as $table => $indexes
        ) {
            $existingIndexes =
                $this->getIndexNames(
                    $table
                );

            foreach (
                $indexes
                as $index
            ) {
                $this->addCheck(
                    $checks,
                    name:
                        $table
                        . '.'
                        . $index,
                    passed:
                        in_array(
                            $index,
                            $existingIndexes,
                            true
                        ),
                    severity:
                        'ERROR',
                    message:
                        'Index query/filter SmartFind.'
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Existing Critical Indexes
        |--------------------------------------------------------------------------
        */

        $criticalExistingIndexes = [
            'documents' => [
                'documents_title_index',
                'documents_document_code_index',
                'documents_part_number_index',
            ],

            'activity_logs' => [
                'activity_logs_created_at_index',
                'activity_logs_action_index',
            ],

            'favorites' => [
                'favorites_user_id_document_id_unique',
            ],

            'document_versions' => [
                'document_versions_document_id_revision_number_unique',
            ],
        ];

        foreach (
            $criticalExistingIndexes
            as $table => $indexes
        ) {
            $existingIndexes =
                $this->getIndexNames(
                    $table
                );

            foreach (
                $indexes
                as $index
            ) {
                $this->addCheck(
                    $checks,
                    name:
                        'Existing index: '
                        . $table
                        . '.'
                        . $index,
                    passed:
                        in_array(
                            $index,
                            $existingIndexes,
                            true
                        ),
                    severity:
                        'ERROR'
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Abuse Protection
        |--------------------------------------------------------------------------
        */

        $throttleChecks = [
            'dashboard' =>
                'throttle:60,1',

            'documents.search.live' =>
                'throttle:120,1',

            'documents.store' =>
                'throttle:10,1',

            'documents.revisions.store' =>
                'throttle:10,1',

            'documents.open' =>
                'throttle:60,1',

            'documents.download' =>
                'throttle:60,1',

            'documents.revisions.open' =>
                'throttle:60,1',

            'documents.revisions.download' =>
                'throttle:60,1',

            'favorites.store' =>
                'throttle:60,1',

            'favorites.destroy' =>
                'throttle:60,1',
        ];

        foreach (
            $throttleChecks
            as $routeName => $throttle
        ) {
            $route =
                Route::getRoutes()
                    ->getByName(
                        $routeName
                    );

            $middleware =
                $route !== null
                    ? $route
                        ->gatherMiddleware()
                    : [];

            $this->addCheck(
                $checks,
                name:
                    'Throttle '
                    . $routeName,
                passed:
                    $route !== null
                    && in_array(
                        $throttle,
                        $middleware,
                        true
                    ),
                severity:
                    'ERROR',
                message:
                    $throttle
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Query Plan — Informational
        |--------------------------------------------------------------------------
        |
        | Tiny development databases can choose a table scan even when
        | the correct index exists. Therefore EXPLAIN is INFO, not FAIL.
        |
        */

        try {

            $plan =
                DB::selectOne(
                    '
                    EXPLAIN
                    SELECT id, title, created_at
                    FROM documents
                    WHERE is_active = 1
                    ORDER BY created_at DESC
                    LIMIT 15
                    '
                );

            $usedKey =
                $plan->key
                ?? null;

            $this->addCheck(
                $checks,
                name:
                    'EXPLAIN document listing',
                passed:
                    true,
                severity:
                    'INFO',
                message:
                    'key='
                    . (
                        $usedKey
                        ?: '[optimizer memilih scan]'
                    )
            );

        } catch (Throwable $exception) {

            $this->addCheck(
                $checks,
                name:
                    'EXPLAIN document listing',
                passed:
                    true,
                severity:
                    'INFO',
                message:
                    'Tidak dapat membaca query plan: '
                    . $exception->getMessage()
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Database Runtime
        |--------------------------------------------------------------------------
        */

        $this->addCheck(
            $checks,
            name:
                'Database connection',
            passed:
                DB::connection()
                    ->getPdo()
                    !== null,
            severity:
                'ERROR'
        );


        /*
        |--------------------------------------------------------------------------
        | Output
        |--------------------------------------------------------------------------
        */

        $this->table(
            [
                'Status',
                'Check',
                'Catatan',
            ],
            array_map(
                fn (array $check) => [
                    $check['status'],
                    $check['name'],
                    $check['message'],
                ],
                $checks
            )
        );

        $failures =
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

        if ($failures === 0) {
            $this->info(
                'PERFORMANCE & ABUSE AUDIT: PASS'
            );
        } else {
            $this->error(
                'PERFORMANCE & ABUSE AUDIT: FAIL'
            );
        }

        $this->line(
            'Critical Fail: '
            . $failures
            . ' | Warning: '
            . $warnings
        );

        return $failures === 0
            ? self::SUCCESS
            : self::FAILURE;
    }


    private function getIndexNames(
        string $table
    ): array {

        $rows =
            DB::select(
                'SHOW INDEX FROM `'
                . str_replace(
                    '`',
                    '``',
                    $table
                )
                . '`'
            );

        return collect(
            $rows
        )
            ->pluck(
                'Key_name'
            )
            ->filter()
            ->unique()
            ->values()
            ->all();
    }


    private function addCheck(
        array &$checks,
        string $name,
        bool $passed,
        string $severity,
        string $message = ''
    ): void {

        if ($severity === 'INFO') {

            $status =
                'INFO';

        } elseif ($severity === 'WARNING') {

            $status =
                $passed
                    ? 'PASS'
                    : 'WARN';

        } else {

            $status =
                $passed
                    ? 'PASS'
                    : 'FAIL';
        }

        $checks[] = [
            'status' =>
                $status,

            'name' =>
                $name,

            'message' =>
                $message,
        ];
    }
}
