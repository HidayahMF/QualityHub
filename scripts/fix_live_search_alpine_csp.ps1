$ErrorActionPreference = 'Stop'

$root = (Get-Location).Path
$viewPath = Join-Path $root 'resources\views\documents\index.blade.php'

if (-not (Test-Path $viewPath)) {
    throw "File tidak ditemukan: $viewPath"
}

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$original = [System.IO.File]::ReadAllText($viewPath)
$content = $original

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backupPath = Join-Path $root ("storage\backups\index-live-search-before-csp-fix-" + $stamp + ".blade.php")
New-Item -ItemType Directory -Force -Path (Split-Path $backupPath -Parent) | Out-Null
[System.IO.File]::WriteAllText($backupPath, $original, $utf8NoBom)

Write-Host ''
Write-Host '=== BMC QualityHub Live Search CSP Fix ===' -ForegroundColor Cyan

# 1. CSP Alpine build cannot resolve arbitrary global functions from x-data.
$oldXData = 'x-data="documentSearch()"'
$newXData = 'x-data="documentSearch"'

$xDataCount = ([regex]::Matches($content, [regex]::Escape($oldXData))).Count

if ($xDataCount -eq 1) {
    $content = $content.Replace($oldXData, $newXData)
    Write-Host '[OK] x-data documentSearch() -> documentSearch' -ForegroundColor Green
}
elseif ($content.Contains($newXData)) {
    Write-Host '[OK] x-data already CSP-safe' -ForegroundColor Green
}
else {
    throw 'x-data="documentSearch()" tidak ditemukan. Tidak ada perubahan ditulis.'
}

# 2. Register existing function as an Alpine.data provider.
$registrationMarker = "Alpine.data('documentSearch', documentSearch)"

if (-not $content.Contains($registrationMarker)) {
    $functionIndex = $content.IndexOf('function documentSearch()')

    if ($functionIndex -lt 0) {
        throw 'function documentSearch() tidak ditemukan.'
    }

    $scriptCloseIndex = $content.IndexOf('</script>', $functionIndex)

    if ($scriptCloseIndex -lt 0) {
        throw 'Penutup </script> setelah documentSearch() tidak ditemukan.'
    }

    $registration = @'

        /*
        |--------------------------------------------------------------------------
        | CSP-SAFE ALPINE REGISTRATION
        |--------------------------------------------------------------------------
        |
        | @alpinejs/csp tidak membaca arbitrary global function dari x-data.
        | Register provider secara resmi sebelum Alpine.start().
        |
        */
        document.addEventListener('alpine:init', () => {
            Alpine.data('documentSearch', documentSearch);
        });

'@

    $content =
        $content.Substring(0, $scriptCloseIndex) +
        $registration +
        $content.Substring($scriptCloseIndex)

    Write-Host '[OK] Alpine.data documentSearch registration added' -ForegroundColor Green
}
else {
    Write-Host '[OK] Alpine.data registration already exists' -ForegroundColor Green
}

# 3. Guard: the inline block must be nonced after SH-5.
$functionIndex = $content.IndexOf('function documentSearch()')
$scriptOpenIndex = $content.LastIndexOf('<script', $functionIndex)

if ($scriptOpenIndex -lt 0) {
    throw 'Opening <script> untuk documentSearch tidak ditemukan.'
}

$scriptOpenEnd = $content.IndexOf('>', $scriptOpenIndex)

if ($scriptOpenEnd -lt 0) {
    throw 'Opening <script> tidak valid.'
}

$scriptOpenTag = $content.Substring(
    $scriptOpenIndex,
    $scriptOpenEnd - $scriptOpenIndex + 1
)

if ($scriptOpenTag -notmatch 'nonce\s*=') {
    throw "Script documentSearch tidak memiliki CSP nonce. Tag: $scriptOpenTag"
}

# 4. Write UTF-8 without BOM.
[System.IO.File]::WriteAllText(
    $viewPath,
    $content,
    $utf8NoBom
)

Write-Host ('[OK] Updated: ' + $viewPath) -ForegroundColor Green
Write-Host ('[OK] Backup : ' + $backupPath) -ForegroundColor Green

# 5. Clear compiled views/cache.
& php artisan view:clear
if ($LASTEXITCODE -ne 0) {
    throw 'php artisan view:clear gagal.'
}

& php artisan optimize:clear
if ($LASTEXITCODE -ne 0) {
    throw 'php artisan optimize:clear gagal.'
}

Write-Host ''
Write-Host 'LIVE SEARCH CSP FIX: APPLIED' -ForegroundColor Green
Write-Host 'Next: Ctrl+F5 /documents, lalu ketik 1-2 huruf.'
Write-Host 'Expected: hasil muncul setelah debounce ~350ms dan spinner berhenti.'
