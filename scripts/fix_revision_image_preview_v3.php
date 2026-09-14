<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BMC QualityHub - Revision Image Preview Fix V3
|--------------------------------------------------------------------------
|
| V3 tidak memakai exact block marker.
| Pencarian dilakukan berdasarkan isi baris yang sudah di-trim.
|
| Fix:
| - Tombol "Buka" preview JPG/JPEG/PNG/WebP inline.
| - Tombol "Unduh" tetap download.
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

$eol =
    str_contains($original, "\r\n")
        ? "\r\n"
        : "\n";

$text =
    str_replace(
        ["\r\n", "\r"],
        "\n",
        $original
    );

$lines = explode("\n", $text);

$backupDir =
    $root
    . DIRECTORY_SEPARATOR . 'storage'
    . DIRECTORY_SEPARATOR . 'backups'
    . DIRECTORY_SEPARATOR . 'revision-image-preview-before-v3-'
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

function indentOf(string $line): string
{
    preg_match(
        '/^[ \t]*/',
        $line,
        $match
    );

    return $match[0] ?? '';
}

function findTrim(
    array $lines,
    string $needle,
    int $start = 0,
    ?int $end = null,
    string $label = 'marker'
): int {
    $end ??= count($lines) - 1;

    for ($i = $start; $i <= $end; $i++) {
        if (trim($lines[$i]) === $needle) {
            return $i;
        }
    }

    throw new RuntimeException(
        "Marker {$label} tidak ditemukan."
    );
}

function findContains(
    array $lines,
    string $needle,
    int $start = 0,
    ?int $end = null,
    string $label = 'marker'
): int {
    $end ??= count($lines) - 1;

    for ($i = $start; $i <= $end; $i++) {
        if (str_contains($lines[$i], $needle)) {
            return $i;
        }
    }

    throw new RuntimeException(
        "Marker {$label} tidak ditemukan."
    );
}

function rangeContainsTrim(
    array $lines,
    int $start,
    int $end,
    string $needle
): bool {
    for ($i = $start; $i <= $end; $i++) {
        if (trim($lines[$i]) === $needle) {
            return true;
        }
    }

    return false;
}

echo PHP_EOL;
echo "=== BMC QualityHub Revision Image Preview Fix V3 ===" . PHP_EOL;

try {
    /*
    |--------------------------------------------------------------------------
    | 1. Previewable extension list
    |--------------------------------------------------------------------------
    */

    $previewStart =
        findContains(
            $lines,
            '$previewableExtensions = [',
            0,
            null,
            'previewableExtensions start'
        );

    $previewEnd =
        findTrim(
            $lines,
            '];',
            $previewStart + 1,
            min(
                $previewStart + 30,
                count($lines) - 1
            ),
            'previewableExtensions end'
        );

    $extensionIndent =
        null;

    for (
        $i = $previewStart + 1;
        $i < $previewEnd;
        $i++
    ) {
        if (trim($lines[$i]) !== '') {
            $extensionIndent =
                indentOf($lines[$i]);
            break;
        }
    }

    if ($extensionIndent === null) {
        $extensionIndent =
            indentOf($lines[$previewStart])
            . '    ';
    }

    $newExtensionLines = [];

    foreach (
        [
            "'jpg',",
            "'jpeg',",
            "'png',",
            "'webp',",
        ]
        as $extensionLine
    ) {
        if (
            ! rangeContainsTrim(
                $lines,
                $previewStart,
                $previewEnd,
                $extensionLine
            )
        ) {
            $newExtensionLines[] =
                $extensionIndent
                . $extensionLine;
        }
    }

    if ($newExtensionLines !== []) {
        array_splice(
            $lines,
            $previewEnd,
            0,
            $newExtensionLines
        );

        echo "[OK] JPG/JPEG/PNG/WebP ditambahkan ke daftar preview." . PHP_EOL;
    } else {
        echo "[OK] Extension gambar sudah ada di daftar preview." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 2. Find inline preview Content-Type match block
    |--------------------------------------------------------------------------
    */

    $responseFileLine =
        findContains(
            $lines,
            '$response = response()->file(',
            $previewEnd,
            null,
            'response()->file'
        );

    $contentTypeLine =
        findTrim(
            $lines,
            "'Content-Type' =>",
            $responseFileLine,
            min(
                $responseFileLine + 80,
                count($lines) - 1
            ),
            'Content-Type'
        );

    $matchLine =
        findContains(
            $lines,
            'match(',
            $contentTypeLine,
            min(
                $contentTypeLine + 10,
                count($lines) - 1
            ),
            'Content-Type match'
        );

    $defaultLine =
        findTrim(
            $lines,
            'default =>',
            $matchLine,
            min(
                $matchLine + 60,
                count($lines) - 1
            ),
            'Content-Type default'
        );

    /*
    |--------------------------------------------------------------------------
    | 3. Add image MIME mappings before default
    |--------------------------------------------------------------------------
    */

    $mimeAlreadyPresent =
        rangeContainsTrim(
            $lines,
            $matchLine,
            $defaultLine,
            "'jpg', 'jpeg' =>"
        )
        && rangeContainsTrim(
            $lines,
            $matchLine,
            $defaultLine,
            "'png' =>"
        )
        && rangeContainsTrim(
            $lines,
            $matchLine,
            $defaultLine,
            "'webp' =>"
        );

    if (! $mimeAlreadyPresent) {
        $caseIndent =
            indentOf(
                $lines[$defaultLine]
            );

        $valueIndent =
            $caseIndent
            . '    ';

        $mimeLines = [
            $caseIndent . "'jpg', 'jpeg' =>",
            $valueIndent . "'image/jpeg',",
            '',
            $caseIndent . "'png' =>",
            $valueIndent . "'image/png',",
            '',
            $caseIndent . "'webp' =>",
            $valueIndent . "'image/webp',",
            '',
        ];

        array_splice(
            $lines,
            $defaultLine,
            0,
            $mimeLines
        );

        echo "[OK] MIME type gambar untuk inline preview ditambahkan." . PHP_EOL;
    } else {
        echo "[OK] MIME mapping gambar sudah ada." . PHP_EOL;
    }


    /*
    |--------------------------------------------------------------------------
    | 4. Guards
    |--------------------------------------------------------------------------
    */

    $resultText =
        implode(
            "\n",
            $lines
        );

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
        if (! str_contains($resultText, $required)) {
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
        $resultText =
            str_replace(
                "\n",
                "\r\n",
                $resultText
            );
    }

    file_put_contents(
        $controllerPath,
        $resultText
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
            . implode(
                PHP_EOL,
                $lint
            )
        );
    }

    echo "[OK] PHP lint otomatis PASS." . PHP_EOL;

    echo PHP_EOL;
    echo "Backup:" . PHP_EOL;
    echo $backupPath . PHP_EOL;
    echo PHP_EOL;
    echo "REVISION IMAGE PREVIEW FIX V3: APPLIED" . PHP_EOL;
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
        . 'PATCH V3 GAGAL: '
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
