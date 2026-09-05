<?php

namespace App\Console\Commands;

use App\Http\Controllers\DepartmentManagementController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class AuditDepartmentManagement extends Command
{
    protected $signature =
        'smartfind:audit-departments';

    protected $description =
        'Audit read-only kelengkapan Department Management BMC SmartFind.';


    public function handle(): int
    {
        $checks = [];


        /*
        |--------------------------------------------------------------------------
        | Controller Contract
        |--------------------------------------------------------------------------
        */

        foreach (
            [
                'index',
                'create',
                'store',
                'edit',
                'update',
                'toggleStatus',
            ]
            as $method
        ) {
            $this->addCheck(
                $checks,
                'Controller method: '
                    . $method,
                method_exists(
                    DepartmentManagementController::class,
                    $method
                ),
                'ERROR'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Route Contract
        |--------------------------------------------------------------------------
        */

        $routes = [
            'departments.index' => [
                'GET',
            ],

            'departments.create' => [
                'GET',
            ],

            'departments.store' => [
                'POST',
            ],

            'departments.edit' => [
                'GET',
            ],

            'departments.update' => [
                'PUT',
            ],

            'departments.toggle-status' => [
                'PATCH',
            ],
        ];

        foreach (
            $routes
            as $routeName => $expectedMethods
        ) {
            $route =
                Route::getRoutes()
                    ->getByName(
                        $routeName
                    );

            $exists =
                $route !== null;

            $methodMatches =
                $exists
                && count(
                    array_intersect(
                        $expectedMethods,
                        $route->methods()
                    )
                ) > 0;

            $this->addCheck(
                $checks,
                'Route: '
                    . $routeName,
                $exists
                    && $methodMatches,
                'ERROR',
                implode(
                    '|',
                    $expectedMethods
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | View Contract
        |--------------------------------------------------------------------------
        */

        foreach (
            [
                'departments.index',
                'departments.create',
                'departments.edit',
            ]
            as $view
        ) {
            $this->addCheck(
                $checks,
                'View: '
                    . $view,
                view()->exists(
                    $view
                ),
                'ERROR'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CSRF / HTTP Mutation Contract
        |--------------------------------------------------------------------------
        */

        $mutationRoutes = [
            'departments.store',
            'departments.update',
            'departments.toggle-status',
        ];

        foreach (
            $mutationRoutes
            as $routeName
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
                'Web/CSRF group: '
                    . $routeName,
                $route !== null
                && in_array(
                    'web',
                    $middleware,
                    true
                ),
                'ERROR'
            );

            $this->addCheck(
                $checks,
                'Authenticated route: '
                    . $routeName,
                $route !== null
                && in_array(
                    'auth',
                    $middleware,
                    true
                ),
                'ERROR'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Test File
        |--------------------------------------------------------------------------
        */

        $testPath =
            base_path(
                'tests/Feature/DepartmentManagementTest.php'
            );

        $this->addCheck(
            $checks,
            'Regression test file tersedia',
            is_file(
                $testPath
            ),
            'ERROR',
            'tests/Feature/DepartmentManagementTest.php'
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
                'DEPARTMENT MANAGEMENT AUDIT: PASS'
            );
        } else {
            $this->error(
                'DEPARTMENT MANAGEMENT AUDIT: FAIL'
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


    private function addCheck(
        array &$checks,
        string $name,
        bool $passed,
        string $severity,
        string $message = ''
    ): void {

        if ($severity === 'WARNING') {

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
