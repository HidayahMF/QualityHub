<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BMC QualityHub - Revision Name + Revision Date Patch V3
|--------------------------------------------------------------------------
|
| V3 dibuat lebih fleksibel:
| - Tidak bergantung pada jumlah spasi/indentasi.
| - Aman untuk CRLF Windows maupun LF.
| - Membuat backup sebelum mengubah file.
| - Rollback otomatis jika satu tahap gagal.
| - Backend tetap backward-compatible jika request lama tidak membawa
|   revision_file_name/document_date.
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

$controller = str_replace(["\r\n", "\r"], "\n", $controllerOriginal);
$blade = str_replace(["\r\n", "\r"], "\n", $bladeOriginal);

$stamp = date('Ymd-His');

$backupDir =
    $root
    . DIRECTORY_SEPARATOR . 'storage'
    . DIRECTORY_SEPARATOR . 'backups'
    . DIRECTORY_SEPARATOR . 'revision-form-before-v3-'
    . $stamp;

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

function replaceRegexOnce(
    string $subject,
    string $pattern,
    string $replacement,
    string $label
): string {
    $count = 0;

    $result = preg_replace(
        $pattern,
        $replacement,
        $subject,
        1,
        $count
    );

    if ($result === null) {
        throw new RuntimeException(
            "Regex error saat patch: {$label}"
        );
    }

    if ($count !== 1) {
        throw new RuntimeException(
            "Marker {$label} tidak ditemukan atau tidak unik."
        );
    }

    return $result;
}

echo PHP_EOL;
echo "=== BMC QualityHub Revision Metadata Upgrade V3 ===" . PHP_EOL;

try {
    /*
    |--------------------------------------------------------------------------
    | 1. Validation
    |--------------------------------------------------------------------------
    |
    | nullable di backend untuk backward compatibility.
    | Form Blade tetap required.
    |
    */

    if (! str_contains($controller, "'revision_file_name' =>")) {
        $pattern =
            '/(?P<indent>^[ \t]*)\'revision_note\'\s*=>\s*\[\s*\n'
            . '(?P<body>.*?^[ \t]*\],)/ms';

        $replacement =
            '${indent}\'revision_file_name\' => [' . "\n"
            . '${indent}    \'nullable\',' . "\n"
            . '${indent}    \'string\',' . "\n"
            . '${indent}    \'max:180\',' . "\n"
            . '${indent}],' . "\n\n"
            . '${indent}\'document_date\' => [' . "\n"
            . '${indent}    \'nullable\',' . "\n"
            . '${indent}    \'date_format:Y-m-d\',' . "\n"
            . '${indent}],' . "\n\n"
            . '${indent}\'revision_note\' => [' . "\n"
            . '${body}';

        $controller = replaceRegexOnce(
            $controller,
            $pattern,
            $replacement,
            'validation revision_note'
        );

        echo "[OK] Validation nama file + tanggal revisi ditambahkan." . PHP_EOL;
    } else {
        echo "[OK] Validation sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 2. Prepare display filename + revision document date
    |--------------------------------------------------------------------------
    */

    if (! str_contains($controller, '$revisionDisplayName =')) {
        $pattern =
            '/(?P<indent>^[ \t]*)\$safeOriginalName\s*=\s*'
            . '\$security\[\'original_name\'\];/m';

        $replacement =
            '${indent}$safeOriginalName =' . "\n"
            . '${indent}    $security[\'original_name\'];' . "\n\n"
            . '${indent}/*' . "\n"
            . '${indent}|--------------------------------------------------------------------------' . "\n"
            . '${indent}| Revision Display Filename + Revision Document Date' . "\n"
            . '${indent}|--------------------------------------------------------------------------' . "\n"
            . '${indent}| Nama tampilan boleh direvisi user, file fisik tetap UUID.' . "\n"
            . '${indent}*/' . "\n\n"
            . '${indent}$requestedRevisionName =' . "\n"
            . '${indent}    trim(' . "\n"
            . '${indent}        (string) (' . "\n"
            . '${indent}            $validated[\'revision_file_name\']' . "\n"
            . '${indent}            ?? pathinfo(' . "\n"
            . '${indent}                $safeOriginalName,' . "\n"
            . '${indent}                PATHINFO_FILENAME' . "\n"
            . '${indent}            )' . "\n"
            . '${indent}        )' . "\n"
            . '${indent}    );' . "\n\n"
            . '${indent}$requestedRevisionName =' . "\n"
            . '${indent}    pathinfo(' . "\n"
            . '${indent}        $requestedRevisionName,' . "\n"
            . '${indent}        PATHINFO_FILENAME' . "\n"
            . '${indent}    );' . "\n\n"
            . '${indent}$safeRevisionBaseName =' . "\n"
            . '${indent}    Str::slug(' . "\n"
            . '${indent}        $requestedRevisionName,' . "\n"
            . '${indent}        \'-\'' . "\n"
            . '${indent}    );' . "\n\n"
            . '${indent}if ($safeRevisionBaseName === \'\') {' . "\n"
            . '${indent}    return back()' . "\n"
            . '${indent}        ->withInput()' . "\n"
            . '${indent}        ->withErrors([' . "\n"
            . '${indent}            \'revision_file_name\' =>' . "\n"
            . '${indent}                \'Nama file revisi tidak valid.\',' . "\n"
            . '${indent}        ]);' . "\n"
            . '${indent}}' . "\n\n"
            . '${indent}$revisionDisplayName =' . "\n"
            . '${indent}    $safeRevisionBaseName' . "\n"
            . '${indent}    . \'.\'' . "\n"
            . '${indent}    . $extension;' . "\n\n"
            . '${indent}$revisionDocumentDate =' . "\n"
            . '${indent}    $validated[\'document_date\']' . "\n"
            . '${indent}    ?? $document' . "\n"
            . '${indent}        ->document_date' . "\n"
            . '${indent}        ?->format(\'Y-m-d\')' . "\n"
            . '${indent}    ?? now()->toDateString();';

        $controller = replaceRegexOnce(
            $controller,
            $pattern,
            $replacement,
            'safeOriginalName'
        );

        echo "[OK] Nama file revisi + tanggal revisi dipersiapkan." . PHP_EOL;
    } else {
        echo "[OK] Revision metadata preparation sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 3. Pass variables into transaction closure
    |--------------------------------------------------------------------------
    */

    if (! preg_match(
        '/function\s*\(\)\s*use\s*\([^)]*\$revisionDisplayName/s',
        $controller
    )) {
        $pattern =
            '/(\$safeOriginalName,\s*\n)(?P<indent>[ \t]*)\$serverMime,/m';

        $replacement =
            '$1'
            . '${indent}$revisionDisplayName,' . "\n"
            . '${indent}$revisionDocumentDate,' . "\n"
            . '${indent}$serverMime,';

        $controller = replaceRegexOnce(
            $controller,
            $pattern,
            $replacement,
            'transaction use-list'
        );

        echo "[OK] Metadata revisi diteruskan ke transaction." . PHP_EOL;
    } else {
        echo "[OK] Transaction use-list sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 4. Metadata snapshot document_date
    |--------------------------------------------------------------------------
    */

    if (! preg_match(
        '/\'document_date\'\s*=>\s*\$revisionDocumentDate/',
        $controller
    )) {
        $pattern =
            '/\'document_date\'\s*=>\s*'
            . '\$lockedDocument\s*->document_date\s*\?->format\(\'Y-m-d\'\),/s';

        $controller = replaceRegexOnce(
            $controller,
            $pattern,
            "'document_date' =>\n                            \$revisionDocumentDate,",
            'metadata snapshot document_date'
        );

        echo "[OK] Snapshot metadata memakai tanggal revisi." . PHP_EOL;
    } else {
        echo "[OK] Snapshot tanggal revisi sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 5. DocumentVersion file_name
    |--------------------------------------------------------------------------
    */

    if (! preg_match(
        '/DocumentVersion::create\(\[.*?\'file_name\'\s*=>\s*\$revisionDisplayName/s',
        $controller
    )) {
        $pattern =
            '/(DocumentVersion::create\(\[.*?'
            . '\'file_name\'\s*=>\s*)\$safeOriginalName,/s';

        $controller = replaceRegexOnce(
            $controller,
            $pattern,
            '$1$revisionDisplayName,',
            'DocumentVersion file_name'
        );

        echo "[OK] DocumentVersion memakai nama file revisi." . PHP_EOL;
    } else {
        echo "[OK] DocumentVersion file_name sudah benar." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 6. Update active document filename + document date
    |--------------------------------------------------------------------------
    */

    if (! preg_match(
        '/\$lockedDocument->update\(\[.*?'
        . '\'file_name\'\s*=>\s*\$revisionDisplayName.*?'
        . '\'document_date\'\s*=>\s*\$revisionDocumentDate/s',
        $controller
    )) {
        $pattern =
            '/(\$lockedDocument->update\(\[\s*'
            . '\'current_revision\'\s*=>\s*\$nextRevision,\s*'
            . '\'file_name\'\s*=>\s*)\$safeOriginalName,(\s*'
            . '\'file_path\'\s*=>)/s';

        $replacement =
            '$1$revisionDisplayName,' . "\n\n"
            . "                        'document_date' =>\n"
            . '                            $revisionDocumentDate,'
            . '$2';

        $controller = replaceRegexOnce(
            $controller,
            $pattern,
            $replacement,
            'lockedDocument update'
        );

        echo "[OK] Dokumen aktif memperbarui nama file + tanggal." . PHP_EOL;
    } else {
        echo "[OK] Update dokumen aktif sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 7. Audit metadata
    |--------------------------------------------------------------------------
    */

    if (! str_contains($controller, "'new_document_date' =>")) {
        $pattern =
            '/(?P<indent>^[ \t]*)\'new_file_name\'\s*=>\s*'
            . '\$version->file_name,/m';

        $replacement =
            '${indent}\'new_file_name\' =>' . "\n"
            . '${indent}    $version->file_name,' . "\n\n"
            . '${indent}\'previous_document_date\' =>' . "\n"
            . '${indent}    $document' . "\n"
            . '${indent}        ->document_date' . "\n"
            . '${indent}        ?->format(\'Y-m-d\'),' . "\n\n"
            . '${indent}\'new_document_date\' =>' . "\n"
            . '${indent}    $revisionDocumentDate,' . "\n\n"
            . '${indent}\'uploaded_original_name\' =>' . "\n"
            . '${indent}    $safeOriginalName,';

        $controller = replaceRegexOnce(
            $controller,
            $pattern,
            $replacement,
            'activity metadata new_file_name'
        );

        echo "[OK] Audit log nama/tanggal revisi ditambahkan." . PHP_EOL;
    } else {
        echo "[OK] Audit metadata sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 8. Blade form fields
    |--------------------------------------------------------------------------
    */

    if (! str_contains($blade, 'name="revision_file_name"')) {
        $pattern =
            '/(?P<indent>^[ \t]*)\{\{--\s*REVISION NOTE\s*--\}\}/m';

        $replacement = <<<'BLADE'
${indent}{{-- REVISION METADATA --}}

${indent}<div
${indent}    class="grid
${indent}           md:grid-cols-2
${indent}           gap-5"
${indent}>

${indent}    <div>
${indent}        <label
${indent}            for="revision_file_name"
${indent}            class="block
${indent}                   mb-2
${indent}                   text-sm
${indent}                   font-semibold
${indent}                   text-slate-700"
${indent}        >
${indent}            Nama File Revisi
${indent}            <span class="text-red-500">*</span>
${indent}        </label>

${indent}        <input
${indent}            id="revision_file_name"
${indent}            type="text"
${indent}            name="revision_file_name"
${indent}            required
${indent}            maxlength="180"
${indent}            value="{{ old(
${indent}                'revision_file_name',
${indent}                pathinfo(
${indent}                    $document->file_name,
${indent}                    PATHINFO_FILENAME
${indent}                )
${indent}            ) }}"
${indent}            placeholder="Contoh: laporan-hasil-produksi-axle-assy-rev-1"
${indent}            class="w-full
${indent}                   rounded-xl
${indent}                   border-slate-200
${indent}                   bg-slate-50
${indent}                   px-4
${indent}                   py-3"
${indent}        >

${indent}        <p
${indent}            class="mt-2
${indent}                   text-xs
${indent}                   text-slate-400"
${indent}        >
${indent}            Isi nama tanpa ekstensi. Ekstensi mengikuti file revisi yang dipilih.
${indent}        </p>
${indent}    </div>


${indent}    <div>
${indent}        <label
${indent}            for="document_date"
${indent}            class="block
${indent}                   mb-2
${indent}                   text-sm
${indent}                   font-semibold
${indent}                   text-slate-700"
${indent}        >
${indent}            Tanggal Revisi Dokumen
${indent}            <span class="text-red-500">*</span>
${indent}        </label>

${indent}        <input
${indent}            id="document_date"
${indent}            type="date"
${indent}            name="document_date"
${indent}            required
${indent}            value="{{ old(
${indent}                'document_date',
${indent}                $document->document_date
${indent}                    ?->format('Y-m-d')
${indent}                ?? now()->format('Y-m-d')
${indent}            ) }}"
${indent}            class="w-full
${indent}                   rounded-xl
${indent}                   border-slate-200
${indent}                   bg-slate-50
${indent}                   px-4
${indent}                   py-3"
${indent}        >

${indent}        <p
${indent}            class="mt-2
${indent}                   text-xs
${indent}                   text-slate-400"
${indent}        >
${indent}            Tanggal ini menjadi tanggal dokumen untuk revisi terbaru.
${indent}        </p>
${indent}    </div>

${indent}</div>


${indent}{{-- REVISION NOTE --}}
BLADE;

        $blade = replaceRegexOnce(
            $blade,
            $pattern,
            $replacement,
            'Blade REVISION NOTE'
        );

        echo "[OK] Field Nama File Revisi + Tanggal Revisi ditambahkan." . PHP_EOL;
    } else {
        echo "[OK] Field Blade sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | Restore original line endings
    |--------------------------------------------------------------------------
    */

    if ($controllerEol === "\r\n") {
        $controller = str_replace("\n", "\r\n", $controller);
    }

    if ($bladeEol === "\r\n") {
        $blade = str_replace("\n", "\r\n", $blade);
    }

    file_put_contents($controllerPath, $controller);
    file_put_contents($bladePath, $blade);

    echo PHP_EOL;
    echo "Backup sebelum patch:" . PHP_EOL;
    echo $backupDir . PHP_EOL;
    echo PHP_EOL;
    echo "REVISION FORM UPGRADE V3: APPLIED" . PHP_EOL;
    echo PHP_EOL;
    echo "Next command:" . PHP_EOL;
    echo "php -l .\\app\\Http\\Controllers\\DocumentVersionController.php" . PHP_EOL;
    echo "php artisan optimize:clear" . PHP_EOL;
    echo "php artisan test" . PHP_EOL;

} catch (Throwable $exception) {
    file_put_contents($controllerPath, $controllerOriginal);
    file_put_contents($bladePath, $bladeOriginal);

    fwrite(STDERR, PHP_EOL);
    fwrite(
        STDERR,
        'PATCH V3 GAGAL: '
        . $exception->getMessage()
        . PHP_EOL
    );
    fwrite(
        STDERR,
        'Controller dan Blade dikembalikan ke kondisi awal.'
        . PHP_EOL
    );

    exit(1);
}
