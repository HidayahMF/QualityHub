$ErrorActionPreference = 'Stop'

$root = (Get-Location).Path
$viewsRoot = Join-Path $root 'resources\views'

if (-not (Test-Path $viewsRoot)) {
    throw "resources\views tidak ditemukan. Jalankan script dari root BMC-SmartFind."
}

$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backupRoot = Join-Path $root ("storage\backups\mojibake-fix-" + $timestamp)
$reportPath = Join-Path $root ("storage\logs\mojibake-fix-" + $timestamp + ".txt")

New-Item -ItemType Directory -Force -Path $backupRoot | Out-Null
New-Item -ItemType Directory -Force -Path (Split-Path $reportPath -Parent) | Out-Null

$utf8Strict = New-Object System.Text.UTF8Encoding($false, $true)
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

$cp1252Strict = [System.Text.Encoding]::GetEncoding(
    1252,
    [System.Text.EncoderFallback]::ExceptionFallback,
    [System.Text.DecoderFallback]::ExceptionFallback
)

# Common UTF-8 -> Windows-1252 mojibake markers.
# U+00C2, U+00C3, U+00E2, "f0 9f" style emoji corruption,
# UTF-8 BOM corruption, and replacement char.
$suspiciousPattern =
    '\u00C2|\u00C3|\u00E2|\u00F0\u0178|\u00EF\u00BB\u00BF|\uFFFD'

function Get-SuspiciousScore {
    param([string]$Value)

    if ([string]::IsNullOrEmpty($Value)) {
        return 0
    }

    return [regex]::Matches(
        $Value,
        $suspiciousPattern
    ).Count
}

function Get-LineNumber {
    param(
        [string]$Source,
        [int]$Offset
    )

    if ($Offset -le 0) {
        return 1
    }

    return (
        [regex]::Matches(
            $Source.Substring(0, $Offset),
            "`n"
        ).Count + 1
    )
}

$changedFiles = @()
$unresolved = @()
$totalRepairs = 0
$checkedFiles = 0

$files = Get-ChildItem $viewsRoot -Recurse -Filter *.blade.php -File

foreach ($file in $files) {
    $checkedFiles++

    $relative = $file.FullName.Replace($root + '\', '')
    $bytes = [System.IO.File]::ReadAllBytes($file.FullName)

    try {
        $text = $utf8Strict.GetString($bytes)
    }
    catch {
        throw "File bukan UTF-8 valid: $relative"
    }

    $originalText = $text

    # Remove only a leading BOM/U+FEFF.
    if ($text.Length -gt 0 -and $text[0] -eq [char]0xFEFF) {
        $text = $text.Substring(1)
    }

    $script:currentRepairCount = 0

    $text = [regex]::Replace(
        $text,
        '[^\x00-\x7F]+',
        [System.Text.RegularExpressions.MatchEvaluator]{
            param($match)

            $value = $match.Value
            $beforeScore = Get-SuspiciousScore $value

            if ($beforeScore -eq 0) {
                return $value
            }

            try {
                $legacyBytes = $cp1252Strict.GetBytes($value)
                $candidate = $utf8Strict.GetString($legacyBytes)
            }
            catch {
                return $value
            }

            $afterScore = Get-SuspiciousScore $candidate

            if ($afterScore -lt $beforeScore) {
                $script:currentRepairCount++
                return $candidate
            }

            return $value
        }
    )

    if ($text -ne $originalText) {
        $backupPath = Join-Path $backupRoot $relative
        $backupDir = Split-Path $backupPath -Parent

        New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
        Copy-Item -Path $file.FullName -Destination $backupPath -Force

        [System.IO.File]::WriteAllText(
            $file.FullName,
            $text,
            $utf8NoBom
        )

        $changedFiles += [pscustomobject]@{
            File = $relative
            Repairs = $script:currentRepairCount
        }

        $totalRepairs += $script:currentRepairCount
    }

    $matches = [regex]::Matches(
        $text,
        $suspiciousPattern
    )

    foreach ($match in $matches) {
        $line = Get-LineNumber -Source $text -Offset $match.Index
        $lineText = ($text -split "`r?`n")[$line - 1].Trim()

        $unresolved += [pscustomobject]@{
            File = $relative
            Line = $line
            Text = $lineText
        }
    }
}

$report = New-Object System.Collections.Generic.List[string]

$report.Add('BMC QualityHub - UTF-8 Mojibake Repair')
$report.Add(('Generated: {0}' -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss')))
$report.Add(('Files checked: {0}' -f $checkedFiles))
$report.Add(('Files changed: {0}' -f $changedFiles.Count))
$report.Add(('Recovered mojibake runs: {0}' -f $totalRepairs))
$report.Add(('Backup: {0}' -f $backupRoot))
$report.Add('')

$report.Add('CHANGED FILES')
$report.Add('-------------')

if ($changedFiles.Count -eq 0) {
    $report.Add('NONE')
}
else {
    foreach ($item in $changedFiles) {
        $report.Add(
            ('{0} | repaired runs: {1}' -f
                $item.File,
                $item.Repairs
            )
        )
    }
}

$report.Add('')
$report.Add('UNRESOLVED SUSPICIOUS TEXT')
$report.Add('--------------------------')

if ($unresolved.Count -eq 0) {
    $report.Add('NONE')
}
else {
    foreach ($item in $unresolved) {
        $report.Add(
            ('{0}:{1} => {2}' -f
                $item.File,
                $item.Line,
                $item.Text
            )
        )
    }
}

$report | Set-Content -Path $reportPath -Encoding ASCII

Write-Host ''
Write-Host 'BMC QualityHub - UTF-8 mojibake repair selesai.' -ForegroundColor Green
Write-Host ('Blade files checked       : {0}' -f $checkedFiles)
Write-Host ('Files changed             : {0}' -f $changedFiles.Count)
Write-Host ('Recovered mojibake runs   : {0}' -f $totalRepairs)
Write-Host ('Unresolved suspicious     : {0}' -f $unresolved.Count)
Write-Host ''
Write-Host ('Backup: {0}' -f $backupRoot) -ForegroundColor Cyan
Write-Host ('Report: {0}' -f $reportPath) -ForegroundColor Cyan

if ($changedFiles.Count -gt 0) {
    Write-Host ''
    Write-Host 'Changed files:' -ForegroundColor Cyan

    foreach ($item in $changedFiles) {
        Write-Host (
            ' - {0} ({1} repair)' -f
            $item.File,
            $item.Repairs
        )
    }
}

if ($unresolved.Count -gt 0) {
    Write-Host ''
    Write-Host 'WARNING: masih ada suspicious text. Baca report sebelum edit manual.' -ForegroundColor Yellow
}

Write-Host ''
Write-Host 'Next:' -ForegroundColor Cyan
Write-Host 'php artisan view:clear'
Write-Host 'php artisan optimize:clear'
Write-Host 'npm run build'
Write-Host ''
Write-Host 'Lalu Ctrl+F5 browser.'
