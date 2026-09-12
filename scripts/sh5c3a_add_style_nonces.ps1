$ErrorActionPreference = 'Stop'

$viewsRoot = Join-Path (Get-Location) 'resources\views'

if (-not (Test-Path $viewsRoot)) {
    throw "resources\views tidak ditemukan. Jalankan script ini dari root BMC-SmartFind."
}

$pattern = '<style\b(?![^>]*\bnonce\s*=)([^>]*)>'
$replacement = '<style nonce="{{ $cspNonce }}"$1>'
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
Write-Host 'SH-5C3A CSP style nonce migration selesai.' -ForegroundColor Green

if ($changed.Count -eq 0) {
    Write-Host 'Tidak ada inline <style> tanpa nonce yang perlu diubah.' -ForegroundColor Yellow
} else {
    Write-Host ("File diubah: {0}" -f $changed.Count) -ForegroundColor Cyan
    $changed | ForEach-Object { Write-Host " - $_" }
}

Write-Host ''
Write-Host 'Selanjutnya jalankan:' -ForegroundColor Cyan
Write-Host 'php artisan test --filter=CspInlineStyleAuditTest'
