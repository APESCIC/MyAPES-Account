const normalize = (value) => String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();

const tokens = (value) => normalize(value).split(/\s+/).filter(Boolean);

export const matchesAdminSearchEntry = (entry, query = '') => {
    const queryTokens = tokens(query);
    if (queryTokens.length === 0) {
        return false;
    }

    const haystack = normalize([
        entry.label,
        entry.description,
        ...(Array.isArray(entry.keywords) ? entry.keywords : []),
    ].join(' '));

    return queryTokens.every((token) => haystack.includes(token));
};

export const filterAdminSearchEntries = (entries, query = '') => entries.filter(
    (entry) => matchesAdminSearchEntry(entry, query),
);

export const initAdminSearch = (root = document) => {
    const containers = [...root.querySelectorAll('[data-admin-search]')];

    for (const container of containers) {
        const input = container.querySelector('[data-admin-search-input]');
        const results = container.querySelector('[data-admin-search-results]');
        const empty = container.querySelector('[data-admin-search-empty]');
        const indexNode = container.querySelector('[data-admin-search-index]');

        if (!(input instanceof HTMLInputElement)
            || !(results instanceof HTMLElement)
            || !(indexNode instanceof HTMLElement)) {
            continue;
        }

        let entries = [];
        try {
            entries = JSON.parse(indexNode.textContent || '[]');
        } catch {
            entries = [];
        }

        if (!Array.isArray(entries)) {
            entries = [];
        }

        const render = () => {
            const query = input.value;
            const matches = filterAdminSearchEntries(entries, query);
            results.replaceChildren();

            if (query.trim() === '') {
                results.hidden = true;
                if (empty instanceof HTMLElement) {
                    empty.hidden = true;
                }
                return;
            }

            if (matches.length === 0) {
                results.hidden = true;
                if (empty instanceof HTMLElement) {
                    empty.hidden = false;
                }
                return;
            }

            if (empty instanceof HTMLElement) {
                empty.hidden = true;
            }
            results.hidden = false;

            for (const entry of matches) {
                const item = document.createElement('li');
                item.className = 'admin-search__result';
                item.setAttribute('role', 'option');

                const link = document.createElement('a');
                link.href = entry.url;
                link.className = 'admin-search__result-link';

                const title = document.createElement('strong');
                title.textContent = entry.label;
                link.append(title);

                if (entry.description) {
                    const description = document.createElement('span');
                    description.className = 'admin-search__result-description muted';
                    description.textContent = entry.description;
                    link.append(description);
                }

                item.append(link);
                results.append(item);
            }
        };

        input.addEventListener('input', render);
        input.addEventListener('search', render);
        render();
    }
};
