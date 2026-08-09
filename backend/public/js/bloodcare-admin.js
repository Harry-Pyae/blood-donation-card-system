(() => {
    'use strict';

    const storageKey = 'bloodcare.sidebar.collapsed';
    const desktopQuery = window.matchMedia('(min-width: 992px)');

    const readPreference = () => {
        try {
            return window.localStorage.getItem(storageKey) === 'true';
        } catch (error) {
            return false;
        }
    };

    const writePreference = (collapsed) => {
        try {
            window.localStorage.setItem(storageKey, String(collapsed));
        } catch (error) {
            // The sidebar still works when private browsing blocks local storage.
        }
    };

    const initializeSidebar = () => {
        const root = document.documentElement;
        const sidebar = document.querySelector('aside.navbar-vertical');
        const toggle = document.getElementById('bc-sidebar-toggle');

        if (!sidebar || !toggle) {
            return;
        }

        const accessibleLabel = toggle.querySelector('.visually-hidden');
        const collapseLabel = toggle.dataset.collapseLabel || 'Collapse sidebar';
        const expandLabel = toggle.dataset.expandLabel || 'Expand sidebar';

        const applyState = (requestedState) => {
            const collapsed = desktopQuery.matches && requestedState;
            const label = collapsed ? expandLabel : collapseLabel;

            root.classList.toggle('bc-sidebar-collapsed', collapsed);
            toggle.setAttribute('aria-expanded', String(!collapsed));
            toggle.setAttribute('aria-label', label);
            toggle.title = label;

            if (accessibleLabel) {
                accessibleLabel.textContent = label;
            }

        };

        applyState(readPreference());

        toggle.addEventListener('click', () => {
            const collapsed = !root.classList.contains('bc-sidebar-collapsed');
            writePreference(collapsed);
            applyState(collapsed);
        });

        desktopQuery.addEventListener('change', () => {
            applyState(readPreference());
        });
    };

    const initializeUtilityControls = () => {
        document.querySelectorAll('[data-bc-theme-toggle]').forEach((themeToggle) => {
            const applyTheme = (theme, persist = false) => {
                const normalized = theme === 'dark' ? 'dark' : 'light';
                const nextLabel = normalized === 'dark'
                    ? themeToggle.dataset.lightLabel
                    : themeToggle.dataset.darkLabel;

                document.documentElement.setAttribute('data-bs-theme', normalized);
                document.documentElement.setAttribute('data-theme', normalized);
                themeToggle.setAttribute('aria-label', nextLabel || '');
                themeToggle.setAttribute('title', nextLabel || '');

                if (persist) {
                    try {
                        window.localStorage.setItem('colorMode', normalized);
                    } catch (error) {
                        // The selected theme still applies for the current page.
                    }
                }
            };

            applyTheme(document.documentElement.getAttribute('data-bs-theme'));

            themeToggle.addEventListener('click', () => {
                const current = document.documentElement.getAttribute('data-bs-theme');
                applyTheme(current === 'dark' ? 'light' : 'dark', true);
            });
        });

        document.querySelectorAll('[data-bc-language-form]').forEach((languageForm) => {
            const languageMenu = languageForm.querySelector('[data-bc-language-menu]');

            if (!languageMenu) {
                return;
            }

            document.addEventListener('click', (event) => {
                if (languageMenu.open && !languageForm.contains(event.target)) {
                    languageMenu.open = false;
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && languageMenu.open) {
                    languageMenu.open = false;
                    languageMenu.querySelector('summary')?.focus();
                }
            });

            languageForm.addEventListener('submit', (event) => {
                if (event.submitter) {
                    event.submitter.setAttribute('aria-busy', 'true');
                }
            });
        });
    };

    const initializeResponsiveTables = () => {
        const tables = document.querySelectorAll('table.bc-table, table.bc-module-table, table.bc-report-table');

        tables.forEach((table) => {
            const headerRow = table.tHead?.rows[table.tHead.rows.length - 1];
            const labels = headerRow
                ? Array.from(headerRow.cells, (cell) => cell.textContent.trim())
                : [];
            const body = table.tBodies[0];

            if (!body || labels.length === 0) {
                return;
            }

            table.classList.add('bc-responsive-table');
            table.closest('.table-responsive')?.classList.add('bc-responsive-table-wrap');

            Array.from(headerRow.cells).forEach((cell) => {
                if (!cell.hasAttribute('scope')) {
                    cell.setAttribute('scope', 'col');
                }
            });

            const labelRows = () => {
                Array.from(body.rows).forEach((row) => {
                    Array.from(row.cells).forEach((cell, index) => {
                        const isSpanningCell = cell.colSpan > 1;

                        cell.classList.toggle('bc-responsive-table-empty', isSpanningCell);

                        if (isSpanningCell || !labels[index]) {
                            cell.removeAttribute('data-label');
                            return;
                        }

                        cell.setAttribute('data-label', labels[index]);
                    });
                });
            };

            labelRows();

            new MutationObserver(labelRows).observe(body, {
                childList: true,
                subtree: true,
            });
        });
    };

    const initialize = () => {
        initializeSidebar();
        initializeUtilityControls();
        initializeResponsiveTables();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
