$ErrorActionPreference = 'Stop'

$viewsRoot = Join-Path (Get-Location) 'resources\views'

if (-not (Test-Path $viewsRoot)) {
    throw "resources\views tidak ditemukan. Jalankan script ini dari root BMC-SmartFind."
}

$pattern = '<script\b(?![^>]*\bsrc\s*=)(?![^>]*\bnonce\s*=)([^>]*)>'
$replacement = '<script nonce="{{ $cspNonce }}"$1>'
$changed = @()

Get-ChildItem $viewsRoot -Recurse -Filter *.blade.php -File | ForEach-Object {
    $path = $_.FullName
    $source = Get-Content $path -Raw
    $updated = [regex]::Replace(
        $source,
        $pattern,
        $replacement,
        [System.Text.RegularExpressions.RegexOptions]::IgnoreCase
    )

    if ($updated -ne $source) {
        Set-Content -Path $path -Value $updated -Encoding utf8
        $changed += $path.Replace((Get-Location).Path + '\', '')
    }
}

Write-Host ''
Write-Host 'SH-5C2 CSP nonce migration selesai.' -ForegroundColor Green

if ($changed.Count -eq 0) {
    Write-Host 'Tidak ada inline <script> tanpa nonce yang perlu diubah.' -ForegroundColor Yellow
} else {
    Write-Host ("File diubah: {0}" -f $changed.Count) -ForegroundColor Cyan
    $changed | ForEach-Object { Write-Host " - $_" }
}

Write-Host ''
Write-Host 'Selanjutnya jalankan:' -ForegroundColor Cyan
Write-Host 'php artisan test --filter=CspInlineScriptAuditTest'
