$ErrorActionPreference = 'Stop'

$root = (Get-Location).Path
$viewsRoot = Join-Path $root 'resources\views'

if (-not (Test-Path $viewsRoot)) {
    throw "resources\views tidak ditemukan. Jalankan dari root BMC-SmartFind."
}

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$changed = @()
$checked = 0

Get-ChildItem $viewsRoot -Recurse -Filter *.blade.php -File | ForEach-Object {
    $checked++

    $path = $_.FullName
    $bytes = [System.IO.File]::ReadAllBytes($path)

    $hasUtf8Bom =
        $bytes.Length -ge 3 `
        -and $bytes[0] -eq 0xEF `
        -and $bytes[1] -eq 0xBB `
        -and $bytes[2] -eq 0xBF

    if ($hasUtf8Bom) {
        $text = [System.IO.File]::ReadAllText(
            $path,
            [System.Text.Encoding]::UTF8
        )

        # Safety: remove only leading U+FEFF/BOM.
        $text = $text.TrimStart([char]0xFEFF)

        [System.IO.File]::WriteAllText(
            $path,
            $text,
            $utf8NoBom
        )

        $changed += $path.Replace($root + '\', '')
    }
}

Write-Host ''
Write-Host 'BMC QualityHub - Blade UTF-8 BOM cleanup selesai.' -ForegroundColor Green
Write-Host ("Blade files checked : {0}" -f $checked)
Write-Host ("BOM files fixed     : {0}" -f $changed.Count)

if ($changed.Count -gt 0) {
    Write-Host ''
    $changed | ForEach-Object {
        Write-Host (" - {0}" -f $_)
    }
}

Write-Host ''
Write-Host 'Verifikasi guest.blade.php:' -ForegroundColor Cyan
$guest = Join-Path $viewsRoot 'layouts\guest.blade.php'

if (Test-Path $guest) {
    $guestBytes = [System.IO.File]::ReadAllBytes($guest)
    $guestHasBom =
        $guestBytes.Length -ge 3 `
        -and $guestBytes[0] -eq 0xEF `
        -and $guestBytes[1] -eq 0xBB `
        -and $guestBytes[2] -eq 0xBF

    Write-Host ("guest.blade.php BOM : {0}" -f $guestHasBom)
}

Write-Host ''
Write-Host 'Next:' -ForegroundColor Cyan
Write-Host 'php artisan view:clear'
Write-Host 'php artisan optimize:clear'
Write-Host 'npm run build'
