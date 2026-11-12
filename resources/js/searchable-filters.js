/**
 * QualityHub searchable single-select filters (Select2-style).
 *
 * No jQuery/CDN dependency: suitable for private LAN + strict CSP.
 * The original <select> remains in the GET form and keeps its name/value,
 * so existing Laravel filters and Alpine x-model/@change keep working.
 * JS enhancement is optional: without JS, native selects are still usable.
 */
const MAX_VISIBLE_OPTIONS = 120;
let nextFilterId = 0;
let activeDropdown = null;

const normalized = (value) => String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLocaleLowerCase('id-ID')
    .replace(/\s+/g, ' ')
    .trim();

function makeElement(tag, className, text) {
    const element = document.createElement(tag);
    if (className) element.className = className;
    if (text !== undefined) element.textContent = text;
    return element;
}

function enhanceFilter(select) {
    if (select.dataset.qhSearchableReady === 'true' || select.multiple) return;
    select.dataset.qhSearchableReady = 'true';

    const id = ++nextFilterId;
    const fieldLabel = select.labels?.[0]?.textContent.replace(/\s+/g, ' ').trim()
        || select.options[0]?.textContent.replace(/\s+/g, ' ').trim()
        || 'filter';

    const root = makeElement('div', 'qh-searchable-filter');
    select.parentElement.insertBefore(root, select);
    root.appendChild(select);

    const trigger = makeElement('button', 'qh-searchable-trigger');
    trigger.type = 'button';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    trigger.setAttribute('aria-label', 'Pilih ' + fieldLabel + '. Ketik untuk mencari pilihan.');
    const text = makeElement('span', 'qh-searchable-label');
    const chevron = makeElement('span', 'qh-searchable-chevron', '⌄');
    chevron.setAttribute('aria-hidden', 'true');
    trigger.append(text, chevron);
    root.append(trigger);

    // Portal the popup to body; filter cards are sometimes overflow-hidden.
    const popup = makeElement('div', 'qh-searchable-popup');
    popup.hidden = true;
    popup.id = 'qh-searchable-popup-' + id;
    const searchWrap = makeElement('div', 'qh-searchable-search-wrap');
    const search = makeElement('input', 'qh-searchable-search');
    search.type = 'search';
    search.autocomplete = 'off';
    search.placeholder = 'Ketik untuk cari ' + fieldLabel.toLowerCase() + '...';
    search.setAttribute('role', 'combobox');
    search.setAttribute('aria-label', 'Cari ' + fieldLabel);
    search.setAttribute('aria-autocomplete', 'list');
    search.setAttribute('aria-expanded', 'false');

    const list = makeElement('div', 'qh-searchable-options');
    list.id = 'qh-searchable-options-' + id;
    list.setAttribute('role', 'listbox');
    list.setAttribute('aria-label', 'Pilihan ' + fieldLabel);
    search.setAttribute('aria-controls', list.id);

    const status = makeElement('div', 'qh-searchable-status');
    status.setAttribute('aria-live', 'polite');
    searchWrap.append(search);
    popup.append(searchWrap, list, status);
    document.body.append(popup);

    let visible = [];
    let activeIndex = -1;
    let previousValue = select.value;

    const selectedLabel = () => select.selectedOptions[0]?.textContent.replace(/\s+/g, ' ').trim()
        || select.options[0]?.textContent.replace(/\s+/g, ' ').trim()
        || 'Pilih...';

    const syncTrigger = () => {
        text.textContent = selectedLabel();
        text.title = text.textContent;
        previousValue = select.value;
        trigger.disabled = select.disabled;
    };

    const highlight = (index) => {
        if (!visible.length) {
            activeIndex = -1;
            search.removeAttribute('aria-activedescendant');
            return;
        }
        activeIndex = (index + visible.length) % visible.length;
        for (const [i, item] of visible.entries()) {
            item.element.classList.toggle('qh-searchable-option-active', i === activeIndex);
        }
        const target = visible[activeIndex].element;
        search.setAttribute('aria-activedescendant', target.id);
        target.scrollIntoView({ block: 'nearest' });
    };

    const renderOptions = () => {
        const query = normalized(search.value);
        const matches = [];
        let totalMatches = 0;

        // Limit DOM creation, NOT the query: even a 2000-item list remains searchable.
        for (const option of select.options) {
            if (option.disabled) continue;
            const label = option.textContent.replace(/\s+/g, ' ').trim();
            if (query && !normalized(label).includes(query)
                && !normalized(option.value).includes(query)) continue;
            totalMatches += 1;
            if (matches.length < MAX_VISIBLE_OPTIONS) {
                matches.push({ option, label });
            }
        }

        const fragment = document.createDocumentFragment();
        visible = matches.map(({ option, label }, i) => {
            const choice = makeElement('button', 'qh-searchable-option', label);
            choice.type = 'button';
            choice.id = 'qh-searchable-choice-' + id + '-' + i;
            choice.setAttribute('role', 'option');
            choice.setAttribute('aria-selected', String(option.value === select.value));
            if (option.value === '') choice.classList.add('qh-searchable-all');
            choice.addEventListener('click', () => {
                select.value = option.value;
                syncTrigger();
                // Alpine x-model on <select> listens to change; submit GET forms
                // continue to receive the native name/value unchanged.
                select.dispatchEvent(new Event('input', { bubbles: true }));
                select.dispatchEvent(new Event('change', { bubbles: true }));
                close(true);
            });
            fragment.append(choice);
            return { element: choice, option };
        });

        list.replaceChildren(fragment);
        if (!totalMatches) {
            const empty = makeElement('div', 'qh-searchable-empty', 'Tidak ada pilihan yang cocok.');
            list.append(empty);
        }

        status.textContent = totalMatches === 0
            ? 'Tidak ada hasil.'
            : (totalMatches > MAX_VISIBLE_OPTIONS
                ? 'Menampilkan ' + MAX_VISIBLE_OPTIONS + ' dari ' + totalMatches + ' pilihan. Ketik lebih spesifik.'
                : totalMatches + ' pilihan ditemukan.');

        const selectedIndex = visible.findIndex(x => x.option.value === select.value);
        activeIndex = -1;
        if (visible.length) highlight(selectedIndex >= 0 ? selectedIndex : 0);
        else search.removeAttribute('aria-activedescendant');
    };

    const positionPopup = () => {
        if (popup.hidden) return;
        const rect = trigger.getBoundingClientRect();
        const availableWidth = Math.max(160, window.innerWidth - 24);
        const width = Math.min(Math.max(rect.width, 240), availableWidth);
        const left = Math.max(12, Math.min(rect.left, window.innerWidth - width - 12));
        const below = window.innerHeight - rect.bottom - 12;
        const above = rect.top - 12;
        const openAbove = below < 220 && above > below;
        const room = Math.max(130, openAbove ? above : below);
        const height = Math.min(340, room - 6);

        popup.style.width = width + 'px';
        popup.style.left = left + 'px';
        popup.style.maxHeight = height + 'px';
        if (openAbove) {
            popup.style.top = Math.max(10, rect.top - height - 6) + 'px';
        } else {
            popup.style.top = Math.max(10, rect.bottom + 6) + 'px';
        }
    };

    function close(restoreFocus = false) {
        popup.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        search.setAttribute('aria-expanded', 'false');
        search.removeAttribute('aria-activedescendant');
        root.classList.remove('qh-searchable-open');
        if (activeDropdown?.select === select) activeDropdown = null;
        if (restoreFocus) trigger.focus();
    }

    function open() {
        if (select.disabled) return;
        if (activeDropdown && activeDropdown.select !== select) activeDropdown.close();
        activeDropdown = { select, close };

        syncTrigger();
        popup.hidden = false;
        root.classList.add('qh-searchable-open');
        trigger.setAttribute('aria-expanded', 'true');
        search.setAttribute('aria-expanded', 'true');
        search.value = '';
        renderOptions();
        positionPopup();
        search.focus();
    }

    trigger.addEventListener('click', () => popup.hidden ? open() : close(true));
    search.addEventListener('input', renderOptions);
    search.addEventListener('keydown', event => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (visible.length) highlight(activeIndex + (event.key === 'ArrowDown' ? 1 : -1));
        } else if (event.key === 'Enter') {
            event.preventDefault();
            if (activeIndex >= 0 && visible[activeIndex]) visible[activeIndex].element.click();
        } else if (event.key === 'Escape') {
            event.preventDefault();
            close(true);
        } else if (event.key === 'Tab') {
            close();
        }
    });

    document.addEventListener('pointerdown', event => {
        if (!popup.hidden && !popup.contains(event.target) && !root.contains(event.target)) {
            close();
        }
    });
    window.addEventListener('resize', positionPopup);
    window.addEventListener('scroll', positionPopup, true);
    select.addEventListener('change', syncTrigger);
    select.addEventListener('input', syncTrigger);

    // Laravel selected states are retained. For Alpine x-model programmatic
    // updates (quick filters/reset), .value may change without a DOM event.
    // Poll only reactive selects; close on page unload/disconnection.
    if (select.hasAttribute('x-model')) {
        const timer = window.setInterval(() => {
            if (!select.isConnected) {
                window.clearInterval(timer);
                return;
            }
            if (select.value !== previousValue) syncTrigger();
        }, 350);
    }

    // Keep the associated <label for="..."> keyboard/mouse usable.
    if (select.id && select.labels) {
        for (const label of select.labels) {
            label.addEventListener('click', event => {
                event.preventDefault();
                open();
            });
        }
    }

    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');
    syncTrigger();
}

export function initializeSearchableFilters() {
    document.querySelectorAll('select[data-qh-searchable-select]').forEach(enhanceFilter);
}
