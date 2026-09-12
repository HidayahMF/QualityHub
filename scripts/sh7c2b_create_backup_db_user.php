<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/bootstrap/app.php';

/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

function failCreate(string $message): never
{
    fwrite(STDERR, "SH-7C2B CREATE: FAIL\n");
    fwrite(STDERR, $message . "\n");
    exit(1);
}

$password = getenv('BMC_BACKUP_DB_PASSWORD');

if (!is_string($password) || $password === '') {
    failCreate('BMC_BACKUP_DB_PASSWORD kosong.');
}

$connection = DB::connection();
$pdo = $connection->getPdo();

$database = (string) $connection->getDatabaseName();

if ($database !== 'bmc_smartfind') {
    failCreate(
        'Database aktif bukan bmc_smartfind: ' . $database
    );
}

$quotedPassword = $pdo->quote($password);

$accounts = [
    "'bmc_smartfind_backup'@'localhost'",
    "'bmc_smartfind_backup'@'127.0.0.1'",
];

foreach ($accounts as $account) {
    DB::unprepared(
        "CREATE USER IF NOT EXISTS {$account} IDENTIFIED BY {$quotedPassword}"
    );

    DB::unprepared(
        "ALTER USER {$account} IDENTIFIED BY {$quotedPassword}"
    );

    try {
        DB::unprepared(
            "REVOKE ALL PRIVILEGES, GRANT OPTION FROM {$account}"
        );
    } catch (Throwable) {
        // A fresh account may have nothing to revoke.
    }

    // Exact logical-backup privileges for the current SmartFind dump command.
    DB::unprepared(
        "GRANT SELECT, SHOW VIEW, TRIGGER, EVENT "
        . "ON `bmc_smartfind`.* TO {$account}"
    );

    // MariaDB 10.4 --routines reads routine definitions from mysql.proc.
    DB::unprepared(
        "GRANT SELECT ON `mysql`.`proc` TO {$account}"
    );
}

echo "SH-7C2B: backup DB account created.\n";
echo "User: bmc_smartfind_backup\n";
echo "Hosts: localhost, 127.0.0.1\n";
echo "Database privileges: SELECT, SHOW VIEW, TRIGGER, EVENT\n";
echo "System-table privilege: SELECT ON mysql.proc\n";
echo "No INSERT/UPDATE/DELETE/CREATE/DROP/GRANT OPTION granted.\n";
