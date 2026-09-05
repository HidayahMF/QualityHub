<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class SmartFindBackupService
{
    public function __construct(
        private readonly PrivateDocumentStorageService $privateStorage
    ) {
    }


    /**
     * Audit read-only antara database dan private document storage.
     */
    public function auditCurrentIntegrity(): array
    {
        $diskName =
            (string) config(
                'smartfind_backup.document_disk',
                'bmc_documents'
            );

        $rows = [];
        $criticalFailures = 0;
        $warnings = 0;
        $referencedPaths = [];
        $recordCount = 0;


        /*
        |--------------------------------------------------------------------------
        | Current Documents
        |--------------------------------------------------------------------------
        */

        Document::query()
            ->select([
                'id',
                'title',
                'file_path',
                'file_name',
                'file_size',
                'current_revision',
            ])
            ->orderBy('id')
            ->chunkById(
                200,
                function ($documents) use (
                    $diskName,
                    &$rows,
                    &$criticalFailures,
                    &$referencedPaths,
                    &$recordCount
                ) {

                    foreach ($documents as $document) {

                        $recordCount++;

                        $result =
                            $this->auditDatabaseFileRecord(
                                source:
                                    'document',

                                sourceId:
                                    (int) $document->id,

                                label:
                                    (string) $document->title,

                                relativePath:
                                    (string) $document->file_path,

                                expectedSize:
                                    $document->file_size !== null
                                        ? (int) $document->file_size
                                        : null,

                                diskName:
                                    $diskName
                            );

                        $rows[] =
                            $result;

                        if ($result['status'] === 'FAIL') {
                            $criticalFailures++;
                        }

                        if ($result['safe_path']) {
                            $referencedPaths[
                                $this->normalizeRelativePath(
                                    (string) $document->file_path
                                )
                            ] = true;
                        }
                    }
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Document Versions
        |--------------------------------------------------------------------------
        */

        DocumentVersion::query()
            ->select([
                'id',
                'document_id',
                'revision_number',
                'file_path',
                'file_name',
                'file_size',
            ])
            ->orderBy('id')
            ->chunkById(
                200,
                function ($versions) use (
                    $diskName,
                    &$rows,
                    &$criticalFailures,
                    &$referencedPaths,
                    &$recordCount
                ) {

                    foreach ($versions as $version) {

                        $recordCount++;

                        $result =
                            $this->auditDatabaseFileRecord(
                                source:
                                    'version',

                                sourceId:
                                    (int) $version->id,

                                label:
                                    'Document '
                                    . (int) $version->document_id
                                    . ' / REV '
                                    . (int) $version->revision_number,

                                relativePath:
                                    (string) $version->file_path,

                                expectedSize:
                                    $version->file_size !== null
                                        ? (int) $version->file_size
                                        : null,

                                diskName:
                                    $diskName
                            );

                        $rows[] =
                            $result;

                        if ($result['status'] === 'FAIL') {
                            $criticalFailures++;
                        }

                        if ($result['safe_path']) {
                            $referencedPaths[
                                $this->normalizeRelativePath(
                                    (string) $version->file_path
                                )
                            ] = true;
                        }
                    }
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Current Revision Consistency
        |--------------------------------------------------------------------------
        |
        | documents.current_revision harus memiliki DocumentVersion dengan
        | revision_number yang sama dan menunjuk file_path yang sama.
        |
        */

        Document::query()
            ->select([
                'id',
                'title',
                'file_path',
                'current_revision',
            ])
            ->orderBy('id')
            ->chunkById(
                200,
                function ($documents) use (
                    &$rows,
                    &$criticalFailures
                ) {

                    foreach ($documents as $document) {

                        $version =
                            DocumentVersion::query()
                                ->where(
                                    'document_id',
                                    $document->id
                                )
                                ->where(
                                    'revision_number',
                                    (int) $document->current_revision
                                )
                                ->first([
                                    'id',
                                    'file_path',
                                ]);

                        if ($version === null) {

                            $rows[] = [
                                'status' =>
                                    'FAIL',

                                'type' =>
                                    'revision_link',

                                'source' =>
                                    'document',

                                'source_id' =>
                                    (int) $document->id,

                                'label' =>
                                    (string) $document->title,

                                'path' =>
                                    (string) $document->file_path,

                                'note' =>
                                    'Current revision tidak memiliki record DocumentVersion yang sesuai.',
                            ];

                            $criticalFailures++;

                            continue;
                        }

                        $documentPath =
                            $this->normalizeRelativePath(
                                (string) $document->file_path
                            );

                        $versionPath =
                            $this->normalizeRelativePath(
                                (string) $version->file_path
                            );

                        if ($documentPath !== $versionPath) {

                            $rows[] = [
                                'status' =>
                                    'FAIL',

                                'type' =>
                                    'revision_link',

                                'source' =>
                                    'document',

                                'source_id' =>
                                    (int) $document->id,

                                'label' =>
                                    (string) $document->title,

                                'path' =>
                                    (string) $document->file_path,

                                'note' =>
                                    'Current document file_path berbeda dengan file_path current DocumentVersion.',
                            ];

                            $criticalFailures++;
                        }
                    }
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Physical Storage Scan
        |--------------------------------------------------------------------------
        */

        $scan =
            $this->scanPrivateStorage(
                includeHashes:
                    false
            );

        foreach (
            $scan['symlinks']
            as $symlink
        ) {
            $rows[] = [
                'status' =>
                    'FAIL',

                'type' =>
                    'symlink',

                'source' =>
                    'storage',

                'source_id' =>
                    '-',

                'label' =>
                    'Symlink / junction terdeteksi',

                'path' =>
                    $symlink,

                'note' =>
                    'Private storage tidak boleh mengandalkan link yang dapat keluar dari root.',
            ];

            $criticalFailures++;
        }

        foreach (
            $scan['files']
            as $file
        ) {
            $path =
                $file['path'];

            if (
                ! isset(
                    $referencedPaths[
                        $path
                    ]
                )
            ) {
                $rows[] = [
                    'status' =>
                        'WARN',

                    'type' =>
                        'orphan',

                    'source' =>
                        'storage',

                    'source_id' =>
                        '-',

                    'label' =>
                        'File fisik tidak direferensikan database',

                    'path' =>
                        $path,

                    'note' =>
                        'Tidak dihapus otomatis. Review manual sebelum tindakan apa pun.',
                ];

                $warnings++;
            }
        }


        return [
            'status' =>
                $criticalFailures === 0
                    ? 'PASS'
                    : 'FAIL',

            'critical_failures' =>
                $criticalFailures,

            'warnings' =>
                $warnings,

            'database_file_records' =>
                $recordCount,

            'unique_referenced_files' =>
                count(
                    $referencedPaths
                ),

            'physical_files' =>
                count(
                    $scan['files']
                ),

            'physical_bytes' =>
                $scan['total_bytes'],

            'rows' =>
                $rows,
        ];
    }


    /**
     * Membuat full backup database + private documents.
     */
    public function createFullBackup(): array
    {
        $integrity =
            $this->auditCurrentIntegrity();

        if (
            $integrity[
                'critical_failures'
            ] > 0
        ) {
            throw new RuntimeException(
                'Backup dibatalkan karena data integrity audit memiliki critical failure. '
                . 'Jalankan php artisan smartfind:audit-integrity --details.'
            );
        }

        $backupRoot =
            $this->prepareBackupRoot();

        $documentRoot =
            $this->privateStorageRoot();

        $this->assertBackupRootSeparated(
            $backupRoot,
            $documentRoot
        );

        $scan =
            $this->scanPrivateStorage(
                includeHashes:
                    false
            );

        $requiredBytes =
            (int) $scan['total_bytes']
            + (int) config(
                'smartfind_backup.minimum_free_space_margin_bytes',
                52428800
            );

        $freeBytes =
            @disk_free_space(
                $backupRoot
            );

        if (
            is_float($freeBytes)
            || is_int($freeBytes)
        ) {
            if ($freeBytes < $requiredBytes) {
                throw new RuntimeException(
                    'Ruang kosong backup tidak mencukupi. Dibutuhkan minimal '
                    . number_format($requiredBytes)
                    . ' byte.'
                );
            }
        }

        $backupName =
            'smartfind-'
            . now()->format(
                'Ymd-His'
            )
            . '-'
            . substr(
                bin2hex(
                    random_bytes(
                        4
                    )
                ),
                0,
                8
            );

        $partialDirectory =
            $backupRoot
            . DIRECTORY_SEPARATOR
            . '.partial-'
            . $backupName;

        $finalDirectory =
            $backupRoot
            . DIRECTORY_SEPARATOR
            . $backupName;

        $documentsDirectory =
            $partialDirectory
            . DIRECTORY_SEPARATOR
            . 'documents';

        $databaseDirectory =
            $partialDirectory
            . DIRECTORY_SEPARATOR
            . 'database';

        try {

            $this->makeDirectory(
                $documentsDirectory
            );

            $this->makeDirectory(
                $databaseDirectory
            );


            /*
            |--------------------------------------------------------------------------
            | Database Dump
            |--------------------------------------------------------------------------
            */

            $databaseDumpPath =
                $databaseDirectory
                . DIRECTORY_SEPARATOR
                . 'bmc_smartfind.sql';

            $database =
                $this->dumpDatabase(
                    $databaseDumpPath
                );


            /*
            |--------------------------------------------------------------------------
            | Private Documents
            |--------------------------------------------------------------------------
            */

            $documentFiles = [];
            $documentBytes = 0;

            foreach (
                $scan['files']
                as $file
            ) {
                $relativePath =
                    $file['path'];

                $source =
                    $this->privateStorage
                        ->resolveExistingFile(
                            $relativePath,
                            (string) config(
                                'smartfind_backup.document_disk',
                                'bmc_documents'
                            )
                        );

                if ($source === null) {
                    throw new RuntimeException(
                        'File berubah / hilang selama backup: '
                        . $relativePath
                    );
                }

                $destination =
                    $documentsDirectory
                    . DIRECTORY_SEPARATOR
                    . str_replace(
                        '/',
                        DIRECTORY_SEPARATOR,
                        $relativePath
                    );

                $this->makeDirectory(
                    dirname(
                        $destination
                    )
                );

                if (
                    ! copy(
                        $source,
                        $destination
                    )
                ) {
                    throw new RuntimeException(
                        'Gagal menyalin file backup: '
                        . $relativePath
                    );
                }

                $sourceHash =
                    hash_file(
                        'sha256',
                        $source
                    );

                $destinationHash =
                    hash_file(
                        'sha256',
                        $destination
                    );

                if (
                    ! is_string(
                        $sourceHash
                    )
                    || ! is_string(
                        $destinationHash
                    )
                    || ! hash_equals(
                        $sourceHash,
                        $destinationHash
                    )
                ) {
                    throw new RuntimeException(
                        'Hash hasil copy tidak cocok: '
                        . $relativePath
                    );
                }

                $size =
                    filesize(
                        $destination
                    );

                if ($size === false) {
                    throw new RuntimeException(
                        'Tidak dapat membaca ukuran hasil backup: '
                        . $relativePath
                    );
                }

                $documentFiles[] = [
                    'path' =>
                        $relativePath,

                    'size' =>
                        (int) $size,

                    'sha256' =>
                        $destinationHash,
                ];

                $documentBytes +=
                    (int) $size;
            }


            /*
            |--------------------------------------------------------------------------
            | Manifest
            |--------------------------------------------------------------------------
            */

            $manifest = [
                'format' =>
                    'bmc-smartfind-backup',

                'version' =>
                    1,

                'created_at' =>
                    now()->toIso8601String(),

                'backup_name' =>
                    $backupName,

                'app_name' =>
                    (string) config(
                        'app.name'
                    ),

                'database' =>
                    $database,

                'documents' => [
                    'disk' =>
                        (string) config(
                            'smartfind_backup.document_disk',
                            'bmc_documents'
                        ),

                    'file_count' =>
                        count(
                            $documentFiles
                        ),

                    'total_bytes' =>
                        $documentBytes,

                    'files' =>
                        $documentFiles,
                ],

                'integrity_snapshot' => [
                    'database_file_records' =>
                        $integrity[
                            'database_file_records'
                        ],

                    'unique_referenced_files' =>
                        $integrity[
                            'unique_referenced_files'
                        ],

                    'physical_files' =>
                        $integrity[
                            'physical_files'
                        ],

                    'warnings' =>
                        $integrity[
                            'warnings'
                        ],
                ],
            ];

            $manifestPath =
                $partialDirectory
                . DIRECTORY_SEPARATOR
                . 'manifest.json';

            $encodedManifest =
                json_encode(
                    $manifest,
                    JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_SLASHES
                    | JSON_UNESCAPED_UNICODE
                    | JSON_THROW_ON_ERROR
                );

            if (
                file_put_contents(
                    $manifestPath,
                    $encodedManifest
                ) === false
            ) {
                throw new RuntimeException(
                    'Gagal menulis manifest backup.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Atomic Finalization
            |--------------------------------------------------------------------------
            */

            if (
                ! rename(
                    $partialDirectory,
                    $finalDirectory
                )
            ) {
                throw new RuntimeException(
                    'Gagal memfinalisasi folder backup.'
                );
            }

            return [
                'backup_name' =>
                    $backupName,

                'path' =>
                    $finalDirectory,

                'database_bytes' =>
                    $database['size'],

                'document_files' =>
                    count(
                        $documentFiles
                    ),

                'document_bytes' =>
                    $documentBytes,

                'integrity_warnings' =>
                    $integrity['warnings'],
            ];

        } catch (Throwable $exception) {

            $this->deleteDirectory(
                $partialDirectory
            );

            throw $exception;
        }
    }


    /**
     * Verifikasi SHA-256, ukuran, dan daftar file suatu backup.
     *
     * Verifikasi ini mendeteksi corruption / incomplete copy.
     * Ini bukan digital-signature authenticity verification.
     */
    public function verifyBackup(
        string $backupName
    ): array {

        if (
            preg_match(
                '/^smartfind-\d{8}-\d{6}-[a-f0-9]{8}$/',
                $backupName
            ) !== 1
        ) {
            throw new RuntimeException(
                'Nama backup tidak valid.'
            );
        }

        $backupRoot =
            $this->prepareBackupRoot();

        $backupDirectory =
            $backupRoot
            . DIRECTORY_SEPARATOR
            . $backupName;

        $manifestPath =
            $backupDirectory
            . DIRECTORY_SEPARATOR
            . 'manifest.json';

        if (
            ! is_file(
                $manifestPath
            )
        ) {
            throw new RuntimeException(
                'manifest.json tidak ditemukan.'
            );
        }

        $manifest =
            json_decode(
                (string) file_get_contents(
                    $manifestPath
                ),
                true,
                flags:
                    JSON_THROW_ON_ERROR
            );

        if (
            ($manifest['format'] ?? null)
                !== 'bmc-smartfind-backup'
            || (int) (
                $manifest['version']
                ?? 0
            ) !== 1
        ) {
            throw new RuntimeException(
                'Format manifest backup tidak dikenali.'
            );
        }

        $failures = [];
        $verifiedFiles = 0;


        /*
        |--------------------------------------------------------------------------
        | Database Dump
        |--------------------------------------------------------------------------
        */

        if (
            (string) (
                $manifest[
                    'backup_name'
                ]
                ?? ''
            ) !== $backupName
        ) {
            throw new RuntimeException(
                'Nama backup pada manifest tidak cocok dengan folder backup.'
            );
        }

        $databaseFile =
            (string) (
                $manifest[
                    'database'
                ][
                    'file'
                ]
                ?? ''
            );

        if (
            $databaseFile
                !== 'database/bmc_smartfind.sql'
        ) {
            throw new RuntimeException(
                'Path database dump pada manifest tidak valid.'
            );
        }

        $databasePath =
            $backupDirectory
            . DIRECTORY_SEPARATOR
            . 'database'
            . DIRECTORY_SEPARATOR
            . 'bmc_smartfind.sql';

        $this->verifyManifestFile(
            absolutePath:
                $databasePath,

            expectedSize:
                (int) (
                    $manifest[
                        'database'
                    ][
                        'size'
                    ]
                    ?? -1
                ),

            expectedHash:
                (string) (
                    $manifest[
                        'database'
                    ][
                        'sha256'
                    ]
                    ?? ''
                ),

            label:
                $databaseFile,

            failures:
                $failures
        );

        $verifiedFiles++;


        /*
        |--------------------------------------------------------------------------
        | Private Documents
        |--------------------------------------------------------------------------
        */

        $expectedDocumentPaths = [];

        foreach (
            (
                $manifest[
                    'documents'
                ][
                    'files'
                ]
                ?? []
            )
            as $file
        ) {
            $relativePath =
                (string) (
                    $file[
                        'path'
                    ]
                    ?? ''
                );

            if (
                ! $this->privateStorage
                    ->isSafeRelativePath(
                        $relativePath
                    )
            ) {
                $failures[] =
                    'Manifest memiliki relative path tidak aman: '
                    . $relativePath;

                continue;
            }

            $expectedDocumentPaths[
                $this->normalizeRelativePath(
                    $relativePath
                )
            ] = true;

            $absolutePath =
                $backupDirectory
                . DIRECTORY_SEPARATOR
                . 'documents'
                . DIRECTORY_SEPARATOR
                . str_replace(
                    '/',
                    DIRECTORY_SEPARATOR,
                    $relativePath
                );

            $this->verifyManifestFile(
                absolutePath:
                    $absolutePath,

                expectedSize:
                    (int) (
                        $file[
                            'size'
                        ]
                        ?? -1
                    ),

                expectedHash:
                    (string) (
                        $file[
                            'sha256'
                        ]
                        ?? ''
                    ),

                label:
                    $relativePath,

                failures:
                    $failures
            );

            $verifiedFiles++;
        }


        /*
        |--------------------------------------------------------------------------
        | Detect Unexpected Files
        |--------------------------------------------------------------------------
        */

        $backupDocumentsDirectory =
            $backupDirectory
            . DIRECTORY_SEPARATOR
            . 'documents';

        $actualBackupFiles =
            $this->scanDirectoryFiles(
                $backupDocumentsDirectory,
                includeHashes:
                    false
            );

        foreach (
            $actualBackupFiles['files']
            as $file
        ) {
            if (
                ! isset(
                    $expectedDocumentPaths[
                        $file['path']
                    ]
                )
            ) {
                $failures[] =
                    'File tambahan tidak tercatat manifest: '
                    . $file['path'];
            }
        }

        if (
            count(
                $expectedDocumentPaths
            ) !== (int) (
                $manifest[
                    'documents'
                ][
                    'file_count'
                ]
                ?? -1
            )
        ) {
            $failures[] =
                'Jumlah file pada manifest tidak konsisten.';
        }

        return [
            'status' =>
                empty(
                    $failures
                )
                    ? 'PASS'
                    : 'FAIL',

            'backup_name' =>
                $backupName,

            'verified_files' =>
                $verifiedFiles,

            'failures' =>
                $failures,

            'manifest' =>
                $manifest,
        ];
    }


    public function backupRoot(): string
    {
        return $this->prepareBackupRoot();
    }


    private function auditDatabaseFileRecord(
        string $source,
        int $sourceId,
        string $label,
        string $relativePath,
        ?int $expectedSize,
        string $diskName
    ): array {

        $safe =
            $this->privateStorage
                ->isSafeRelativePath(
                    $relativePath
                );

        $absolute =
            $safe
                ? $this->privateStorage
                    ->resolveExistingFile(
                        $relativePath,
                        $diskName
                    )
                : null;

        if (! $safe) {
            return [
                'status' =>
                    'FAIL',

                'type' =>
                    'unsafe_path',

                'source' =>
                    $source,

                'source_id' =>
                    $sourceId,

                'label' =>
                    $label,

                'path' =>
                    $relativePath !== ''
                        ? $relativePath
                        : '[EMPTY]',

                'note' =>
                    'file_path bukan relative path yang aman.',

                'safe_path' =>
                    false,
            ];
        }

        if ($absolute === null) {
            return [
                'status' =>
                    'FAIL',

                'type' =>
                    'missing_file',

                'source' =>
                    $source,

                'source_id' =>
                    $sourceId,

                'label' =>
                    $label,

                'path' =>
                    $relativePath,

                'note' =>
                    'File database tidak ditemukan di private storage / berada di luar root.',

                'safe_path' =>
                    true,
            ];
        }

        if ($expectedSize !== null) {

            $actualSize =
                filesize(
                    $absolute
                );

            if (
                $actualSize === false
                || (int) $actualSize
                    !== $expectedSize
            ) {
                return [
                    'status' =>
                        'FAIL',

                    'type' =>
                        'size_mismatch',

                    'source' =>
                        $source,

                    'source_id' =>
                        $sourceId,

                    'label' =>
                        $label,

                    'path' =>
                        $relativePath,

                    'note' =>
                        'Ukuran file fisik berbeda dengan file_size database.',

                    'safe_path' =>
                        true,
                ];
            }
        }

        return [
            'status' =>
                'PASS',

            'type' =>
                'file_record',

            'source' =>
                $source,

            'source_id' =>
                $sourceId,

            'label' =>
                $label,

            'path' =>
                $relativePath,

            'note' =>
                'Database dan private file sesuai.',

            'safe_path' =>
                true,
        ];
    }


    private function scanPrivateStorage(
        bool $includeHashes
    ): array {

        return $this->scanDirectoryFiles(
            $this->privateStorageRoot(),
            $includeHashes
        );
    }


    private function scanDirectoryFiles(
        string $root,
        bool $includeHashes
    ): array {

        $canonicalRoot =
            realpath(
                $root
            );

        if (
            $canonicalRoot === false
            || ! is_dir(
                $canonicalRoot
            )
        ) {
            throw new RuntimeException(
                'Directory storage tidak tersedia: '
                . $root
            );
        }

        $files = [];
        $symlinks = [];
        $totalBytes = 0;

        $iterator =
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $canonicalRoot,
                    RecursiveDirectoryIterator::SKIP_DOTS
                ),
                RecursiveIteratorIterator::SELF_FIRST
            );

        foreach (
            $iterator
            as $item
        ) {
            if ($item->isLink()) {

                $symlinks[] =
                    $this->relativeFromRoot(
                        $canonicalRoot,
                        $item->getPathname()
                    );

                continue;
            }

            if (! $item->isFile()) {
                continue;
            }

            $absolutePath =
                $item->getPathname();

            $relativePath =
                $this->relativeFromRoot(
                    $canonicalRoot,
                    $absolutePath
                );

            if (
                ! $this->privateStorage
                    ->isSafeRelativePath(
                        $relativePath
                    )
            ) {
                throw new RuntimeException(
                    'Storage memiliki relative path tidak aman: '
                    . $relativePath
                );
            }

            $size =
                $item->getSize();

            $record = [
                'path' =>
                    $relativePath,

                'size' =>
                    $size,
            ];

            if ($includeHashes) {
                $record['sha256'] =
                    hash_file(
                        'sha256',
                        $absolutePath
                    );
            }

            $files[] =
                $record;

            $totalBytes +=
                $size;
        }

        usort(
            $files,
            fn (array $a, array $b) =>
                strcmp(
                    $a['path'],
                    $b['path']
                )
        );

        sort(
            $symlinks
        );

        return [
            'files' =>
                $files,

            'symlinks' =>
                $symlinks,

            'total_bytes' =>
                $totalBytes,
        ];
    }


    private function dumpDatabase(
        string $resultFile
    ): array {

        $connection =
            DB::connection();

        $driver =
            strtolower(
                (string) $connection
                    ->getDriverName()
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
            throw new RuntimeException(
                'Full database backup 17G mendukung MySQL/MariaDB. Driver saat ini: '
                . $driver
            );
        }

        $config =
            $connection
                ->getConfig();

        $database =
            (string) (
                $config[
                    'database'
                ]
                ?? ''
            );

        if ($database === '') {
            throw new RuntimeException(
                'Nama database tidak tersedia.'
            );
        }

        $binary =
            $this->resolveMysqldumpBinary();

        $command = [
            $binary,
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--events',
            '--default-character-set=utf8mb4',
            '--host='
                . (string) (
                    $config[
                        'host'
                    ]
                    ?? '127.0.0.1'
                ),
            '--port='
                . (string) (
                    $config[
                        'port'
                    ]
                    ?? '3306'
                ),
            '--user='
                . (string) (
                    $config[
                        'username'
                    ]
                    ?? ''
                ),
            '--result-file='
                . $resultFile,
            $database,
        ];

        $password =
            (string) (
                $config[
                    'password'
                ]
                ?? ''
            );

        $environment = [];

        if ($password !== '') {
            $environment[
                'MYSQL_PWD'
            ] = $password;
        }

        $process =
            new Process(
                $command,
                null,
                $environment,
                null,
                (float) config(
                    'smartfind_backup.database_timeout_seconds',
                    300
                )
            );

        $process->run();

        if (! $process->isSuccessful()) {

            $error =
                trim(
                    $process->getErrorOutput()
                );

            throw new RuntimeException(
                'Database dump gagal.'
                . (
                    $error !== ''
                        ? ' '
                            . $error
                        : ''
                )
            );
        }

        if (
            ! is_file(
                $resultFile
            )
        ) {
            throw new RuntimeException(
                'Database dump tidak menghasilkan file.'
            );
        }

        $size =
            filesize(
                $resultFile
            );

        if (
            $size === false
            || $size <= 0
        ) {
            throw new RuntimeException(
                'Database dump kosong.'
            );
        }

        $hash =
            hash_file(
                'sha256',
                $resultFile
            );

        if (! is_string($hash)) {
            throw new RuntimeException(
                'Tidak dapat menghitung hash database dump.'
            );
        }

        return [
            'driver' =>
                $driver,

            'database' =>
                $database,

            'file' =>
                'database/bmc_smartfind.sql',

            'size' =>
                (int) $size,

            'sha256' =>
                $hash,
        ];
    }


    private function resolveMysqldumpBinary(): string
    {
        $configured =
            trim(
                (string) config(
                    'smartfind_backup.mysqldump_binary',
                    ''
                )
            );

        if ($configured !== '') {
            return $configured;
        }

        if (
            PHP_OS_FAMILY
                === 'Windows'
        ) {
            $xampp =
                'C:\\xampp\\mysql\\bin\\mysqldump.exe';

            if (is_file($xampp)) {
                return $xampp;
            }
        }

        return 'mysqldump';
    }


    private function privateStorageRoot(): string
    {
        $diskName =
            (string) config(
                'smartfind_backup.document_disk',
                'bmc_documents'
            );

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
            throw new RuntimeException(
                'Backup 17G memerlukan private document disk local / mapped drive.'
            );
        }

        try {

            $declaredRoot =
                Storage::disk(
                    $diskName
                )->path('');

        } catch (Throwable $exception) {

            throw new RuntimeException(
                'Private storage root tidak dapat diakses.',
                previous:
                    $exception
            );
        }

        $root =
            realpath(
                $declaredRoot
            );

        if (
            $root === false
            || ! is_dir(
                $root
            )
        ) {
            throw new RuntimeException(
                'Private storage root tidak tersedia: '
                . $declaredRoot
            );
        }

        return $root;
    }


    private function prepareBackupRoot(): string
    {
        $root =
            trim(
                (string) config(
                    'smartfind_backup.root',
                    ''
                )
            );

        if ($root === '') {
            throw new RuntimeException(
                'SMARTFIND_BACKUP_PATH belum dikonfigurasi.'
            );
        }

        if (! is_dir($root)) {
            $this->makeDirectory(
                $root
            );
        }

        $canonical =
            realpath(
                $root
            );

        if (
            $canonical === false
            || ! is_dir(
                $canonical
            )
        ) {
            throw new RuntimeException(
                'Backup root tidak dapat digunakan: '
                . $root
            );
        }

        return $canonical;
    }


    private function assertBackupRootSeparated(
        string $backupRoot,
        string $documentRoot
    ): void {

        $backup =
            $this->normalizeAbsolutePath(
                $backupRoot
            );

        $documents =
            $this->normalizeAbsolutePath(
                $documentRoot
            );

        if (
            $backup === $documents
            || str_starts_with(
                $backup . '/',
                $documents . '/'
            )
            || str_starts_with(
                $documents . '/',
                $backup . '/'
            )
        ) {
            throw new RuntimeException(
                'Backup root harus terpisah dari private document storage root.'
            );
        }
    }


    private function verifyManifestFile(
        string $absolutePath,
        int $expectedSize,
        string $expectedHash,
        string $label,
        array &$failures
    ): void {

        if (! is_file($absolutePath)) {

            $failures[] =
                'File backup hilang: '
                . $label;

            return;
        }

        $actualSize =
            filesize(
                $absolutePath
            );

        if (
            $actualSize === false
            || (int) $actualSize
                !== $expectedSize
        ) {
            $failures[] =
                'Ukuran file backup tidak cocok: '
                . $label;

            return;
        }

        $actualHash =
            hash_file(
                'sha256',
                $absolutePath
            );

        if (
            ! is_string(
                $actualHash
            )
            || $expectedHash === ''
            || ! hash_equals(
                $expectedHash,
                $actualHash
            )
        ) {
            $failures[] =
                'SHA-256 file backup tidak cocok: '
                . $label;
        }
    }


    private function relativeFromRoot(
        string $root,
        string $absolutePath
    ): string {

        $rootNormalized =
            rtrim(
                str_replace(
                    '\\',
                    '/',
                    $root
                ),
                '/'
            );

        $pathNormalized =
            str_replace(
                '\\',
                '/',
                $absolutePath
            );

        if (
            PHP_OS_FAMILY === 'Windows'
        ) {
            $compareRoot =
                mb_strtolower(
                    $rootNormalized
                );

            $comparePath =
                mb_strtolower(
                    $pathNormalized
                );
        } else {
            $compareRoot =
                $rootNormalized;

            $comparePath =
                $pathNormalized;
        }

        $prefix =
            $compareRoot
            . '/';

        if (
            ! str_starts_with(
                $comparePath,
                $prefix
            )
        ) {
            throw new RuntimeException(
                'Path scan keluar dari storage root.'
            );
        }

        return substr(
            $pathNormalized,
            strlen(
                $rootNormalized
            )
            + 1
        );
    }


    private function normalizeRelativePath(
        string $path
    ): string {

        return str_replace(
            '\\',
            '/',
            $path
        );
    }


    private function normalizeAbsolutePath(
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
            PHP_OS_FAMILY === 'Windows'
        ) {
            return mb_strtolower(
                $normalized
            );
        }

        return $normalized;
    }


    private function makeDirectory(
        string $directory
    ): void {

        if (is_dir($directory)) {
            return;
        }

        if (
            ! mkdir(
                $directory,
                0775,
                true
            )
            && ! is_dir(
                $directory
            )
        ) {
            throw new RuntimeException(
                'Gagal membuat directory: '
                . $directory
            );
        }
    }


    private function deleteDirectory(
        string $directory
    ): void {

        if (! is_dir($directory)) {
            return;
        }

        $iterator =
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $directory,
                    RecursiveDirectoryIterator::SKIP_DOTS
                ),
                RecursiveIteratorIterator::CHILD_FIRST
            );

        foreach (
            $iterator
            as $item
        ) {
            if (
                $item->isFile()
                || $item->isLink()
            ) {
                @unlink(
                    $item->getPathname()
                );
            } elseif ($item->isDir()) {
                @rmdir(
                    $item->getPathname()
                );
            }
        }

        @rmdir(
            $directory
        );
    }
}
