$ErrorActionPreference = 'Stop'

$root = (Get-Location).Path

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

function Replace-OldOrConfirm-New {
    param(
        [string]$Path,
        [string]$OldPattern,
        [string]$NewPattern,
        [string]$Replacement,
        [string]$Label,
        [System.Text.RegularExpressions.RegexOptions]$Options =
            [System.Text.RegularExpressions.RegexOptions]::IgnoreCase
    )

    $source = Read-Text $Path

    $newRegex = New-Object System.Text.RegularExpressions.Regex(
        $NewPattern,
        $Options
    )

    if ($newRegex.IsMatch($source)) {
        Write-Host "[SKIP] $Label already migrated"
        return
    }

    $oldRegex = New-Object System.Text.RegularExpressions.Regex(
        $OldPattern,
        $Options
    )

    $matches = $oldRegex.Matches($source)

    if ($matches.Count -ne 1) {
        throw "${Label}: expected 1 old match or existing migrated form, found $($matches.Count). STOP."
    }

    $updated = $oldRegex.Replace(
        $source,
        $Replacement,
        1
    )

    Write-Text -Path $Path -Content $updated

    Write-Host "[OK] $Label migrated"
}

$dashboard = Join-Path $root 'resources\views\dashboard.blade.php'
$analytics = Join-Path $root 'resources\views\documents\partials\analytics-dashboard.blade.php'
$appJs = Join-Path $root 'resources\js\app.js'
$appCss = Join-Path $root 'resources\css\app.css'

foreach ($path in @($dashboard, $analytics, $appJs, $appCss)) {
    if (-not (Test-Path $path)) {
        throw "Required file not found: $path"
    }
}

Write-Host ''
Write-Host 'SH-5C3B2 RESUME HOTFIX' -ForegroundColor Cyan
Write-Host 'This resumes safely after the previous fail-fast stop.' -ForegroundColor DarkGray
Write-Host ''

# ------------------------------------------------------------------
# Dashboard dynamic bar
# Broad match because formatting may span whitespace differently.
# ------------------------------------------------------------------

Replace-OldOrConfirm-New `
    -Path $dashboard `
    -OldPattern 'style\s*=\s*"[^"]*width\s*:\s*\$\{width\}\s*%[^"]*background\s*:\s*\$\{color\}[^"]*animation-delay\s*:\s*\$\{index\s*\*\s*70\}\s*ms[^"]*"' `
    -NewPattern 'data-qh-width\s*=\s*"\$\{width\}"[^>]*data-qh-background\s*=\s*"\$\{color\}"[^>]*data-qh-animation-delay\s*=\s*"\$\{index\s*\*\s*70\}"' `
    -Replacement 'data-qh-width="${width}" data-qh-background="${color}" data-qh-animation-delay="${index * 70}"' `
    -Label 'Dashboard dynamic bar'

# ------------------------------------------------------------------
# Analytics JS template background
# ------------------------------------------------------------------

Replace-OldOrConfirm-New `
    -Path $analytics `
    -OldPattern 'style\s*=\s*"[\s\r\n]*background\s*:\s*\$\{colors\[index\s*%\s*colors\.length\]\}\s*;?[\s\r\n]*"' `
    -NewPattern 'data-qh-background\s*=\s*"\$\{colors\[index\s*%\s*colors\.length\]\}"' `
    -Replacement 'data-qh-background="${colors[index % colors.length]}"' `
    -Label 'Analytics dynamic legend background' `
    -Options (
        [System.Text.RegularExpressions.RegexOptions]::IgnoreCase `
        -bor [System.Text.RegularExpressions.RegexOptions]::Singleline
    )

# ------------------------------------------------------------------
# Analytics average marker position
# ------------------------------------------------------------------

Replace-OldOrConfirm-New `
    -Path $analytics `
    -OldPattern 'style\s*=\s*"left\s*:\s*\$\{averagePosition\}\s*%\s*;?"' `
    -NewPattern 'data-qh-left\s*=\s*"\$\{averagePosition\}"' `
    -Replacement 'data-qh-left="${averagePosition}"' `
    -Label 'Analytics average marker'

# ------------------------------------------------------------------
# Ensure x-cloak exists in app.css
# ------------------------------------------------------------------

$appCssSource = Read-Text $appCss

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

    Write-Text -Path $appCss -Content $appCssSource
    Write-Host '[OK] Added global x-cloak CSS'
} else {
    Write-Host '[SKIP] Global x-cloak CSS already present'
}

# ------------------------------------------------------------------
# Ensure CSP-safe style bridge exists in external app.js
# ------------------------------------------------------------------

$appJsSource = Read-Text $appJs
$marker = 'QualityHub CSP-safe data style bridge'

if ($appJsSource -notmatch [regex]::Escape($marker)) {
    $bridge = @'

/*
|--------------------------------------------------------------------------
| QualityHub CSP-safe data style bridge
|--------------------------------------------------------------------------
|
| style-src-attr 'none' blocks style="" / setAttribute("style") / cssText.
| Direct element.style.property assignment remains usable.
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
    Write-Text -Path $appJs -Content $appJsSource

    Write-Host '[OK] Added CSP-safe style bridge to app.js'
} else {
    Write-Host '[SKIP] CSP-safe style bridge already present'
}

Write-Host ''
Write-Host 'RESUME MIGRATION COMPLETE' -ForegroundColor Green
Write-Host 'Do not change CSP policy yet.' -ForegroundColor Yellow
Write-Host ''
Write-Host 'Next commands:' -ForegroundColor Cyan
Write-Host 'npm run build'
Write-Host 'php artisan optimize:clear'
Write-Host 'powershell -ExecutionPolicy Bypass -File .\scripts\sh5c3b_inventory_style_attributes.ps1'
Write-Host 'Get-Content .\storage\logs\sh5c3b-style-attribute-inventory.txt'
