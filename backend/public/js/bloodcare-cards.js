(() => {
    'use strict';

    const root = document.querySelector('[data-bc-module="cards"]');
    const configNode = document.getElementById('bc-card-config');

    if (!root || !configNode) {
        return;
    }

    const config = JSON.parse(configNode.textContent);
    const labels = config.labels;
    const storageKey = 'bloodcare.cards.interactive.v1';
    const donorStorageKey = 'bloodcare.donors.interactive.v1';
    const historyStorageKey = 'bloodcare.history.interactive.v1';
    const auditSource = 'cards-database';
    const pageSize = 5;
    const donorSearchDebounceMs = 250;
    const originalRecords = config.records.map(normalizeRecord);
    const originalContribution = contributionFor(originalRecords);

    function endpoint(template, reference) {
        return template.replace('__REFERENCE__', encodeURIComponent(reference));
    }

    async function apiRequest(url, method, payload) {
        const response = await fetch(url, {
            method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
            },
            body: payload === undefined ? undefined : JSON.stringify(payload),
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const validation = Object.values(data.errors || {}).flat().join(' ');
            throw new Error(validation || data.message || 'Unable to save the card.');
        }

        return data;
    }

    function cardPayload(record, action = '') {
        return {
            issueDate: record.issueDate,
            expiryDate: record.expiryDate,
            status: record.status === 'Suspended' ? 'Suspended' : 'Active',
            replacementCount: Number(record.replacementCount || 0),
            printCount: Number(record.printCount || 0),
            notes: record.notes || '',
            action,
        };
    }

    const elements = {
        body: document.getElementById('bc-card-table-body'),
        search: document.getElementById('bc-card-search'),
        group: document.getElementById('bc-card-group'),
        status: document.getElementById('bc-card-status'),
        issued: document.getElementById('bc-card-issued'),
        resultCount: document.getElementById('bc-card-result-count'),
        page: document.getElementById('bc-card-page'),
        previous: document.getElementById('bc-card-prev'),
        next: document.getElementById('bc-card-next'),
        primaryAction: document.getElementById('bc-primary-action'),
        dataSourceButton: document.getElementById('bc-data-source-button'),
        editor: document.getElementById('bc-card-editor'),
        details: document.getElementById('bc-card-details'),
        confirm: document.getElementById('bc-card-confirm'),
        dataSource: document.getElementById('bc-card-data-source-modal'),
        form: document.getElementById('bc-card-form'),
        formError: document.getElementById('bc-card-form-error'),
        donor: document.getElementById('bc-card-donor'),
        donorSearch: document.getElementById('bc-card-donor-search'),
        donorResults: document.getElementById('bc-card-donor-results'),
        cardNumber: document.getElementById('bc-card-number'),
        formGroup: document.getElementById('bc-card-form-group'),
        formPhone: document.getElementById('bc-card-form-phone'),
        issueDate: document.getElementById('bc-card-issue-date'),
        expiryDate: document.getElementById('bc-card-expiry-date'),
        notes: document.getElementById('bc-card-notes'),
        detailsContent: document.getElementById('bc-card-details-content'),
        detailTabs: [...document.querySelectorAll('[data-card-detail-view]')],
        confirmTitle: document.getElementById('bc-card-confirm-title'),
        confirmMessage: document.getElementById('bc-card-confirm-message'),
        confirmIcon: document.getElementById('bc-card-confirm-icon'),
        confirmSubmit: document.getElementById('bc-card-confirm-submit'),
        copy: document.getElementById('bc-card-copy'),
        print: document.getElementById('bc-card-print'),
        reset: document.getElementById('bc-reset-cards'),
        toast: document.getElementById('bc-card-toast'),
    };

    const state = {
        donors: readDonors(),
        donorMatches: [],
        history: readHistory(),
        records: [],
        page: 1,
        detailId: null,
    };

    state.records = syncWithDonors(readRecords(), state.donors);

    const customSelects = new Map();
    let lastFocusedElement = null;
    let toastTimer = null;
    let pendingConfirmation = null;
    let donorSearchTimer = null;
    let donorSearchController = null;
    let donorSearchRequestId = 0;

    function normalizeRecord(record) {
        const normalized = {
            cardNumber: String(record.cardNumber || record.donorId || ''),
            donorId: String(record.donorId || record.cardNumber || ''),
            donorName: String(record.donorName || ''),
            group: String(record.group || ''),
            phone: String(record.phone || ''),
            issueDate: record.issueDate || null,
            expiryDate: record.expiryDate || null,
            status: String(record.status || 'Pending'),
            replacementCount: Number(record.replacementCount || 0),
            printCount: Number(record.printCount || 0),
            qrToken: String(record.qrToken || ''),
            traceUrl: String(record.traceUrl || ''),
            notes: String(record.notes || ''),
        };

        normalized.status = deriveStatus(normalized);
        return normalized;
    }

    function readRecords() {
        return originalRecords.map((record) => ({ ...record }));
    }

    function readDonors() {
        return config.donors.map(normalizeDonor);
    }

    function normalizeDonor(donor) {
        return {
            id: String(donor.id || ''),
            name: String(donor.name || ''),
            group: String(donor.group || ''),
            phone: String(donor.phone || ''),
            eligibility: String(donor.eligibility || 'Review'),
            status: String(donor.status || 'Pending'),
        };
    }

    function syncWithDonors(records, donors) {
        const synchronized = records.map((record) => {
            const donor = donors.find((candidate) => candidate.id === record.donorId);

            return donor
                ? normalizeRecord({
                    ...record,
                    donorName: donor.name,
                    group: donor.group,
                    phone: donor.phone,
                })
                : normalizeRecord(record);
        });

        for (const donor of donors) {
            if (donor.status === 'Inactive'
                || synchronized.some((record) => record.donorId === donor.id)) {
                continue;
            }

            synchronized.push(normalizeRecord({
                cardNumber: donor.id,
                donorId: donor.id,
                donorName: donor.name,
                group: donor.group,
                phone: donor.phone,
                issueDate: null,
                expiryDate: null,
                status: 'Pending',
                replacementCount: 0,
                printCount: 0,
                qrToken: '',
                traceUrl: '',
                notes: '',
            }));
        }

        return synchronized;
    }

    function saveRecords() {
        // Laravel and MySQL are the source of truth.
    }

    function readHistory() {
        return [];
    }

    function auditDateTime() {
        const now = new Date();
        const time = [now.getHours(), now.getMinutes(), now.getSeconds()]
            .map((value) => String(value).padStart(2, '0'))
            .join(':');
        return `${config.today}T${time}`;
    }

    function auditEvent(record, action) {
        // The authenticated Laravel route writes the permanent activity log.
    }

    function parseDate(value) {
        return value ? new Date(`${value}T00:00:00`) : null;
    }

    function isoDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function addYears(value, years) {
        const date = parseDate(value);

        if (!date) {
            return '';
        }

        const originalMonth = date.getMonth();
        date.setFullYear(date.getFullYear() + Number(years));

        if (date.getMonth() !== originalMonth) {
            date.setDate(0);
        }

        return isoDate(date);
    }

    function addDays(value, days) {
        const date = parseDate(value);
        date.setDate(date.getDate() + Number(days));
        return isoDate(date);
    }

    function deriveStatus(record) {
        if (!record.issueDate || !record.expiryDate) {
            return 'Pending';
        }

        if (record.status === 'Suspended') {
            return 'Suspended';
        }

        if (record.expiryDate < config.today) {
            return 'Expired';
        }

        if (record.expiryDate <= addDays(config.today, config.expiringWithinDays)) {
            return 'Expiring';
        }

        return 'Active';
    }

    function contributionFor(records) {
        return records.reduce((totals, record) => {
            const status = deriveStatus(record);

            if (status === 'Active' || status === 'Expiring') {
                totals.active += 1;
            }

            if (status === 'Pending') {
                totals.pending += 1;
            }

            if (status === 'Expiring') {
                totals.expiring += 1;
            }

            return totals;
        }, { active: 0, pending: 0, expiring: 0 });
    }

    function formatDate(value) {
        const date = parseDate(value);

        if (!date) {
            return labels.notIssued;
        }

        return new Intl.DateTimeFormat(config.locale === 'my' ? 'my-MM' : 'en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        }).format(date);
    }

    function escapeHtml(value) {
        return String(value ?? '')
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
        return String(value).toLowerCase().replace(/\s+/g, '-');
    }

    function translatedStatus(status) {
        return labels[statusSlug(status)] || status;
    }

    function initializeCustomSelects() {
        const widgets = [...root.querySelectorAll('[data-bc-select]')];

        const closeAll = (except = null) => {
            for (const widget of widgets) {
                if (widget === except) {
                    continue;
                }

                widget.classList.remove('open');
                widget.querySelector('.bc-filter-dropdown-trigger')
                    ?.setAttribute('aria-expanded', 'false');

                const menu = widget.querySelector('.bc-filter-dropdown-menu');
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
                const selected = canonicalOptions.find((option) => option.dataset.value === select.value)
                    || canonicalOptions[0];
                label.textContent = selected.querySelector('span')?.textContent.trim()
                    || selected.textContent.trim();

                for (const option of canonicalOptions) {
                    option.setAttribute('aria-selected', String(option === selected));
                }

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

            const choose = (option) => {
                select.value = option.dataset.value;
                sync();
                close(true);
                select.dispatchEvent(new Event('change', { bubbles: true }));
            };

            trigger.addEventListener('click', () => (menu.hidden ? open() : close()));
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
                const nextIndex = event.key === 'ArrowUp'
                    ? (selectedIndex - 1 + options.length) % options.length
                    : selectedIndex;
                options[nextIndex].focus();
            });

            canonicalOptions.forEach((option) => {
                option.addEventListener('click', () => choose(option));
                option.addEventListener('keydown', (event) => {
                    const options = orderedOptions();
                    const index = options.indexOf(option);

                    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                        event.preventDefault();
                        const direction = event.key === 'ArrowDown' ? 1 : -1;
                        options[(index + direction + options.length) % options.length].focus();
                    } else if (event.key === 'Home' || event.key === 'End') {
                        event.preventDefault();
                        options[event.key === 'Home' ? 0 : options.length - 1].focus();
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

            customSelects.set(select, sync);
            sync();
        }

        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-bc-select]')) {
                closeAll();
            }
        });
    }

    function syncCustomSelect(select) {
        customSelects.get(select)?.();
    }

    function filteredRecords() {
        const term = elements.search.value.trim().toLowerCase();
        const currentYear = config.today.slice(0, 4);
        const currentMonth = config.today.slice(0, 7);

        return state.records.filter((record) => {
            record.status = deriveStatus(record);
            const searchable = [
                record.cardNumber,
                record.donorName,
                record.group,
                record.phone,
                record.status,
                translatedStatus(record.status),
            ].join(' ').toLowerCase();

            if (term && !searchable.includes(term)) {
                return false;
            }

            if (elements.group.value !== 'all' && record.group !== elements.group.value) {
                return false;
            }

            if (elements.status.value !== 'all' && record.status !== elements.status.value) {
                return false;
            }

            if (elements.issued.value === 'month') {
                return record.issueDate?.startsWith(currentMonth);
            }

            if (elements.issued.value === 'year') {
                return record.issueDate?.startsWith(currentYear);
            }

            if (elements.issued.value === 'older') {
                return record.issueDate && record.issueDate.slice(0, 4) < currentYear;
            }

            if (elements.issued.value === 'pending') {
                return !record.issueDate;
            }

            return true;
        });
    }

    function rowActions(record) {
        if (record.status === 'Pending') {
            return `
                <button type="button" data-card-action="issue" data-card-id="${escapeHtml(record.cardNumber)}">
                    <i class="la la-id-card"></i>${escapeHtml(labels.issueCard || labels.view)}
                </button>`;
        }

        const actions = [
            `<button type="button" data-card-action="view" data-card-id="${escapeHtml(record.cardNumber)}"><i class="la la-eye"></i>${escapeHtml(labels.view)}</button>`,
            `<button type="button" data-card-action="print" data-card-id="${escapeHtml(record.cardNumber)}"><i class="la la-print"></i>${escapeHtml(labels.print)}</button>`,
            `<button type="button" data-card-action="renew" data-card-id="${escapeHtml(record.cardNumber)}"><i class="la la-sync"></i>${escapeHtml(labels.renew)}</button>`,
        ];

        if (record.status !== 'Expired') {
            actions.push(`<button type="button" data-card-action="replace" data-card-id="${escapeHtml(record.cardNumber)}"><i class="la la-redo"></i>${escapeHtml(labels.replaceLost)}</button>`);
        }

        actions.push('<span class="bc-row-menu-divider"></span>');

        if (record.status === 'Suspended') {
            actions.push(`<button type="button" data-card-action="reactivate" data-card-id="${escapeHtml(record.cardNumber)}"><i class="la la-check-circle"></i>${escapeHtml(labels.reactivate)}</button>`);
        } else if (record.status !== 'Expired') {
            actions.push(`<button class="bc-row-menu-danger" type="button" data-card-action="suspend" data-card-id="${escapeHtml(record.cardNumber)}"><i class="la la-ban"></i>${escapeHtml(labels.suspend)}</button>`);
        }

        return actions.join('');
    }

    function renderRows() {
        const records = filteredRecords();
        const totalPages = Math.max(1, Math.ceil(records.length / pageSize));
        state.page = Math.min(state.page, totalPages);
        const startIndex = (state.page - 1) * pageSize;
        const pageRecords = records.slice(startIndex, startIndex + pageSize);

        if (pageRecords.length === 0) {
            elements.body.innerHTML = `
                <tr>
                    <td class="bc-empty-state" colspan="7">
                        <i class="la la-id-card-alt"></i>
                        <strong>${escapeHtml(labels.noResults)}</strong>
                    </td>
                </tr>`;
        } else {
            elements.body.innerHTML = pageRecords.map((record) => {
                const initial = Array.from(record.donorName.trim())[0]?.toUpperCase() || 'D';

                return `
                    <tr>
                        <td><strong>${escapeHtml(record.cardNumber)}</strong></td>
                        <td>
                            <span class="bc-donor-cell">
                                <span class="bc-donor-table-avatar">${escapeHtml(initial)}</span>
                                <span>${escapeHtml(record.donorName)}</span>
                            </span>
                        </td>
                        <td><span class="bc-group-badge">${escapeHtml(record.group)}</span></td>
                        <td>${escapeHtml(record.issueDate ? formatDate(record.issueDate) : labels.notIssued)}</td>
                        <td>${escapeHtml(record.expiryDate ? formatDate(record.expiryDate) : '—')}</td>
                        <td><span class="bc-status bc-status-${statusSlug(record.status)}">${escapeHtml(translatedStatus(record.status))}</span></td>
                        <td class="text-end">
                            <span class="bc-row-menu-wrap">
                                <button class="bc-row-action" type="button" data-card-menu="${escapeHtml(record.cardNumber)}"
                                        aria-expanded="false"
                                        aria-label="${escapeHtml(interpolate(labels.actionsFor, { donor: record.donorName }))}">
                                    <i class="la la-ellipsis-h"></i>
                                </button>
                                <span class="bc-row-menu" role="menu" hidden>
                                    ${rowActions(record)}
                                </span>
                            </span>
                        </td>
                    </tr>`;
            }).join('');
        }

        const from = records.length === 0 ? 0 : startIndex + 1;
        const to = Math.min(startIndex + pageSize, records.length);
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
        const contribution = contributionFor(state.records);

        for (const metric of root.querySelectorAll('[data-card-metric]')) {
            const key = metric.dataset.cardMetric;
            const value = Number(config.summary[key])
                + contribution[key]
                - originalContribution[key];
            const output = metric.querySelector('strong');

            if (output) {
                output.textContent = new Intl.NumberFormat(
                    config.locale === 'my' ? 'my-MM' : 'en-US',
                ).format(Math.max(0, value));
            }
        }
    }

    function render() {
        closeRowMenus();
        renderRows();
        renderMetrics();
    }

    function verifiedDonor(donor) {
        return donor?.status === 'Active' && donor?.eligibility === 'Eligible';
    }

    function rememberDonors(donors) {
        for (const donor of donors) {
            const index = state.donors.findIndex((candidate) => candidate.id === donor.id);

            if (index >= 0) {
                state.donors[index] = donor;
            } else {
                state.donors.push(donor);
            }
        }
    }

    function donorSearchMessage(message, spinning = false) {
        elements.donorResults.setAttribute('aria-busy', String(spinning));
        elements.donorResults.innerHTML = `
            <div class="bc-card-donor-empty" role="status">
                <i class="la ${spinning ? 'la-spinner la-spin' : 'la-search'}" aria-hidden="true"></i>
                <span>${escapeHtml(message)}</span>
            </div>`;
    }

    function refreshDonorPicker(selectedValue = elements.donor.value) {
        const matches = state.donorMatches;
        const selectedDonor = state.donors.find((donor) => donor.id === selectedValue);
        const options = selectedDonor && !matches.some((donor) => donor.id === selectedDonor.id)
            ? [selectedDonor, ...matches]
            : matches;

        elements.donor.innerHTML = [
            `<option value="">${escapeHtml(labels.chooseDonorPlaceholder)}</option>`,
            ...options.map((donor) => (
                `<option value="${escapeHtml(donor.id)}">${escapeHtml(`${donor.id} · ${donor.name} · ${donor.group}`)}</option>`
            )),
        ].join('');
        elements.donor.value = options.some((donor) => donor.id === selectedValue)
            ? selectedValue
            : '';

        elements.donorResults.setAttribute('aria-busy', 'false');
        elements.donorResults.innerHTML = matches.length > 0
            ? matches.map((donor) => {
                const selected = donor.id === elements.donor.value;
                const initial = Array.from(donor.name.trim())[0]?.toUpperCase() || 'D';

                return `
                    <button class="bc-card-donor-option" type="button" role="option"
                            data-donor-value="${escapeHtml(donor.id)}" aria-selected="${selected}">
                        <span class="bc-card-donor-option-avatar" aria-hidden="true">${escapeHtml(initial)}</span>
                        <span class="bc-card-donor-option-copy">
                            <strong>${escapeHtml(donor.name)}</strong>
                            <small>${escapeHtml(donor.id)} · ${escapeHtml(donor.phone || labels.notRecorded)}</small>
                        </span>
                        <span class="bc-group-badge">${escapeHtml(donor.group)}</span>
                        <i class="la la-check-circle" aria-hidden="true"></i>
                    </button>`;
            }).join('')
            : `<div class="bc-card-donor-empty" role="status"><i class="la la-search"></i><span>${escapeHtml(
                elements.donorSearch.value.trim() ? labels.noDonorMatches : labels.noVerifiedDonors,
            )}</span></div>`;
    }

    async function searchVerifiedDonors(query = elements.donorSearch.value, selectedValue = elements.donor.value) {
        window.clearTimeout(donorSearchTimer);
        donorSearchController?.abort();
        donorSearchController = new AbortController();
        const requestId = ++donorSearchRequestId;
        const url = new URL(config.donorSearchUrl, window.location.origin);
        const term = String(query || '').trim();

        if (term) {
            url.searchParams.set('q', term);
        }

        donorSearchMessage(labels.searchingDonors, true);

        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                signal: donorSearchController.signal,
            });
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(data.message || labels.donorSearchUnavailable);
            }
            if (requestId !== donorSearchRequestId) {
                return;
            }

            const donors = Array.isArray(data.donors)
                ? data.donors.map(normalizeDonor).filter(verifiedDonor)
                : [];
            state.donorMatches = donors;
            rememberDonors(donors);
            refreshDonorPicker(selectedValue);
        } catch (error) {
            if (error.name === 'AbortError' || requestId !== donorSearchRequestId) {
                return;
            }

            state.donorMatches = [];
            donorSearchMessage(labels.donorSearchUnavailable);
        }
    }

    function chooseDonor(value) {
        elements.donor.value = value;
        const donor = state.donors.find((candidate) => candidate.id === value);
        elements.cardNumber.value = donor?.id || '';
        elements.formGroup.value = donor?.group || '';
        elements.formPhone.value = donor?.phone || '';
        refreshDonorPicker(value);
        elements.donor.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function openIssueEditor(preselectedDonorId = '') {
        state.donors = readDonors();
        state.donorMatches = [];
        state.records = syncWithDonors(state.records, state.donors);
        render();
        elements.form.reset();
        elements.formError.hidden = true;
        elements.formError.textContent = '';
        elements.donorSearch.value = '';
        elements.donor.innerHTML = `<option value="">${escapeHtml(labels.chooseDonorPlaceholder)}</option>`;
        donorSearchMessage(labels.searchingDonors, true);

        elements.issueDate.value = config.today;
        elements.expiryDate.value = addYears(config.today, config.validYears);
        elements.cardNumber.value = '';
        elements.formGroup.value = '';
        elements.formPhone.value = '';

        openModal(elements.editor, elements.donorSearch);
        searchVerifiedDonors(preselectedDonorId, preselectedDonorId).then(() => {
            if (preselectedDonorId && state.donorMatches.some((donor) => donor.id === preselectedDonorId)) {
                chooseDonor(preselectedDonorId);
            }
        });
    }

    function renderQr(target, url) {
        if (!target) {
            return;
        }

        if (!url || typeof window.qrcode !== 'function') {
            target.classList.add('is-unavailable');
            target.textContent = labels.qrUnavailable;
            return;
        }

        try {
            const qr = window.qrcode(0, 'M');
            qr.addData(url);
            qr.make();
            target.innerHTML = qr.createSvgTag(4, 2);
        } catch (error) {
            target.classList.add('is-unavailable');
            target.textContent = labels.qrUnavailable;
        }
    }

    function renderQrCodes(container) {
        container?.querySelectorAll('[data-bc-qr-url]').forEach((target) => {
            renderQr(target, target.dataset.bcQrUrl || '');
        });
    }

    function setCardDetailView(view = 'preview', focus = false) {
        const nextView = view === 'details' ? 'details' : 'preview';

        for (const tab of elements.detailTabs) {
            const selected = tab.dataset.cardDetailView === nextView;
            tab.classList.toggle('active', selected);
            tab.setAttribute('aria-selected', String(selected));
            tab.setAttribute('tabindex', selected ? '0' : '-1');

            if (selected && focus) {
                tab.focus();
            }
        }

        for (const panel of elements.detailsContent.querySelectorAll('[data-card-detail-panel]')) {
            panel.hidden = panel.dataset.cardDetailPanel !== nextView;
        }
    }

    function openDetails(record) {
        state.detailId = record.cardNumber;
        const version = Number(record.replacementCount || 0) + 1;
        const notes = record.notes || labels.notRecorded;

        elements.detailsContent.innerHTML = `
            <section class="bc-card-view-panel" id="bc-card-preview-panel" role="tabpanel"
                     aria-labelledby="bc-card-preview-tab" data-card-detail-panel="preview">
                <div class="bc-card-preview-stack" data-printable-card>
                    <div class="bc-card-side-preview">
                        <p class="bc-card-side-label">${escapeHtml(labels.frontSide)}</p>
                        <article class="bc-donation-card bc-donation-card-front" data-printable-card-side="front">
                            <div class="bc-donation-card-glow" aria-hidden="true"></div>
                            <header class="bc-donation-card-header">
                                <span class="bc-donation-card-brand">
                                    <i class="la la-tint"></i>
                                    <span><strong>BloodCare</strong><small>Donation Card</small></span>
                                </span>
                                <span class="bc-donation-card-status bc-card-state-${statusSlug(record.status)}">
                                    ${escapeHtml(translatedStatus(record.status))}
                                </span>
                            </header>
                            <div class="bc-donation-card-body">
                                <div class="bc-donation-card-person">
                                    <small>${escapeHtml(labels.donor)}</small>
                                    <h3>${escapeHtml(record.donorName)}</h3>
                                    <strong>${escapeHtml(record.cardNumber)}</strong>
                                </div>
                                <span class="bc-donation-card-group">${escapeHtml(record.group)}</span>
                            </div>
                            <div class="bc-donation-card-meta">
                                <span><small>${escapeHtml(labels.issuedOn)}</small><strong>${escapeHtml(formatDate(record.issueDate))}</strong></span>
                                <span><small>${escapeHtml(labels.validUntil)}</small><strong>${escapeHtml(formatDate(record.expiryDate))}</strong></span>
                                <span class="bc-card-qr bc-card-qr-front" data-bc-qr-url="${escapeHtml(record.traceUrl)}" aria-label="${escapeHtml(labels.scanToVerify)}"></span>
                            </div>
                            <footer class="bc-donation-card-footer">
                                <span>${escapeHtml(labels.secureReference)} · ${escapeHtml(record.cardNumber)}</span>
                                <span>${escapeHtml(interpolate(labels.versionValue, { version }))}</span>
                            </footer>
                        </article>
                    </div>
                    <div class="bc-card-side-preview">
                        <p class="bc-card-side-label">${escapeHtml(labels.backSide)}</p>
                        <article class="bc-donation-card bc-donation-card-back" data-printable-card-side="back">
                            <div class="bc-donation-card-glow" aria-hidden="true"></div>
                            <header class="bc-donation-card-header">
                                <span class="bc-donation-card-brand">
                                    <i class="la la-shield-alt"></i>
                                    <span><strong>BloodCare</strong><small>${escapeHtml(labels.liveVerification)}</small></span>
                                </span>
                                <span class="bc-donation-card-status">${escapeHtml(interpolate(labels.versionValue, { version }))}</span>
                            </header>
                            <div class="bc-donation-card-back-body">
                                <div class="bc-donation-card-back-copy">
                                    <small>${escapeHtml(labels.secureReference)}</small>
                                    <strong>${escapeHtml(record.cardNumber)}</strong>
                                    <h3>${escapeHtml(labels.scanToVerify)}</h3>
                                    <p>${escapeHtml(labels.scanHelp)}</p>
                                    <div class="bc-card-back-points">
                                        <span><i class="la la-lock"></i>${escapeHtml(labels.privacyProtected)}</span>
                                        <span><i class="la la-sync"></i>${escapeHtml(labels.liveVerification)}</span>
                                    </div>
                                </div>
                                <span class="bc-card-qr bc-card-qr-back" data-bc-qr-url="${escapeHtml(record.traceUrl)}" aria-label="${escapeHtml(labels.scanToVerify)}"></span>
                            </div>
                            <footer class="bc-donation-card-footer bc-donation-card-back-footer">
                                <span>${escapeHtml(labels.backDisclaimer)}</span>
                            </footer>
                        </article>
                    </div>
                </div>
                <div class="bc-card-public-note">
                    <i class="la la-shield-alt"></i>
                    <span>${escapeHtml(labels.publicSafe)}</span>
                </div>
            </section>
            <section class="bc-card-view-panel bc-card-information-panel" id="bc-card-information-panel"
                     role="tabpanel" aria-labelledby="bc-card-information-tab"
                     data-card-detail-panel="details" hidden>
                <div class="bc-card-record-hero">
                    <span class="bc-card-record-icon"><i class="la la-id-card-alt"></i></span>
                    <span>
                        <small>${escapeHtml(labels.recordSummary)}</small>
                        <strong>${escapeHtml(record.donorName)}</strong>
                        <em>${escapeHtml(record.cardNumber)}</em>
                    </span>
                    <span class="bc-status bc-status-${statusSlug(record.status)}">${escapeHtml(translatedStatus(record.status))}</span>
                </div>
                <dl class="bc-card-information-grid">
                    <div class="bc-card-information-card">
                        <dt>${escapeHtml(labels.cardNumber)}</dt>
                        <dd>${escapeHtml(record.cardNumber)}</dd>
                    </div>
                    <div class="bc-card-information-card">
                        <dt>${escapeHtml(labels.donor)}</dt>
                        <dd>${escapeHtml(record.donorName)}</dd>
                    </div>
                    <div class="bc-card-information-card">
                        <dt>${escapeHtml(labels.bloodGroup)}</dt>
                        <dd><span class="bc-group-badge">${escapeHtml(record.group)}</span></dd>
                    </div>
                    <div class="bc-card-information-card">
                        <dt>${escapeHtml(labels.phone)}</dt>
                        <dd>${escapeHtml(record.phone || labels.notRecorded)}</dd>
                    </div>
                    <div class="bc-card-information-card">
                        <dt>${escapeHtml(labels.status)}</dt>
                        <dd>${escapeHtml(translatedStatus(record.status))}</dd>
                    </div>
                    <div class="bc-card-information-card">
                        <dt>${escapeHtml(labels.issueDate)}</dt>
                        <dd>${escapeHtml(formatDate(record.issueDate))}</dd>
                    </div>
                    <div class="bc-card-information-card">
                        <dt>${escapeHtml(labels.expiryDate)}</dt>
                        <dd>${escapeHtml(formatDate(record.expiryDate))}</dd>
                    </div>
                    <div class="bc-card-information-card">
                        <dt>${escapeHtml(labels.cardVersion)}</dt>
                        <dd>${escapeHtml(interpolate(labels.versionValue, { version }))}</dd>
                    </div>
                    <div class="bc-card-information-card">
                        <dt>${escapeHtml(labels.printCount)}</dt>
                        <dd>${escapeHtml(interpolate(labels.printsValue, { count: record.printCount }))}</dd>
                    </div>
                    <div class="bc-card-information-card bc-card-information-wide">
                        <dt>${escapeHtml(labels.notes)}</dt>
                        <dd>${escapeHtml(notes)}</dd>
                    </div>
                </dl>
            </section>`;

        renderQrCodes(elements.detailsContent);
        setCardDetailView('preview');
        openModal(elements.details, elements.detailTabs[0] || elements.print);
    }

    function openModal(modal, focusTarget = null) {
        if (!modal) {
            return;
        }

        lastFocusedElement = document.activeElement;
        modal.hidden = false;
        document.body.classList.add('bc-modal-open');

        window.requestAnimationFrame(() => {
            (focusTarget || modal.querySelector('button, input, select, textarea'))?.focus();
        });
    }

    function closeModal(modal, restoreFocus = true) {
        if (!modal || modal.hidden) {
            return;
        }

        modal.hidden = true;

        if (![elements.editor, elements.details, elements.confirm, elements.dataSource].some(
            (candidate) => candidate && !candidate.hidden,
        )) {
            document.body.classList.remove('bc-modal-open');
        }

        if (restoreFocus && lastFocusedElement instanceof HTMLElement) {
            lastFocusedElement.focus();
        }
    }

    function closeAllModals() {
        const openModalElement = [elements.editor, elements.details, elements.confirm, elements.dataSource]
            .find((modal) => modal && !modal.hidden);

        if (openModalElement) {
            closeModal(openModalElement);
        }
    }

    function closeRowMenus(except = null) {
        for (const menu of root.querySelectorAll('.bc-row-menu')) {
            if (menu === except) {
                continue;
            }

            if (window.BloodCareUI?.closeRowMenu) {
                window.BloodCareUI.closeRowMenu(menu);
            } else {
                menu.hidden = true;
                menu.closest('.bc-row-menu-wrap')
                    ?.querySelector('.bc-row-action')
                    ?.setAttribute('aria-expanded', 'false');
            }
        }
    }

    function showFormError(message) {
        elements.formError.textContent = message;
        elements.formError.hidden = false;
        elements.formError.focus();
    }

    function showToast(message) {
        window.clearTimeout(toastTimer);
        elements.toast.querySelector('span').textContent = message;
        elements.toast.hidden = false;

        window.requestAnimationFrame(() => {
            elements.toast.classList.add('show');
        });

        toastTimer = window.setTimeout(() => {
            elements.toast.classList.remove('show');
            window.setTimeout(() => {
                elements.toast.hidden = true;
            }, 200);
        }, 3200);
    }

    function requestConfirmation({
        title,
        message,
        confirmLabel,
        icon = 'la-check-circle',
        danger = false,
        onConfirm,
    }) {
        if (!elements.confirm || typeof onConfirm !== 'function') {
            return;
        }

        pendingConfirmation = onConfirm;
        elements.confirmTitle.textContent = title || labels.confirmAction;
        elements.confirmMessage.textContent = message;
        elements.confirmSubmit.textContent = confirmLabel || labels.confirmAction;
        elements.confirmSubmit.classList.toggle('bc-confirm-danger', danger);
        elements.confirmIcon.classList.toggle('is-warning', !danger);
        elements.confirmIcon.innerHTML = `<i class="la ${icon}"></i>`;
        openModal(elements.confirm, elements.confirmSubmit);
    }

    function confirmPendingAction() {
        const action = pendingConfirmation;
        pendingConfirmation = null;
        closeModal(elements.confirm, false);
        action?.();
    }

    async function updateRecord(record, changes, messageTemplate, auditAction) {
        const nextRecord = normalizeRecord({ ...record, ...changes });

        try {
            await apiRequest(
                endpoint(config.updateUrlTemplate, record.cardNumber),
                'PUT',
                cardPayload(nextRecord, auditAction),
            );
            showToast(interpolate(messageTemplate, { card: record.cardNumber }));
            window.setTimeout(() => window.location.reload(), 350);
        } catch (error) {
            showToast(error.message);
        }
    }

    function handleRowAction(action, cardNumber) {
        const record = state.records.find((candidate) => candidate.cardNumber === cardNumber);

        if (!record) {
            return;
        }

        closeRowMenus();

        if (action === 'issue') {
            const donor = state.donors.find((candidate) => candidate.id === record.donorId);

            if (!verifiedDonor(donor)) {
                showToast(labels.donorNotVerified);
                return;
            }

            openIssueEditor(record.donorId);
        } else if (action === 'view') {
            openDetails(record);
        } else if (action === 'print') {
            openDetails(record);
            window.setTimeout(printCurrentCard, 80);
        } else if (action === 'renew') {
            requestConfirmation({
                title: labels.renewTitle,
                message: interpolate(labels.confirmRenew, { card: record.cardNumber }),
                confirmLabel: labels.renew,
                icon: 'la-sync',
                onConfirm: () => {
                    updateRecord(record, {
                        expiryDate: addYears(config.today, config.validYears),
                    }, labels.renewedMessage, 'Donation card renewed');
                },
            });
        } else if (action === 'suspend') {
            requestConfirmation({
                title: labels.suspendTitle,
                message: interpolate(labels.confirmSuspend, { card: record.cardNumber }),
                confirmLabel: labels.suspend,
                icon: 'la-ban',
                danger: true,
                onConfirm: () => updateRecord(
                    record,
                    { status: 'Suspended' },
                    labels.suspendedMessage,
                    'Donation card suspended',
                ),
            });
        } else if (action === 'reactivate') {
            record.status = 'Active';
            updateRecord(record, { status: deriveStatus(record) }, labels.reactivatedMessage, 'Donation card reactivated');
        } else if (action === 'replace') {
            requestConfirmation({
                title: labels.replaceTitle,
                message: interpolate(labels.confirmReplace, { card: record.cardNumber }),
                confirmLabel: labels.replaceLost,
                icon: 'la-redo',
                danger: true,
                onConfirm: () => updateRecord(record, {
                    issueDate: config.today,
                    expiryDate: addYears(config.today, config.validYears),
                    replacementCount: Number(record.replacementCount || 0) + 1,
                    printCount: Number(record.printCount || 0) + 1,
                    status: 'Active',
                }, labels.replacedMessage, 'Lost card replaced'),
            });
        }
    }

    async function handleSubmit(event) {
        event.preventDefault();
        elements.formError.hidden = true;

        if (!elements.donor.value) {
            showFormError(labels.donorNotVerified);
            elements.donorSearch.focus();
            return;
        }
        if (!elements.form.reportValidity()) {
            return;
        }

        const donor = state.donors.find((candidate) => candidate.id === elements.donor.value);
        if (!verifiedDonor(donor)) {
            showFormError(labels.donorNotVerified);
            return;
        }
        if (elements.expiryDate.value <= elements.issueDate.value) {
            showFormError(labels.invalidDates);
            elements.expiryDate.focus();
            return;
        }
        const existing = state.records.find((record) => record.donorId === donor.id);
        if (existing?.issueDate) {
            showFormError(labels.duplicateCard);
            return;
        }

        try {
            await apiRequest(config.storeUrl, 'POST', {
                donorId: donor.id,
                cardNumber: elements.cardNumber.value.trim() || donor.id,
                issueDate: elements.issueDate.value,
                expiryDate: elements.expiryDate.value,
                notes: elements.notes.value.trim(),
            });
            closeModal(elements.editor, false);
            showToast(interpolate(labels.issuedMessage, { card: donor.id, donor: donor.name }));
            window.setTimeout(() => window.location.reload(), 350);
        } catch (error) {
            showFormError(error.message);
        }
    }

    async function printCurrentCard() {
        const record = state.records.find((candidate) => candidate.cardNumber === state.detailId);
        if (!record) {
            return;
        }

        const nextRecord = normalizeRecord({
            ...record,
            printCount: Number(record.printCount || 0) + 1,
        });
        try {
            await apiRequest(
                endpoint(config.updateUrlTemplate, record.cardNumber),
                'PUT',
                cardPayload(nextRecord, 'Donation card printed'),
            );
            Object.assign(record, nextRecord);
            openDetails(record);
            document.documentElement.classList.add('bc-print-card');
            showToast(interpolate(labels.printedMessage, { card: record.cardNumber }));
            window.print();
            window.setTimeout(() => document.documentElement.classList.remove('bc-print-card'), 400);
        } catch (error) {
            showToast(error.message);
        }
    }

    async function copyCurrentReference() {
        const record = state.records.find((candidate) => candidate.cardNumber === state.detailId);

        if (!record) {
            return;
        }

        try {
            await navigator.clipboard.writeText(record.cardNumber);
        } catch (error) {
            const helper = document.createElement('textarea');
            helper.value = record.cardNumber;
            helper.setAttribute('readonly', '');
            helper.style.position = 'fixed';
            helper.style.opacity = '0';
            document.body.appendChild(helper);
            helper.select();
            document.execCommand('copy');
            helper.remove();
        }

        showToast(labels.copiedMessage);
    }

    function resetFilters() {
        elements.search.value = '';
        elements.group.value = 'all';
        elements.status.value = 'all';
        elements.issued.value = 'all';
        syncCustomSelect(elements.group);
        syncCustomSelect(elements.status);
        syncCustomSelect(elements.issued);
        state.page = 1;
    }

    elements.primaryAction?.addEventListener('click', () => openIssueEditor());
    elements.dataSourceButton?.addEventListener('click', () => openModal(elements.dataSource));
    elements.form?.addEventListener('submit', handleSubmit);
    elements.confirmSubmit?.addEventListener('click', confirmPendingAction);
    elements.copy?.addEventListener('click', copyCurrentReference);
    elements.print?.addEventListener('click', printCurrentCard);

    for (const tab of elements.detailTabs) {
        tab.addEventListener('click', () => setCardDetailView(tab.dataset.cardDetailView));
        tab.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) {
                return;
            }

            event.preventDefault();
            const currentIndex = Math.max(0, elements.detailTabs.indexOf(tab));
            const nextIndex = event.key === 'Home'
                ? 0
                : event.key === 'End'
                    ? elements.detailTabs.length - 1
                    : (currentIndex + (event.key === 'ArrowRight' ? 1 : -1) + elements.detailTabs.length)
                        % elements.detailTabs.length;
            const nextTab = elements.detailTabs[nextIndex];
            setCardDetailView(nextTab.dataset.cardDetailView, true);
        });
    }

    elements.issueDate?.addEventListener('change', () => {
        if (elements.issueDate.value) {
            elements.expiryDate.value = addYears(elements.issueDate.value, config.validYears);
        }
    });

    elements.search?.addEventListener('input', () => {
        state.page = 1;
        renderRows();
    });

    for (const select of [elements.group, elements.status, elements.issued]) {
        select?.addEventListener('change', () => {
            state.page = 1;
            renderRows();
        });
    }

    elements.previous?.addEventListener('click', () => {
        if (state.page > 1) {
            state.page -= 1;
            renderRows();
        }
    });

    elements.next?.addEventListener('click', () => {
        const totalPages = Math.max(1, Math.ceil(filteredRecords().length / pageSize));

        if (state.page < totalPages) {
            state.page += 1;
            renderRows();
        }
    });

    function resetCards() {
        window.location.reload();
    }

    elements.reset?.addEventListener('click', () => {
        closeModal(elements.dataSource, false);
        requestConfirmation({
            title: labels.resetTitle,
            message: labels.confirmReset,
            confirmLabel: labels.resetSample,
            icon: 'la-undo',
            danger: true,
            onConfirm: resetCards,
        });
    });

    elements.donorSearch?.addEventListener('input', () => {
        window.clearTimeout(donorSearchTimer);
        donorSearchTimer = window.setTimeout(() => {
            searchVerifiedDonors(elements.donorSearch.value, elements.donor.value);
        }, donorSearchDebounceMs);
    });

    elements.donorSearch?.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            elements.donorResults.querySelector('[data-donor-value]')?.focus();
        }
    });

    elements.donorResults?.addEventListener('click', (event) => {
        const option = event.target.closest('[data-donor-value]');

        if (option) {
            chooseDonor(option.dataset.donorValue);
        }
    });

    elements.donorResults?.addEventListener('keydown', (event) => {
        const options = [...elements.donorResults.querySelectorAll('[data-donor-value]')];
        const focusedIndex = options.indexOf(document.activeElement);

        if (focusedIndex >= 0 && (event.key === 'ArrowDown' || event.key === 'ArrowUp')) {
            event.preventDefault();
            const direction = event.key === 'ArrowDown' ? 1 : -1;
            options[(focusedIndex + direction + options.length) % options.length].focus();
        } else if (focusedIndex >= 0 && (event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault();
            chooseDonor(document.activeElement.dataset.donorValue);
        }
    });

    root.addEventListener('click', (event) => {
        const menuButton = event.target.closest('[data-card-menu]');

        if (menuButton) {
            const menu = menuButton.closest('.bc-row-menu-wrap')?.querySelector('.bc-row-menu');

            if (!menu) {
                return;
            }

            const willOpen = menu.hidden;
            closeRowMenus(menu);

            if (willOpen && window.BloodCareUI?.openRowMenu) {
                window.BloodCareUI.openRowMenu(menuButton, menu);
            } else if (!willOpen && window.BloodCareUI?.closeRowMenu) {
                window.BloodCareUI.closeRowMenu(menu);
            } else {
                menu.hidden = !willOpen;
                menuButton.setAttribute('aria-expanded', String(willOpen));
            }
            return;
        }

        const actionButton = event.target.closest('[data-card-action]');

        if (actionButton) {
            handleRowAction(actionButton.dataset.cardAction, actionButton.dataset.cardId);
        }
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.bc-row-menu-wrap')) {
            closeRowMenus();
        }

    });

    for (const closeButton of root.querySelectorAll('[data-modal-close]')) {
        closeButton.addEventListener('click', () => {
            const modal = closeButton.closest('.bc-modal');

            if (modal === elements.confirm) {
                pendingConfirmation = null;
            }

            closeModal(modal);
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeRowMenus();
            pendingConfirmation = null;
            closeAllModals();
        }
    });

    window.addEventListener('afterprint', () => {
        document.documentElement.classList.remove('bc-print-card');
    });

    initializeCustomSelects();
    render();
})();
