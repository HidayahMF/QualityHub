const normalize = (value) => String(value ?? '')
    .toLocaleLowerCase('id-ID')
    .replace(/\s+/g, ' ')
    .trim();

const initializeCategoryCombobox = (root) => {
    const nativeWrapper = root.querySelector('[data-category-native]');
    const enhancedWrapper = root.querySelector('[data-category-enhanced]');
    const select = root.querySelector('select[name="category_id"]');
    const search = root.querySelector('[data-category-search]');
    const listbox = root.querySelector('[data-category-listbox]');
    const status = root.querySelector('[data-category-status]');
    const empty = root.querySelector('[data-category-empty]');
    const createOption = root.querySelector('[data-category-create-option]');
    const createLabel = root.querySelector('[data-category-create-label]');
    const newFields = root.querySelector('[data-category-new-fields]');
    const newName = root.querySelector('[data-category-new-name]');
    const newDepartment = root.querySelector('[data-category-new-department]');
    const options = Array.from(root.querySelectorAll('[data-category-option]'));
    const allowCreate = root.dataset.allowCreate === 'true';

    if (!nativeWrapper || !enhancedWrapper || !select || !search || !listbox) {
        return;
    }

    let activeIndex = -1;
    let visibleOptions = [];

    const selectedLabel = () => {
        const option = select.selectedOptions[0];

        if (!option || !option.value) {
            return '';
        }

        return option.textContent.replace(/\s+/g, ' ').trim();
    };

    const setOpen = (open) => {
        listbox.classList.toggle('hidden', !open);
        search.setAttribute('aria-expanded', open ? 'true' : 'false');

        if (!open) {
            activeIndex = -1;
            search.removeAttribute('aria-activedescendant');
        }
    };

    const updateActiveOption = (nextIndex) => {
        visibleOptions.forEach((option) => {
            option.classList.remove('bg-blue-50');
        });

        if (visibleOptions.length === 0) {
            activeIndex = -1;
            search.removeAttribute('aria-activedescendant');
            return;
        }

        activeIndex = (nextIndex + visibleOptions.length) % visibleOptions.length;
        const option = visibleOptions[activeIndex];

        option.classList.add('bg-blue-50');
        search.setAttribute('aria-activedescendant', option.id);
        option.scrollIntoView({ block: 'nearest' });
    };

    const markCommitted = (committed) => {
        search.setCustomValidity(
            committed
                ? ''
                : (allowCreate
                    ? 'Pilih kategori dari daftar atau pilih opsi membuat kategori baru.'
                    : 'Pilih kategori dari daftar.')
        );
    };

    const setNewFieldsEnabled = (enabled) => {
        if (!allowCreate || !newFields || !newName || !newDepartment) {
            return;
        }

        newFields.classList.toggle('hidden', !enabled);
        newName.disabled = !enabled;
        newDepartment.disabled = !enabled;
        newName.required = enabled;
        newDepartment.required = enabled;
    };

    const syncSelectedState = () => {
        options.forEach((option) => {
            option.setAttribute(
                'aria-selected',
                option.dataset.categoryValue === select.value ? 'true' : 'false'
            );
        });
    };

    const switchToExisting = (value, emitChange = true) => {
        select.value = value;
        root.dataset.categoryMode = value ? 'existing' : 'search';
        search.value = selectedLabel();
        setNewFieldsEnabled(false);

        if (newName) {
            newName.value = '';
        }

        if (newDepartment) {
            newDepartment.value = '';
        }

        syncSelectedState();
        markCommitted(Boolean(value));
        setOpen(false);

        if (emitChange) {
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    };

    const switchToNew = (name) => {
        if (!allowCreate || !newName) {
            return;
        }

        const trimmedName = String(name ?? '').replace(/\s+/g, ' ').trim();

        select.value = '';
        root.dataset.categoryMode = 'new';
        search.value = trimmedName;
        newName.value = trimmedName;
        setNewFieldsEnabled(true);
        syncSelectedState();
        markCommitted(Boolean(trimmedName));
        setOpen(false);
        select.dispatchEvent(new Event('change', { bubbles: true }));
        newDepartment?.focus();
    };

    const renderOptions = () => {
        const query = normalize(search.value);
        let existingCount = 0;

        options.forEach((option) => {
            const matches = !query
                || normalize(option.dataset.categorySearchText).includes(query);

            option.classList.remove('bg-blue-50');
            option.classList.toggle('hidden', !matches);

            if (matches) {
                existingCount += 1;
            }
        });

        if (createOption) {
            const showCreate = allowCreate && query.length > 0;
            createOption.classList.toggle('hidden', !showCreate);
            createOption.dataset.categoryNewName = search.value.replace(/\s+/g, ' ').trim();

            if (createLabel) {
                createLabel.textContent = `"${createOption.dataset.categoryNewName}"`;
            }
        }

        const createVisible = Boolean(createOption && !createOption.classList.contains('hidden'));
        empty?.classList.toggle('hidden', existingCount > 0 || createVisible);
        visibleOptions = [
            ...options.filter((option) => !option.classList.contains('hidden')),
            ...(createVisible ? [createOption] : []),
        ];
        activeIndex = -1;
        search.removeAttribute('aria-activedescendant');

        if (status) {
            status.textContent = existingCount > 0
                ? `${existingCount} kategori ditemukan.`
                : (createVisible
                    ? 'Kategori belum ada. Opsi membuat kategori baru tersedia.'
                    : 'Kategori tidak ditemukan.');
        }
    };

    nativeWrapper.classList.add('hidden');
    enhancedWrapper.classList.remove('hidden');
    select.required = false;
    search.required = true;

    if (root.dataset.categoryMode === 'new' && allowCreate && newName?.value.trim()) {
        select.value = '';
        search.value = newName.value;
        setNewFieldsEnabled(true);
        markCommitted(true);
    } else {
        root.dataset.categoryMode = select.value ? 'existing' : 'search';
        search.value = selectedLabel();
        setNewFieldsEnabled(false);
        markCommitted(Boolean(select.value));
    }

    syncSelectedState();
    renderOptions();

    search.addEventListener('focus', () => {
        renderOptions();
        setOpen(true);
    });

    search.addEventListener('input', () => {
        if (root.dataset.categoryMode === 'new' && newName) {
            newName.value = search.value;
            markCommitted(Boolean(search.value.trim()));
        } else {
            if (select.value) {
                select.value = '';
                syncSelectedState();
            }

            root.dataset.categoryMode = 'search';
            markCommitted(false);
        }

        renderOptions();
        setOpen(true);
    });

    search.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();

            if (listbox.classList.contains('hidden')) {
                renderOptions();
                setOpen(true);
            }

            const nextIndex = activeIndex < 0
                ? (event.key === 'ArrowDown' ? 0 : visibleOptions.length - 1)
                : activeIndex + (event.key === 'ArrowDown' ? 1 : -1);

            updateActiveOption(nextIndex);
            return;
        }

        if (event.key === 'Enter' && !listbox.classList.contains('hidden')) {
            const option = visibleOptions[activeIndex] ?? visibleOptions[0];

            if (option) {
                event.preventDefault();
                option.click();
            }
            return;
        }

        if (event.key === 'Escape') {
            setOpen(false);
            return;
        }

        if (event.key === 'Tab') {
            setOpen(false);
        }
    });

    listbox.addEventListener('mousedown', (event) => {
        event.preventDefault();
    });

    listbox.addEventListener('click', (event) => {
        const existingOption = event.target.closest('[data-category-option]');

        if (existingOption) {
            switchToExisting(existingOption.dataset.categoryValue);
            search.focus();
            return;
        }

        const requestedCreate = event.target.closest('[data-category-create-option]');

        if (requestedCreate) {
            switchToNew(requestedCreate.dataset.categoryNewName);
        }
    });

    select.addEventListener('change', () => {
        if (root.dataset.categoryMode === 'new' && !select.value) {
            return;
        }

        switchToExisting(select.value, false);
    });

    newName?.addEventListener('input', () => {
        if (root.dataset.categoryMode !== 'new') {
            return;
        }

        search.value = newName.value;
        markCommitted(Boolean(newName.value.trim()));
    });

    document.addEventListener('mousedown', (event) => {
        if (!root.contains(event.target)) {
            setOpen(false);
        }
    });
};

export const initializeCategoryComboboxes = () => {
    document.querySelectorAll('[data-category-combobox]').forEach((root) => {
        initializeCategoryCombobox(root);
    });
};
