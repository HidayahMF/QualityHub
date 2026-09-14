<?php

declare(strict_types=1);

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

$backupRoot =
    $root . DIRECTORY_SEPARATOR . 'storage'
    . DIRECTORY_SEPARATOR . 'backups';

$backupCandidates =
    glob(
        $backupRoot
        . DIRECTORY_SEPARATOR
        . 'revision-form-before-v3-*',
        GLOB_ONLYDIR
    ) ?: [];

if ($backupCandidates === []) {
    fwrite(
        STDERR,
        "Backup revision-form-before-v3-* tidak ditemukan.\n"
    );
    exit(1);
}

usort(
    $backupCandidates,
    static fn (string $a, string $b): int =>
        filemtime($b) <=> filemtime($a)
);

$backupDir = $backupCandidates[0];

$backupController =
    $backupDir . DIRECTORY_SEPARATOR . 'DocumentVersionController.php';

$backupBlade =
    $backupDir . DIRECTORY_SEPARATOR . 'create.blade.php';

foreach ([$backupController, $backupBlade] as $path) {
    if (! is_file($path)) {
        fwrite(
            STDERR,
            "Backup tidak lengkap: {$path}\n"
        );
        exit(1);
    }
}

$controllerOriginal = file_get_contents($backupController);
$bladeOriginal = file_get_contents($backupBlade);

if ($controllerOriginal === false || $bladeOriginal === false) {
    fwrite(STDERR, "Gagal membaca backup V3.\n");
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

function replaceExactlyOnce(
    string $subject,
    string $needle,
    string $replacement,
    string $label
): string {
    $count = substr_count($subject, $needle);

    if ($count !== 1) {
        throw new RuntimeException(
            "{$label}: marker ditemukan {$count} kali."
        );
    }

    return str_replace(
        $needle,
        $replacement,
        $subject
    );
}


function replaceFirst(
    string $subject,
    string $needle,
    string $replacement,
    string $label
): string {
    $position = strpos($subject, $needle);

    if ($position === false) {
        throw new RuntimeException(
            "{$label}: marker tidak ditemukan."
        );
    }

    return substr_replace(
        $subject,
        $replacement,
        $position,
        strlen($needle)
    );
}

echo PHP_EOL;
echo "=== BMC QualityHub Revision Fields V4 ===" . PHP_EOL;
echo "Restore source dari backup: {$backupDir}" . PHP_EOL;

try {
    $controller = replaceExactlyOnce(
        $controller,
        <<<'PHP'
            'revision_note' => [
                'required',
                'string',
                'min:3',
                'max:1000',
            ],
PHP,
        <<<'PHP'
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
PHP,
        'validation'
    );

    $controller = replaceExactlyOnce(
        $controller,
        <<<'PHP'
        $safeOriginalName =
            $security['original_name'];

        $serverMime =
PHP,
        <<<'PHP'
        $safeOriginalName =
            $security['original_name'];

        /*
        |--------------------------------------------------------------------------
        | Revision Display Filename + Revision Date
        |--------------------------------------------------------------------------
        |
        | Nama file yang tampil boleh ditentukan user.
        | File fisik tetap memakai UUID di private storage.
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
            $validated['document_date'];

        $serverMime =
PHP,
        'revision metadata preparation'
    );

    $controller = replaceExactlyOnce(
        $controller,
        <<<'PHP'
                    $extension,
                    $safeOriginalName,
                    $serverMime,
PHP,
        <<<'PHP'
                    $extension,
                    $safeOriginalName,
                    $revisionDisplayName,
                    $revisionDocumentDate,
                    $serverMime,
PHP,
        'transaction use-list'
    );

    $controller = replaceExactlyOnce(
        $controller,
        <<<'PHP'
                        'document_date' =>
                            $lockedDocument
                                ->document_date
                                ?->format('Y-m-d'),
PHP,
        <<<'PHP'
                        'document_date' =>
                            $revisionDocumentDate,
PHP,
        'snapshot document_date'
    );

    $controller = replaceFirst(
        $controller,
        <<<'PHP'
                        'file_name' =>
                            $safeOriginalName,

                        'file_path' =>
PHP,
        <<<'PHP'
                        'file_name' =>
                            $revisionDisplayName,

                        'file_path' =>
PHP,
        'DocumentVersion file_name'
    );

    $controller = replaceExactlyOnce(
        $controller,
        <<<'PHP'
                        'file_name' =>
                            $safeOriginalName,

                        'file_path' =>
PHP,
        <<<'PHP'
                        'file_name' =>
                            $revisionDisplayName,

                        'document_date' =>
                            $revisionDocumentDate,

                        'file_path' =>
PHP,
        'current document file_name/date'
    );

    $controller = replaceExactlyOnce(
        $controller,
        <<<'PHP'
                            'new_file_name' =>
                                $version->file_name,

                            'security_validation' => [
PHP,
        <<<'PHP'
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
PHP,
        'audit metadata'
    );

    $blade = replaceExactlyOnce(
        $blade,
        <<<'BLADE'


                {{-- REVISION NOTE --}}
BLADE,
        <<<'BLADE'


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
                            Isi nama tanpa ekstensi.
                            Ekstensi mengikuti file revisi yang dipilih.
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
                            Tanggal ini menjadi tanggal dokumen
                            untuk revisi terbaru.
                        </p>

                    </div>

                </div>



                {{-- REVISION NOTE --}}
BLADE,
        'Blade revision fields'
    );

    if ($controllerEol === "\r\n") {
        $controller =
            str_replace(
                "\n",
                "\r\n",
                $controller
            );
    }

    if ($bladeEol === "\r\n") {
        $blade =
            str_replace(
                "\n",
                "\r\n",
                $blade
            );
    }

    file_put_contents(
        $controllerPath,
        $controller
    );

    file_put_contents(
        $bladePath,
        $blade
    );

    echo "[OK] Controller dipulihkan dari backup lalu dipatch ulang." . PHP_EOL;
    echo "[OK] Blade dipulihkan dari backup lalu dipatch ulang." . PHP_EOL;
    echo "REVISION FIELDS V4: APPLIED" . PHP_EOL;
    echo PHP_EOL;
    echo "Jalankan:" . PHP_EOL;
    echo "php -l .\\app\\Http\\Controllers\\DocumentVersionController.php" . PHP_EOL;
    echo "php artisan optimize:clear" . PHP_EOL;

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
        'V4 GAGAL: '
        . $exception->getMessage()
        . PHP_EOL
    );

    fwrite(
        STDERR,
        'Project dikembalikan ke backup sebelum V3.'
        . PHP_EOL
    );

    exit(1);
}
