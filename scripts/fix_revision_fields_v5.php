<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BMC QualityHub - Revision Name + Date Patch V5
|--------------------------------------------------------------------------
|
| V5 sengaja TIDAK memakai regex kompleks untuk struktur utama.
| Patch dilakukan berdasarkan token yang benar-benar ada di controller.
| Jika syntax check gagal, file otomatis dikembalikan.
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

$controllerEol =
    str_contains($controllerOriginal, "\r\n")
        ? "\r\n"
        : "\n";

$bladeEol =
    str_contains($bladeOriginal, "\r\n")
        ? "\r\n"
        : "\n";

$controller =
    str_replace(
        ["\r\n", "\r"],
        "\n",
        $controllerOriginal
    );

$blade =
    str_replace(
        ["\r\n", "\r"],
        "\n",
        $bladeOriginal
    );

$stamp = date('Ymd-His');

$backupDir =
    $root
    . DIRECTORY_SEPARATOR . 'storage'
    . DIRECTORY_SEPARATOR . 'backups'
    . DIRECTORY_SEPARATOR . 'revision-form-before-v5-'
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

function insertBeforeFirst(
    string $text,
    string $needle,
    string $insert,
    string $label
): string {
    $pos = strpos($text, $needle);

    if ($pos === false) {
        throw new RuntimeException(
            "Marker {$label} tidak ditemukan."
        );
    }

    return substr($text, 0, $pos)
        . $insert
        . substr($text, $pos);
}

function insertAfterFirst(
    string $text,
    string $needle,
    string $insert,
    string $label
): string {
    $pos = strpos($text, $needle);

    if ($pos === false) {
        throw new RuntimeException(
            "Marker {$label} tidak ditemukan."
        );
    }

    $pos += strlen($needle);

    return substr($text, 0, $pos)
        . $insert
        . substr($text, $pos);
}

function replaceFirstExact(
    string $text,
    string $needle,
    string $replacement,
    string $label
): string {
    $pos = strpos($text, $needle);

    if ($pos === false) {
        throw new RuntimeException(
            "Marker {$label} tidak ditemukan."
        );
    }

    return substr($text, 0, $pos)
        . $replacement
        . substr(
            $text,
            $pos + strlen($needle)
        );
}

function sliceBetween(
    string $text,
    string $startNeedle,
    string $endNeedle,
    string $label
): array {
    $start = strpos($text, $startNeedle);

    if ($start === false) {
        throw new RuntimeException(
            "Awal marker {$label} tidak ditemukan."
        );
    }

    $end = strpos(
        $text,
        $endNeedle,
        $start + strlen($startNeedle)
    );

    if ($end === false) {
        throw new RuntimeException(
            "Akhir marker {$label} tidak ditemukan."
        );
    }

    $end += strlen($endNeedle);

    return [
        'start' => $start,
        'end' => $end,
        'text' => substr(
            $text,
            $start,
            $end - $start
        ),
    ];
}

function replaceSlice(
    string $text,
    int $start,
    int $end,
    string $replacement
): string {
    return substr($text, 0, $start)
        . $replacement
        . substr($text, $end);
}

function restoreLineEnding(
    string $text,
    string $eol
): string {
    if ($eol === "\r\n") {
        return str_replace("\n", "\r\n", $text);
    }

    return $text;
}

echo PHP_EOL;
echo "=== BMC QualityHub Revision Fields V5 ===" . PHP_EOL;

try {
    /*
    |--------------------------------------------------------------------------
    | 1. VALIDATION
    |--------------------------------------------------------------------------
    */

    if (! str_contains($controller, "'revision_file_name' =>")) {
        $validationInsert = <<<'PHP'
            'revision_file_name' => [
                'nullable',
                'string',
                'max:180',
            ],

            'document_date' => [
                'nullable',
                'date_format:Y-m-d',
            ],

PHP;

        $controller = insertBeforeFirst(
            $controller,
            "            'revision_note' => [",
            $validationInsert,
            'validation revision_note'
        );

        echo "[OK] Validation field ditambahkan." . PHP_EOL;
    } else {
        echo "[OK] Validation field sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 2. PREPARE REVISION NAME + DATE
    |--------------------------------------------------------------------------
    */

    if (! str_contains($controller, '$revisionDisplayName =')) {
        $prepareInsert = <<<'PHP'


        /*
        |--------------------------------------------------------------------------
        | Revision Display Filename + Document Date
        |--------------------------------------------------------------------------
        |
        | Nama tampilan boleh diubah user.
        | File fisik tetap memakai UUID di private storage.
        |
        */

        $requestedRevisionName =
            trim(
                (string) (
                    $validated['revision_file_name']
                    ?? pathinfo(
                        $safeOriginalName,
                        PATHINFO_FILENAME
                    )
                )
            );

        $requestedRevisionName =
            pathinfo(
                $requestedRevisionName,
                PATHINFO_FILENAME
            );

        $safeRevisionBaseName =
            Str::slug(
                $requestedRevisionName,
                '-'
            );

        if ($safeRevisionBaseName === '') {

            return back()
                ->withInput()
                ->withErrors([
                    'revision_file_name' =>
                        'Nama file revisi tidak valid.',
                ]);
        }

        $revisionDisplayName =
            $safeRevisionBaseName
            . '.'
            . $extension;

        $revisionDocumentDate =
            $validated['document_date']
            ?? $document
                ->document_date
                ?->format('Y-m-d')
            ?? now()->toDateString();
PHP;

        $controller = insertAfterFirst(
            $controller,
            "        \$safeOriginalName =\n"
            . "            \$security['original_name'];",
            $prepareInsert,
            'safeOriginalName'
        );

        echo "[OK] Nama file + tanggal revisi dipersiapkan." . PHP_EOL;
    } else {
        echo "[OK] Preparation metadata sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 3. TRANSACTION USE-LIST
    |--------------------------------------------------------------------------
    */

    if (
        ! str_contains(
            $controller,
            "                    \$revisionDisplayName,"
        )
    ) {
        $controller = replaceFirstExact(
            $controller,
            "                    \$safeOriginalName,\n"
            . "                    \$serverMime,",
            "                    \$safeOriginalName,\n"
            . "                    \$revisionDisplayName,\n"
            . "                    \$revisionDocumentDate,\n"
            . "                    \$serverMime,",
            'transaction use-list'
        );

        echo "[OK] Variable revisi diteruskan ke transaction." . PHP_EOL;
    } else {
        echo "[OK] Transaction use-list sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 4. METADATA SNAPSHOT
    |--------------------------------------------------------------------------
    */

    $snapshot = sliceBetween(
        $controller,
        "                    \$metadataSnapshot = [",
        "                    ];",
        'metadata snapshot'
    );

    $snapshotText = $snapshot['text'];

    if (! str_contains($snapshotText, '$revisionDocumentDate')) {
        $oldDate = <<<'PHP'
                        'document_date' =>
                            $lockedDocument
                                ->document_date
                                ?->format('Y-m-d'),
PHP;

        $newDate = <<<'PHP'
                        'document_date' =>
                            $revisionDocumentDate,
PHP;

        $snapshotText = replaceFirstExact(
            $snapshotText,
            $oldDate,
            $newDate,
            'snapshot document_date'
        );

        $controller = replaceSlice(
            $controller,
            $snapshot['start'],
            $snapshot['end'],
            $snapshotText
        );

        echo "[OK] Snapshot memakai tanggal revisi." . PHP_EOL;
    } else {
        echo "[OK] Snapshot tanggal sudah benar." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 5. DOCUMENT VERSION
    |--------------------------------------------------------------------------
    */

    $versionBlock = sliceBetween(
        $controller,
        "                    \$version = DocumentVersion::create([",
        "                    ]);",
        'DocumentVersion create'
    );

    $versionText = $versionBlock['text'];

    if (! str_contains($versionText, '$revisionDisplayName')) {
        $versionText = replaceFirstExact(
            $versionText,
            "                        'file_name' =>\n"
            . "                            \$safeOriginalName,",
            "                        'file_name' =>\n"
            . "                            \$revisionDisplayName,",
            'DocumentVersion file_name'
        );

        $controller = replaceSlice(
            $controller,
            $versionBlock['start'],
            $versionBlock['end'],
            $versionText
        );

        echo "[OK] DocumentVersion memakai nama file revisi." . PHP_EOL;
    } else {
        echo "[OK] DocumentVersion file_name sudah benar." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 6. UPDATE CURRENT DOCUMENT
    |--------------------------------------------------------------------------
    */

    $updateBlock = sliceBetween(
        $controller,
        "                    \$lockedDocument->update([",
        "                    ]);",
        'lockedDocument update'
    );

    $updateText = $updateBlock['text'];

    if (! str_contains($updateText, '$revisionDisplayName')) {
        $updateText = replaceFirstExact(
            $updateText,
            "                        'file_name' =>\n"
            . "                            \$safeOriginalName,",
            "                        'file_name' =>\n"
            . "                            \$revisionDisplayName,\n\n"
            . "                        'document_date' =>\n"
            . "                            \$revisionDocumentDate,",
            'lockedDocument file_name'
        );

        $controller = replaceSlice(
            $controller,
            $updateBlock['start'],
            $updateBlock['end'],
            $updateText
        );

        echo "[OK] Dokumen aktif memperbarui nama + tanggal." . PHP_EOL;
    } else {
        echo "[OK] Update dokumen aktif sudah benar." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 7. AUDIT METADATA
    |--------------------------------------------------------------------------
    */

    if (! str_contains($controller, "'new_document_date' =>")) {
        $auditInsert = <<<'PHP'

                            'previous_document_date' =>
                                $document
                                    ->document_date
                                    ?->format('Y-m-d'),

                            'new_document_date' =>
                                $revisionDocumentDate,

                            'uploaded_original_name' =>
                                $safeOriginalName,
PHP;

        $controller = insertAfterFirst(
            $controller,
            "                            'new_file_name' =>\n"
            . "                                \$version->file_name,",
            $auditInsert,
            'audit new_file_name'
        );

        echo "[OK] Audit nama/tanggal revisi ditambahkan." . PHP_EOL;
    } else {
        echo "[OK] Audit metadata sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 8. BLADE FIELDS
    |--------------------------------------------------------------------------
    */

    if (! str_contains($blade, 'name="revision_file_name"')) {
        $bladeFields = <<<'BLADE'
                {{-- REVISION METADATA --}}

                <div
                    class="grid
                           md:grid-cols-2
                           gap-5"
                >

                    <div>

                        <label
                            for="revision_file_name"
                            class="block
                                   mb-2
                                   text-sm
                                   font-semibold
                                   text-slate-700"
                        >
                            Nama File Revisi
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="revision_file_name"
                            type="text"
                            name="revision_file_name"
                            required
                            maxlength="180"
                            value="{{ old(
                                'revision_file_name',
                                pathinfo(
                                    $document->file_name,
                                    PATHINFO_FILENAME
                                )
                            ) }}"
                            placeholder="Contoh: laporan-hasil-produksi-axle-assy-rev-1"
                            class="w-full
                                   rounded-xl
                                   border-slate-200
                                   bg-slate-50
                                   px-4
                                   py-3"
                        >

                        <p
                            class="mt-2
                                   text-xs
                                   text-slate-400"
                        >
                            Isi nama tanpa ekstensi. Ekstensi mengikuti file revisi.
                        </p>

                    </div>


                    <div>

                        <label
                            for="document_date"
                            class="block
                                   mb-2
                                   text-sm
                                   font-semibold
                                   text-slate-700"
                        >
                            Tanggal Revisi Dokumen
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="document_date"
                            type="date"
                            name="document_date"
                            required
                            value="{{ old(
                                'document_date',
                                $document->document_date
                                    ?->format('Y-m-d')
                                ?? now()->format('Y-m-d')
                            ) }}"
                            class="w-full
                                   rounded-xl
                                   border-slate-200
                                   bg-slate-50
                                   px-4
                                   py-3"
                        >

                        <p
                            class="mt-2
                                   text-xs
                                   text-slate-400"
                        >
                            Tanggal ini menjadi tanggal dokumen revisi terbaru.
                        </p>

                    </div>

                </div>



BLADE;

        $blade = insertBeforeFirst(
            $blade,
            "                {{-- REVISION NOTE --}}",
            $bladeFields,
            'Blade REVISION NOTE'
        );

        echo "[OK] Field Nama File + Tanggal Revisi ditambahkan." . PHP_EOL;
    } else {
        echo "[OK] Field Blade sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | SAVE TEMPORARILY
    |--------------------------------------------------------------------------
    */

    $controllerSave =
        restoreLineEnding(
            $controller,
            $controllerEol
        );

    $bladeSave =
        restoreLineEnding(
            $blade,
            $bladeEol
        );

    file_put_contents(
        $controllerPath,
        $controllerSave
    );

    file_put_contents(
        $bladePath,
        $bladeSave
    );


    /*
    |--------------------------------------------------------------------------
    | PHP SYNTAX CHECK
    |--------------------------------------------------------------------------
    */

    $lintOutput = [];
    $lintExitCode = 1;

    $command =
        escapeshellarg(PHP_BINARY)
        . ' -l '
        . escapeshellarg($controllerPath)
        . ' 2>&1';

    exec(
        $command,
        $lintOutput,
        $lintExitCode
    );

    if ($lintExitCode !== 0) {
        throw new RuntimeException(
            "PHP lint gagal:\n"
            . implode(
                PHP_EOL,
                $lintOutput
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
    echo "Backup sebelum V5:" . PHP_EOL;
    echo $backupDir . PHP_EOL;
    echo PHP_EOL;
    echo "REVISION FIELDS V5: APPLIED" . PHP_EOL;
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

    fwrite(STDERR, PHP_EOL);
    fwrite(
        STDERR,
        'V5 GAGAL: '
        . $exception->getMessage()
        . PHP_EOL
    );

    fwrite(
        STDERR,
        'Controller dan Blade dikembalikan ke kondisi sebelum V5.'
        . PHP_EOL
    );

    exit(1);
}
