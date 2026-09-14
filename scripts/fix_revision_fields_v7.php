<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BMC QualityHub - Revision Fields Patch V7
|--------------------------------------------------------------------------
|
| V7 memakai pencarian BERDASARKAN ISI BARIS (trim), bukan jumlah spasi.
| Tujuan:
| - Tambah Nama File Revisi
| - Tambah Tanggal Revisi Dokumen
| - Simpan ke DocumentVersion, dokumen aktif, snapshot, dan audit
| - File fisik tetap UUID/private storage
| - Backup + rollback otomatis bila gagal
|
*/

$root = dirname(__DIR__);

$controllerPath =
    $root . DIRECTORY_SEPARATOR . 'app'
    . DIRECTORY_SEPARATOR . 'Http'
    . DIRECTORY_SEPARATOR . 'Controllers'
    . DIRECTORY_SEPARATOR . 'DocumentVersionController.php';

$bladePath =
    $root . DIRECTORY_SEPARATOR . 'resources'
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

$controllerText = str_replace(["\r\n", "\r"], "\n", $controllerOriginal);
$bladeText = str_replace(["\r\n", "\r"], "\n", $bladeOriginal);

$controller = explode("\n", $controllerText);
$blade = explode("\n", $bladeText);

$backupDir =
    $root . DIRECTORY_SEPARATOR . 'storage'
    . DIRECTORY_SEPARATOR . 'backups'
    . DIRECTORY_SEPARATOR . 'revision-form-before-v7-'
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

    throw new RuntimeException("Marker {$label} tidak ditemukan.");
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

function insertLines(array &$lines, int $index, array $newLines): void
{
    array_splice($lines, $index, 0, $newLines);
}

function replaceRange(
    array &$lines,
    int $start,
    int $length,
    array $newLines
): void {
    array_splice($lines, $start, $length, $newLines);
}

function hasTrim(array $lines, string $needle): bool
{
    foreach ($lines as $line) {
        if (trim($line) === $needle) {
            return true;
        }
    }

    return false;
}

echo PHP_EOL;
echo "=== BMC QualityHub Revision Fields V7 ===" . PHP_EOL;

try {
    /*
    |--------------------------------------------------------------------------
    | 1. VALIDATION
    |--------------------------------------------------------------------------
    */

    if (! hasTrim($controller, "'revision_file_name' => [")) {
        $validateStart = findContains(
            $controller,
            '$validated = $request->validate([',
            0,
            null,
            'request validation start'
        );

        $revisionNote = findTrim(
            $controller,
            "'revision_note' => [",
            $validateStart,
            min($validateStart + 80, count($controller) - 1),
            'revision_note validation'
        );

        $i = indentOf($controller[$revisionNote]);

        insertLines(
            $controller,
            $revisionNote,
            [
                $i . "'revision_file_name' => [",
                $i . "    'required',",
                $i . "    'string',",
                $i . "    'min:1',",
                $i . "    'max:180',",
                $i . "],",
                '',
                $i . "'document_date' => [",
                $i . "    'required',",
                $i . "    'date_format:Y-m-d',",
                $i . "],",
                '',
            ]
        );

        echo "[OK] Validation Nama File Revisi + Tanggal Revisi ditambahkan." . PHP_EOL;
    } else {
        echo "[OK] Validation sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 2. PREPARE DISPLAY NAME + DATE
    |--------------------------------------------------------------------------
    */

    if (! hasTrim($controller, '$revisionDisplayName =')) {
        $safeNameLine = findTrim(
            $controller,
            '$safeOriginalName =',
            0,
            null,
            'safeOriginalName'
        );

        $safeNameValue = findTrim(
            $controller,
            "\$security['original_name'];",
            $safeNameLine + 1,
            min($safeNameLine + 8, count($controller) - 1),
            'safeOriginalName value'
        );

        $i = indentOf($controller[$safeNameLine]);

        $block = [
            '',
            $i . '/*',
            $i . '|--------------------------------------------------------------------------',
            $i . '| Revision Display Filename + Revision Date',
            $i . '|--------------------------------------------------------------------------',
            $i . '| Nama tampilan dapat direvisi user. File fisik tetap UUID/private storage.',
            $i . '*/',
            '',
            $i . '$requestedRevisionName =',
            $i . '    trim(',
            $i . "        (string) \$validated['revision_file_name']",
            $i . '    );',
            '',
            $i . '$requestedRevisionName =',
            $i . '    pathinfo(',
            $i . '        basename(',
            $i . "            str_replace('\\\\', '/', \$requestedRevisionName)",
            $i . '        ),',
            $i . '        PATHINFO_FILENAME',
            $i . '    );',
            '',
            $i . '$safeRevisionBaseName =',
            $i . '    preg_replace(',
            $i . '        \'/[\\\\x00-\\\\x1F\\\\x7F\\\\\\\\\\/:*?"<>|]+/u\',',
            $i . "        '-',",
            $i . '        $requestedRevisionName',
            $i . "    ) ?? '';",
            '',
            $i . '$safeRevisionBaseName =',
            $i . '    trim(',
            $i . '        $safeRevisionBaseName,',
            $i . '        " .-_"',
            $i . '    );',
            '',
            $i . "if (\$safeRevisionBaseName === '') {",
            '',
            $i . '    return back()',
            $i . '        ->withInput()',
            $i . '        ->withErrors([',
            $i . "            'revision_file_name' =>",
            $i . "                'Nama file revisi tidak valid.',",
            $i . '        ]);',
            $i . '}',
            '',
            $i . '$revisionDisplayName =',
            $i . '    $safeRevisionBaseName',
            $i . "    . '.'",
            $i . '    . $extension;',
            '',
            $i . '$revisionDocumentDate =',
            $i . "    \$validated['document_date'];",
        ];

        insertLines(
            $controller,
            $safeNameValue + 1,
            $block
        );

        echo "[OK] Nama file tampilan + tanggal revisi dipersiapkan." . PHP_EOL;
    } else {
        echo "[OK] Preparation metadata sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 3. TRANSACTION USE LIST
    |--------------------------------------------------------------------------
    */

    if (! hasTrim($controller, '$revisionDisplayName,')) {
        $transactionStart = findContains(
            $controller,
            'function () use (',
            0,
            null,
            'transaction use'
        );

        $safeUse = findTrim(
            $controller,
            '$safeOriginalName,',
            $transactionStart,
            min($transactionStart + 40, count($controller) - 1),
            'safeOriginalName transaction'
        );

        $i = indentOf($controller[$safeUse]);

        insertLines(
            $controller,
            $safeUse + 1,
            [
                $i . '$revisionDisplayName,',
                $i . '$revisionDocumentDate,',
            ]
        );

        echo "[OK] Variable revisi diteruskan ke transaction." . PHP_EOL;
    } else {
        echo "[OK] Transaction use-list sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 4. METADATA SNAPSHOT DATE
    |--------------------------------------------------------------------------
    */

    $snapshotStart = findTrim(
        $controller,
        '$metadataSnapshot = [',
        0,
        null,
        'metadataSnapshot'
    );

    $snapshotEnd = findTrim(
        $controller,
        '];',
        $snapshotStart + 1,
        min($snapshotStart + 100, count($controller) - 1),
        'metadataSnapshot end'
    );

    $snapshotAlreadyUpdated = false;

    for ($n = $snapshotStart; $n <= $snapshotEnd; $n++) {
        if (trim($controller[$n]) === '$revisionDocumentDate,') {
            $snapshotAlreadyUpdated = true;
            break;
        }
    }

    if (! $snapshotAlreadyUpdated) {
        $dateKey = findTrim(
            $controller,
            "'document_date' =>",
            $snapshotStart,
            $snapshotEnd,
            'snapshot document_date key'
        );

        $descriptionKey = findTrim(
            $controller,
            "'description' =>",
            $dateKey + 1,
            $snapshotEnd,
            'snapshot description key'
        );

        $i = indentOf($controller[$dateKey]);

        replaceRange(
            $controller,
            $dateKey,
            $descriptionKey - $dateKey,
            [
                $i . "'document_date' =>",
                $i . '    $revisionDocumentDate,',
                '',
            ]
        );

        echo "[OK] Snapshot metadata memakai tanggal revisi." . PHP_EOL;
    } else {
        echo "[OK] Snapshot tanggal sudah benar." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 5. DOCUMENT VERSION FILE NAME
    |--------------------------------------------------------------------------
    */

    $versionStart = findContains(
        $controller,
        '$version = DocumentVersion::create([',
        0,
        null,
        'DocumentVersion create'
    );

    $versionEnd = findTrim(
        $controller,
        ']);',
        $versionStart + 1,
        min($versionStart + 100, count($controller) - 1),
        'DocumentVersion end'
    );

    $versionFileKey = findTrim(
        $controller,
        "'file_name' =>",
        $versionStart,
        $versionEnd,
        'DocumentVersion file_name'
    );

    if (trim($controller[$versionFileKey + 1]) !== '$revisionDisplayName,') {
        $valueIndent = indentOf($controller[$versionFileKey + 1]);
        $controller[$versionFileKey + 1] =
            $valueIndent . '$revisionDisplayName,';

        echo "[OK] DocumentVersion memakai nama file revisi." . PHP_EOL;
    } else {
        echo "[OK] DocumentVersion file_name sudah benar." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 6. UPDATE CURRENT DOCUMENT NAME + DATE
    |--------------------------------------------------------------------------
    */

    $updateStart = findContains(
        $controller,
        '$lockedDocument->update([',
        $versionEnd,
        null,
        'lockedDocument update'
    );

    $updateEnd = findTrim(
        $controller,
        ']);',
        $updateStart + 1,
        min($updateStart + 100, count($controller) - 1),
        'lockedDocument update end'
    );

    $updateFileKey = findTrim(
        $controller,
        "'file_name' =>",
        $updateStart,
        $updateEnd,
        'lockedDocument file_name'
    );

    $valueIndent = indentOf($controller[$updateFileKey + 1]);
    $controller[$updateFileKey + 1] =
        $valueIndent . '$revisionDisplayName,';

    $dateExists = false;

    for ($n = $updateStart; $n <= $updateEnd; $n++) {
        if (trim($controller[$n]) === "'document_date' =>") {
            $dateExists = true;
            break;
        }
    }

    if (! $dateExists) {
        $keyIndent = indentOf($controller[$updateFileKey]);

        insertLines(
            $controller,
            $updateFileKey + 2,
            [
                '',
                $keyIndent . "'document_date' =>",
                $valueIndent . '$revisionDocumentDate,',
            ]
        );
    }

    echo "[OK] Dokumen aktif memperbarui nama file + tanggal." . PHP_EOL;


    /*
    |--------------------------------------------------------------------------
    | 7. AUDIT METADATA
    |--------------------------------------------------------------------------
    */

    if (! hasTrim($controller, "'new_document_date' =>")) {
        $auditStart = findContains(
            $controller,
            'ActivityLog::create([',
            $updateStart,
            null,
            'ActivityLog create'
        );

        $newFileKey = findTrim(
            $controller,
            "'new_file_name' =>",
            $auditStart,
            min($auditStart + 140, count($controller) - 1),
            'audit new_file_name'
        );

        $keyIndent = indentOf($controller[$newFileKey]);
        $valueIndent = indentOf($controller[$newFileKey + 1]);

        insertLines(
            $controller,
            $newFileKey + 2,
            [
                '',
                $keyIndent . "'previous_document_date' =>",
                $valueIndent . '$document',
                $valueIndent . '    ->document_date',
                $valueIndent . "    ?->format('Y-m-d'),",
                '',
                $keyIndent . "'new_document_date' =>",
                $valueIndent . '$revisionDocumentDate,',
                '',
                $keyIndent . "'uploaded_original_name' =>",
                $valueIndent . '$safeOriginalName,',
            ]
        );

        echo "[OK] Audit nama/tanggal revisi ditambahkan." . PHP_EOL;
    } else {
        echo "[OK] Audit metadata sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 8. BLADE FORM
    |--------------------------------------------------------------------------
    */

    $bladeHasField = false;

    foreach ($blade as $line) {
        if (str_contains($line, 'name="revision_file_name"')) {
            $bladeHasField = true;
            break;
        }
    }

    if (! $bladeHasField) {
        $revisionComment = findContains(
            $blade,
            'REVISION NOTE',
            0,
            null,
            'Blade REVISION NOTE'
        );

        $i = indentOf($blade[$revisionComment]);

        $fields = [
            $i . '{{-- REVISION METADATA --}}',
            '',
            $i . '<div',
            $i . '    class="grid',
            $i . '           md:grid-cols-2',
            $i . '           gap-5"',
            $i . '>',
            '',
            $i . '    <div>',
            '',
            $i . '        <label',
            $i . '            for="revision_file_name"',
            $i . '            class="block',
            $i . '                   mb-2',
            $i . '                   text-sm',
            $i . '                   font-semibold',
            $i . '                   text-slate-700"',
            $i . '        >',
            $i . '            Nama File Revisi',
            $i . '            <span class="text-red-500">*</span>',
            $i . '        </label>',
            '',
            $i . '        <input',
            $i . '            id="revision_file_name"',
            $i . '            type="text"',
            $i . '            name="revision_file_name"',
            $i . '            required',
            $i . '            maxlength="180"',
            $i . '            value="{{ old(',
            $i . "                'revision_file_name',",
            $i . '                pathinfo(',
            $i . '                    $document->file_name,',
            $i . '                    PATHINFO_FILENAME',
            $i . '                )',
            $i . '            ) }}"',
            $i . '            placeholder="Contoh: laporan-hasil-produksi-axle-assy-rev-1"',
            $i . '            class="w-full',
            $i . '                   rounded-xl',
            $i . '                   border-slate-200',
            $i . '                   bg-slate-50',
            $i . '                   px-4',
            $i . '                   py-3"',
            $i . '        >',
            '',
            $i . '        <p class="mt-2 text-xs text-slate-400">',
            $i . '            Isi nama tanpa ekstensi. Ekstensi mengikuti file revisi.',
            $i . '        </p>',
            '',
            $i . '    </div>',
            '',
            $i . '    <div>',
            '',
            $i . '        <label',
            $i . '            for="document_date"',
            $i . '            class="block',
            $i . '                   mb-2',
            $i . '                   text-sm',
            $i . '                   font-semibold',
            $i . '                   text-slate-700"',
            $i . '        >',
            $i . '            Tanggal Revisi Dokumen',
            $i . '            <span class="text-red-500">*</span>',
            $i . '        </label>',
            '',
            $i . '        <input',
            $i . '            id="document_date"',
            $i . '            type="date"',
            $i . '            name="document_date"',
            $i . '            required',
            $i . '            value="{{ old(',
            $i . "                'document_date',",
            $i . '                $document->document_date',
            $i . "                    ?->format('Y-m-d')",
            $i . "                ?? now()->format('Y-m-d')",
            $i . '            ) }}"',
            $i . '            class="w-full',
            $i . '                   rounded-xl',
            $i . '                   border-slate-200',
            $i . '                   bg-slate-50',
            $i . '                   px-4',
            $i . '                   py-3"',
            $i . '        >',
            '',
            $i . '        <p class="mt-2 text-xs text-slate-400">',
            $i . '            Tanggal ini menjadi tanggal dokumen revisi terbaru.',
            $i . '        </p>',
            '',
            $i . '    </div>',
            '',
            $i . '</div>',
            '',
            '',
        ];

        insertLines(
            $blade,
            $revisionComment,
            $fields
        );

        echo "[OK] Field Nama File Revisi + Tanggal Revisi ditambahkan." . PHP_EOL;
    } else {
        echo "[OK] Field Blade sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | SAVE
    |--------------------------------------------------------------------------
    */

    $controllerResult = implode("\n", $controller);
    $bladeResult = implode("\n", $blade);

    if ($controllerEol === "\r\n") {
        $controllerResult = str_replace("\n", "\r\n", $controllerResult);
    }

    if ($bladeEol === "\r\n") {
        $bladeResult = str_replace("\n", "\r\n", $bladeResult);
    }

    file_put_contents($controllerPath, $controllerResult);
    file_put_contents($bladePath, $bladeResult);


    /*
    |--------------------------------------------------------------------------
    | AUTOMATIC PHP LINT
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
            . implode(PHP_EOL, $lint)
        );
    }

    echo "[OK] PHP lint otomatis PASS." . PHP_EOL;

    echo PHP_EOL;
    echo "Backup sebelum V7:" . PHP_EOL;
    echo $backupDir . PHP_EOL;
    echo PHP_EOL;
    echo "REVISION FIELDS V7: APPLIED" . PHP_EOL;
    echo PHP_EOL;
    echo "Lanjutkan:" . PHP_EOL;
    echo "php artisan optimize:clear" . PHP_EOL;
    echo "php artisan test" . PHP_EOL;

} catch (Throwable $exception) {

    file_put_contents($controllerPath, $controllerOriginal);
    file_put_contents($bladePath, $bladeOriginal);

    fwrite(
        STDERR,
        PHP_EOL
        . 'V7 GAGAL: '
        . $exception->getMessage()
        . PHP_EOL
    );

    fwrite(
        STDERR,
        'Controller dan Blade otomatis dikembalikan ke kondisi sebelum V7.'
        . PHP_EOL
    );

    exit(1);
}
