<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/bootstrap/app.php';

/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

function failCheck(string $message): never
{
    fwrite(STDERR, "SH-7C2A VERIFY: FAIL\n");
    fwrite(STDERR, $message . "\n");
    exit(1);
}

$password = getenv('BMC_APP_DB_PASSWORD');

if (!is_string($password) || $password === '') {
    failCheck('BMC_APP_DB_PASSWORD kosong.');
}

$database = (string) config('database.connections.mysql.database', '');

if ($database !== 'bmc_smartfind') {
    failCheck('Database aktif bukan bmc_smartfind.');
}

echo "=== CURRENT GRANTS ===\n";

$grantTargets = [
    "'bmc_smartfind_app'@'localhost'",
    "'bmc_smartfind_app'@'127.0.0.1'",
];

foreach ($grantTargets as $target) {
    echo "\n{$target}\n";

    $rows = DB::select("SHOW GRANTS FOR {$target}");

    foreach ($rows as $row) {
        foreach ((array) $row as $value) {
            echo $value . "\n";
        }
    }
}

$dsn =
    'mysql:host=127.0.0.1;port=3306;dbname=bmc_smartfind;charset=utf8mb4';

$runtime = new PDO(
    $dsn,
    'bmc_smartfind_app',
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

echo "\n=== RUNTIME SELECT TEST ===\n";

$row = $runtime
    ->query(
        'SELECT CURRENT_USER() AS privilege_user, '
        . 'DATABASE() AS db, '
        . '(SELECT COUNT(*) FROM users) AS users'
    )
    ->fetch();

if (!is_array($row)) {
    failCheck('SELECT runtime tidak menghasilkan data.');
}

echo 'privilege_user: ' . ($row['privilege_user'] ?? '-') . "\n";
echo 'db: ' . ($row['db'] ?? '-') . "\n";
echo 'users: ' . ($row['users'] ?? '-') . "\n";
echo "SELECT: PASS\n";

echo "\n=== RUNTIME CREATE TEST ===\n";

$table = '__sf_should_fail';

try {
    $runtime->exec("CREATE TABLE {$table} (id INT)");

    // Safety cleanup using the current admin connection if CREATE was wrongly allowed.
    try {
        DB::statement("DROP TABLE IF EXISTS `{$table}`");
    } catch (Throwable) {
        // Nothing else to do; report the privilege failure below.
    }

    failCheck(
        'CREATE ternyata ALLOWED. Runtime account masih terlalu kuat.'
    );
} catch (PDOException $e) {
    $message = $e->getMessage();

    if (
        !str_contains(strtolower($message), 'denied')
        && !str_contains($message, '1142')
    ) {
        failCheck(
            'CREATE gagal, tetapi bukan karena privilege denied: ' . $message
        );
    }

    echo "CREATE: DENIED (expected)\n";
}

echo "\nSH-7C2A VERIFY: PASS\n";
echo "Runtime privileges are least-privilege for normal app DML.\n";
