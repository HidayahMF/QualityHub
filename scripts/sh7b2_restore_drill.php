<?php

declare(strict_types=1);

use App\Services\SmartFindBackupService;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Process\Process;

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/bootstrap/app.php';

/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

function failDrill(string $message, int $code = 1): never
{
    fwrite(STDERR, PHP_EOL . "SH-7B2 RESTORE DRILL: FAIL" . PHP_EOL);
    fwrite(STDERR, $message . PHP_EOL);
    exit($code);
}

function normalizePathForCompare(string $path): string
{
    $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
    $real = realpath($path);

    if ($real !== false) {
        $path = $real;
    }

    $path = rtrim($path, "\\/");

    if (PHP_OS_FAMILY === 'Windows') {
        $path = strtolower($path);
    }

    return $path;
}

function pathIsInside(string $child, string $parent): bool
{
    $child = normalizePathForCompare($child);
    $parent = normalizePathForCompare($parent);

    if ($child === $parent) {
        return true;
    }

    return str_starts_with(
        $child . DIRECTORY_SEPARATOR,
        $parent . DIRECTORY_SEPARATOR
    );
}

function safeRelativePath(string $path): bool
{
    if ($path === '') {
        return false;
    }

    if (
        str_contains($path, "\0")
        || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1
        || str_starts_with($path, '/')
        || str_starts_with($path, '\\')
    ) {
        return false;
    }

    $parts = preg_split('/[\\\\\\/]+/', $path) ?: [];

    foreach ($parts as $part) {
        if ($part === '..') {
            return false;
        }
    }

    return true;
}

function findLatestBackupName(string $backupRoot): ?string
{
    $entries = scandir($backupRoot);

    if ($entries === false) {
        return null;
    }

    $candidates = [];

    foreach ($entries as $entry) {
        if (
            $entry === '.'
            || $entry === '..'
            || preg_match(
                '/^smartfind-\d{8}-\d{6}-[a-f0-9]{8}$/',
                $entry
            ) !== 1
        ) {
            continue;
        }

        $path =
            $backupRoot
            . DIRECTORY_SEPARATOR
            . $entry;

        if (! is_dir($path)) {
            continue;
        }

        $mtime = filemtime($path);

        $candidates[] = [
            'name' => $entry,
            'mtime' => $mtime === false ? 0 : $mtime,
        ];
    }

    usort(
        $candidates,
        static fn (array $a, array $b): int =>
            $b['mtime'] <=> $a['mtime']
    );

    return $candidates[0]['name'] ?? null;
}

function resolveMysqlBinary(): string
{
    $configuredDump =
        trim(
            (string) config(
                'smartfind_backup.mysqldump_binary',
                ''
            )
        );

    if ($configuredDump !== '') {
        $candidate = preg_replace(
            '/mysqldump(\.exe)?$/i',
            PHP_OS_FAMILY === 'Windows'
                ? 'mysql.exe'
                : 'mysql',
            $configuredDump
        );

        if (
            is_string($candidate)
            && $candidate !== ''
            && is_file($candidate)
        ) {
            return $candidate;
        }
    }

    if (PHP_OS_FAMILY === 'Windows') {
        $xampp = 'C:\\xampp\\mysql\\bin\\mysql.exe';

        if (is_file($xampp)) {
            return $xampp;
        }
    }

    return 'mysql';
}

function quoteMysqlIdentifier(string $value): string
{
    return '`' . str_replace('`', '``', $value) . '`';
}

try {
    $backupRoot =
        (string) config(
            'smartfind_backup.root',
            ''
        );

    if ($backupRoot === '') {
        failDrill(
            'smartfind_backup.root kosong.'
        );
    }

    if (! is_dir($backupRoot)) {
        failDrill(
            'Backup root tidak ditemukan: '
            . $backupRoot
        );
    }

    /*
     * Safety gate:
     * SH-7B2 hanya boleh memakai backup yang berada di luar project.
     */
    if (
        pathIsInside(
            $backupRoot,
            base_path()
        )
    ) {
        failDrill(
            'Backup root masih berada di dalam project: '
            . $backupRoot
            . PHP_EOL
            . 'Set SMARTFIND_BACKUP_PATH ke lokasi eksternal '
            . 'seperti D:/BMC_Backups/SmartFind terlebih dahulu.'
        );
    }

    $options = getopt(
        '',
        [
            'backup::',
        ]
    );

    $backupName =
        isset($options['backup'])
            ? trim((string) $options['backup'])
            : '';

    if ($backupName === '') {
        $backupName =
            findLatestBackupName(
                $backupRoot
            )
            ?? '';
    }

    if (
        preg_match(
            '/^smartfind-\d{8}-\d{6}-[a-f0-9]{8}$/',
            $backupName
        ) !== 1
    ) {
        failDrill(
            'Nama backup tidak valid: '
            . (
                $backupName !== ''
                    ? $backupName
                    : '(kosong)'
            )
        );
    }

    $backupDirectory =
        rtrim(
            $backupRoot,
            "\\/"
        )
        . DIRECTORY_SEPARATOR
        . $backupName;

    if (! is_dir($backupDirectory)) {
        failDrill(
            'Folder backup tidak ditemukan: '
            . $backupDirectory
        );
    }

    /** @var SmartFindBackupService $backupService */
    $backupService =
        app(
            SmartFindBackupService::class
        );

    echo PHP_EOL;
    echo "=== SH-7B2 PRE-RESTORE BACKUP VERIFY ===" . PHP_EOL;

    $verification =
        $backupService
            ->verifyBackup(
                $backupName
            );

    $failures =
        $verification['failures']
        ?? [];

    if (
        ($verification['status'] ?? '') !== 'PASS'
        || ! empty($failures)
    ) {
        failDrill(
            'Backup verification tidak PASS. '
            . 'Restore dibatalkan.'
        );
    }

    echo 'Backup      : ' . $backupName . PHP_EOL;
    echo 'Verified    : '
        . (string) (
            $verification['verified_files']
            ?? 0
        )
        . ' files'
        . PHP_EOL;
    echo "Failures    : 0" . PHP_EOL;

    $manifestPath =
        $backupDirectory
        . DIRECTORY_SEPARATOR
        . 'manifest.json';

    $manifestRaw =
        file_get_contents(
            $manifestPath
        );

    if ($manifestRaw === false) {
        failDrill(
            'Gagal membaca manifest.json.'
        );
    }

    $manifest =
        json_decode(
            $manifestRaw,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

    if (
        ! is_array($manifest)
        || ($manifest['format'] ?? '') !== 'bmc-smartfind-backup'
    ) {
        failDrill(
            'Format manifest tidak valid.'
        );
    }

    $databaseFile =
        (string) (
            $manifest['database']['file']
            ?? 'database/bmc_smartfind.sql'
        );

    if (! safeRelativePath($databaseFile)) {
        failDrill(
            'Path database dump tidak aman.'
        );
    }

    $databaseDump =
        $backupDirectory
        . DIRECTORY_SEPARATOR
        . str_replace(
            [
                '/',
                '\\',
            ],
            DIRECTORY_SEPARATOR,
            $databaseFile
        );

    if (! is_file($databaseDump)) {
        failDrill(
            'Database dump tidak ditemukan: '
            . $databaseDump
        );
    }

    $expectedDatabaseHash =
        strtolower(
            (string) (
                $manifest['database']['sha256']
                ?? ''
            )
        );

    $actualDatabaseHash =
        strtolower(
            (string) hash_file(
                'sha256',
                $databaseDump
            )
        );

    if (
        $expectedDatabaseHash === ''
        || ! hash_equals(
            $expectedDatabaseHash,
            $actualDatabaseHash
        )
    ) {
        failDrill(
            'SHA-256 database dump tidak cocok.'
        );
    }

    /*
     * Extra safety:
     * Backup SmartFind dibuat tanpa --databases.
     * Restore drill menolak dump yang mencoba memilih / membuat database.
     */
    $dumpPreview =
        file_get_contents(
            $databaseDump
        );

    if ($dumpPreview === false) {
        failDrill(
            'Gagal membaca database dump.'
        );
    }

    if (
        preg_match(
            '/^\s*(CREATE\s+DATABASE|USE\s+[`"\']?)/im',
            $dumpPreview
        ) === 1
    ) {
        failDrill(
            'Dump mengandung CREATE DATABASE / USE. '
            . 'Restore drill dibatalkan demi melindungi database aktif.'
        );
    }

    unset($dumpPreview);

    $connectionName =
        (string) config(
            'database.default'
        );

    $dbConfig =
        (array) config(
            'database.connections.'
            . $connectionName,
            []
        );

    $driver =
        strtolower(
            (string) (
                $dbConfig['driver']
                ?? ''
            )
        );

    if (
        ! in_array(
            $driver,
            [
                'mysql',
                'mariadb',
            ],
            true
        )
    ) {
        failDrill(
            'Restore drill hanya mendukung MySQL/MariaDB.'
        );
    }

    $activeDatabase =
        (string) (
            $dbConfig['database']
            ?? ''
        );

    if ($activeDatabase === '') {
        failDrill(
            'Nama database aktif kosong.'
        );
    }

    $suffix =
        date(
            'Ymd_His'
        );

    $restoreDatabase =
        'bmc_smartfind_restore_'
        . $suffix;

    if (
        strcasecmp(
            $restoreDatabase,
            $activeDatabase
        ) === 0
    ) {
        failDrill(
            'Safety gate gagal: database restore sama dengan database aktif.'
        );
    }

    $restoreBase =
        'D:'
        . DIRECTORY_SEPARATOR
        . 'BMC_Restore_Test'
        . DIRECTORY_SEPARATOR
        . $backupName
        . '-'
        . $suffix;

    $restoreDocuments =
        $restoreBase
        . DIRECTORY_SEPARATOR
        . 'SmartFindDocuments';

    $activeDocumentRoot =
        (string) config(
            'filesystems.disks.'
            . (string) config(
                'smartfind_backup.document_disk',
                'bmc_documents'
            )
            . '.root',
            ''
        );

    if (
        $activeDocumentRoot !== ''
        && (
            pathIsInside(
                $restoreDocuments,
                $activeDocumentRoot
            )
            || pathIsInside(
                $activeDocumentRoot,
                $restoreDocuments
            )
        )
    ) {
        failDrill(
            'Safety gate gagal: folder restore berpotensi tumpang tindih '
            . 'dengan private document storage aktif.'
        );
    }

    if (
        ! is_dir($restoreDocuments)
        && ! mkdir(
            $restoreDocuments,
            0775,
            true
        )
        && ! is_dir($restoreDocuments)
    ) {
        failDrill(
            'Gagal membuat folder restore: '
            . $restoreDocuments
        );
    }

    /*
     * Create a UNIQUE test DB. No DROP DATABASE and no write to active DB.
     */
    $host =
        (string) (
            $dbConfig['host']
            ?? '127.0.0.1'
        );

    $port =
        (string) (
            $dbConfig['port']
            ?? '3306'
        );

    $username =
        (string) (
            $dbConfig['username']
            ?? ''
        );

    $password =
        (string) (
            $dbConfig['password']
            ?? ''
        );

    $serverDsn =
        'mysql:host='
        . $host
        . ';port='
        . $port
        . ';charset=utf8mb4';

    $serverPdo =
        new PDO(
            $serverDsn,
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE =>
                    PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE =>
                    PDO::FETCH_ASSOC,
            ]
        );

    $serverPdo->exec(
        'CREATE DATABASE '
        . quoteMysqlIdentifier(
            $restoreDatabase
        )
        . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );

    echo PHP_EOL;
    echo "=== DATABASE RESTORE ===" . PHP_EOL;
    echo 'Active DB    : ' . $activeDatabase . ' (READ-ONLY / untouched)' . PHP_EOL;
    echo 'Restore DB   : ' . $restoreDatabase . PHP_EOL;

    $mysqlBinary =
        resolveMysqlBinary();

    $environment = [];

    if ($password !== '') {
        $environment['MYSQL_PWD'] =
            $password;
    }

    $sqlHandle =
        fopen(
            $databaseDump,
            'rb'
        );

    if ($sqlHandle === false) {
        failDrill(
            'Gagal membuka SQL dump.'
        );
    }

    $process =
        new Process(
            [
                $mysqlBinary,
                '--default-character-set=utf8mb4',
                '--host=' . $host,
                '--port=' . $port,
                '--user=' . $username,
                $restoreDatabase,
            ],
            null,
            $environment,
            null,
            (float) config(
                'smartfind_backup.database_timeout_seconds',
                300
            )
        );

    $process->setInput(
        $sqlHandle
    );

    $process->run();

    fclose($sqlHandle);

    if (! $process->isSuccessful()) {
        failDrill(
            'Import database restore gagal.'
            . PHP_EOL
            . trim(
                $process->getErrorOutput()
            )
        );
    }

    $restoreDsn =
        'mysql:host='
        . $host
        . ';port='
        . $port
        . ';dbname='
        . $restoreDatabase
        . ';charset=utf8mb4';

    $restorePdo =
        new PDO(
            $restoreDsn,
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE =>
                    PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE =>
                    PDO::FETCH_ASSOC,
            ]
        );

    $tableRows =
        $restorePdo
            ->query(
                'SHOW TABLES'
            )
            ->fetchAll(
                PDO::FETCH_COLUMN
            );

    $tableNames =
        array_values(
            array_map(
                'strval',
                $tableRows
            )
        );

    $requiredTables = [
        'users',
        'departments',
        'categories',
        'documents',
        'document_versions',
        'activity_logs',
        'document_scan_metadata',
    ];

    $missingTables =
        array_values(
            array_diff(
                $requiredTables,
                $tableNames
            )
        );

    if (! empty($missingTables)) {
        failDrill(
            'Restore DB kehilangan required tables: '
            . implode(
                ', ',
                $missingTables
            )
        );
    }

    $rowCounts = [];

    foreach ($tableNames as $table) {
        if (
            preg_match(
                '/^[A-Za-z0-9_]+$/',
                $table
            ) !== 1
        ) {
            continue;
        }

        $count =
            $restorePdo
                ->query(
                    'SELECT COUNT(*) FROM '
                    . quoteMysqlIdentifier(
                        $table
                    )
                )
                ->fetchColumn();

        $rowCounts[$table] =
            (int) $count;
    }

    /*
     * Restore documents from the verified backup and re-hash each copy.
     */
    echo PHP_EOL;
    echo "=== DOCUMENT RESTORE ===" . PHP_EOL;
    echo 'Restore path : ' . $restoreDocuments . PHP_EOL;

    $documentFiles =
        $manifest['documents']['files']
        ?? [];

    if (! is_array($documentFiles)) {
        failDrill(
            'Daftar documents pada manifest tidak valid.'
        );
    }

    $restoredFileCount = 0;
    $restoredBytes = 0;

    foreach ($documentFiles as $file) {
        if (! is_array($file)) {
            failDrill(
                'Entry document manifest tidak valid.'
            );
        }

        $relativePath =
            (string) (
                $file['path']
                ?? ''
            );

        if (! safeRelativePath($relativePath)) {
            failDrill(
                'Path document tidak aman: '
                . $relativePath
            );
        }

        $source =
            $backupDirectory
            . DIRECTORY_SEPARATOR
            . 'documents'
            . DIRECTORY_SEPARATOR
            . str_replace(
                [
                    '/',
                    '\\',
                ],
                DIRECTORY_SEPARATOR,
                $relativePath
            );

        if (! is_file($source)) {
            failDrill(
                'File backup document hilang: '
                . $relativePath
            );
        }

        $destination =
            $restoreDocuments
            . DIRECTORY_SEPARATOR
            . str_replace(
                [
                    '/',
                    '\\',
                ],
                DIRECTORY_SEPARATOR,
                $relativePath
            );

        $destinationDirectory =
            dirname(
                $destination
            );

        if (
            ! is_dir($destinationDirectory)
            && ! mkdir(
                $destinationDirectory,
                0775,
                true
            )
            && ! is_dir($destinationDirectory)
        ) {
            failDrill(
                'Gagal membuat folder restore document.'
            );
        }

        if (
            ! copy(
                $source,
                $destination
            )
        ) {
            failDrill(
                'Gagal restore document: '
                . $relativePath
            );
        }

        $expectedHash =
            strtolower(
                (string) (
                    $file['sha256']
                    ?? ''
                )
            );

        $actualHash =
            strtolower(
                (string) hash_file(
                    'sha256',
                    $destination
                )
            );

        $expectedSize =
            (int) (
                $file['size']
                ?? -1
            );

        $actualSize =
            filesize(
                $destination
            );

        if (
            $expectedHash === ''
            || ! hash_equals(
                $expectedHash,
                $actualHash
            )
            || $actualSize === false
            || (int) $actualSize !== $expectedSize
        ) {
            failDrill(
                'Hash / size hasil restore document tidak cocok: '
                . $relativePath
            );
        }

        $restoredFileCount++;
        $restoredBytes +=
            (int) $actualSize;
    }

    $expectedFileCount =
        (int) (
            $manifest['documents']['file_count']
            ?? -1
        );

    $expectedDocumentBytes =
        (int) (
            $manifest['documents']['total_bytes']
            ?? -1
        );

    if (
        $restoredFileCount !== $expectedFileCount
        || $restoredBytes !== $expectedDocumentBytes
    ) {
        failDrill(
            'Total file / byte hasil restore tidak cocok dengan manifest.'
        );
    }

    $report = [
        'status' =>
            'PASS',
        'stage' =>
            'SH-7B2',
        'created_at' =>
            date(DATE_ATOM),
        'backup' => [
            'root' =>
                $backupRoot,
            'name' =>
                $backupName,
            'verified_files' =>
                (int) (
                    $verification['verified_files']
                    ?? 0
                ),
            'failures' =>
                0,
        ],
        'database_restore' => [
            'active_database' =>
                $activeDatabase,
            'restore_database' =>
                $restoreDatabase,
            'table_count' =>
                count($tableNames),
            'missing_required_tables' =>
                $missingTables,
            'row_counts' =>
                $rowCounts,
        ],
        'document_restore' => [
            'active_storage_root' =>
                $activeDocumentRoot,
            'restore_root' =>
                $restoreDocuments,
            'restored_files' =>
                $restoredFileCount,
            'restored_bytes' =>
                $restoredBytes,
        ],
    ];

    $logDirectory =
        storage_path(
            'logs'
        );

    if (
        ! is_dir($logDirectory)
        && ! mkdir(
            $logDirectory,
            0775,
            true
        )
        && ! is_dir($logDirectory)
    ) {
        failDrill(
            'Gagal membuat storage/logs.'
        );
    }

    $reportPath =
        $logDirectory
        . DIRECTORY_SEPARATOR
        . 'sh7b2-restore-drill-'
        . date(
            'Ymd-His'
        )
        . '.json';

    $encodedReport =
        json_encode(
            $report,
            JSON_PRETTY_PRINT
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_THROW_ON_ERROR
        );

    if (
        file_put_contents(
            $reportPath,
            $encodedReport
        ) === false
    ) {
        failDrill(
            'Gagal menulis report restore drill.'
        );
    }

    echo 'Restored files : '
        . $restoredFileCount
        . PHP_EOL;
    echo 'Restored bytes : '
        . number_format(
            $restoredBytes
        )
        . PHP_EOL;

    echo PHP_EOL;
    echo "=== RESTORE VALIDATION ===" . PHP_EOL;
    echo 'Tables         : '
        . count($tableNames)
        . PHP_EOL;
    echo 'Required       : PASS' . PHP_EOL;
    echo 'Document hash  : PASS' . PHP_EOL;
    echo 'Document size  : PASS' . PHP_EOL;
    echo 'Active DB      : UNTOUCHED' . PHP_EOL;
    echo 'Active storage : UNTOUCHED' . PHP_EOL;

    echo PHP_EOL;
    echo "SH-7B2 RESTORE DRILL: PASS" . PHP_EOL;
    echo 'Restore DB   : '
        . $restoreDatabase
        . PHP_EOL;
    echo 'Restore docs : '
        . $restoreDocuments
        . PHP_EOL;
    echo 'Report       : '
        . $reportPath
        . PHP_EOL;
    echo PHP_EOL;

    exit(0);
} catch (Throwable $exception) {
    failDrill(
        $exception->getMessage()
    );
}
