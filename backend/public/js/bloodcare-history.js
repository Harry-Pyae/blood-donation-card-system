(() => {
    'use strict';

    const root = document.querySelector('[data-bc-module="history"]');
    const configNode = document.getElementById('bc-history-config');

    if (!root || !configNode) {
        return;
    }

    const config = JSON.parse(configNode.textContent);
    const labels = config.labels;
    const storageKey = 'bloodcare.history.interactive.v1';
    const pageSize = 5;

    const elements = {
        body: document.getElementById('bc-history-table-body'),
        search: document.getElementById('bc-history-search'),
        type: document.getElementById('bc-history-type'),
        staff: document.getElementById('bc-history-staff'),
        date: document.getElementById('bc-history-date'),
        resultCount: document.getElementById('bc-history-result-count'),
        page: document.getElementById('bc-history-page'),
        previous: document.getElementById('bc-history-prev'),
        next: document.getElementById('bc-history-next'),
        export: document.getElementById('bc-history-export'),
        dataSourceButton: document.getElementById('bc-data-source-button'),
        details: document.getElementById('bc-history-details'),
        detailsContent: document.getElementById('bc-history-details-content'),
        dataSource: document.getElementById('bc-history-data-source-modal'),
        toast: document.getElementById('bc-history-toast'),
    };

    const state = {
        records: readRecords(),
        page: 1,
        detailId: null,
    };

    const customSelects = new Map();
    let lastFocusedElement = null;
    let toastTimer = null;

    function normalizeRecord(record) {
        return {
            id: String(record.id || ''),
            type: String(record.type || 'Activity'),
            action: String(record.action || ''),
            reference: String(record.reference || ''),
            donorId: String(record.donorId || ''),
            donorName: String(record.donorName || ''),
            staff: String(record.staff || config.staffName || ''),
            dateTime: String(record.dateTime || `${config.today}T12:00:00`),
            result: String(record.result || 'Completed'),
            details: String(record.details || ''),
            source: String(record.source || 'interactive'),
        };
    }

    function isValidRecord(record) {
        return record
            && typeof record.id === 'string'
            && typeof record.action === 'string'
            && typeof record.reference === 'string'
            && typeof record.dateTime === 'string';
    }

    function readStoredRecords() {
        return [];
    }

    function readRecords() {
        return config.records.map(normalizeRecord).sort(
            (left, right) => parseDateTime(right.dateTime) - parseDateTime(left.dateTime),
        );
    }

    function parseDateTime(value) {
        const parsed = new Date(value);
        return Number.isNaN(parsed.getTime()) ? new Date(`${config.today}T00:00:00`) : parsed;
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function interpolate(template, values) {
        return Object.entries(values).reduce(
            (output, [key, value]) => output.replaceAll(`:${key}`, String(value)),
            template,
        );
    }

    function statusSlug(value) {
        return String(value).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    }

    function translatedType(type) {
        return labels.types[type] || type;
    }

    function translatedAction(action) {
        return labels.actions[action] || action;
    }

    function translatedResult(result) {
        return labels.results[result] || result;
    }

    function typeIcon(type) {
        return {
            Donor: 'la-user',
            Card: 'la-id-card',
            Donation: 'la-tint',
            Inventory: 'la-boxes',
            Appointment: 'la-calendar-check',
            Centre: 'la-hospital',
            User: 'la-user-shield',
        }[type] || 'la-history';
    }

    function localeName() {
        return config.locale === 'my' ? 'my-MM' : 'en-GB';
    }

    function formatDate(value) {
        return new Intl.DateTimeFormat(localeName(), {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        }).format(parseDateTime(value));
    }

    function formatTime(value) {
        return new Intl.DateTimeFormat(localeName(), {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        }).format(parseDateTime(value));
    }

    function formatDateTime(value) {
        return new Intl.DateTimeFormat(localeName(), {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        }).format(parseDateTime(value));
    }

    function populateStaffFilter() {
        const widget = elements.staff?.closest('[data-bc-select]');
        const menu = widget?.querySelector('.bc-filter-dropdown-menu');

        if (!elements.staff || !menu) {
            return;
        }

        const knownValues = new Set([...elements.staff.options].map((option) => option.value));
        const staffNames = [...new Set(state.records.map((record) => record.staff).filter(Boolean))]
            .sort((left, right) => left.localeCompare(right));

        for (const staff of staffNames) {
            if (knownValues.has(staff)) {
                continue;
            }

            const option = document.createElement('option');
            option.value = staff;
            option.textContent = staff;
            elements.staff.append(option);

            const menuOption = document.createElement('button');
            menuOption.type = 'button';
            menuOption.setAttribute('role', 'option');
            menuOption.setAttribute('aria-selected', 'false');
            menuOption.dataset.value = staff;

            const label = document.createElement('span');
            label.textContent = staff;
            const check = document.createElement('i');
            check.className = 'la la-check';
            check.setAttribute('aria-hidden', 'true');
            menuOption.append(label, check);
            menu.append(menuOption);
        }
    }

    function initializeCustomSelects() {
        const widgets = [...root.querySelectorAll('[data-bc-select]')];

        const closeAll = (except = null) => {
            for (const widget of widgets) {
                if (widget === except) {
                    continue;
                }

                const trigger = widget.querySelector('.bc-filter-dropdown-trigger');
                const menu = widget.querySelector('.bc-filter-dropdown-menu');
                widget.classList.remove('open');
                trigger?.setAttribute('aria-expanded', 'false');

                if (menu) {
                    menu.hidden = true;
                }
            }
        };

        for (const widget of widgets) {
            const select = widget.querySelector('select');
            const trigger = widget.querySelector('.bc-filter-dropdown-trigger');
            const menu = widget.querySelector('.bc-filter-dropdown-menu');
            const label = widget.querySelector('[data-bc-select-label]');
            const canonicalOptions = [...widget.querySelectorAll('[role="option"]')];

            if (!select || !trigger || !menu || !label || canonicalOptions.length === 0) {
                continue;
            }

            const orderedOptions = () => [...menu.querySelectorAll('[role="option"]')];

            const sync = () => {
                const selected = canonicalOptions.find(
                    (option) => option.dataset.value === select.value,
                ) || canonicalOptions[0];
                label.textContent = selected.querySelector('span')?.textContent.trim() || selected.textContent.trim();

                for (const option of canonicalOptions) {
                    option.setAttribute('aria-selected', String(option === selected));
                }

                // Keep exactly six choices in the menu. The active choice is
                // always the first row and the other five retain their normal
                // activity order below it.
                menu.replaceChildren(
                    selected,
                    ...canonicalOptions.filter((option) => option !== selected),
                );
                menu.scrollTop = 0;
            };

            const close = (restoreFocus = false) => {
                widget.classList.remove('open');
                trigger.setAttribute('aria-expanded', 'false');
                menu.hidden = true;

                if (restoreFocus) {
                    trigger.focus();
                }
            };

            const open = () => {
                closeAll(widget);
                widget.classList.add('open');
                trigger.setAttribute('aria-expanded', 'true');
                menu.hidden = false;
                menu.scrollTop = 0;
            };

            const focusOption = (index) => {
                const options = orderedOptions();
                options[(index + options.length) % options.length].focus();
            };

            const choose = (option) => {
                select.value = option.dataset.value;
                sync();
                close(true);
                select.dispatchEvent(new Event('change', { bubbles: true }));
            };

            trigger.addEventListener('click', () => {
                if (menu.hidden) {
                    open();
                } else {
                    close();
                }
            });

            trigger.addEventListener('keydown', (event) => {
                if (!['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
                    return;
                }

                event.preventDefault();
                open();
                const options = orderedOptions();
                const selectedIndex = Math.max(0, options.findIndex(
                    (option) => option.getAttribute('aria-selected') === 'true',
                ));
                focusOption(event.key === 'ArrowUp' ? selectedIndex - 1 : selectedIndex);
            });

            canonicalOptions.forEach((option) => {
                option.addEventListener('click', () => choose(option));
                option.addEventListener('keydown', (event) => {
                    const options = orderedOptions();
                    const index = options.indexOf(option);

                    if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        focusOption(index + 1);
                    } else if (event.key === 'ArrowUp') {
                        event.preventDefault();
                        focusOption(index - 1);
                    } else if (event.key === 'Home') {
                        event.preventDefault();
                        focusOption(0);
                    } else if (event.key === 'End') {
                        event.preventDefault();
                        focusOption(options.length - 1);
                    } else if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        choose(option);
                    } else if (event.key === 'Escape') {
                        event.preventDefault();
                        close(true);
                    } else if (event.key === 'Tab') {
                        close();
                    }
                });
            });

            select.addEventListener('change', sync);
            customSelects.set(select, { sync, close });
            sync();
        }

        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-bc-select]')) {
                closeAll();
            }
        });
    }

    function withinSelectedDate(record) {
        if (elements.date.value === 'all') {
            return true;
        }

        const recordDate = parseDateTime(record.dateTime);
        const today = new Date(`${config.today}T23:59:59`);

        if (elements.date.value === 'today') {
            return record.dateTime.slice(0, 10) === config.today;
        }

        const days = Number(elements.date.value);
        const cutoff = new Date(`${config.today}T00:00:00`);
        cutoff.setDate(cutoff.getDate() - (days - 1));
        return recordDate >= cutoff && recordDate <= today;
    }

    function filteredRecords() {
        const query = elements.search.value.trim().toLowerCase();

        return state.records.filter((record) => {
            const matchesSearch = !query || [
                record.type,
                record.action,
                record.reference,
                record.donorId,
                record.donorName,
                record.staff,
                record.details,
                record.result,
            ].some((value) => value.toLowerCase().includes(query));
            const matchesType = elements.type.value === 'all' || record.type === elements.type.value;
            const matchesSelectedType = elements.type.value === 'Appointment'
                ? ['Appointment', 'Centre'].includes(record.type)
                : matchesType;
            const matchesStaff = elements.staff.value === 'all' || record.staff === elements.staff.value;
            return matchesSearch && matchesSelectedType && matchesStaff && withinSelectedDate(record);
        });
    }

    function renderRows() {
        const records = filteredRecords();
        const totalPages = Math.max(1, Math.ceil(records.length / pageSize));
        state.page = Math.min(state.page, totalPages);
        const start = (state.page - 1) * pageSize;
        const visible = records.slice(start, start + pageSize);

        if (visible.length === 0) {
            elements.body.innerHTML = `
                <tr class="bc-empty-row">
                    <td colspan="7"><i class="la la-search"></i><span>${escapeHtml(labels.noResults)}</span></td>
                </tr>`;
        } else {
            elements.body.innerHTML = visible.map((record) => `
                <tr>
                    <td>
                        <span class="bc-history-time">
                            <strong>${escapeHtml(formatTime(record.dateTime))}</strong>
                            <small>${escapeHtml(formatDate(record.dateTime))}</small>
                        </span>
                    </td>
                    <td>
                        <span class="bc-history-activity">
                            <span class="bc-history-type-icon bc-history-type-${statusSlug(record.type)}">
                                <i class="la ${typeIcon(record.type)}"></i>
                            </span>
                            <span>
                                <strong>${escapeHtml(translatedAction(record.action))}</strong>
                                <small>${escapeHtml(translatedType(record.type))}</small>
                            </span>
                        </span>
                    </td>
                    <td><strong>${escapeHtml(record.reference)}</strong></td>
                    <td>${escapeHtml(record.staff)}</td>
                    <td><span class="bc-history-detail-preview">${escapeHtml(record.details || labels.notApplicable)}</span></td>
                    <td><span class="bc-status bc-status-${statusSlug(record.result)}">${escapeHtml(translatedResult(record.result))}</span></td>
                    <td class="text-end">
                        <button class="bc-row-action" type="button" data-history-view="${escapeHtml(record.id)}"
                                aria-label="${escapeHtml(interpolate(labels.viewEvent, { reference: record.reference }))}">
                            <i class="la la-eye"></i>
                        </button>
                    </td>
                </tr>`).join('');
        }

        const from = records.length === 0 ? 0 : start + 1;
        const to = Math.min(start + pageSize, records.length);
        elements.resultCount.textContent = interpolate(labels.showingRange, {
            from,
            to,
            total: records.length,
        });
        elements.page.textContent = String(state.page);
        elements.previous.disabled = state.page <= 1;
        elements.next.disabled = state.page >= totalPages;
    }

    function renderMetrics() {
        const activitiesToday = state.records.filter(
            (record) => record.dateTime.slice(0, 10) === config.today,
        ).length;
        const staffActive = new Set(state.records.map((record) => record.staff).filter(Boolean)).size;
        const inventoryChanges = state.records.filter((record) => record.type === 'Inventory').length;
        const values = {
            today: activitiesToday,
            staff: staffActive,
            inventory: inventoryChanges,
        };

        for (const metric of root.querySelectorAll('[data-history-metric]')) {
            metric.querySelector('strong').textContent = values[metric.dataset.historyMetric].toLocaleString(localeName());
        }
    }

    function render() {
        renderRows();
        renderMetrics();
    }

    function openModal(modal) {
        if (!modal) {
            return;
        }

        lastFocusedElement = document.activeElement;
        modal.hidden = false;
        document.body.classList.add('bc-modal-open');
        modal.querySelector('.bc-modal-close, button, input')?.focus();
    }

    function closeModal(modal) {
        if (!modal || modal.hidden) {
            return;
        }

        modal.hidden = true;

        if (![...document.querySelectorAll('.bc-modal')].some((item) => !item.hidden)) {
            document.body.classList.remove('bc-modal-open');
        }

        lastFocusedElement?.focus?.();
    }

    function sourceLabel(source) {
        return source
            .replace('-interactive', ' workflow')
            .replaceAll('-', ' ')
            .replace(/\b\w/g, (letter) => letter.toUpperCase());
    }

    function openDetails(record) {
        state.detailId = record.id;
        const fields = [
            [labels.eventId, record.id],
            [labels.time, formatDateTime(record.dateTime)],
            [labels.activity, `${translatedType(record.type)} · ${translatedAction(record.action)}`],
            [labels.reference, record.reference],
            [labels.staffMember, record.staff],
            [labels.result, translatedResult(record.result)],
            [labels.donorId, record.donorId || labels.notApplicable],
            [labels.donor, record.donorName || labels.notApplicable],
        ];

        elements.detailsContent.innerHTML = `
            <div class="bc-history-detail-hero">
                <span class="bc-history-type-icon bc-history-type-${statusSlug(record.type)}">
                    <i class="la ${typeIcon(record.type)}"></i>
                </span>
                <div>
                    <p>${escapeHtml(translatedType(record.type))}</p>
                    <h3>${escapeHtml(translatedAction(record.action))}</h3>
                    <span class="bc-status bc-status-${statusSlug(record.result)}">${escapeHtml(translatedResult(record.result))}</span>
                </div>
            </div>
            <div class="bc-unit-detail-grid">
                ${fields.map(([label, value]) => `
                    <div><small>${escapeHtml(label)}</small><strong>${escapeHtml(value)}</strong></div>
                `).join('')}
            </div>
            <div class="bc-unit-detail-note">
                <small>${escapeHtml(labels.details)}</small>
                <p>${escapeHtml(record.details || labels.notApplicable)}</p>
            </div>
            <div class="bc-history-source">
                <i class="la la-fingerprint"></i>
                <span><small>${escapeHtml(labels.source)}</small><strong>${escapeHtml(sourceLabel(record.source))}</strong></span>
            </div>`;

        openModal(elements.details);
    }

    function csvCell(value) {
        return `"${String(value ?? '').replaceAll('"', '""')}"`;
    }

    function exportCsv() {
        const records = filteredRecords();
        const rows = [
            labels.csvColumns,
            ...records.map((record) => [
                record.id,
                record.dateTime,
                translatedType(record.type),
                translatedAction(record.action),
                record.reference,
                record.donorId,
                record.donorName,
                record.staff,
                record.details,
                translatedResult(record.result),
            ]),
        ];
        const csv = `\uFEFF${rows.map((row) => row.map(csvCell).join(',')).join('\r\n')}`;
        const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
        const link = document.createElement('a');
        link.href = url;
        link.download = `bloodcare-history-${config.today}.csv`;
        document.body.append(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
        showToast(interpolate(labels.exportedMessage, { count: records.length }));
    }

    function showToast(message) {
        window.clearTimeout(toastTimer);
        elements.toast.querySelector('span').textContent = message;
        elements.toast.hidden = false;
        window.requestAnimationFrame(() => elements.toast.classList.add('show'));
        toastTimer = window.setTimeout(() => {
            elements.toast.classList.remove('show');
            window.setTimeout(() => {
                elements.toast.hidden = true;
            }, 180);
        }, 3200);
    }

    elements.search.addEventListener('input', () => {
        state.page = 1;
        renderRows();
    });

    for (const select of [elements.type, elements.staff, elements.date]) {
        select.addEventListener('change', () => {
            state.page = 1;
            renderRows();
        });
    }

    elements.previous.addEventListener('click', () => {
        state.page = Math.max(1, state.page - 1);
        renderRows();
    });

    elements.next.addEventListener('click', () => {
        const totalPages = Math.max(1, Math.ceil(filteredRecords().length / pageSize));

        if (state.page < totalPages) {
            state.page += 1;
            renderRows();
        }
    });

    elements.export.addEventListener('click', exportCsv);
    elements.dataSourceButton.addEventListener('click', () => openModal(elements.dataSource));

    root.addEventListener('click', (event) => {
        const viewButton = event.target.closest('[data-history-view]');
        const closeButton = event.target.closest('[data-modal-close]');

        if (viewButton) {
            const record = state.records.find((candidate) => candidate.id === viewButton.dataset.historyView);

            if (record) {
                openDetails(record);
            }
        } else if (closeButton) {
            closeModal(closeButton.closest('.bc-modal'));
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        const openModalElement = [...document.querySelectorAll('.bc-modal')]
            .reverse()
            .find((modal) => !modal.hidden);
        closeModal(openModalElement);
    });

    window.addEventListener('storage', (event) => {
        if (event.key === storageKey) {
            state.records = readRecords();
            populateStaffFilter();
            render();
        }
    });

    populateStaffFilter();
    initializeCustomSelects();
    render();
})();
