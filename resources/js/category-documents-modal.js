const setVisible = (element, visible) => {
    if (element) {
        element.hidden = ! visible;
        element.classList.toggle('hidden', ! visible);
    }
};

const setText = (element, value) => {
    if (element) {
        element.textContent = String(value ?? '-');
    }
};

export const initializeCategoryDocumentsModal = () => {
    const browser = document.querySelector(
        '[data-category-documents-browser]'
    );

    if (! browser) {
        return;
    }

    const elements = {
        name: browser.querySelector('[data-category-documents-name]'),
        summary: browser.querySelector('[data-category-documents-summary]'),
        search: browser.querySelector('[data-category-documents-search]'),
        loading: browser.querySelector('[data-category-documents-loading]'),
        error: browser.querySelector('[data-category-documents-error]'),
        empty: browser.querySelector('[data-category-documents-empty]'),
        noResults: browser.querySelector('[data-category-documents-no-results]'),
        results: browser.querySelector('[data-category-documents-results]'),
        pagination: browser.querySelector('[data-category-documents-pagination]'),
        pageInfo: browser.querySelector('[data-category-documents-page-info]'),
        previous: browser.querySelector('[data-category-documents-previous]'),
        next: browser.querySelector('[data-category-documents-next]'),
        retry: browser.querySelector('[data-category-documents-retry]'),
        template: browser.querySelector('[data-category-document-template]'),
        scroll: browser.querySelector('[data-category-documents-scroll]'),
    };

    let endpoint = '';
    let currentPage = 1;
    let lastPage = 1;
    let requestedPage = 1;
    let latestRequest = 0;
    let requestController = null;
    let searchTimer = null;

    const showState = (state) => {
        setVisible(elements.loading, state === 'loading');
        setVisible(elements.error, state === 'error');
        setVisible(elements.empty, state === 'empty');
        setVisible(elements.noResults, state === 'no-results');
        setVisible(elements.results, state === 'results');
    };

    const renderResults = (results) => {
        elements.results.replaceChildren();

        results.forEach((documentData) => {
            const fragment = elements.template.content.cloneNode(true);
            const title = fragment.querySelector(
                '[data-category-document-title]'
            );
            const fileName = fragment.querySelector(
                '[data-category-document-file-name]'
            );
            const code = fragment.querySelector(
                '[data-category-document-code]'
            );
            const partNumber = fragment.querySelector(
                '[data-category-document-part-number]'
            );
            const date = fragment.querySelector(
                '[data-category-document-date]'
            );
            const activeStatus = fragment.querySelector(
                '[data-category-document-status-active]'
            );
            const archivedStatus = fragment.querySelector(
                '[data-category-document-status-archived]'
            );
            const detail = fragment.querySelector(
                '[data-category-document-detail]'
            );
            const unavailable = fragment.querySelector(
                '[data-category-document-detail-unavailable]'
            );

            setText(title, documentData.title);
            setText(fileName, documentData.file_name);
            setText(code, documentData.document_code);
            setText(partNumber, documentData.part_number);
            setText(date, documentData.document_date);
            setVisible(activeStatus, documentData.is_active === true);
            setVisible(archivedStatus, documentData.is_active !== true);

            let detailUrl = null;

            if (documentData.detail_url) {
                const candidate = new URL(
                    documentData.detail_url,
                    window.location.origin
                );

                if (candidate.origin === window.location.origin) {
                    detailUrl = candidate.pathname
                        + candidate.search
                        + candidate.hash;
                }
            }

            if (detailUrl) {
                detail.href = detailUrl;
                setVisible(detail, true);
                setVisible(unavailable, false);
            } else {
                detail.removeAttribute('href');
                setVisible(detail, false);
                setVisible(unavailable, true);
            }

            elements.results.append(fragment);
        });
    };

    const renderPagination = (pagination) => {
        currentPage = Number(pagination.current_page) || 1;
        lastPage = Number(pagination.last_page) || 1;

        setText(
            elements.pageInfo,
            `Halaman ${currentPage} dari ${lastPage}`
        );

        elements.previous.disabled = currentPage <= 1;
        elements.next.disabled = currentPage >= lastPage;
        setVisible(elements.pagination, lastPage > 1);
    };

    const loadDocuments = async (page = 1) => {
        if (! endpoint) {
            return;
        }

        requestedPage = Math.max(1, Number(page) || 1);
        const requestId = ++latestRequest;

        requestController?.abort();
        requestController = new AbortController();

        showState('loading');
        setVisible(elements.pagination, false);
        elements.scroll.scrollTop = 0;

        const url = new URL(endpoint, window.location.origin);
        const search = elements.search.value.trim();

        url.searchParams.set('page', String(requestedPage));

        if (search !== '') {
            url.searchParams.set('search', search);
        }

        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: requestController.signal,
            });

            if (! response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            const payload = await response.json();

            if (requestId !== latestRequest) {
                return;
            }

            const results = Array.isArray(payload.results)
                ? payload.results
                : [];
            const categoryTotal = Number(payload.category_total) || 0;
            const filteredTotal = Number(payload.filtered_total) || 0;

            setText(elements.name, payload.category?.name ?? '-');
            setText(
                elements.summary,
                search === ''
                    ? `${categoryTotal} dokumen`
                    : `${filteredTotal} dari ${categoryTotal} dokumen`
            );

            renderResults(results);
            renderPagination(payload.pagination ?? {});

            if (categoryTotal === 0) {
                showState('empty');
            } else if (filteredTotal === 0) {
                showState('no-results');
            } else {
                showState('results');
            }
        } catch (error) {
            if (
                requestId === latestRequest
                && error.name !== 'AbortError'
            ) {
                showState('error');
            }
        }
    };

    document
        .querySelectorAll('[data-category-documents-trigger]')
        .forEach((trigger) => {
            trigger.addEventListener('click', () => {
                endpoint = trigger.dataset.categoryDocumentsUrl ?? '';
                currentPage = 1;
                lastPage = 1;
                requestedPage = 1;
                window.clearTimeout(searchTimer);
                elements.search.value = '';
                setText(
                    elements.name,
                    trigger.dataset.categoryName ?? '-'
                );
                setText(
                    elements.summary,
                    `${trigger.dataset.categoryDocumentCount ?? 0} dokumen`
                );

                window.dispatchEvent(new CustomEvent(
                    'open-modal',
                    { detail: 'category-documents' }
                ));

                loadDocuments();
            });
        });

    elements.search.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(
            () => loadDocuments(1),
            250
        );
    });

    elements.previous.addEventListener('click', () => {
        loadDocuments(Math.max(1, currentPage - 1));
    });

    elements.next.addEventListener('click', () => {
        loadDocuments(Math.min(lastPage, currentPage + 1));
    });

    elements.retry.addEventListener('click', () => {
        loadDocuments(requestedPage);
    });
};
