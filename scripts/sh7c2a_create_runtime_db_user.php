<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/bootstrap/app.php';

/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$password = getenv('BMC_APP_DB_PASSWORD');

if (!is_string($password) || $password === '') {
    fwrite(STDERR, "SH-7C2A: BMC_APP_DB_PASSWORD kosong.\n");
    exit(1);
}

$connection = DB::connection();
$pdo = $connection->getPdo();
$quotedPassword = $pdo->quote($password);

$database = (string) $connection->getDatabaseName();

if ($database !== 'bmc_smartfind') {
    fwrite(
        STDERR,
        "SH-7C2A: database aktif bukan bmc_smartfind ({$database}). Dibatalkan.\n"
    );
    exit(1);
}

$accounts = [
    "'bmc_smartfind_app'@'localhost'",
    "'bmc_smartfind_app'@'127.0.0.1'",
];

foreach ($accounts as $account) {
    DB::unprepared(
        "CREATE USER IF NOT EXISTS {$account} IDENTIFIED BY {$quotedPassword}"
    );

    DB::unprepared(
        "ALTER USER {$account} IDENTIFIED BY {$quotedPassword}"
    );

    // Ensure this account starts from an exact least-privilege baseline.
    try {
        DB::unprepared(
            "REVOKE ALL PRIVILEGES, GRANT OPTION FROM {$account}"
        );
    } catch (Throwable $e) {
        // A newly-created account may have nothing to revoke; continue safely.
    }

    DB::unprepared(
        "GRANT SELECT, INSERT, UPDATE, DELETE "
        . "ON `bmc_smartfind`.* TO {$account}"
    );
}

echo "SH-7C2A: runtime DB account created.\n";
echo "Database: bmc_smartfind\n";
echo "User: bmc_smartfind_app\n";
echo "Hosts: localhost, 127.0.0.1\n";
echo "Privileges: SELECT, INSERT, UPDATE, DELETE\n";
