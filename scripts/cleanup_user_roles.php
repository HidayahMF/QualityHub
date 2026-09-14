<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BMC QualityHub - User Role Cleanup
|--------------------------------------------------------------------------
|
| Role aktif yang dipertahankan:
| - super_admin => Super Admin
| - user        => Pengguna
|
| Dihapus dari User Management:
| - admin
| - viewer / Peninjau
|
| File yang diubah:
| - app/Http/Controllers/UserManagementController.php
| - resources/views/users/index.blade.php
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

$controller =
    str_replace(
        ["\r\n", "\r"],
        "\n",
        $controllerOriginal
    );

$view =
    str_replace(
        ["\r\n", "\r"],
        "\n",
        $viewOriginal
    );

$backupDir =
    $root
    . DIRECTORY_SEPARATOR . 'storage'
    . DIRECTORY_SEPARATOR . 'backups'
    . DIRECTORY_SEPARATOR . 'user-role-cleanup-'
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

echo PHP_EOL;
echo "=== BMC QualityHub User Role Cleanup ===" . PHP_EOL;

try {
    /*
    |--------------------------------------------------------------------------
    | 1. Backend whitelist
    |--------------------------------------------------------------------------
    */

    $oldRoleWhitelist = <<<'PHP'
                Rule::in([
                    'super_admin',
                    'admin',
                    'user',
                    'viewer',
                ]),
PHP;

    $newRoleWhitelist = <<<'PHP'
                Rule::in([
                    'super_admin',
                    'user',
                ]),
PHP;

    if (str_contains($controller, $oldRoleWhitelist)) {
        $controller =
            str_replace(
                $oldRoleWhitelist,
                $newRoleWhitelist,
                $controller
            );

        echo "[OK] Backend role whitelist: hanya super_admin + user." . PHP_EOL;
    } elseif (
        str_contains($controller, "'super_admin',")
        && str_contains($controller, "'user',")
        && ! str_contains($controller, "'viewer',")
    ) {
        echo "[OK] Backend role whitelist sudah bersih." . PHP_EOL;
    } else {
        throw new RuntimeException(
            'Marker backend role whitelist tidak cocok.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 2. Remove Admin option
    |--------------------------------------------------------------------------
    */

    $adminOptionPattern =
        '/\n[ \t]*<option\s*'
        . '\n[ \t]*value="admin"\s*'
        . '\n[ \t]*@selected\(request\(\'role\'\) === \'admin\'\)\s*'
        . '\n[ \t]*>\s*'
        . '\n[ \t]*Admin\s*'
        . '\n[ \t]*<\/option>\s*/';

    $countAdmin = 0;

    $view =
        preg_replace(
            $adminOptionPattern,
            "\n",
            $view,
            1,
            $countAdmin
        );

    if ($view === null) {
        throw new RuntimeException(
            'Regex option Admin gagal.'
        );
    }

    if ($countAdmin === 1) {
        echo "[OK] Opsi Admin di filter dihapus." . PHP_EOL;
    } elseif (! str_contains($view, 'value="admin"')) {
        echo "[OK] Opsi Admin memang sudah tidak ada." . PHP_EOL;
    } else {
        throw new RuntimeException(
            "Opsi Admin ditemukan {$countAdmin} kali."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 3. Remove Viewer / Peninjau option
    |--------------------------------------------------------------------------
    */

    $viewerOptionPattern =
        '/\n[ \t]*<option\s*'
        . '\n[ \t]*value="viewer"\s*'
        . '\n[ \t]*@selected\(request\(\'role\'\) === \'viewer\'\)\s*'
        . '\n[ \t]*>\s*'
        . '\n[ \t]*Peninjau\s*'
        . '\n[ \t]*<\/option>\s*/';

    $countViewer = 0;

    $view =
        preg_replace(
            $viewerOptionPattern,
            "\n",
            $view,
            1,
            $countViewer
        );

    if ($view === null) {
        throw new RuntimeException(
            'Regex option Peninjau gagal.'
        );
    }

    if ($countViewer === 1) {
        echo "[OK] Opsi Peninjau di filter dihapus." . PHP_EOL;
    } elseif (! str_contains($view, 'value="viewer"')) {
        echo "[OK] Opsi Peninjau memang sudah tidak ada." . PHP_EOL;
    } else {
        throw new RuntimeException(
            "Opsi Peninjau ditemukan {$countViewer} kali."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 4. Clean role labels in table
    |--------------------------------------------------------------------------
    */

    $view =
        str_replace(
            [
                "                                            'admin' => 'Admin',\n",
                "                                            'viewer' => 'Peninjau',\n",
            ],
            '',
            $view
        );

    echo "[OK] Label legacy Admin/Peninjau dibersihkan dari tabel." . PHP_EOL;


    /*
    |--------------------------------------------------------------------------
    | 5. Guards
    |--------------------------------------------------------------------------
    */

    if (str_contains($view, 'value="admin"')) {
        throw new RuntimeException(
            'Guard gagal: option admin masih ada.'
        );
    }

    if (str_contains($view, 'value="viewer"')) {
        throw new RuntimeException(
            'Guard gagal: option viewer masih ada.'
        );
    }

    if (
        ! str_contains(
            $view,
            'value="super_admin"'
        )
        || ! str_contains(
            $view,
            'value="user"'
        )
    ) {
        throw new RuntimeException(
            'Guard gagal: Super Admin atau Pengguna hilang.'
        );
    }

    if (
        str_contains(
            $controller,
            "'admin',"
        )
        || str_contains(
            $controller,
            "'viewer',"
        )
    ) {
        throw new RuntimeException(
            'Guard gagal: role legacy masih ada di controller whitelist.'
        );
    }

    echo "[OK] Guard role hanya Super Admin + Pengguna PASS." . PHP_EOL;


    /*
    |--------------------------------------------------------------------------
    | Save
    |--------------------------------------------------------------------------
    */

    if ($controllerEol === "\r\n") {
        $controller =
            str_replace(
                "\n",
                "\r\n",
                $controller
            );
    }

    if ($viewEol === "\r\n") {
        $view =
            str_replace(
                "\n",
                "\r\n",
                $view
            );
    }

    file_put_contents(
        $controllerPath,
        $controller
    );

    file_put_contents(
        $viewPath,
        $view
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

    echo "[OK] PHP lint UserManagementController PASS." . PHP_EOL;

    echo PHP_EOL;
    echo "Backup:" . PHP_EOL;
    echo $backupDir . PHP_EOL;
    echo PHP_EOL;
    echo "USER ROLE CLEANUP: APPLIED" . PHP_EOL;
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
        . 'PATCH GAGAL: '
        . $exception->getMessage()
        . PHP_EOL
    );

    fwrite(
        STDERR,
        'Controller dan Blade otomatis dikembalikan ke kondisi sebelum patch.'
        . PHP_EOL
    );

    exit(1);
}
