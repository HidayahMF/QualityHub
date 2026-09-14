<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BMC QualityHub - Optional Revision Fix V10
|--------------------------------------------------------------------------
|
| Fix spesifik setelah V9:
| Saat tidak ada file baru, $security sudah memiliki fallback metadata,
| tetapi variabel turunan ($extension, $safeOriginalName, $serverMime,
| $verifiedFileSize) sebelumnya hanya diisi di jalur upload file baru.
|
| V10 menormalisasi nilai tersebut untuk KEDUA jalur sebelum nama revisi
| dibentuk. Tidak mengubah Blade, DB schema, atau storage.
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
$text = str_replace(["\r\n", "\r"], "\n", $original);
$lines = explode("\n", $text);

$backupDir =
    $root
    . DIRECTORY_SEPARATOR . 'storage'
    . DIRECTORY_SEPARATOR . 'backups'
    . DIRECTORY_SEPARATOR . 'revision-optional-file-before-v10-'
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

copy($controllerPath, $backupPath);

function indentOf(string $line): string
{
    preg_match('/^[ \t]*/', $line, $match);
    return $match[0] ?? '';
}

echo PHP_EOL;
echo "=== BMC QualityHub Optional Revision Fix V10 ===" . PHP_EOL;

try {
    $alreadyApplied = false;

    foreach ($lines as $line) {
        if (
            str_contains(
                $line,
                'Normalize Security Result for Both Revision Modes'
            )
        ) {
            $alreadyApplied = true;
            break;
        }
    }

    if (! $alreadyApplied) {
        $revisionHeader = null;

        foreach ($lines as $i => $line) {
            if (
                str_contains(
                    $line,
                    'Revision Display Filename + Revision Date'
                )
            ) {
                $revisionHeader = $i;
                break;
            }
        }

        if ($revisionHeader === null) {
            throw new RuntimeException(
                'Header Revision Display Filename + Revision Date tidak ditemukan.'
            );
        }

        $commentStart = $revisionHeader;

        while (
            $commentStart >= 0
            && trim($lines[$commentStart]) !== '/*'
        ) {
            $commentStart--;
        }

        if (
            $commentStart < 0
            || trim($lines[$commentStart]) !== '/*'
        ) {
            throw new RuntimeException(
                'Awal comment Revision Display tidak ditemukan.'
            );
        }

        $indent = indentOf($lines[$commentStart]);

        $block = [
            $indent . '/*',
            $indent . '|--------------------------------------------------------------------------',
            $indent . '| Normalize Security Result for Both Revision Modes',
            $indent . '|--------------------------------------------------------------------------',
            $indent . '|',
            $indent . '| Jalur upload baru dan metadata-only harus sama-sama memiliki',
            $indent . '| extension, nama file, MIME, dan ukuran sebelum metadata revisi dibuat.',
            $indent . '|',
            $indent . '*/',
            '',
            $indent . '$extension =',
            $indent . "    \$security['extension'];",
            '',
            $indent . '$safeOriginalName =',
            $indent . "    \$security['original_name'];",
            '',
            $indent . '$serverMime =',
            $indent . "    \$security['server_mime']",
            $indent . "    ?? \$security['mime_type']",
            $indent . '    ?? $document->mime_type;',
            '',
            $indent . '$verifiedFileSize =',
            $indent . "    \$security['file_size']",
            $indent . "    ?? \$security['size']",
            $indent . '    ?? $document->file_size;',
            '',
            '',
        ];

        array_splice(
            $lines,
            $commentStart,
            0,
            $block
        );

        echo "[OK] Variabel file dinormalisasi untuk upload baru + metadata-only." . PHP_EOL;
    } else {
        echo "[OK] Fix V10 sudah terpasang sebelumnya." . PHP_EOL;
    }

    $result = implode("\n", $lines);

    if ($eol === "\r\n") {
        $result = str_replace("\n", "\r\n", $result);
    }

    file_put_contents(
        $controllerPath,
        $result
    );

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
    echo "Backup sebelum V10:" . PHP_EOL;
    echo $backupPath . PHP_EOL;
    echo PHP_EOL;
    echo "OPTIONAL REVISION FIX V10: APPLIED" . PHP_EOL;
    echo PHP_EOL;
    echo "Lanjutkan:" . PHP_EOL;
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
        . 'V10 GAGAL: '
        . $exception->getMessage()
        . PHP_EOL
    );

    fwrite(
        STDERR,
        'Controller otomatis dikembalikan ke kondisi sebelum V10.'
        . PHP_EOL
    );

    exit(1);
}
