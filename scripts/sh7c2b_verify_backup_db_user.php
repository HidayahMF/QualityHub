<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/bootstrap/app.php';

/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

function failVerify(string $message): never
{
    fwrite(STDERR, PHP_EOL . "SH-7C2B VERIFY: FAIL\n");
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function resolveDumpBinary(): string
{
    $configured = trim(
        (string) config(
            'smartfind_backup.mysqldump_binary',
            ''
        )
    );

    if ($configured !== '') {
        return $configured;
    }

    if (PHP_OS_FAMILY === 'Windows') {
        $xampp = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';

        if (is_file($xampp)) {
            return $xampp;
        }
    }

    return 'mysqldump';
}

$password = getenv('BMC_BACKUP_DB_PASSWORD');

if (!is_string($password) || $password === '') {
    failVerify('BMC_BACKUP_DB_PASSWORD kosong.');
}

echo "=== CURRENT BACKUP ACCOUNT GRANTS ===\n";

$targets = [
    "'bmc_smartfind_backup'@'localhost'",
    "'bmc_smartfind_backup'@'127.0.0.1'",
];

foreach ($targets as $target) {
    echo PHP_EOL . $target . PHP_EOL;

    $rows = DB::select(
        "SHOW GRANTS FOR {$target}"
    );

    foreach ($rows as $row) {
        foreach ((array) $row as $value) {
            echo $value . PHP_EOL;
        }
    }
}

$dbConfig = (array) config(
    'database.connections.'
    . (string) config('database.default'),
    []
);

$host = (string) ($dbConfig['host'] ?? '127.0.0.1');
$port = (string) ($dbConfig['port'] ?? '3306');
$database = (string) ($dbConfig['database'] ?? '');

if ($database !== 'bmc_smartfind') {
    failVerify(
        'Database aktif bukan bmc_smartfind.'
    );
}

echo PHP_EOL . "=== BACKUP ACCOUNT READ TEST ===\n";

$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
    'bmc_smartfind_backup',
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

$row = $pdo
    ->query(
        "SELECT CURRENT_USER() AS privilege_user, "
        . "DATABASE() AS db, "
        . "(SELECT COUNT(*) FROM documents) AS documents"
    )
    ->fetch();

if (!is_array($row)) {
    failVerify(
        'Read test tidak menghasilkan data.'
    );
}

echo 'privilege_user: '
    . ($row['privilege_user'] ?? '-')
    . PHP_EOL;
echo 'db: '
    . ($row['db'] ?? '-')
    . PHP_EOL;
echo 'documents: '
    . ($row['documents'] ?? '-')
    . PHP_EOL;
echo "SELECT: PASS\n";

echo PHP_EOL . "=== WRITE-DENY TEST ===\n";

try {
    $pdo->exec(
        "INSERT INTO departments (name, created_at, updated_at) "
        . "VALUES ('__SH7_BACKUP_SHOULD_FAIL__', NOW(), NOW())"
    );

    try {
        DB::table('departments')
            ->where(
                'name',
                '__SH7_BACKUP_SHOULD_FAIL__'
            )
            ->delete();
    } catch (Throwable) {
        // Best-effort cleanup only.
    }

    failVerify(
        'INSERT ternyata ALLOWED. Backup account terlalu kuat.'
    );
} catch (PDOException $e) {
    $message = strtolower(
        $e->getMessage()
    );

    if (
        !str_contains($message, 'denied')
        && !str_contains($message, '1142')
    ) {
        failVerify(
            'INSERT gagal tetapi bukan privilege denied: '
            . $e->getMessage()
        );
    }

    echo "INSERT: DENIED (expected)\n";
}

echo PHP_EOL . "=== EXACT SMARTFIND MYSQLDUMP TEST ===\n";

$tempRoot = storage_path(
    'app/sh7c2b-test'
);

if (
    !is_dir($tempRoot)
    && !mkdir($tempRoot, 0775, true)
    && !is_dir($tempRoot)
) {
    failVerify(
        'Gagal membuat folder test dump.'
    );
}

$tempDump =
    $tempRoot
    . DIRECTORY_SEPARATOR
    . 'backup-user-test-'
    . date('Ymd-His')
    . '.sql';

$command = [
    resolveDumpBinary(),
    '--single-transaction',
    '--quick',
    '--routines',
    '--triggers',
    '--events',
    '--default-character-set=utf8mb4',
    '--host=' . $host,
    '--port=' . $port,
    '--user=bmc_smartfind_backup',
    '--result-file=' . $tempDump,
    $database,
];

$process = new Process(
    $command,
    null,
    [
        'MYSQL_PWD' => $password,
    ],
    null,
    300
);

$process->run();

if (!$process->isSuccessful()) {
    @unlink($tempDump);

    failVerify(
        'mysqldump gagal: '
        . trim($process->getErrorOutput())
    );
}

if (!is_file($tempDump)) {
    failVerify(
        'mysqldump tidak menghasilkan file.'
    );
}

$size = filesize($tempDump);

if ($size === false || $size <= 0) {
    @unlink($tempDump);

    failVerify(
        'mysqldump menghasilkan file kosong.'
    );
}

$hash = hash_file(
    'sha256',
    $tempDump
);

echo 'Dump bytes : '
    . number_format((int) $size)
    . PHP_EOL;
echo 'SHA-256    : '
    . $hash
    . PHP_EOL;
echo "MYSQLDUMP: PASS\n";

@unlink($tempDump);

echo PHP_EOL;
echo "SH-7C2B VERIFY: PASS\n";
echo "Dedicated backup account can create the current SmartFind logical backup ";
echo "without application write privileges.\n";
