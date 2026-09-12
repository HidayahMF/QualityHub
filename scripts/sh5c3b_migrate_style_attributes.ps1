$ErrorActionPreference = 'Stop'

$root = (Get-Location).Path

$required = @(
    'resources\js\app.js',
    'resources\css\app.css',
    'resources\views\dashboard.blade.php',
    'resources\views\welcome.blade.php',
    'resources\views\components\modal.blade.php',
    'resources\views\documents\index.blade.php',
    'resources\views\documents\partials\analytics-dashboard.blade.php'
)

foreach ($relative in $required) {
    if (-not (Test-Path (Join-Path $root $relative))) {
        throw "File wajib tidak ditemukan: $relative"
    }
}

function Write-Utf8NoBom {
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

function Replace-RegexExpected {
    param(
        [string]$Path,
        [string]$Pattern,
        [string]$Replacement,
        [int]$Expected,
        [string]$Label,
        [System.Text.RegularExpressions.RegexOptions]$Options =
            [System.Text.RegularExpressions.RegexOptions]::IgnoreCase
    )

    $source = [System.IO.File]::ReadAllText($Path)
    $regex = New-Object System.Text.RegularExpressions.Regex(
        $Pattern,
        $Options
    )

    $matches = $regex.Matches($source)

    if ($matches.Count -ne $Expected) {
        throw "${Label}: expected $Expected match(es), found $($matches.Count). STOP - source berbeda dari inventory."
    }

    $updated = $regex.Replace(
        $source,
        $Replacement
    )

    Write-Utf8NoBom -Path $Path -Content $updated
}

function Replace-LiteralExpected {
    param(
        [string]$Path,
        [string]$Old,
        [string]$New,
        [int]$Expected,
        [string]$Label
    )

    $source = [System.IO.File]::ReadAllText($Path)
    $count = ([regex]::Matches(
        $source,
        [regex]::Escape($Old)
    )).Count

    if ($count -ne $Expected) {
        throw "${Label}: expected $Expected match(es), found $count. STOP - source berbeda dari inventory."
    }

    $updated = $source.Replace(
        $Old,
        $New
    )

    Write-Utf8NoBom -Path $Path -Content $updated
}

Write-Host ''
Write-Host 'SH-5C3B2 - CSP-safe style attribute migration' -ForegroundColor Cyan
Write-Host 'Mode: fail-fast / exact inventory' -ForegroundColor DarkGray
Write-Host ''

# ------------------------------------------------------------------
# 1. Global x-cloak support in external stylesheet
# ------------------------------------------------------------------

$appCss = Join-Path $root 'resources\css\app.css'
$appCssSource = [System.IO.File]::ReadAllText($appCss)

if ($appCssSource -notmatch '(?m)^\s*\[x-cloak\]\s*\{') {
    $appCssSource += @'

/*
|--------------------------------------------------------------------------
| QualityHub - Alpine cloak (CSP-safe)
|--------------------------------------------------------------------------
*/
[x-cloak] {
    display: none !important;
}
'@

    Write-Utf8NoBom -Path $appCss -Content $appCssSource
    Write-Host '[OK] Added global [x-cloak] rule to resources/css/app.css'
} else {
    Write-Host '[OK] Global [x-cloak] rule already exists'
}

# ------------------------------------------------------------------
# 2. Replace initial style="display:none" with x-cloak
#    Inventory target: 11 static + 1 dynamic modal = 12
# ------------------------------------------------------------------

$bladeFiles = Get-ChildItem `
    (Join-Path $root 'resources\views') `
    -Recurse `
    -Filter *.blade.php `
    -File

$staticHiddenCount = 0

foreach ($file in $bladeFiles) {
    $source = [System.IO.File]::ReadAllText($file.FullName)

    $regex = New-Object System.Text.RegularExpressions.Regex(
        'style\s*=\s*"display\s*:\s*none\s*;?"',
        [System.Text.RegularExpressions.RegexOptions]::IgnoreCase
    )

    $count = $regex.Matches($source).Count

    if ($count -gt 0) {
        $staticHiddenCount += $count
        $source = $regex.Replace(
            $source,
            'x-cloak'
        )

        Write-Utf8NoBom -Path $file.FullName -Content $source
    }
}

if ($staticHiddenCount -ne 11) {
    throw "Static display:none migration: expected 11, found $staticHiddenCount. STOP."
}

Write-Host '[OK] Replaced 11 static display:none attributes with x-cloak'

$modal = Join-Path $root 'resources\views\components\modal.blade.php'

Replace-RegexExpected `
    -Path $modal `
    -Pattern 'style\s*=\s*"display\s*:\s*\{\{\s*\$show\s*\?\s*''block''\s*:\s*''none''\s*\}\}\s*;?"' `
    -Replacement 'x-cloak' `
    -Expected 1 `
    -Label 'Dynamic modal display'

Write-Host '[OK] Replaced dynamic modal display style with x-cloak'

# ------------------------------------------------------------------
# 3. Welcome blend modes -> constrained data attributes
# ------------------------------------------------------------------

$welcome = Join-Path $root 'resources\views\welcome.blade.php'

Replace-RegexExpected `
    -Path $welcome `
    -Pattern 'style\s*=\s*"mix-blend-mode\s*:\s*hard-light\s*;?"' `
    -Replacement 'data-qh-blend="hard-light"' `
    -Expected 6 `
    -Label 'Welcome hard-light styles'

Replace-RegexExpected `
    -Path $welcome `
    -Pattern 'style\s*=\s*"mix-blend-mode\s*:\s*plus-darker\s*;?"' `
    -Replacement 'data-qh-blend="plus-darker"' `
    -Expected 1 `
    -Label 'Welcome plus-darker style'

Write-Host '[OK] Migrated 7 welcome blend-mode attributes'

# ------------------------------------------------------------------
# 4. Document table col widths -> data attributes
# ------------------------------------------------------------------

$documentsIndex = Join-Path $root 'resources\views\documents\index.blade.php'

$colSource = [System.IO.File]::ReadAllText($documentsIndex)

$colRegex = New-Object System.Text.RegularExpressions.Regex(
    '<col\s+style\s*=\s*"width\s*:\s*(27|12|18|10|14|7)%"(?:\s*/?)>',
    [System.Text.RegularExpressions.RegexOptions]::IgnoreCase
)

$colMatches = $colRegex.Matches($colSource)

if ($colMatches.Count -ne 7) {
    throw "Document col widths: expected 7, found $($colMatches.Count). STOP."
}

$colSource = $colRegex.Replace(
    $colSource,
    '<col data-qh-width="$1">'
)

Write-Utf8NoBom -Path $documentsIndex -Content $colSource
Write-Host '[OK] Migrated 7 document table column widths'

# ------------------------------------------------------------------
# 5. Server-rendered analytics percentage widths
# ------------------------------------------------------------------

$analytics = Join-Path $root 'resources\views\documents\partials\analytics-dashboard.blade.php'

Replace-RegexExpected `
    -Path $analytics `
    -Pattern 'style\s*=\s*"width\s*:\s*\{\{\s*\$columnCompleteness\s*\}\}\s*%\s*;?"' `
    -Replacement 'data-qh-width="{{ $columnCompleteness }}"' `
    -Expected 1 `
    -Label 'Analytics column completeness'

Replace-RegexExpected `
    -Path $analytics `
    -Pattern 'style\s*=\s*"width\s*:\s*\{\{\s*\$topPercent\s*\}\}\s*%\s*;?"' `
    -Replacement 'data-qh-width="{{ $topPercent }}"' `
    -Expected 1 `
    -Label 'Analytics top percent'

Write-Host '[OK] Migrated 2 server-rendered analytics widths'

# ------------------------------------------------------------------
# 6. Dashboard JS template styles -> data attributes
# ------------------------------------------------------------------

$dashboard = Join-Path $root 'resources\views\dashboard.blade.php'

Replace-LiteralExpected `
    -Path $dashboard `
    -Old 'style="cursor: crosshair;"' `
    -New 'data-qh-cursor="crosshair"' `
    -Expected 1 `
    -Label 'Dashboard cursor'

Replace-LiteralExpected `
    -Path $dashboard `
    -Old 'style="margin-top:4px;color:#bfdbfe"' `
    -New 'data-qh-margin-top="4" data-qh-color="#bfdbfe"' `
    -Expected 1 `
    -Label 'Dashboard tooltip document style'

Replace-LiteralExpected `
    -Path $dashboard `
    -Old 'style="color:#a5f3fc"' `
    -New 'data-qh-color="#a5f3fc"' `
    -Expected 1 `
    -Label 'Dashboard tooltip activity style'

Replace-RegexExpected `
    -Path $dashboard `
    -Pattern 'style\s*=\s*"background\s*:\s*\$\{colors\[index\s*%\s*colors\.length\]\}\s*;?"' `
    -Replacement 'data-qh-background="${colors[index % colors.length]}"' `
    -Expected 1 `
    -Label 'Dashboard donut legend background'

Replace-RegexExpected `
    -Path $dashboard `
    -Pattern 'style\s*=\s*"width\s*:\s*\$\{width\}%\s*;\s*background\s*:\s*\$\{color\}\s*;\s*animation-delay\s*:\s*\$\{index\s*\*\s*70\}ms\s*;?"' `
    -Replacement 'data-qh-width="${width}" data-qh-background="${color}" data-qh-animation-delay="${index * 70}"' `
    -Expected 1 `
    -Label 'Dashboard bar dynamic style'

Write-Host '[OK] Migrated 5 dashboard template style attributes'

# ------------------------------------------------------------------
# 7. Analytics JS template styles -> data attributes
# ------------------------------------------------------------------

Replace-RegexExpected `
    -Path $analytics `
    -Pattern 'style\s*=\s*"[\s\r\n]*background\s*:\s*\$\{colors\[index\s*%\s*colors\.length\]\}\s*;?[\s\r\n]*"' `
    -Replacement 'data-qh-background="${colors[index % colors.length]}"' `
    -Expected 1 `
    -Label 'Analytics donut legend background' `
    -Options (
        [System.Text.RegularExpressions.RegexOptions]::IgnoreCase `
        -bor [System.Text.RegularExpressions.RegexOptions]::Singleline
    )

Replace-RegexExpected `
    -Path $analytics `
    -Pattern 'style\s*=\s*"left\s*:\s*\$\{averagePosition\}%\s*;?"' `
    -Replacement 'data-qh-left="${averagePosition}"' `
    -Expected 1 `
    -Label 'Analytics average marker left'

Write-Host '[OK] Migrated 2 analytics template style attributes'

# ------------------------------------------------------------------
# 8. External JS style bridge
# ------------------------------------------------------------------

$appJs = Join-Path $root 'resources\js\app.js'
$appJsSource = [System.IO.File]::ReadAllText($appJs)

$marker = 'QualityHub CSP-safe data style bridge'

if ($appJsSource -notmatch [regex]::Escape($marker)) {
    $bridge = @'

/*
|--------------------------------------------------------------------------
| QualityHub CSP-safe data style bridge
|--------------------------------------------------------------------------
|
| style-src-attr 'none' blocks style="" / setAttribute("style") / cssText.
| Direct element.style.property assignment is still allowed by CSP.
|
| Data attributes below are strictly parsed/whitelisted before applying.
|
*/

const applyQualityHubCspStyles = (root = document) => {
    const selector = [
        '[data-qh-width]',
        '[data-qh-left]',
        '[data-qh-background]',
        '[data-qh-color]',
        '[data-qh-animation-delay]',
        '[data-qh-margin-top]',
        '[data-qh-blend]',
        '[data-qh-cursor]',
    ].join(',');

    const elements = [];

    if (
        root instanceof Element
        && root.matches(selector)
    ) {
        elements.push(root);
    }

    if (
        root instanceof Document
        || root instanceof DocumentFragment
        || root instanceof Element
    ) {
        elements.push(
            ...root.querySelectorAll(selector)
        );
    }

    const parsePercent = (value) => {
        const number = Number(value);

        if (!Number.isFinite(number)) {
            return null;
        }

        return Math.min(
            100,
            Math.max(0, number)
        );
    };

    const parsePixels = (value) => {
        const number = Number(value);

        if (!Number.isFinite(number)) {
            return null;
        }

        return Math.min(
            500,
            Math.max(0, number)
        );
    };

    const parseMilliseconds = (value) => {
        const number = Number(value);

        if (!Number.isFinite(number)) {
            return null;
        }

        return Math.min(
            10000,
            Math.max(0, number)
        );
    };

    const parseHexColor = (value) => {
        const color = String(value ?? '').trim();

        return /^#[0-9a-f]{3,8}$/i.test(color)
            ? color
            : null;
    };

    elements.forEach((element) => {
        if (element.dataset.qhWidth !== undefined) {
            const value = parsePercent(
                element.dataset.qhWidth
            );

            if (value !== null) {
                element.style.width = `${value}%`;
            }
        }

        if (element.dataset.qhLeft !== undefined) {
            const value = parsePercent(
                element.dataset.qhLeft
            );

            if (value !== null) {
                element.style.left = `${value}%`;
            }
        }

        if (element.dataset.qhBackground !== undefined) {
            const value = parseHexColor(
                element.dataset.qhBackground
            );

            if (value !== null) {
                element.style.background = value;
            }
        }

        if (element.dataset.qhColor !== undefined) {
            const value = parseHexColor(
                element.dataset.qhColor
            );

            if (value !== null) {
                element.style.color = value;
            }
        }

        if (element.dataset.qhAnimationDelay !== undefined) {
            const value = parseMilliseconds(
                element.dataset.qhAnimationDelay
            );

            if (value !== null) {
                element.style.animationDelay = `${value}ms`;
            }
        }

        if (element.dataset.qhMarginTop !== undefined) {
            const value = parsePixels(
                element.dataset.qhMarginTop
            );

            if (value !== null) {
                element.style.marginTop = `${value}px`;
            }
        }

        if (element.dataset.qhBlend !== undefined) {
            const allowed = new Set([
                'hard-light',
                'plus-darker',
            ]);

            const value =
                String(
                    element.dataset.qhBlend
                    ?? ''
                ).trim();

            if (allowed.has(value)) {
                element.style.mixBlendMode = value;
            }
        }

        if (element.dataset.qhCursor !== undefined) {
            const value =
                String(
                    element.dataset.qhCursor
                    ?? ''
                ).trim();

            if (value === 'crosshair') {
                element.style.cursor = value;
            }
        }
    });
};

document.addEventListener(
    'DOMContentLoaded',
    () => {
        applyQualityHubCspStyles(document);

        const observer = new MutationObserver(
            (mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (
                            node instanceof Element
                            || node instanceof DocumentFragment
                        ) {
                            applyQualityHubCspStyles(node);
                        }
                    });
                });
            }
        );

        if (document.body) {
            observer.observe(
                document.body,
                {
                    childList: true,
                    subtree: true,
                }
            );
        }
    }
);
'@

    $appJsSource += $bridge

    Write-Utf8NoBom -Path $appJs -Content $appJsSource
    Write-Host '[OK] Added CSP-safe data style bridge to resources/js/app.js'
} else {
    Write-Host '[OK] CSP-safe data style bridge already exists'
}

Write-Host ''
Write-Host 'SH-5C3B2 migration selesai.' -ForegroundColor Green
Write-Host 'CSP POLICY BELUM DIUBAH - style-src-attr unsafe-inline masih sementara.' -ForegroundColor Yellow
Write-Host ''
Write-Host 'Next:' -ForegroundColor Cyan
Write-Host '1. npm run build'
Write-Host '2. powershell -ExecutionPolicy Bypass -File .\scripts\sh5c3b_inventory_style_attributes.ps1'
Write-Host '3. Get-Content .\storage\logs\sh5c3b-style-attribute-inventory.txt'
