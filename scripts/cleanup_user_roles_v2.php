<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BMC QualityHub - User Role Cleanup V2
|--------------------------------------------------------------------------
|
| Mempertahankan hanya:
| - super_admin => Super Admin
| - user        => Pengguna
|
| Menghapus dari User Management:
| - admin
| - viewer / Peninjau
|
| V2 mencari berdasarkan isi baris, jadi tidak tergantung jumlah spasi/CRLF.
|
*/

$root = dirname(__DIR__);

$controllerPath =
    $root
    . DIRECTORY_SEPARATOR . 'app'
    . DIRECTORY_SEPARATOR . 'Http'
    . DIRECTORY_SEPARATOR . 'Controllers'
    . DIRECTORY_SEPARATOR . 'UserManagementController.php';

$viewPath =
    $root
    . DIRECTORY_SEPARATOR . 'resources'
    . DIRECTORY_SEPARATOR . 'views'
    . DIRECTORY_SEPARATOR . 'users'
    . DIRECTORY_SEPARATOR . 'index.blade.php';

foreach ([$controllerPath, $viewPath] as $path) {
    if (! is_file($path)) {
        fwrite(
            STDERR,
            "File tidak ditemukan: {$path}" . PHP_EOL
        );
        exit(1);
    }
}

$controllerOriginal = file_get_contents($controllerPath);
$viewOriginal = file_get_contents($viewPath);

if ($controllerOriginal === false || $viewOriginal === false) {
    fwrite(STDERR, "Gagal membaca file project." . PHP_EOL);
    exit(1);
}

$controllerEol =
    str_contains($controllerOriginal, "\r\n")
        ? "\r\n"
        : "\n";

$viewEol =
    str_contains($viewOriginal, "\r\n")
        ? "\r\n"
        : "\n";

$controllerLines = explode(
    "\n",
    str_replace(
        ["\r\n", "\r"],
        "\n",
        $controllerOriginal
    )
);

$viewLines = explode(
    "\n",
    str_replace(
        ["\r\n", "\r"],
        "\n",
        $viewOriginal
    )
);

$backupDir =
    $root
    . DIRECTORY_SEPARATOR . 'storage'
    . DIRECTORY_SEPARATOR . 'backups'
    . DIRECTORY_SEPARATOR . 'user-role-cleanup-v2-'
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
    $backupDir . DIRECTORY_SEPARATOR . 'UserManagementController.php'
);

copy(
    $viewPath,
    $backupDir . DIRECTORY_SEPARATOR . 'index.blade.php'
);

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

function indentOf(string $line): string
{
    preg_match(
        '/^[ \t]*/',
        $line,
        $match
    );

    return $match[0] ?? '';
}

function removeOptionBlock(
    array &$lines,
    string $value,
    string $label
): void {
    $target = 'value="' . $value . '"';

    $valueIndex = null;

    foreach ($lines as $i => $line) {
        if (trim($line) === $target) {
            $valueIndex = $i;
            break;
        }
    }

    if ($valueIndex === null) {
        echo "[OK] Opsi {$label} memang sudah tidak ada." . PHP_EOL;
        return;
    }

    $start = $valueIndex;

    while (
        $start >= 0
        && trim($lines[$start]) !== '<option'
    ) {
        $start--;
    }

    if ($start < 0) {
        throw new RuntimeException(
            "Awal option {$label} tidak ditemukan."
        );
    }

    $end = $valueIndex;

    while (
        $end < count($lines)
        && trim($lines[$end]) !== '</option>'
    ) {
        $end++;
    }

    if ($end >= count($lines)) {
        throw new RuntimeException(
            "Akhir option {$label} tidak ditemukan."
        );
    }

    // Ikut hapus satu baris kosong sesudah option bila ada.
    $length = $end - $start + 1;

    if (
        isset($lines[$end + 1])
        && trim($lines[$end + 1]) === ''
    ) {
        $length++;
    }

    array_splice(
        $lines,
        $start,
        $length
    );

    echo "[OK] Opsi {$label} dihapus dari filter." . PHP_EOL;
}

echo PHP_EOL;
echo "=== BMC QualityHub User Role Cleanup V2 ===" . PHP_EOL;

try {
    /*
    |--------------------------------------------------------------------------
    | 1. Backend whitelist
    |--------------------------------------------------------------------------
    */

    $roleKey =
        findTrim(
            $controllerLines,
            "'role' => [",
            0,
            null,
            'role validation'
        );

    $ruleIn =
        findTrim(
            $controllerLines,
            'Rule::in([',
            $roleKey,
            min(
                $roleKey + 12,
                count($controllerLines) - 1
            ),
            'role Rule::in'
        );

    $ruleEnd =
        findTrim(
            $controllerLines,
            ']),',
            $ruleIn + 1,
            min(
                $ruleIn + 12,
                count($controllerLines) - 1
            ),
            'role Rule::in end'
        );

    $roleIndent =
        indentOf(
            $controllerLines[$ruleIn + 1]
        );

    $newRoleLines = [
        $roleIndent . "'super_admin',",
        $roleIndent . "'user',",
    ];

    array_splice(
        $controllerLines,
        $ruleIn + 1,
        $ruleEnd - $ruleIn - 1,
        $newRoleLines
    );

    echo "[OK] Backend whitelist menjadi super_admin + user." . PHP_EOL;


    /*
    |--------------------------------------------------------------------------
    | 2. Dropdown
    |--------------------------------------------------------------------------
    */

    removeOptionBlock(
        $viewLines,
        'admin',
        'Admin'
    );

    removeOptionBlock(
        $viewLines,
        'viewer',
        'Peninjau'
    );


    /*
    |--------------------------------------------------------------------------
    | 3. Table role labels
    |--------------------------------------------------------------------------
    */

    $cleanedViewLines = [];

    foreach ($viewLines as $line) {
        $trim = trim($line);

        if (
            $trim === "'admin' => 'Admin',"
            || $trim === "'viewer' => 'Peninjau',"
        ) {
            continue;
        }

        $cleanedViewLines[] = $line;
    }

    $viewLines = $cleanedViewLines;

    echo "[OK] Label legacy Admin/Peninjau dibersihkan dari tabel." . PHP_EOL;


    /*
    |--------------------------------------------------------------------------
    | 4. Guards
    |--------------------------------------------------------------------------
    */

    $controllerText =
        implode(
            "\n",
            $controllerLines
        );

    $viewText =
        implode(
            "\n",
            $viewLines
        );

    if (
        str_contains(
            $viewText,
            'value="admin"'
        )
        || str_contains(
            $viewText,
            'value="viewer"'
        )
    ) {
        throw new RuntimeException(
            'Guard gagal: option Admin/Peninjau masih ada.'
        );
    }

    if (
        ! str_contains(
            $viewText,
            'value="super_admin"'
        )
        || ! str_contains(
            $viewText,
            'value="user"'
        )
    ) {
        throw new RuntimeException(
            'Guard gagal: Super Admin/Pengguna hilang dari filter.'
        );
    }

    // Cek khusus blok role whitelist.
    $roleBlockText =
        implode(
            "\n",
            array_slice(
                $controllerLines,
                $roleKey,
                12
            )
        );

    if (
        str_contains(
            $roleBlockText,
            "'admin',"
        )
        || str_contains(
            $roleBlockText,
            "'viewer',"
        )
    ) {
        throw new RuntimeException(
            'Guard gagal: legacy role masih ada di whitelist.'
        );
    }

    if (
        ! str_contains(
            $roleBlockText,
            "'super_admin',"
        )
        || ! str_contains(
            $roleBlockText,
            "'user',"
        )
    ) {
        throw new RuntimeException(
            'Guard gagal: role utama tidak lengkap di whitelist.'
        );
    }

    echo "[OK] Guard hanya Super Admin + Pengguna PASS." . PHP_EOL;


    /*
    |--------------------------------------------------------------------------
    | Save
    |--------------------------------------------------------------------------
    */

    if ($controllerEol === "\r\n") {
        $controllerText =
            str_replace(
                "\n",
                "\r\n",
                $controllerText
            );
    }

    if ($viewEol === "\r\n") {
        $viewText =
            str_replace(
                "\n",
                "\r\n",
                $viewText
            );
    }

    file_put_contents(
        $controllerPath,
        $controllerText
    );

    file_put_contents(
        $viewPath,
        $viewText
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

    echo "[OK] PHP lint UserManagementController PASS." . PHP_EOL;

    echo PHP_EOL;
    echo "Backup:" . PHP_EOL;
    echo $backupDir . PHP_EOL;
    echo PHP_EOL;
    echo "USER ROLE CLEANUP V2: APPLIED" . PHP_EOL;
    echo PHP_EOL;
    echo "Next:" . PHP_EOL;
    echo "php artisan view:clear" . PHP_EOL;
    echo "php artisan optimize:clear" . PHP_EOL;
    echo "php artisan test" . PHP_EOL;

} catch (Throwable $exception) {

    file_put_contents(
        $controllerPath,
        $controllerOriginal
    );

    file_put_contents(
        $viewPath,
        $viewOriginal
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
        'Controller dan Blade otomatis dikembalikan ke kondisi sebelum V2.'
        . PHP_EOL
    );

    exit(1);
}
