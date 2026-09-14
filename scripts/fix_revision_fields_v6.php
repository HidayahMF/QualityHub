<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BMC QualityHub - Revision Fields Patch V6
|--------------------------------------------------------------------------
|
| V6 dibuat dari struktur controller lokal yang sudah dikirim pengguna.
| Tidak memakai regex kompleks untuk potongan utama.
| Jika satu marker gagal atau PHP lint gagal, otomatis rollback.
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
    . DIRECTORY_SEPARATOR . 'revision-form-before-v6-'
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

function replaceOnce(
    string $text,
    string $needle,
    string $replacement,
    string $label
): string {
    $count = substr_count($text, $needle);

    if ($count !== 1) {
        throw new RuntimeException(
            "{$label}: marker ditemukan {$count} kali."
        );
    }

    return str_replace(
        $needle,
        $replacement,
        $text
    );
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
echo "=== BMC QualityHub Revision Fields V6 ===" . PHP_EOL;

try {
    /*
    |--------------------------------------------------------------------------
    | 1. VALIDATION
    |--------------------------------------------------------------------------
    */

    if (! str_contains($controller, "'revision_file_name' =>")) {
        $old = <<<'PHP'
            'revision_note' => [
                'required',
                'string',
                'min:3',
                'max:1000',
            ],
PHP;

        $new = <<<'PHP'
            'revision_file_name' => [
                'required',
                'string',
                'min:1',
                'max:180',
            ],

            'document_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'revision_note' => [
                'required',
                'string',
                'min:3',
                'max:1000',
            ],
PHP;

        $controller = replaceOnce(
            $controller,
            $old,
            $new,
            'validation'
        );

        echo "[OK] Validation nama file + tanggal ditambahkan." . PHP_EOL;
    } else {
        echo "[OK] Validation sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 2. PREPARE REVISION DISPLAY NAME + DATE
    |--------------------------------------------------------------------------
    */

    if (! str_contains($controller, '$revisionDisplayName =')) {
        $old = <<<'PHP'
        $safeOriginalName =
            $security['original_name'];

        $serverMime =
PHP;

        $new = <<<'PHP'
        $safeOriginalName =
            $security['original_name'];

        /*
        |--------------------------------------------------------------------------
        | Revision Display Filename + Revision Date
        |--------------------------------------------------------------------------
        |
        | Nama yang diisi user hanya menjadi nama tampilan/logical filename.
        | File fisik tetap disimpan memakai UUID pada private storage.
        |
        */

        $requestedRevisionName =
            trim(
                (string) $validated['revision_file_name']
            );

        $requestedRevisionName =
            pathinfo(
                $requestedRevisionName,
                PATHINFO_FILENAME
            );

        $safeRevisionBaseName =
            preg_replace(
                '/[^\pL\pN._ -]+/u',
                '-',
                $requestedRevisionName
            ) ?? '';

        $safeRevisionBaseName =
            trim(
                preg_replace(
                    '/\s+/u',
                    ' ',
                    $safeRevisionBaseName
                ) ?? '',
                " .-_"
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
            $validated['document_date'];

        $serverMime =
PHP;

        $controller = replaceOnce(
            $controller,
            $old,
            $new,
            'prepare metadata'
        );

        echo "[OK] Nama file + tanggal revisi dipersiapkan." . PHP_EOL;
    } else {
        echo "[OK] Preparation metadata sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 3. TRANSACTION USE LIST
    |--------------------------------------------------------------------------
    */

    if (! str_contains(
        $controller,
        "                    \$revisionDisplayName,"
    )) {
        $old = <<<'PHP'
                    $extension,
                    $safeOriginalName,
                    $serverMime,
PHP;

        $new = <<<'PHP'
                    $extension,
                    $safeOriginalName,
                    $revisionDisplayName,
                    $revisionDocumentDate,
                    $serverMime,
PHP;

        $controller = replaceOnce(
            $controller,
            $old,
            $new,
            'transaction use-list'
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

    if (! str_contains(
        $controller,
        "'document_date' =>\n                            \$revisionDocumentDate,"
    )) {
        $old = <<<'PHP'
                        'document_date' =>
                            $lockedDocument
                                ->document_date
                                ?->format('Y-m-d'),
PHP;

        $new = <<<'PHP'
                        'document_date' =>
                            $revisionDocumentDate,
PHP;

        $controller = replaceOnce(
            $controller,
            $old,
            $new,
            'snapshot document_date'
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

    $old = <<<'PHP'
                    $version = DocumentVersion::create([
                        'document_id' =>
                            $lockedDocument->id,

                        'revision_number' =>
                            $nextRevision,

                        'uploaded_by' =>
                            Auth::id(),

                        'file_name' =>
                            $safeOriginalName,
PHP;

    if (str_contains($controller, $old)) {
        $new = <<<'PHP'
                    $version = DocumentVersion::create([
                        'document_id' =>
                            $lockedDocument->id,

                        'revision_number' =>
                            $nextRevision,

                        'uploaded_by' =>
                            Auth::id(),

                        'file_name' =>
                            $revisionDisplayName,
PHP;

        $controller = replaceOnce(
            $controller,
            $old,
            $new,
            'DocumentVersion file_name'
        );

        echo "[OK] DocumentVersion memakai nama file revisi." . PHP_EOL;
    } elseif (
        str_contains(
            $controller,
            "'file_name' =>\n                            \$revisionDisplayName,"
        )
    ) {
        echo "[OK] DocumentVersion file_name sudah benar." . PHP_EOL;
    } else {
        throw new RuntimeException(
            'DocumentVersion file_name: marker tidak ditemukan.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 6. CURRENT DOCUMENT FILE NAME + DATE
    |--------------------------------------------------------------------------
    */

    $old = <<<'PHP'
                    $lockedDocument->update([
                        'current_revision' =>
                            $nextRevision,

                        'file_name' =>
                            $safeOriginalName,

                        'file_path' =>
PHP;

    if (str_contains($controller, $old)) {
        $new = <<<'PHP'
                    $lockedDocument->update([
                        'current_revision' =>
                            $nextRevision,

                        'file_name' =>
                            $revisionDisplayName,

                        'document_date' =>
                            $revisionDocumentDate,

                        'file_path' =>
PHP;

        $controller = replaceOnce(
            $controller,
            $old,
            $new,
            'lockedDocument update'
        );

        echo "[OK] Dokumen aktif memperbarui nama + tanggal." . PHP_EOL;
    } elseif (
        str_contains(
            $controller,
            "'document_date' =>\n                            \$revisionDocumentDate,"
        )
        && str_contains(
            $controller,
            "\$lockedDocument->update(["
        )
    ) {
        echo "[OK] Update dokumen aktif sudah benar." . PHP_EOL;
    } else {
        throw new RuntimeException(
            'lockedDocument update: marker tidak ditemukan.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 7. AUDIT METADATA
    |--------------------------------------------------------------------------
    */

    if (! str_contains($controller, "'new_document_date' =>")) {
        $old = <<<'PHP'
                            'new_file_name' =>
                                $version->file_name,

                            'security_validation' => [
PHP;

        $new = <<<'PHP'
                            'new_file_name' =>
                                $version->file_name,

                            'previous_document_date' =>
                                $document
                                    ->document_date
                                    ?->format('Y-m-d'),

                            'new_document_date' =>
                                $revisionDocumentDate,

                            'uploaded_original_name' =>
                                $safeOriginalName,

                            'security_validation' => [
PHP;

        $controller = replaceOnce(
            $controller,
            $old,
            $new,
            'audit metadata'
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
        $marker = <<<'BLADE'
                {{-- REVISION NOTE --}}
BLADE;

        $fields = <<<'BLADE'
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

                            <span class="text-red-500">
                                *
                            </span>
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

                            <span class="text-red-500">
                                *
                            </span>
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



                {{-- REVISION NOTE --}}
BLADE;

        $blade = replaceOnce(
            $blade,
            $marker,
            $fields,
            'Blade revision note'
        );

        echo "[OK] Field Nama File + Tanggal Revisi ditambahkan." . PHP_EOL;
    } else {
        echo "[OK] Field Blade sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | SAVE
    |--------------------------------------------------------------------------
    */

    file_put_contents(
        $controllerPath,
        restoreEol(
            $controller,
            $controllerEol
        )
    );

    file_put_contents(
        $bladePath,
        restoreEol(
            $blade,
            $bladeEol
        )
    );


    /*
    |--------------------------------------------------------------------------
    | AUTOMATIC PHP LINT
    |--------------------------------------------------------------------------
    */

    $lintOutput = [];
    $lintExit = 1;

    $command =
        escapeshellarg(PHP_BINARY)
        . ' -l '
        . escapeshellarg($controllerPath)
        . ' 2>&1';

    exec(
        $command,
        $lintOutput,
        $lintExit
    );

    if ($lintExit !== 0) {
        throw new RuntimeException(
            "PHP lint gagal:\n"
            . implode(
                PHP_EOL,
                $lintOutput
            )
        );
    }

    echo "[OK] PHP lint otomatis PASS." . PHP_EOL;


    echo PHP_EOL;
    echo "Backup sebelum V6:" . PHP_EOL;
    echo $backupDir . PHP_EOL;
    echo PHP_EOL;
    echo "REVISION FIELDS V6: APPLIED" . PHP_EOL;
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
        'V6 GAGAL: '
        . $exception->getMessage()
        . PHP_EOL
    );

    fwrite(
        STDERR,
        'Controller dan Blade otomatis dikembalikan ke kondisi sebelum V6.'
        . PHP_EOL
    );

    exit(1);
}
