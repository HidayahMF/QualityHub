<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BMC QualityHub - Revision Image Preview Fix
|--------------------------------------------------------------------------
|
| Problem:
| Tombol "Buka" di Revision History hanya preview PDF/TXT/CSV.
| JPG/PNG/WebP masuk fallback response()->download(), sehingga browser
| langsung mengunduh file.
|
| Fix:
| - JPG/JPEG/PNG/WebP dianggap previewable.
| - Content-Type dikirim sesuai tipe gambar.
| - Tombol "Unduh" tetap menggunakan route download terpisah.
| - Backup + rollback otomatis.
|
*/

$root = dirname(__DIR__);

$controllerPath =
    $root
    . DIRECTORY_SEPARATOR . 'app'
    . DIRECTORY_SEPARATOR . 'Http'
    . DIRECTORY_SEPARATOR . 'Controllers'
    . DIRECTORY_SEPARATOR . 'DocumentVersionController.php';

if (! is_file($controllerPath)) {
    fwrite(
        STDERR,
        "Controller tidak ditemukan: {$controllerPath}" . PHP_EOL
    );
    exit(1);
}

$original = file_get_contents($controllerPath);

if ($original === false) {
    fwrite(STDERR, "Gagal membaca controller." . PHP_EOL);
    exit(1);
}

$eol = str_contains($original, "\r\n") ? "\r\n" : "\n";

$content = str_replace(
    ["\r\n", "\r"],
    "\n",
    $original
);

$backupDir =
    $root
    . DIRECTORY_SEPARATOR . 'storage'
    . DIRECTORY_SEPARATOR . 'backups'
    . DIRECTORY_SEPARATOR . 'revision-image-preview-before-fix-'
    . date('Ymd-His');

if (
    ! is_dir($backupDir)
    && ! mkdir($backupDir, 0777, true)
    && ! is_dir($backupDir)
) {
    fwrite(STDERR, "Gagal membuat folder backup." . PHP_EOL);
    exit(1);
}

$backupPath =
    $backupDir
    . DIRECTORY_SEPARATOR
    . 'DocumentVersionController.php';

copy(
    $controllerPath,
    $backupPath
);

echo PHP_EOL;
echo "=== BMC QualityHub Revision Image Preview Fix ===" . PHP_EOL;

try {
    /*
    |--------------------------------------------------------------------------
    | 1. Add image extensions to preview whitelist
    |--------------------------------------------------------------------------
    */

    $alreadyExtensions =
        str_contains($content, "'jpg',")
        && str_contains($content, "'jpeg',")
        && str_contains($content, "'png',")
        && str_contains($content, "'webp',");

    if (! $alreadyExtensions) {
        $old = <<<'PHP'
        $previewableExtensions = [
            'pdf',
            'txt',
            'csv',
        ];
PHP;

        $new = <<<'PHP'
        $previewableExtensions = [
            'pdf',
            'txt',
            'csv',
            'jpg',
            'jpeg',
            'png',
            'webp',
        ];
PHP;

        $count = substr_count(
            $content,
            $old
        );

        if ($count !== 1) {
            throw new RuntimeException(
                "Marker previewableExtensions ditemukan {$count} kali."
            );
        }

        $content = str_replace(
            $old,
            $new,
            $content
        );

        echo "[OK] JPG/JPEG/PNG/WebP ditambahkan ke previewableExtensions." . PHP_EOL;
    } else {
        echo "[OK] Image extensions sudah previewable." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 2. Add image Content-Type mapping
    |--------------------------------------------------------------------------
    */

    $alreadyMime =
        str_contains($content, "'jpg', 'jpeg' =>")
        && str_contains($content, "'png' =>")
        && str_contains($content, "'webp' =>");

    if (! $alreadyMime) {
        $old = <<<'PHP'
                        'csv' =>
                            'text/csv; charset=UTF-8',

                        'txt' =>
                            'text/plain; charset=UTF-8',

                        default =>
                            'application/octet-stream',
PHP;

        $new = <<<'PHP'
                        'csv' =>
                            'text/csv; charset=UTF-8',

                        'txt' =>
                            'text/plain; charset=UTF-8',

                        'jpg', 'jpeg' =>
                            'image/jpeg',

                        'png' =>
                            'image/png',

                        'webp' =>
                            'image/webp',

                        default =>
                            'application/octet-stream',
PHP;

        $count = substr_count(
            $content,
            $old
        );

        if ($count !== 1) {
            throw new RuntimeException(
                "Marker Content-Type mapping ditemukan {$count} kali."
            );
        }

        $content = str_replace(
            $old,
            $new,
            $content
        );

        echo "[OK] MIME type gambar untuk inline preview ditambahkan." . PHP_EOL;
    } else {
        echo "[OK] MIME mapping gambar sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 3. Safety guards
    |--------------------------------------------------------------------------
    */

    foreach (
        [
            "'jpg',",
            "'jpeg',",
            "'png',",
            "'webp',",
            "'jpg', 'jpeg' =>",
            "'image/jpeg'",
            "'image/png'",
            "'image/webp'",
            'ResponseHeaderBag::DISPOSITION_INLINE',
        ]
        as $required
    ) {
        if (! str_contains($content, $required)) {
            throw new RuntimeException(
                "Guard gagal: {$required} tidak ditemukan."
            );
        }
    }

    echo "[OK] Guard inline image preview PASS." . PHP_EOL;


    /*
    |--------------------------------------------------------------------------
    | Save
    |--------------------------------------------------------------------------
    */

    if ($eol === "\r\n") {
        $content =
            str_replace(
                "\n",
                "\r\n",
                $content
            );
    }

    file_put_contents(
        $controllerPath,
        $content
    );


    /*
    |--------------------------------------------------------------------------
    | PHP lint
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
    echo "Backup:" . PHP_EOL;
    echo $backupPath . PHP_EOL;
    echo PHP_EOL;
    echo "REVISION IMAGE PREVIEW FIX: APPLIED" . PHP_EOL;
    echo PHP_EOL;
    echo "Next:" . PHP_EOL;
    echo "php artisan optimize:clear" . PHP_EOL;
    echo "php artisan test" . PHP_EOL;

} catch (Throwable $exception) {

    file_put_contents(
        $controllerPath,
        $original
    );

    fwrite(
        STDERR,
        PHP_EOL
        . 'PATCH GAGAL: '
        . $exception->getMessage()
        . PHP_EOL
    );

    fwrite(
        STDERR,
        'Controller otomatis dikembalikan ke kondisi sebelum patch.'
        . PHP_EOL
    );

    exit(1);
}
