import './bootstrap';
import { initializeCategoryComboboxes } from './category-combobox';
import { initializeCategoryDocumentsModal } from './category-documents-modal';
import { initializeSearchableFilters } from './searchable-filters';

import Alpine from '@alpinejs/csp';

window.Alpine = Alpine;

Alpine.data('documentFileUpload', () => ({
    fileName: '',
    fileSize: '',

    handleFile(event) {
        const file = event.target.files?.[0];

        if (!file) {
            this.fileName = '';
            this.fileSize = '';
            return;
        }

        this.fileName = file.name;

        if (file.size >= 1048576) {
            this.fileSize = `${(file.size / 1048576).toFixed(2)} MB`;
        } else {
            this.fileSize = `${(file.size / 1024).toFixed(1)} KB`;
        }
    },
}));

Alpine.data('qualityHubModal', () => ({
    show: false,
    name: '',
    focusable: false,
    returnFocusTo: null,

    init() {
        this.name = this.$el.dataset.qhModalName ?? '';
        this.show = this.$el.dataset.qhModalShow === 'true';
        this.focusable =
            this.$el.dataset.qhModalFocusable === 'true';

        this.syncBodyLock(this.show);

        this.$watch('show', (value) => {
            this.syncBodyLock(value);
        });
    },

    eventName(event) {
        if (typeof event.detail === 'string') {
            return event.detail;
        }

        return event.detail?.name ?? '';
    },

    open(event) {
        if (this.eventName(event) === this.name) {
            this.returnFocusTo = document.activeElement instanceof HTMLElement
                ? document.activeElement
                : null;
            this.show = true;
        }
    },

    closeRequested(event) {
        if (this.eventName(event) === this.name) {
            this.close();
        }
    },

    close() {
        const wasOpen = this.show;

        this.show = false;

        if (wasOpen && this.returnFocusTo?.isConnected) {
            window.setTimeout(() => this.returnFocusTo?.focus(), 0);
        }
    },

    syncBodyLock(open) {
        document.body.classList.toggle(
            'overflow-y-hidden',
            open
        );

        if (open && this.focusable) {
            window.setTimeout(() => {
                const preferred = this.$el.querySelector(
                    '[data-qh-modal-initial-focus]'
                );
                const preferredIsAvailable = preferred
                    && ! preferred.hasAttribute('disabled')
                    && preferred.getClientRects().length > 0;

                if (preferredIsAvailable) {
                    preferred.focus();
                    return;
                }

                this.focusables()[0]?.focus();
            }, 100);
        }
    },

    focusables() {
        const selector = [
            'a',
            'button',
            "input:not([type='hidden'])",
            'textarea',
            'select',
            'details',
            "[tabindex]:not([tabindex='-1'])",
        ].join(',');

        return [...this.$el.querySelectorAll(selector)]
            .filter((element) =>
                ! element.hasAttribute('disabled')
                && element.getClientRects().length > 0
            );
    },

    handleTab(event) {
        const focusables = this.focusables();

        if (focusables.length === 0) {
            return;
        }

        const currentIndex = focusables.indexOf(
            document.activeElement
        );

        const nextIndex = event.shiftKey
            ? (currentIndex <= 0
                ? focusables.length - 1
                : currentIndex - 1)
            : (currentIndex + 1) % focusables.length;

        focusables[nextIndex].focus();
    },
}));

Alpine.data('qualityHubTypedConfirmation', () => ({
    step: 1,
    confirmation: '',
    submitting: false,

    get firstStep() {
        return this.step === 1;
    },

    get secondStep() {
        return this.step === 2;
    },

    get cannotSubmit() {
        return ! this.secondStep
            || this.confirmation.trim() !== 'HAPUS'
            || this.submitting;
    },

    resetOnOpen(event) {
        const name = typeof event.detail === 'string'
            ? event.detail
            : event.detail?.name;

        if (name !== this.$el.dataset.qhTypedModalName) {
            return;
        }

        this.step = 1;
        this.confirmation = '';
        this.submitting = false;
    },

    advance() {
        this.step = 2;
        this.$nextTick(() => this.$refs.confirmationInput?.focus());
    },

    submit(event) {
        if (this.cannotSubmit) {
            event.preventDefault();
            return;
        }

        this.submitting = true;
    },
}));

// Tambahkan KEDUA komponen ini di resources/js/app.js,
// tepat SEBELUM baris Alpine.start();
// Jangan ikut menempel komentar instruksi di atas jika tidak diperlukan.

Alpine.data('departmentStatusAction', () => ({
    requestConfirmation(event) {
        const form = event.currentTarget;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        window.dispatchEvent(new CustomEvent('qualityhub-confirm', {
            detail: {
                form,
                title: form.dataset.confirmTitle,
                message: form.dataset.confirmMessage,
                confirmText: form.dataset.confirmButton,
                confirmationCode: form.dataset.confirmCode || null,
                destructive: form.dataset.confirmDestructive === 'true',
            },
        }));
    },
}));

Alpine.data('departmentStatusConfirm', () => ({
    open: false,
    form: null,
    title: 'Konfirmasi Tindakan',
    message: 'Pastikan tindakan ini memang ingin dilanjutkan.',
    confirmText: 'Lanjutkan',
    confirmationCode: null,
    typedCode: '',
    destructive: false,
    submitting: false,

    get needsCode() {
        return this.confirmationCode !== null;
    },

    get cannotConfirm() {
        return this.submitting
            || (this.needsCode && this.typedCode !== this.confirmationCode);
    },

    get confirmButtonClass() {
        return this.destructive
            ? 'bg-rose-700 hover:bg-rose-800 focus:ring-rose-500'
            : 'bg-blue-800 hover:bg-blue-900 focus:ring-blue-500';
    },

    showConfirmation(event) {
        if (this.submitting) {
            return;
        }

        const detail = event.detail ?? {};

        if (!(detail.form instanceof HTMLFormElement)) {
            return;
        }

        this.form = detail.form;
        this.title = detail.title || 'Konfirmasi Tindakan';
        this.message = detail.message || 'Pastikan tindakan ini memang ingin dilanjutkan.';
        this.confirmText = detail.confirmText || 'Lanjutkan';
        this.confirmationCode = typeof detail.confirmationCode === 'string'
            ? detail.confirmationCode
            : null;
        this.typedCode = '';
        this.destructive = detail.destructive === true;
        this.open = true;
    },

    close() {
        if (this.submitting) {
            return;
        }

        this.open = false;
        this.form = null;
        this.confirmationCode = null;
        this.typedCode = '';
        this.destructive = false;
    },

    confirm() {
        if (this.cannotConfirm || !(this.form instanceof HTMLFormElement)) {
            return;
        }

        const target = this.form;
        if (this.needsCode) {
            const input = target.querySelector('[data-department-confirmation-code]');

            if (!(input instanceof HTMLInputElement)) {
                return;
            }

            input.value = this.typedCode;
        }

        this.submitting = true;
        this.open = false;
        this.form = null;

        // Native submit menghindari event 'submit' yang sudah memakai .prevent,
        // tetapi tetap mengirim field CSRF dan spoofed PATCH milik form.
        HTMLFormElement.prototype.submit.call(target);
    },
}));


// Vite's deferred module executes after the page is parsed. Enhance the
// filter selects before Alpine starts observing x-model: moving a select
// after Alpine init can otherwise cause directive teardown/reinitialization.
initializeSearchableFilters();
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

    initializeCategoryComboboxes();
    initializeCategoryDocumentsModal();
    // Safe idempotent fallback for deferred/dynamically injected views.
    initializeSearchableFilters();






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
