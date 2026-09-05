<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index ditambahkan berdasarkan query yang dipakai BMC SmartFind.
     *
     * Migration ini kompatibel dengan:
     * - MariaDB / MySQL pada aplikasi utama
     * - SQLite in-memory pada automated test Laravel
     */
    public function up(): void
    {
        $this->addIndexIfMissing(
            'documents',
            'documents_active_created_at_idx',
            fn () =>
                Schema::table(
                    'documents',
                    function (Blueprint $table) {
                        $table->index(
                            [
                                'is_active',
                                'created_at',
                            ],
                            'documents_active_created_at_idx'
                        );
                    }
                )
        );

        $this->addIndexIfMissing(
            'documents',
            'documents_active_extension_idx',
            fn () =>
                Schema::table(
                    'documents',
                    function (Blueprint $table) {
                        $table->index(
                            [
                                'is_active',
                                'file_extension',
                            ],
                            'documents_active_extension_idx'
                        );
                    }
                )
        );

        $this->addIndexIfMissing(
            'documents',
            'documents_active_document_date_idx',
            fn () =>
                Schema::table(
                    'documents',
                    function (Blueprint $table) {
                        $table->index(
                            [
                                'is_active',
                                'document_date',
                            ],
                            'documents_active_document_date_idx'
                        );
                    }
                )
        );

        $this->addIndexIfMissing(
            'documents',
            'documents_category_active_created_idx',
            fn () =>
                Schema::table(
                    'documents',
                    function (Blueprint $table) {
                        $table->index(
                            [
                                'category_id',
                                'is_active',
                                'created_at',
                            ],
                            'documents_category_active_created_idx'
                        );
                    }
                )
        );

        $this->addIndexIfMissing(
            'activity_logs',
            'activity_logs_action_created_document_idx',
            fn () =>
                Schema::table(
                    'activity_logs',
                    function (Blueprint $table) {
                        $table->index(
                            [
                                'action',
                                'created_at',
                                'document_id',
                            ],
                            'activity_logs_action_created_document_idx'
                        );
                    }
                )
        );

        $this->addIndexIfMissing(
            'users',
            'users_account_status_idx',
            fn () =>
                Schema::table(
                    'users',
                    function (Blueprint $table) {
                        $table->index(
                            'account_status',
                            'users_account_status_idx'
                        );
                    }
                )
        );

        $this->addIndexIfMissing(
            'categories',
            'categories_active_name_idx',
            fn () =>
                Schema::table(
                    'categories',
                    function (Blueprint $table) {
                        $table->index(
                            [
                                'is_active',
                                'name',
                            ],
                            'categories_active_name_idx'
                        );
                    }
                )
        );

        $this->addIndexIfMissing(
            'departments',
            'departments_active_name_idx',
            fn () =>
                Schema::table(
                    'departments',
                    function (Blueprint $table) {
                        $table->index(
                            [
                                'is_active',
                                'name',
                            ],
                            'departments_active_name_idx'
                        );
                    }
                )
        );
    }


    public function down(): void
    {
        $this->dropIndexIfExists(
            'documents',
            'documents_active_created_at_idx'
        );

        $this->dropIndexIfExists(
            'documents',
            'documents_active_extension_idx'
        );

        $this->dropIndexIfExists(
            'documents',
            'documents_active_document_date_idx'
        );

        $this->dropIndexIfExists(
            'documents',
            'documents_category_active_created_idx'
        );

        $this->dropIndexIfExists(
            'activity_logs',
            'activity_logs_action_created_document_idx'
        );

        $this->dropIndexIfExists(
            'users',
            'users_account_status_idx'
        );

        $this->dropIndexIfExists(
            'categories',
            'categories_active_name_idx'
        );

        $this->dropIndexIfExists(
            'departments',
            'departments_active_name_idx'
        );
    }


    private function addIndexIfMissing(
        string $table,
        string $index,
        callable $callback
    ): void {

        if (
            ! $this->indexExists(
                $table,
                $index
            )
        ) {
            $callback();
        }
    }


    private function dropIndexIfExists(
        string $table,
        string $index
    ): void {

        if (
            $this->indexExists(
                $table,
                $index
            )
        ) {
            Schema::table(
                $table,
                function (
                    Blueprint $blueprint
                ) use ($index) {
                    $blueprint->dropIndex(
                        $index
                    );
                }
            );
        }
    }


    /**
     * Cross-database index detection.
     *
     * Production SmartFind:
     * MariaDB / MySQL.
     *
     * Automated tests:
     * SQLite :memory:.
     */
    private function indexExists(
        string $table,
        string $index
    ): bool {

        $driver =
            DB::connection()
                ->getDriverName();

        return match ($driver) {

            'mysql',
            'mariadb' =>
                $this->mysqlIndexExists(
                    $table,
                    $index
                ),

            'sqlite' =>
                $this->sqliteIndexExists(
                    $table,
                    $index
                ),

            default =>
                $this->portableIndexExists(
                    $table,
                    $index
                ),
        };
    }


    private function mysqlIndexExists(
        string $table,
        string $index
    ): bool {

        return DB::table(
            'information_schema.statistics'
        )
            ->whereRaw(
                'TABLE_SCHEMA = DATABASE()'
            )
            ->where(
                'TABLE_NAME',
                $table
            )
            ->where(
                'INDEX_NAME',
                $index
            )
            ->exists();
    }


    private function sqliteIndexExists(
        string $table,
        string $index
    ): bool {

        /*
        | SQLite tidak memiliki information_schema.statistics.
        | PRAGMA index_list adalah API native SQLite untuk membaca index.
        */

        $safeTable =
            str_replace(
                '"',
                '""',
                $table
            );

        $rows =
            DB::select(
                'PRAGMA index_list("'
                . $safeTable
                . '")'
            );

        foreach (
            $rows
            as $row
        ) {
            if (
                isset($row->name)
                && (string) $row->name === $index
            ) {
                return true;
            }
        }

        return false;
    }


    private function portableIndexExists(
        string $table,
        string $index
    ): bool {

        /*
        | Laravel modern menyediakan introspection index melalui Schema.
        | Dipakai sebagai fallback untuk driver selain target utama.
        */

        if (
            ! method_exists(
                Schema::getFacadeRoot(),
                'getIndexes'
            )
        ) {
            throw new \RuntimeException(
                'Driver database tidak didukung untuk pemeriksaan index: '
                . DB::connection()->getDriverName()
            );
        }

        $indexes =
            Schema::getIndexes(
                $table
            );

        foreach (
            $indexes
            as $existingIndex
        ) {
            $name =
                is_array(
                    $existingIndex
                )
                    ? (
                        $existingIndex['name']
                        ?? null
                    )
                    : null;

            if (
                is_string($name)
                && $name === $index
            ) {
                return true;
            }
        }

        return false;
    }
};
