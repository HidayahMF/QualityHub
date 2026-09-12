import './bootstrap';

import Alpine from '@alpinejs/csp';

window.Alpine = Alpine;

Alpine.start();

/*
|--------------------------------------------------------------------------
| CSP-safe native UI handlers
|--------------------------------------------------------------------------
|
| Native onclick/onchange attributes tidak digunakan supaya script-src
| dapat berjalan tanpa 'unsafe-inline'.
|
*/

document.addEventListener('DOMContentLoaded', () => {

    /*
    | Dashboard period auto-submit
    */
    document.querySelectorAll('[data-auto-submit]').forEach((element) => {
        element.addEventListener('change', () => {
            const form = element.form;

            if (form) {
                form.requestSubmit();
            }
        });
    });

    /*
    | Logout links
    */
    document.querySelectorAll('[data-submit-form]').forEach((element) => {
        element.addEventListener('click', (event) => {
            event.preventDefault();

            const form = element.closest('form');

            if (form) {
                form.requestSubmit();
            }
        });
    });

});
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