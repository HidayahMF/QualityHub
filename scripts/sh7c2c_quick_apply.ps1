$ErrorActionPreference = 'Stop'

$root = (Get-Location).Path
$configPath = Join-Path $root 'config\smartfind_backup.php'
$servicePath = Join-Path $root 'app\Services\SmartFindBackupService.php'
$envPath = Join-Path $root '.env'

foreach ($path in @($configPath, $servicePath, $envPath)) {
    if (-not (Test-Path $path)) {
        throw "File tidak ditemukan: $path"
    }
}

$appPassword = [Environment]::GetEnvironmentVariable('BMC_APP_DB_PASSWORD')
$backupPassword = [Environment]::GetEnvironmentVariable('BMC_BACKUP_DB_PASSWORD')

if ([string]::IsNullOrWhiteSpace($appPassword)) {
    throw 'BMC_APP_DB_PASSWORD belum diexport ke environment.'
}

if ([string]::IsNullOrWhiteSpace($backupPassword)) {
    throw 'BMC_BACKUP_DB_PASSWORD belum diexport ke environment.'
}

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

$configOriginal = [System.IO.File]::ReadAllText($configPath)
$serviceOriginal = [System.IO.File]::ReadAllText($servicePath)
$envOriginal = [System.IO.File]::ReadAllText($envPath)

function Write-Utf8NoBom {
    param(
        [string]$Path,
        [string]$Content
    )

    [System.IO.File]::WriteAllText(
        $Path,
        $Content,
        $utf8NoBom
    )
}

function Set-DotEnvValue {
    param(
        [string]$Content,
        [string]$Key,
        [string]$Value
    )

    $escapedKey = [regex]::Escape($Key)
    $line = $Key + '=' + $Value
    $pattern = '(?m)^' + $escapedKey + '=.*$'

    if ([regex]::IsMatch($Content, $pattern)) {
        return [regex]::Replace(
            $Content,
            $pattern,
            [System.Text.RegularExpressions.MatchEvaluator]{
                param($m)
                return $line
            },
            1
        )
    }

    if (
        $Content.Length -gt 0 -and
        -not $Content.EndsWith("`n")
    ) {
        $Content += [Environment]::NewLine
    }

    return $Content + $line + [Environment]::NewLine
}

try {
    Write-Host ''
    Write-Host '=== SH-7C2C QUICK APPLY ===' -ForegroundColor Cyan

    # ------------------------------------------------------------
    # 1. Config: dedicated backup username/password
    # ------------------------------------------------------------
    $config = $configOriginal

    if ($config -notmatch "'database_username'\s*=>") {
        $needle = @'
    'mysqldump_binary' =>
        env(
            'SMARTFIND_MYSQLDUMP_PATH'
        ),

    'database_timeout_seconds' =>
'@

        $replacement = @'
    'mysqldump_binary' =>
        env(
            'SMARTFIND_MYSQLDUMP_PATH'
        ),

    /*
    |--------------------------------------------------------------------------
    | Dedicated Backup Database Credentials
    |--------------------------------------------------------------------------
    |
    | Jangan pakai credential runtime Laravel untuk mysqldump.
    | Akun backup hanya memiliki privilege yang diperlukan untuk logical backup.
    |
    */

    'database_username' =>
        env(
            'SMARTFIND_BACKUP_DB_USERNAME'
        ),

    'database_password' =>
        env(
            'SMARTFIND_BACKUP_DB_PASSWORD'
        ),

    'database_timeout_seconds' =>
'@

        if (-not $config.Contains($needle)) {
            throw 'Marker config smartfind_backup.php tidak ditemukan. Patch dibatalkan.'
        }

        $config = $config.Replace(
            $needle,
            $replacement
        )
    }

    Write-Utf8NoBom -Path $configPath -Content $config
    Write-Host '[OK] config\smartfind_backup.php' -ForegroundColor Green

    # ------------------------------------------------------------
    # 2. Service: mysqldump uses dedicated backup credentials
    # ------------------------------------------------------------
    $service = $serviceOriginal

    if ($service -notmatch "smartfind_backup\.database_username") {
        $oldBlock = @'
        $binary =
            $this->resolveMysqldumpBinary();

        $command = [
            $binary,
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--events',
            '--default-character-set=utf8mb4',
            '--host='
                . (string) (
                    $config[
                        'host'
                    ]
                    ?? '127.0.0.1'
                ),
            '--port='
                . (string) (
                    $config[
                        'port'
                    ]
                    ?? '3306'
                ),
            '--user='
                . (string) (
                    $config[
                        'username'
                    ]
                    ?? ''
                ),
            '--result-file='
                . $resultFile,
            $database,
        ];

        $password =
            (string) (
                $config[
                    'password'
                ]
                ?? ''
            );
'@

        $newBlock = @'
        $binary =
            $this->resolveMysqldumpBinary();

        $backupUsername =
            trim(
                (string) config(
                    'smartfind_backup.database_username',
                    ''
                )
            );

        $backupPassword =
            (string) config(
                'smartfind_backup.database_password',
                ''
            );

        if ($backupUsername === '') {
            throw new RuntimeException(
                'SMARTFIND_BACKUP_DB_USERNAME belum dikonfigurasi.'
            );
        }

        if ($backupPassword === '') {
            throw new RuntimeException(
                'SMARTFIND_BACKUP_DB_PASSWORD belum dikonfigurasi.'
            );
        }

        $command = [
            $binary,
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--events',
            '--default-character-set=utf8mb4',
            '--host='
                . (string) (
                    $config[
                        'host'
                    ]
                    ?? '127.0.0.1'
                ),
            '--port='
                . (string) (
                    $config[
                        'port'
                    ]
                    ?? '3306'
                ),
            '--user='
                . $backupUsername,
            '--result-file='
                . $resultFile,
            $database,
        ];

        $password =
            $backupPassword;
'@

        if (-not $service.Contains($oldBlock)) {
            throw 'Marker dumpDatabase() tidak ditemukan. Patch dibatalkan.'
        }

        $service = $service.Replace(
            $oldBlock,
            $newBlock
        )
    }

    Write-Utf8NoBom -Path $servicePath -Content $service
    Write-Host '[OK] app\Services\SmartFindBackupService.php' -ForegroundColor Green

    # ------------------------------------------------------------
    # 3. PHP syntax before touching .env
    # ------------------------------------------------------------
    & php -l $configPath
    if ($LASTEXITCODE -ne 0) {
        throw 'PHP lint config gagal.'
    }

    & php -l $servicePath
    if ($LASTEXITCODE -ne 0) {
        throw 'PHP lint service gagal.'
    }

    # ------------------------------------------------------------
    # 4. .env: runtime app user + dedicated backup user
    # ------------------------------------------------------------
    $envText = $envOriginal

    $envText = Set-DotEnvValue `
        -Content $envText `
        -Key 'DB_USERNAME' `
        -Value 'bmc_smartfind_app'

    $envText = Set-DotEnvValue `
        -Content $envText `
        -Key 'DB_PASSWORD' `
        -Value $appPassword

    $envText = Set-DotEnvValue `
        -Content $envText `
        -Key 'SMARTFIND_BACKUP_DB_USERNAME' `
        -Value 'bmc_smartfind_backup'

    $envText = Set-DotEnvValue `
        -Content $envText `
        -Key 'SMARTFIND_BACKUP_DB_PASSWORD' `
        -Value $backupPassword

    Write-Utf8NoBom -Path $envPath -Content $envText
    Write-Host '[OK] .env credential split applied' -ForegroundColor Green

    # ------------------------------------------------------------
    # 5. Clear Laravel caches
    # ------------------------------------------------------------
    & php artisan optimize:clear
    if ($LASTEXITCODE -ne 0) {
        throw 'optimize:clear gagal.'
    }

    # ------------------------------------------------------------
    # 6. Confirm config WITHOUT printing passwords
    # ------------------------------------------------------------
    Write-Host ''
    Write-Host '=== CONFIG CHECK ===' -ForegroundColor Cyan

    & php artisan tinker --execute="dump(['runtime_user' => config('database.connections.mysql.username'), 'backup_user' => config('smartfind_backup.database_username'), 'backup_root' => config('smartfind_backup.root')]);"
    if ($LASTEXITCODE -ne 0) {
        throw 'Config check gagal.'
    }

    # ------------------------------------------------------------
    # 7. Runtime DB identity check
    # ------------------------------------------------------------
    Write-Host ''
    Write-Host '=== RUNTIME IDENTITY CHECK ===' -ForegroundColor Cyan

    & php artisan tinker --execute="dump(DB::selectOne('SELECT CURRENT_USER() AS privilege_user, DATABASE() AS db'));"
    if ($LASTEXITCODE -ne 0) {
        throw 'Runtime DB connection gagal.'
    }

    # ------------------------------------------------------------
    # 8. Real backup + verify
    # ------------------------------------------------------------
    Write-Host ''
    Write-Host '=== REAL BACKUP TEST ===' -ForegroundColor Cyan

    $backupOutput = (& php artisan smartfind:backup 2>&1 | Out-String)
    Write-Host $backupOutput

    if ($LASTEXITCODE -ne 0) {
        throw 'smartfind:backup gagal.'
    }

    $match = [regex]::Match(
        $backupOutput,
        'smartfind-\d{8}-\d{6}-[a-f0-9]{8}'
    )

    if (-not $match.Success) {
        throw 'Nama backup tidak ditemukan dari output.'
    }

    $backupName = $match.Value

    Write-Host ''
    Write-Host '=== BACKUP VERIFY ===' -ForegroundColor Cyan

    & php artisan smartfind:backup-verify $backupName
    if ($LASTEXITCODE -ne 0) {
        throw 'smartfind:backup-verify gagal.'
    }

    Write-Host ''
    Write-Host 'SH-7C2C + SH-7C2D QUICK APPLY: PASS' -ForegroundColor Green
    Write-Host 'Runtime DB user : bmc_smartfind_app'
    Write-Host 'Backup DB user  : bmc_smartfind_backup'
    Write-Host ('Verified backup : ' + $backupName)
}
catch {
    Write-Host ''
    Write-Host 'SH-7C2C QUICK APPLY: FAIL - rolling back files.' -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red

    Write-Utf8NoBom -Path $configPath -Content $configOriginal
    Write-Utf8NoBom -Path $servicePath -Content $serviceOriginal
    Write-Utf8NoBom -Path $envPath -Content $envOriginal

    try {
        & php artisan optimize:clear | Out-Host
    }
    catch {
        # Ignore rollback cache-clear errors; original files are restored.
    }

    Write-Host 'Original config/service/.env restored.' -ForegroundColor Yellow
    exit 1
}
