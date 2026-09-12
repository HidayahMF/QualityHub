$ErrorActionPreference = 'Stop'

$root = (Get-Location).Path

$configPath = Join-Path $root 'config\smartfind_security.php'
$styleAuditPath = Join-Path $root 'tests\Feature\CspInlineStyleAuditTest.php'
$tighteningPath = Join-Path $root 'tests\Feature\CspTighteningTest.php'
$compatPath = Join-Path $root 'tests\Feature\CspCompatibilityModeTest.php'
$envPath = Join-Path $root '.env'

$required = @(
    $configPath,
    $styleAuditPath,
    $tighteningPath,
    $compatPath
)

foreach ($path in $required) {
    if (-not (Test-Path $path)) {
        throw "Required file not found: $path"
    }
}

function Read-Text {
    param([string]$Path)
    return [System.IO.File]::ReadAllText($Path)
}

function Write-Text {
    param(
        [string]$Path,
        [string]$Content
    )

    $utf8 = New-Object System.Text.UTF8Encoding($false)

    [System.IO.File]::WriteAllText(
        $Path,
        $Content,
        $utf8
    )
}

function Replace-ExactCount {
    param(
        [string]$Path,
        [string]$Old,
        [string]$New,
        [int]$Expected,
        [string]$Label
    )

    $source = Read-Text $Path
    $count = ([regex]::Matches(
        $source,
        [regex]::Escape($Old)
    )).Count

    if ($count -eq 0 -and $source.Contains($New)) {
        Write-Host "[SKIP] $Label already applied"
        return
    }

    if ($count -ne $Expected) {
        throw "${Label}: expected $Expected old match(es), found $count. STOP."
    }

    $updated = $source.Replace(
        $Old,
        $New
    )

    Write-Text -Path $Path -Content $updated
    Write-Host "[OK] $Label"
}

Write-Host ''
Write-Host 'SH-5C3B3 FINAL - remove last unsafe-inline' -ForegroundColor Cyan
Write-Host ''

# ------------------------------------------------------------------
# Guard: do not silently fight a custom CSP policy from .env
# ------------------------------------------------------------------

if (Test-Path $envPath) {
    $envSource = Read-Text $envPath

    $override = [regex]::Match(
        $envSource,
        '(?m)^\s*SMARTFIND_CSP_POLICY\s*=\s*(.+?)\s*$'
    )

    if ($override.Success) {
        $value = $override.Groups[1].Value.Trim()

        if (($value -ne '') -and ($value -ne 'null')) {
            throw "SMARTFIND_CSP_POLICY override exists in .env. STOP so config default is not silently ignored."
        }
    }
}

Write-Host '[OK] No active SMARTFIND_CSP_POLICY override found'

# ------------------------------------------------------------------
# Final CSP policy
# ------------------------------------------------------------------

Replace-ExactCount `
    -Path $configPath `
    -Old '. "style-src-attr ''unsafe-inline''; "' `
    -New '. "style-src-attr ''none''; "' `
    -Expected 1 `
    -Label 'CSP style-src-attr unsafe-inline -> none'

# Update stage documentation only if exact old wording still exists.
$configSource = Read-Text $configPath

$configSource = $configSource.Replace(
    'Content Security Policy - Tightening Stage 3A',
    'Content Security Policy - Tightening Stage 3B Final'
)

$configSource = $configSource.Replace(
    'SH-5C3A menghapus unsafe-inline dari style-src umum.',
    'SH-5C3B menyelesaikan penghapusan unsafe-inline dari seluruh CSP QualityHub.'
)

$configSource = $configSource.Replace(
    '| style="" attribute belum dihapus pada tahap ini karena beberapa tampilan' + "`r`n" +
    '    | analytics masih memakai nilai CSS dinamis. Exception tersebut dipersempit' + "`r`n" +
    '    | hanya ke style-src-attr ''unsafe-inline'', bukan ke seluruh style-src.',
    '| Native style attributes sudah dimigrasikan ke class/data attribute dan' + "`r`n" +
    '    | direct element.style.property yang tervalidasi. style-src-attr sekarang' + "`r`n" +
    '    | ditutup penuh dengan ''none''.'
)

$configSource = $configSource.Replace(
    '| style="" attribute belum dihapus pada tahap ini karena beberapa tampilan' + "`n" +
    '    | analytics masih memakai nilai CSS dinamis. Exception tersebut dipersempit' + "`n" +
    '    | hanya ke style-src-attr ''unsafe-inline'', bukan ke seluruh style-src.',
    '| Native style attributes sudah dimigrasikan ke class/data attribute dan' + "`n" +
    '    | direct element.style.property yang tervalidasi. style-src-attr sekarang' + "`n" +
    '    | ditutup penuh dengan ''none''.'
)

Write-Text -Path $configPath -Content $configSource

# ------------------------------------------------------------------
# Existing CSP tests
# ------------------------------------------------------------------

foreach ($testPath in @(
    $styleAuditPath,
    $tighteningPath,
    $compatPath
)) {
    Replace-ExactCount `
        -Path $testPath `
        -Old '"style-src-attr ''unsafe-inline''"' `
        -New '"style-src-attr ''none''"' `
        -Expected 1 `
        -Label ("Update CSP expectation in " + [System.IO.Path]::GetFileName($testPath))
}

# Optional method naming cleanup without touching user's config-helper hotfix.
$styleAuditSource = Read-Text $styleAuditPath

$styleAuditSource = $styleAuditSource.Replace(
    'test_style_attributes_are_isolated_to_style_src_attr_exception',
    'test_style_attributes_are_fully_blocked_by_csp'
)

Write-Text -Path $styleAuditPath -Content $styleAuditSource

Write-Host ''
Write-Host 'SH-5C3B3 patch complete.' -ForegroundColor Green
Write-Host 'CSP now targets style-src-attr none.' -ForegroundColor Green
Write-Host ''
Write-Host 'Next:' -ForegroundColor Cyan
Write-Host 'php artisan optimize:clear'
Write-Host 'php artisan tinker --execute="dump(config(''smartfind_security.csp.policy''));"'
Write-Host 'php artisan test --filter=CspNoUnsafeDirectivesTest'
Write-Host 'php artisan test'
