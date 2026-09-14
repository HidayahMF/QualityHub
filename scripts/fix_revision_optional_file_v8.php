<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BMC QualityHub - Optional File Revision Patch V8
|--------------------------------------------------------------------------
|
| Tujuan:
| - File Revisi menjadi opsional.
| - Nama file / tanggal dapat direvisi tanpa upload file baru.
| - Jika tidak ada file baru, file_path fisik lama dipakai ulang.
| - Tidak membuat duplicate physical file untuk metadata-only revision.
| - Revision number, history, snapshot, dan audit tetap berjalan.
| - Backup + rollback otomatis jika patch/lint gagal.
|
*/

$root = dirname(__DIR__);

$controllerPath =
    $root
    . DIRECTORY_SEPARATOR . 'app'
    . DIRECTORY_SEPARATOR . 'Http'
    . DIRECTORY_SEPARATOR . 'Controllers'
    . DIRECTORY_SEPARATOR . 'DocumentVersionController.php';

$bladePath =
    $root
    . DIRECTORY_SEPARATOR . 'resources'
    . DIRECTORY_SEPARATOR . 'views'
    . DIRECTORY_SEPARATOR . 'documents'
    . DIRECTORY_SEPARATOR . 'revisions'
    . DIRECTORY_SEPARATOR . 'create.blade.php';

foreach ([$controllerPath, $bladePath] as $path) {
    if (! is_file($path)) {
        fwrite(STDERR, "File tidak ditemukan: {$path}" . PHP_EOL);
        exit(1);
    }
}

$controllerOriginal = file_get_contents($controllerPath);
$bladeOriginal = file_get_contents($bladePath);

if ($controllerOriginal === false || $bladeOriginal === false) {
    fwrite(STDERR, "Gagal membaca file project." . PHP_EOL);
    exit(1);
}

$controllerEol = str_contains($controllerOriginal, "\r\n") ? "\r\n" : "\n";
$bladeEol = str_contains($bladeOriginal, "\r\n") ? "\r\n" : "\n";

$controller = explode(
    "\n",
    str_replace(["\r\n", "\r"], "\n", $controllerOriginal)
);

$blade = explode(
    "\n",
    str_replace(["\r\n", "\r"], "\n", $bladeOriginal)
);

$backupDir =
    $root
    . DIRECTORY_SEPARATOR . 'storage'
    . DIRECTORY_SEPARATOR . 'backups'
    . DIRECTORY_SEPARATOR . 'revision-optional-file-before-v8-'
    . date('Ymd-His');

if (
    ! is_dir($backupDir)
    && ! mkdir($backupDir, 0777, true)
    && ! is_dir($backupDir)
) {
    fwrite(STDERR, "Gagal membuat folder backup." . PHP_EOL);
    exit(1);
}

copy(
    $controllerPath,
    $backupDir . DIRECTORY_SEPARATOR . 'DocumentVersionController.php'
);

copy(
    $bladePath,
    $backupDir . DIRECTORY_SEPARATOR . 'create.blade.php'
);

function indentOf(string $line): string
{
    preg_match('/^[ \t]*/', $line, $match);

    return $match[0] ?? '';
}

function findLine(
    array $lines,
    callable $predicate,
    int $start = 0,
    ?int $end = null,
    string $label = 'marker'
): int {
    $end ??= count($lines) - 1;

    for ($i = $start; $i <= $end; $i++) {
        if ($predicate($lines[$i], $i)) {
            return $i;
        }
    }

    throw new RuntimeException(
        "Marker {$label} tidak ditemukan."
    );
}

function findTrim(
    array $lines,
    string $needle,
    int $start = 0,
    ?int $end = null,
    string $label = 'marker'
): int {
    return findLine(
        $lines,
        fn (string $line): bool => trim($line) === $needle,
        $start,
        $end,
        $label
    );
}

function findContains(
    array $lines,
    string $needle,
    int $start = 0,
    ?int $end = null,
    string $label = 'marker'
): int {
    return findLine(
        $lines,
        fn (string $line): bool => str_contains($line, $needle),
        $start,
        $end,
        $label
    );
}

function insertLines(
    array &$lines,
    int $index,
    array $newLines
): void {
    array_splice(
        $lines,
        $index,
        0,
        $newLines
    );
}

function replaceRange(
    array &$lines,
    int $start,
    int $length,
    array $newLines
): void {
    array_splice(
        $lines,
        $start,
        $length,
        $newLines
    );
}

function hasTrim(
    array $lines,
    string $needle
): bool {
    foreach ($lines as $line) {
        if (trim($line) === $needle) {
            return true;
        }
    }

    return false;
}

function restoreEol(
    string $text,
    string $eol
): string {
    if ($eol === "\r\n") {
        return str_replace("\n", "\r\n", $text);
    }

    return $text;
}

echo PHP_EOL;
echo "=== BMC QualityHub Optional File Revision V8 ===" . PHP_EOL;

try {
    /*
    |--------------------------------------------------------------------------
    | 1. FILE VALIDATION: required -> nullable
    |--------------------------------------------------------------------------
    */

    $validationStart = findContains(
        $controller,
        '$validated = $request->validate([',
        0,
        null,
        'request validation'
    );

    $fileRuleStart = findTrim(
        $controller,
        "'file' => [",
        $validationStart,
        min($validationStart + 40, count($controller) - 1),
        'file validation'
    );

    $fileRuleEnd = findTrim(
        $controller,
        '],',
        $fileRuleStart + 1,
        min($fileRuleStart + 15, count($controller) - 1),
        'file validation end'
    );

    $requiredRule = null;

    for ($i = $fileRuleStart + 1; $i < $fileRuleEnd; $i++) {
        if (trim($controller[$i]) === "'required',") {
            $requiredRule = $i;
            break;
        }
    }

    if ($requiredRule !== null) {
        $controller[$requiredRule] =
            indentOf($controller[$requiredRule])
            . "'nullable',";

        echo "[OK] File Revisi backend menjadi opsional." . PHP_EOL;
    } elseif (
        hasTrim(
            array_slice(
                $controller,
                $fileRuleStart,
                $fileRuleEnd - $fileRuleStart + 1
            ),
            "'nullable',"
        )
    ) {
        echo "[OK] File validation sudah nullable." . PHP_EOL;
    } else {
        throw new RuntimeException(
            "Rule required/nullable pada file tidak ditemukan."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 2. HAS NEW FILE + VALIDITY CHECK
    |--------------------------------------------------------------------------
    */

    $fileRequest = findTrim(
        $controller,
        "\$file = \$request->file('file');",
        $validationStart,
        min($validationStart + 100, count($controller) - 1),
        'request file'
    );

    if (! hasTrim($controller, '$hasNewFile = $file !== null;')) {
        $i = indentOf($controller[$fileRequest]);

        insertLines(
            $controller,
            $fileRequest + 1,
            [
                '',
                $i . '$hasNewFile =',
                $i . '    $file !== null;',
            ]
        );

        echo "[OK] Flag hasNewFile ditambahkan." . PHP_EOL;
    } else {
        echo "[OK] Flag hasNewFile sudah ada." . PHP_EOL;
    }

    $invalidIf = findContains(
        $controller,
        '$file === null',
        $fileRequest,
        min($fileRequest + 35, count($controller) - 1),
        'invalid file condition'
    );

    // Cari awal "if (" tepat sebelum kondisi.
    $invalidIfStart = $invalidIf;

    while (
        $invalidIfStart > $fileRequest
        && trim($controller[$invalidIfStart]) !== 'if ('
    ) {
        $invalidIfStart--;
    }

    if (trim($controller[$invalidIfStart]) !== 'if (') {
        throw new RuntimeException(
            'Awal kondisi validasi file tidak ditemukan.'
        );
    }

    // Cari penutup kondisi ") {" sebelum return back().
    $returnBack = findContains(
        $controller,
        'return back()',
        $invalidIfStart,
        min($invalidIfStart + 30, count($controller) - 1),
        'invalid file return'
    );

    $conditionEnd = $returnBack - 1;

    while (
        $conditionEnd > $invalidIfStart
        && trim($controller[$conditionEnd]) === ''
    ) {
        $conditionEnd--;
    }

    if (trim($controller[$conditionEnd]) !== ') {') {
        throw new RuntimeException(
            'Akhir kondisi validasi file tidak ditemukan.'
        );
    }

    $ifIndent = indentOf($controller[$invalidIfStart]);

    replaceRange(
        $controller,
        $invalidIfStart,
        $conditionEnd - $invalidIfStart + 1,
        [
            $ifIndent . 'if (',
            $ifIndent . '    $hasNewFile',
            $ifIndent . '    && ! $file->isValid()',
            $ifIndent . ') {',
        ]
    );

    echo "[OK] File kosong tidak lagi dianggap error." . PHP_EOL;


    /*
    |--------------------------------------------------------------------------
    | 3. SECURITY BLOCK: NEW FILE vs CURRENT FILE
    |--------------------------------------------------------------------------
    |
    | Detect semua key $security[...] yang dipakai controller.
    | Unknown key -> abort daripada menebak.
    |
    */

    $allControllerText = implode("\n", $controller);

    preg_match_all(
        '/\$security\[[\'"]([^\'"]+)[\'"]\]/',
        $allControllerText,
        $securityMatches
    );

    $securityKeys = array_values(
        array_unique(
            $securityMatches[1] ?? []
        )
    );

    $allowedSecurityKeys = [
        'extension',
        'original_name',
        'server_mime',
        'mime_type',
        'file_size',
        'size',
    ];

    $unknownSecurityKeys = array_values(
        array_diff(
            $securityKeys,
            $allowedSecurityKeys
        )
    );

    if ($unknownSecurityKeys !== []) {
        throw new RuntimeException(
            'Key $security yang belum dikenal: '
            . implode(', ', $unknownSecurityKeys)
        );
    }

    $securityStart = findTrim(
        $controller,
        '$security =',
        $fileRequest,
        min($fileRequest + 80, count($controller) - 1),
        'security assignment'
    );

    $revisionComment = findContains(
        $controller,
        'Revision Display Filename + Revision Date',
        $securityStart,
        min($securityStart + 80, count($controller) - 1),
        'revision display comment'
    );

    // Cari awal comment /* dari header Revision Display.
    $revisionCommentStart = $revisionComment;

    while (
        $revisionCommentStart > $securityStart
        && trim($controller[$revisionCommentStart]) !== '/*'
    ) {
        $revisionCommentStart--;
    }

    if (trim($controller[$revisionCommentStart]) !== '/*') {
        throw new RuntimeException(
            'Awal comment Revision Display tidak ditemukan.'
        );
    }

    // Ambil security block yang sudah ada.
    $existingSecurityBlock = array_slice(
        $controller,
        $securityStart,
        $revisionCommentStart - $securityStart
    );

    $baseIndent = indentOf($controller[$securityStart]);
    $nestedSecurityBlock = [];

    foreach ($existingSecurityBlock as $line) {
        if (trim($line) === '') {
            $nestedSecurityBlock[] = '';
        } else {
            $nestedSecurityBlock[] =
                $baseIndent . '    '
                . substr(
                    $line,
                    strlen($baseIndent)
                );
        }
    }

    $fallback = [
        $baseIndent . '} else {',
        '',
        $baseIndent . '    $security = [',
    ];

    foreach ($securityKeys as $key) {
        $expression = match ($key) {
            'extension' =>
                '$document->file_extension',

            'original_name' =>
                '$document->file_name',

            'server_mime', 'mime_type' =>
                '$document->mime_type',

            'file_size', 'size' =>
                '$document->file_size',

            default =>
                throw new RuntimeException(
                    "Fallback security key {$key} belum didukung."
                ),
        };

        $fallback[] =
            $baseIndent . "        '{$key}' =>";
        $fallback[] =
            $baseIndent . "            {$expression},";
    }

    $fallback[] =
        $baseIndent . '    ];';
    $fallback[] =
        $baseIndent . '}';

    $wrappedSecurity = array_merge(
        [
            $baseIndent . 'if ($hasNewFile) {',
        ],
        $nestedSecurityBlock,
        $fallback,
        ['']
    );

    replaceRange(
        $controller,
        $securityStart,
        $revisionCommentStart - $securityStart,
        $wrappedSecurity
    );

    echo "[OK] Security inspection hanya jalan jika ada file baru." . PHP_EOL;


    /*
    |--------------------------------------------------------------------------
    | 4. PREVENT NO-OP REVISION
    |--------------------------------------------------------------------------
    */

    if (! hasTrim($controller, '$hasMetadataChange =')) {
        $revisionDateLine = findTrim(
            $controller,
            "\$revisionDocumentDate =",
            $revisionCommentStart,
            min($revisionCommentStart + 120, count($controller) - 1),
            'revision document date'
        );

        $revisionDateValueEnd = findLine(
            $controller,
            fn (string $line): bool =>
                str_ends_with(trim($line), ';'),
            $revisionDateLine + 1,
            min($revisionDateLine + 8, count($controller) - 1),
            'revision document date value'
        );

        $i = indentOf($controller[$revisionDateLine]);

        insertLines(
            $controller,
            $revisionDateValueEnd + 1,
            [
                '',
                $i . '$hasMetadataChange =',
                $i . '    $revisionDisplayName !== $document->file_name',
                $i . '    || $revisionDocumentDate',
                $i . '        !== $document',
                $i . '            ->document_date',
                $i . "            ?->format('Y-m-d');",
                '',
                $i . 'if (! $hasNewFile && ! $hasMetadataChange) {',
                '',
                $i . '    return back()',
                $i . '        ->withInput()',
                $i . '        ->withErrors([',
                $i . "            'file' =>",
                $i . "                'Pilih file revisi baru atau ubah nama/tanggal dokumen.',",
                $i . '        ]);',
                $i . '}',
            ]
        );

        echo "[OK] Revisi kosong tanpa perubahan akan ditolak." . PHP_EOL;
    } else {
        echo "[OK] Guard no-op revision sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 5. TRANSACTION USE LIST: add hasNewFile
    |--------------------------------------------------------------------------
    */

    $transactionStart = findContains(
        $controller,
        'function () use (',
        0,
        null,
        'transaction use'
    );

    $transactionEnd = findLine(
        $controller,
        fn (string $line): bool =>
            str_contains($line, ') {'),
        $transactionStart,
        min($transactionStart + 80, count($controller) - 1),
        'transaction use end'
    );

    $hasNewFileInUse = false;

    for ($i = $transactionStart; $i <= $transactionEnd; $i++) {
        if (trim($controller[$i]) === '$hasNewFile,') {
            $hasNewFileInUse = true;
            break;
        }
    }

    if (! $hasNewFileInUse) {
        $fileUse = findTrim(
            $controller,
            '$file,',
            $transactionStart,
            $transactionEnd,
            'file transaction use'
        );

        $useIndent = indentOf($controller[$fileUse]);

        insertLines(
            $controller,
            $fileUse + 1,
            [
                $useIndent . '$hasNewFile,',
            ]
        );

        echo "[OK] hasNewFile diteruskan ke transaction." . PHP_EOL;
    } else {
        echo "[OK] hasNewFile sudah ada di transaction." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 6. PHYSICAL STORAGE: only when hasNewFile
    |--------------------------------------------------------------------------
    */

    if (! hasTrim($controller, 'if ($hasNewFile) {')) {
        throw new RuntimeException(
            'Flag hasNewFile tidak tersedia untuk storage patch.'
        );
    }

    $storedFileName = findTrim(
        $controller,
        '$storedFileName =',
        $transactionStart,
        null,
        'storedFileName'
    );

    $metadataSnapshot = findTrim(
        $controller,
        '$metadataSnapshot = [',
        $storedFileName,
        null,
        'metadataSnapshot'
    );

    // Cari awal comment Metadata Snapshot agar block storage seluruhnya terbungkus.
    $metadataCommentLine = findContains(
        $controller,
        'Metadata Snapshot',
        max($storedFileName, $metadataSnapshot - 15),
        $metadataSnapshot,
        'Metadata Snapshot comment'
    );

    $metadataCommentStart = $metadataCommentLine;

    while (
        $metadataCommentStart > $storedFileName
        && trim($controller[$metadataCommentStart]) !== '/*'
    ) {
        $metadataCommentStart--;
    }

    if (trim($controller[$metadataCommentStart]) !== '/*') {
        throw new RuntimeException(
            'Awal comment Metadata Snapshot tidak ditemukan.'
        );
    }

    // Hindari double wrap jika script pernah dijalankan.
    $alreadyWrapped = false;

    for (
        $i = max($transactionStart, $storedFileName - 12);
        $i < $storedFileName;
        $i++
    ) {
        if (trim($controller[$i]) === 'if ($hasNewFile) {') {
            $alreadyWrapped = true;
            break;
        }
    }

    if (! $alreadyWrapped) {
        $storageIndent = indentOf($controller[$storedFileName]);

        $existingStorageBlock = array_slice(
            $controller,
            $storedFileName,
            $metadataCommentStart - $storedFileName
        );

        $nestedStorageBlock = [];

        foreach ($existingStorageBlock as $line) {
            if (trim($line) === '') {
                $nestedStorageBlock[] = '';
            } else {
                $nestedStorageBlock[] =
                    $storageIndent . '    '
                    . substr(
                        $line,
                        strlen($storageIndent)
                    );
            }
        }

        $wrappedStorage = array_merge(
            [
                $storageIndent . 'if ($hasNewFile) {',
            ],
            $nestedStorageBlock,
            [
                $storageIndent . '} else {',
                '',
                $storageIndent . '    $storedPath =',
                $storageIndent . '        $lockedDocument->file_path;',
                '',
                $storageIndent . '    if (',
                $storageIndent . '        $privateStorage->resolveExistingFile(',
                $storageIndent . '            $storedPath',
                $storageIndent . '        ) === null',
                $storageIndent . '    ) {',
                $storageIndent . '        throw new \RuntimeException(',
                $storageIndent . "            'File aktif tidak ditemukan di private storage.'",
                $storageIndent . '        );',
                $storageIndent . '    }',
                $storageIndent . '}',
                '',
            ]
        );

        replaceRange(
            $controller,
            $storedFileName,
            $metadataCommentStart - $storedFileName,
            $wrappedStorage
        );

        echo "[OK] Metadata-only revision memakai file fisik lama tanpa duplicate." . PHP_EOL;
    } else {
        echo "[OK] Storage optional-file sudah terpasang." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 7. AUDIT: file_replaced
    |--------------------------------------------------------------------------
    */

    if (! hasTrim($controller, "'file_replaced' =>")) {
        $activityStart = findContains(
            $controller,
            'ActivityLog::create([',
            $metadataSnapshot,
            null,
            'ActivityLog create'
        );

        $newFileNameKey = findTrim(
            $controller,
            "'new_file_name' =>",
            $activityStart,
            min($activityStart + 180, count($controller) - 1),
            'audit new_file_name'
        );

        $auditKeyIndent = indentOf($controller[$newFileNameKey]);

        // Cari akhir value new_file_name.
        $newFileNameValue = $newFileNameKey + 1;

        insertLines(
            $controller,
            $newFileNameValue + 1,
            [
                '',
                $auditKeyIndent . "'file_replaced' =>",
                $auditKeyIndent . '    $hasNewFile,',
            ]
        );

        echo "[OK] Audit mencatat apakah file fisik diganti." . PHP_EOL;
    } else {
        echo "[OK] Audit file_replaced sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 8. BLADE: File Revisi (Opsional)
    |--------------------------------------------------------------------------
    */

    $fileLabel = findTrim(
        $blade,
        'File Revisi',
        0,
        null,
        'Blade File Revisi label'
    );

    $labelEnd = findTrim(
        $blade,
        '</label>',
        $fileLabel,
        min($fileLabel + 15, count($blade) - 1),
        'Blade file label end'
    );

    // Ubah label.
    $blade[$fileLabel] =
        indentOf($blade[$fileLabel])
        . 'File Revisi (Opsional)';

    // Hapus span bintang wajib di label File Revisi saja.
    $starStart = null;

    for ($i = $fileLabel + 1; $i < $labelEnd; $i++) {
        if (str_contains($blade[$i], 'text-red-500')) {
            $starStart = $i;
            break;
        }
    }

    if ($starStart !== null) {
        $starEnd = findTrim(
            $blade,
            '</span>',
            $starStart,
            $labelEnd,
            'Blade file required star end'
        );

        replaceRange(
            $blade,
            $starStart,
            $starEnd - $starStart + 1,
            []
        );
    }

    $fileInput = findTrim(
        $blade,
        'name="file"',
        $labelEnd,
        min($labelEnd + 50, count($blade) - 1),
        'Blade file input'
    );

    // Hapus required khusus input file.
    $inputEnd = findLine(
        $blade,
        fn (string $line): bool => trim($line) === '>',
        $fileInput,
        min($fileInput + 30, count($blade) - 1),
        'Blade file input end'
    );

    for ($i = $fileInput + 1; $i < $inputEnd; $i++) {
        if (trim($blade[$i]) === 'required') {
            replaceRange(
                $blade,
                $i,
                1,
                []
            );

            $inputEnd--;
            break;
        }
    }

    // Ubah helper text setelah input file.
    $helperText = findContains(
        $blade,
        'PDF, Excel, Word, CSV',
        $inputEnd,
        min($inputEnd + 35, count($blade) - 1),
        'Blade file helper'
    );

    $helperIndent = indentOf($blade[$helperText]);

    $blade[$helperText] =
        $helperIndent
        . 'Opsional. Kosongkan jika hanya mengubah nama file atau tanggal dokumen.';

    insertLines(
        $blade,
        $helperText + 1,
        [
            $helperIndent
            . 'PDF, Excel, Word, CSV, atau TXT · Maksimal 20 MB',
        ]
    );

    echo "[OK] Form menampilkan File Revisi sebagai opsional." . PHP_EOL;


    /*
    |--------------------------------------------------------------------------
    | 9. SAVE
    |--------------------------------------------------------------------------
    */

    $controllerResult = implode("\n", $controller);
    $bladeResult = implode("\n", $blade);

    file_put_contents(
        $controllerPath,
        restoreEol(
            $controllerResult,
            $controllerEol
        )
    );

    file_put_contents(
        $bladePath,
        restoreEol(
            $bladeResult,
            $bladeEol
        )
    );


    /*
    |--------------------------------------------------------------------------
    | 10. PHP LINT
    |--------------------------------------------------------------------------
    */

    $lint = [];
    $lintExit = 1;

    exec(
        escapeshellarg(PHP_BINARY)
        . ' -l '
        . escapeshellarg($controllerPath)
        . ' 2>&1',
        $lint,
        $lintExit
    );

    if ($lintExit !== 0) {
        throw new RuntimeException(
            "PHP lint gagal:\n"
            . implode(
                PHP_EOL,
                $lint
            )
        );
    }

    echo "[OK] PHP lint otomatis PASS." . PHP_EOL;


    /*
    |--------------------------------------------------------------------------
    | FINAL
    |--------------------------------------------------------------------------
    */

    echo PHP_EOL;
    echo "Backup sebelum V8:" . PHP_EOL;
    echo $backupDir . PHP_EOL;
    echo PHP_EOL;
    echo "OPTIONAL FILE REVISION V8: APPLIED" . PHP_EOL;
    echo PHP_EOL;
    echo "Lanjutkan:" . PHP_EOL;
    echo "php artisan optimize:clear" . PHP_EOL;
    echo "php artisan test" . PHP_EOL;

} catch (Throwable $exception) {

    file_put_contents(
        $controllerPath,
        $controllerOriginal
    );

    file_put_contents(
        $bladePath,
        $bladeOriginal
    );

    fwrite(
        STDERR,
        PHP_EOL
        . 'V8 GAGAL: '
        . $exception->getMessage()
        . PHP_EOL
    );

    fwrite(
        STDERR,
        'Controller dan Blade otomatis dikembalikan ke kondisi sebelum V8.'
        . PHP_EOL
    );

    exit(1);
}
