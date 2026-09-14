<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BMC QualityHub - Revision Image Preview Fix V2
|--------------------------------------------------------------------------
|
| Fix:
| - Tombol "Buka" bisa preview JPG/JPEG/PNG/WebP inline.
| - Tombol "Unduh" tetap memakai route download terpisah.
| - Backend lain tidak diubah.
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
    . DIRECTORY_SEPARATOR . 'revision-image-preview-before-v2-'
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

function replaceOnce(
    string $text,
    string $old,
    string $new,
    string $label
): string {
    $count = substr_count($text, $old);

    if ($count !== 1) {
        throw new RuntimeException(
            "{$label}: marker ditemukan {$count} kali."
        );
    }

    return str_replace(
        $old,
        $new,
        $text
    );
}

echo PHP_EOL;
echo "=== BMC QualityHub Revision Image Preview Fix V2 ===" . PHP_EOL;

try {
    /*
    |--------------------------------------------------------------------------
    | 1. Previewable extensions
    |--------------------------------------------------------------------------
    */

    if (
        ! str_contains($content, "'jpg',")
        || ! str_contains($content, "'jpeg',")
        || ! str_contains($content, "'png',")
        || ! str_contains($content, "'webp',")
    ) {
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

        $content = replaceOnce(
            $content,
            $old,
            $new,
            'previewableExtensions'
        );

        echo "[OK] JPG/JPEG/PNG/WebP ditambahkan ke daftar preview." . PHP_EOL;
    } else {
        echo "[OK] Extension gambar sudah ada di daftar preview." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 2. Content-Type mapping
    |--------------------------------------------------------------------------
    */

    if (
        ! str_contains($content, "'image/jpeg'")
        || ! str_contains($content, "'image/png'")
        || ! str_contains($content, "'image/webp'")
    ) {
        $old = <<<'PHP'
                        'txt' =>
                            'text/plain; charset=UTF-8',

                        default =>
                            'application/octet-stream',
PHP;

        $new = <<<'PHP'
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

        $content = replaceOnce(
            $content,
            $old,
            $new,
            'Content-Type mapping'
        );

        echo "[OK] MIME type gambar untuk inline preview ditambahkan." . PHP_EOL;
    } else {
        echo "[OK] MIME mapping gambar sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 3. Guards
    |--------------------------------------------------------------------------
    */

    $required = [
        "'jpg',",
        "'jpeg',",
        "'png',",
        "'webp',",
        "'jpg', 'jpeg' =>",
        "'image/jpeg'",
        "'image/png'",
        "'image/webp'",
        'ResponseHeaderBag::DISPOSITION_INLINE',
        'response()->download(',
    ];

    foreach ($required as $needle) {
        if (! str_contains($content, $needle)) {
            throw new RuntimeException(
                "Guard gagal: {$needle} tidak ditemukan."
            );
        }
    }

    echo "[OK] Guard preview + download route PASS." . PHP_EOL;


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
    echo "REVISION IMAGE PREVIEW FIX V2: APPLIED" . PHP_EOL;
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
        . 'PATCH V2 GAGAL: '
        . $exception->getMessage()
        . PHP_EOL
    );

    fwrite(
        STDERR,
        'Controller otomatis dikembalikan ke kondisi sebelum patch.' . PHP_EOL
    );

    exit(1);
}
