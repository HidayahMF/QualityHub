$ErrorActionPreference = 'Stop'

$projectRoot = (Get-Location).Path
$viewsRoot = Join-Path $projectRoot 'resources\views'
$jsRoot = Join-Path $projectRoot 'resources\js'
$reportPath = Join-Path $projectRoot 'storage\logs\sh5c3b-style-attribute-inventory.txt'

if (-not (Test-Path $viewsRoot)) {
    throw "resources\views tidak ditemukan. Jalankan script dari root BMC-SmartFind."
}

$report = New-Object System.Collections.Generic.List[string]

function Add-Section {
    param([string]$Title)

    $report.Add('')
    $report.Add(('=' * 90))
    $report.Add($Title)
    $report.Add(('=' * 90))
}

function Get-LineNumber {
    param(
        [string]$Source,
        [int]$Offset
    )

    if ($Offset -le 0) {
        return 1
    }

    return ([regex]::Matches(
        $Source.Substring(0, $Offset),
        "`n"
    ).Count + 1)
}

function Normalize-Preview {
    param([string]$Text)

    $value = [regex]::Replace(
        $Text,
        '\s+',
        ' '
    ).Trim()

    if ($value.Length -gt 180) {
        return $value.Substring(0, 180) + '...'
    }

    return $value
}

$nativeStyleFindings = @()
$alpineStyleFindings = @()
$jsBlockedStyleFindings = @()
$jsAllowedStyleFindings = @()

$bladeFiles = Get-ChildItem $viewsRoot -Recurse -Filter *.blade.php -File

foreach ($file in $bladeFiles) {
    $source = Get-Content $file.FullName -Raw
    $relative = $file.FullName.Replace($projectRoot + '\', '')

    # Native HTML style="..."
    $nativePattern = '\bstyle\s*=\s*(["''])(.*?)\1'
    $matches = [regex]::Matches(
        $source,
        $nativePattern,
        [System.Text.RegularExpressions.RegexOptions]::IgnoreCase `
            -bor [System.Text.RegularExpressions.RegexOptions]::Singleline
    )

    foreach ($match in $matches) {
        # Hindari false positive dari x-bind:style / :style.
        $prefixStart = [Math]::Max(0, $match.Index - 20)
        $prefixLength = $match.Index - $prefixStart
        $prefix = $source.Substring($prefixStart, $prefixLength)

        if ($prefix -match '(x-bind:|:)\s*$') {
            continue
        }

        $value = $match.Groups[2].Value
        $line = Get-LineNumber -Source $source -Offset $match.Index
        $dynamic = $value -match '\{\{|\{!!|@\w|\$[A-Za-z_]|<\?php'

        $nativeStyleFindings += [pscustomobject]@{
            File = $relative
            Line = $line
            Type = $(if ($dynamic) { 'DYNAMIC' } else { 'STATIC' })
            Preview = Normalize-Preview $value
        }
    }

    # Alpine style binding: x-bind:style="..." atau :style="..."
    $alpinePattern = '(?:x-bind:style|(?<![\w-]):style)\s*=\s*(["''])(.*?)\1'
    $alpineMatches = [regex]::Matches(
        $source,
        $alpinePattern,
        [System.Text.RegularExpressions.RegexOptions]::IgnoreCase `
            -bor [System.Text.RegularExpressions.RegexOptions]::Singleline
    )

    foreach ($match in $alpineMatches) {
        $line = Get-LineNumber -Source $source -Offset $match.Index

        $alpineStyleFindings += [pscustomobject]@{
            File = $relative
            Line = $line
            Type = 'ALPINE_STYLE_BINDING'
            Preview = Normalize-Preview $match.Groups[2].Value
        }
    }
}

$sourceFiles = @()

if (Test-Path $jsRoot) {
    $sourceFiles += Get-ChildItem $jsRoot -Recurse -File |
        Where-Object { $_.Extension -in @('.js', '.ts') }
}

# Blade juga bisa punya JS inline.
$sourceFiles += $bladeFiles

foreach ($file in $sourceFiles) {
    $source = Get-Content $file.FullName -Raw
    $relative = $file.FullName.Replace($projectRoot + '\', '')

    # DIBLOKIR oleh style-src-attr 'none'
    $blockedPatterns = @(
        @{
            Name = 'setAttribute(style)'
            Regex = '\.setAttribute\s*\(\s*["'']style["'']'
        },
        @{
            Name = 'style.cssText'
            Regex = '\.style\.cssText\s*='
        }
    )

    foreach ($entry in $blockedPatterns) {
        $matches = [regex]::Matches(
            $source,
            $entry.Regex,
            [System.Text.RegularExpressions.RegexOptions]::IgnoreCase
        )

        foreach ($match in $matches) {
            $line = Get-LineNumber -Source $source -Offset $match.Index

            $lineStart = $source.LastIndexOf("`n", [Math]::Max(0, $match.Index - 1))
            if ($lineStart -lt 0) { $lineStart = 0 } else { $lineStart += 1 }

            $lineEnd = $source.IndexOf("`n", $match.Index)
            if ($lineEnd -lt 0) { $lineEnd = $source.Length }

            $preview = $source.Substring(
                $lineStart,
                $lineEnd - $lineStart
            )

            $jsBlockedStyleFindings += [pscustomobject]@{
                File = $relative
                Line = $line
                Type = $entry.Name
                Preview = Normalize-Preview $preview
            }
        }
    }

    # DIIZINKAN: direct property assignment, contoh element.style.width = ...
    $allowedMatches = [regex]::Matches(
        $source,
        '\.style\.[A-Za-z][A-Za-z0-9]*\s*=',
        [System.Text.RegularExpressions.RegexOptions]::IgnoreCase
    )

    foreach ($match in $allowedMatches) {
        $line = Get-LineNumber -Source $source -Offset $match.Index

        $lineStart = $source.LastIndexOf("`n", [Math]::Max(0, $match.Index - 1))
        if ($lineStart -lt 0) { $lineStart = 0 } else { $lineStart += 1 }

        $lineEnd = $source.IndexOf("`n", $match.Index)
        if ($lineEnd -lt 0) { $lineEnd = $source.Length }

        $preview = $source.Substring(
            $lineStart,
            $lineEnd - $lineStart
        )

        $jsAllowedStyleFindings += [pscustomobject]@{
            File = $relative
            Line = $line
            Type = 'DIRECT_STYLE_PROPERTY'
            Preview = Normalize-Preview $preview
        }
    }
}

$report.Add('BMC QualityHub — SH-5C3B Style Attribute Inventory')
$report.Add(('Generated: {0}' -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss')))
$report.Add(('Project: {0}' -f $projectRoot))

Add-Section 'A. NATIVE HTML style="" — HARUS DIHAPUS sebelum style-src-attr none'

if ($nativeStyleFindings.Count -eq 0) {
    $report.Add('NONE')
} else {
    foreach ($item in $nativeStyleFindings) {
        $report.Add(
            ('[{0}] {1}:{2} => {3}' -f
                $item.Type,
                $item.File,
                $item.Line,
                $item.Preview
            )
        )
    }
}

Add-Section 'B. Alpine x-bind:style / :style — HARUS DIREVIEW'

if ($alpineStyleFindings.Count -eq 0) {
    $report.Add('NONE')
} else {
    foreach ($item in $alpineStyleFindings) {
        $report.Add(
            ('[{0}] {1}:{2} => {3}' -f
                $item.Type,
                $item.File,
                $item.Line,
                $item.Preview
            )
        )
    }
}

Add-Section 'C. JS setAttribute("style") / style.cssText — HARUS DIHAPUS/DIUBAH'

if ($jsBlockedStyleFindings.Count -eq 0) {
    $report.Add('NONE')
} else {
    foreach ($item in $jsBlockedStyleFindings) {
        $report.Add(
            ('[{0}] {1}:{2} => {3}' -f
                $item.Type,
                $item.File,
                $item.Line,
                $item.Preview
            )
        )
    }
}

Add-Section 'D. Direct element.style.property assignment — INFO / CSP-SAFE'

if ($jsAllowedStyleFindings.Count -eq 0) {
    $report.Add('NONE')
} else {
    foreach ($item in $jsAllowedStyleFindings) {
        $report.Add(
            ('[{0}] {1}:{2} => {3}' -f
                $item.Type,
                $item.File,
                $item.Line,
                $item.Preview
            )
        )
    }
}

Add-Section 'SUMMARY'

$report.Add(('Native style attributes : {0}' -f $nativeStyleFindings.Count))
$report.Add(('Alpine style bindings    : {0}' -f $alpineStyleFindings.Count))
$report.Add(('Blocked JS style writes  : {0}' -f $jsBlockedStyleFindings.Count))
$report.Add(('Safe direct style writes : {0}' -f $jsAllowedStyleFindings.Count))

$blocking =
    $nativeStyleFindings.Count `
    + $alpineStyleFindings.Count `
    + $jsBlockedStyleFindings.Count

$report.Add(('TOTAL BLOCKING FINDINGS  : {0}' -f $blocking))

$report | Set-Content -Path $reportPath -Encoding utf8

Write-Host ''
Write-Host 'SH-5C3B inventory selesai.' -ForegroundColor Green
Write-Host ('Native style attributes : {0}' -f $nativeStyleFindings.Count)
Write-Host ('Alpine style bindings    : {0}' -f $alpineStyleFindings.Count)
Write-Host ('Blocked JS style writes  : {0}' -f $jsBlockedStyleFindings.Count)
Write-Host ('Safe direct style writes : {0}' -f $jsAllowedStyleFindings.Count)
Write-Host ('TOTAL BLOCKING FINDINGS  : {0}' -f $blocking)
Write-Host ''
Write-Host ('Report: {0}' -f $reportPath) -ForegroundColor Cyan
Write-Host ''
Write-Host 'Tampilkan report dengan:' -ForegroundColor Cyan
Write-Host 'Get-Content .\storage\logs\sh5c3b-style-attribute-inventory.txt'
