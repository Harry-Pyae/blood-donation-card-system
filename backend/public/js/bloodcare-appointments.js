(() => {
    'use strict';

    const root = document.querySelector('[data-bc-module="appointments"]');
    const configNode = document.getElementById('bc-appointment-config');

    if (!root || !configNode) {
        return;
    }

    const config = JSON.parse(configNode.textContent);
    const labels = config.labels;
    const csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';

    function showUiMessage(message, error = false) {
        window.BloodCareUI?.showNotice(message, { error });
    }

    async function confirmUi(message, confirmLabel, danger = false) {
        return Boolean(await window.BloodCareUI?.confirmAction({
            message,
            confirmLabel,
            danger,
        }));
    }

    async function apiRequest(url, method, payload) {
        const response = await window.fetch(url, {
            method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const firstError = Object.values(data.errors || {}).flat()[0];
            throw new Error(firstError || data.message || 'Unable to save this appointment.');
        }

        return data;
    }
    const storageKey = 'bloodcare.appointments.interactive.v1';
    const donorStorageKey = 'bloodcare.donors.interactive.v1';
    const historyStorageKey = 'bloodcare.history.interactive.v1';
    const handoffStorageKey = 'bloodcare.donation.prefill.v1';
    const viewStorageKey = 'bloodcare.appointments.view.v1';
    const auditSource = 'appointments-interactive';
    const pageSize = 5;
    const activeStatuses = ['Pending', 'Confirmed', 'Checked in'];
    const originalRecords = config.records.map(normalizeRecord);
    const originalCentres = (config.centreRecords || config.centres.map((name, index) => ({
        id: `CTR-${String(index + 1).padStart(4, '0')}`,
        name,
        region: '',
        township: '',
        address: '',
        phone: '',
        hours: '',
        active: true,
    }))).map(normalizeCentre);
    const originalContribution = contributionFor(originalRecords);

    const elements = {
        body: document.getElementById('bc-appointment-table-body'),
        search: document.getElementById('bc-appointment-search'),
        status: document.getElementById('bc-appointment-status'),
        centre: document.getElementById('bc-appointment-centre'),
        dateFilter: document.getElementById('bc-appointment-date'),
        resultCount: document.getElementById('bc-appointment-result-count'),
        page: document.getElementById('bc-appointment-page'),
        previous: document.getElementById('bc-appointment-prev'),
        next: document.getElementById('bc-appointment-next'),
        tableView: document.getElementById('bc-appointment-list-view'),
        tableFooter: document.getElementById('bc-appointment-table-footer'),
        calendarView: document.getElementById('bc-appointment-calendar-view'),
        calendarGrid: document.getElementById('bc-calendar-grid'),
        calendarWeekdays: document.getElementById('bc-calendar-weekdays'),
        calendarNavigation: document.getElementById('bc-calendar-navigation'),
        calendarMonth: document.getElementById('bc-calendar-month'),
        calendarPrevious: document.getElementById('bc-calendar-previous'),
        calendarNext: document.getElementById('bc-calendar-next'),
        calendarToday: document.getElementById('bc-calendar-today'),
        listButton: document.getElementById('bc-appointment-list-button'),
        calendarButton: document.getElementById('bc-appointment-calendar-button'),
        manageCentresButton: document.getElementById('bc-manage-centres-button'),
        primaryAction: document.getElementById('bc-primary-action'),
        dataSourceButton: document.getElementById('bc-data-source-button'),
        editor: document.getElementById('bc-appointment-editor'),
        editorTitle: document.getElementById('bc-appointment-editor-title'),
        details: document.getElementById('bc-appointment-details'),
        detailsContent: document.getElementById('bc-appointment-details-content'),
        detailsActions: document.getElementById('bc-appointment-details-actions'),
        dataSource: document.getElementById('bc-appointment-data-source-modal'),
        centreManager: document.getElementById('bc-centre-manager'),
        centreList: document.getElementById('bc-centre-list'),
        centreCount: document.getElementById('bc-centre-count'),
        centreForm: document.getElementById('bc-centre-form'),
        centreFormTitle: document.getElementById('bc-centre-form-title'),
        centreFormError: document.getElementById('bc-centre-form-error'),
        centreId: document.getElementById('bc-centre-id'),
        centreName: document.getElementById('bc-centre-name'),
        centreRegion: document.getElementById('bc-centre-region'),
        centreTownship: document.getElementById('bc-centre-township'),
        centreAddress: document.getElementById('bc-centre-address'),
        centrePhone: document.getElementById('bc-centre-phone'),
        centreHours: document.getElementById('bc-centre-hours'),
        centreActive: document.getElementById('bc-centre-active'),
        centreSubmit: document.getElementById('bc-centre-submit'),
        centreCancelEdit: document.getElementById('bc-centre-cancel-edit'),
        form: document.getElementById('bc-appointment-form'),
        formError: document.getElementById('bc-appointment-form-error'),
        donor: document.getElementById('bc-appointment-donor'),
        donorPicker: document.querySelector('[data-bc-appointment-donor-select]'),
        reference: document.getElementById('bc-appointment-reference'),
        date: document.getElementById('bc-appointment-date-field'),
        time: document.getElementById('bc-appointment-time'),
        purpose: document.getElementById('bc-appointment-purpose'),
        centreField: document.getElementById('bc-appointment-centre-field'),
        formStatus: document.getElementById('bc-appointment-status-field'),
        source: document.getElementById('bc-appointment-source'),
        staff: document.getElementById('bc-appointment-staff'),
        notes: document.getElementById('bc-appointment-notes'),
        submit: document.getElementById('bc-appointment-submit'),
        reset: document.getElementById('bc-reset-appointments'),
        toast: document.getElementById('bc-appointment-toast'),
    };

    const state = {
        records: readRecords(),
        donors: readDonors(),
        centres: readCentres(),
        history: readHistory(),
        page: 1,
        editingId: null,
        detailId: null,
        view: readView(),
        calendarDate: monthStart(config.today),
    };

    state.records = synchronizeConnectedData(state.records, state.donors);

    const customSelects = new Map();
    let lastFocusedElement = null;
    let toastTimer = null;

    function normalizeRecord(record) {
        return {
            id: String(record.id || ''),
            donorId: String(record.donorId || ''),
            donorName: String(record.donorName || ''),
            group: String(record.group || ''),
            date: String(record.date || config.today),
            time: String(record.time || '09:00'),
            purpose: String(record.purpose || 'Donation'),
            centre: String(record.centre || config.centres[0] || ''),
            status: String(record.status || 'Pending'),
            staff: String(record.staff || config.staffName),
            source: String(record.source || 'Staff'),
            notes: String(record.notes || ''),
            donationCreated: Boolean(record.donationCreated),
        };
    }

    function normalizeDonor(donor) {
        return {
            ...donor,
            id: String(donor.id || ''),
            name: String(donor.name || ''),
            group: String(donor.group || ''),
            phone: String(donor.phone || ''),
            status: String(donor.status || 'Pending'),
            eligibility: String(donor.eligibility || 'Review'),
            nextEligible: donor.nextEligible || 'review',
        };
    }

    function normalizeCentre(centre) {
        return {
            id: String(centre.id || ''),
            name: String(centre.name || '').trim(),
            region: String(centre.region || '').trim(),
            township: String(centre.township || '').trim(),
            address: String(centre.address || '').trim(),
            phone: String(centre.phone || '').trim(),
            hours: String(centre.hours || '').trim(),
            active: centre.active !== false,
        };
    }

    function readRecords() {
        return config.records.map(normalizeRecord)
            .filter((record) => record.id && record.donorId);
    }

    function readDonors() {
        return config.donors.map(normalizeDonor)
            .filter((donor) => donor.id);
    }

    function readCentres() {
        return originalCentres.map(normalizeCentre)
            .filter((centre) => centre.id && centre.name);
    }

    function readHistory() {
        return [];
    }

    function readView() {
        try {
            return window.localStorage.getItem(viewStorageKey) === 'calendar'
                ? 'calendar'
                : 'list';
        } catch (error) {
            return 'list';
        }
    }

    function saveValue(key, value) {
        try {
            window.localStorage.setItem(key, JSON.stringify(value));
        } catch (error) {
            // The workflow remains usable for the current page session.
        }
    }

    function synchronizeConnectedData(records, donors) {
        return records.map((record) => {
            const donor = donors.find((candidate) => candidate.id === record.donorId);

            return normalizeRecord({
                ...record,
                donorName: donor?.name || record.donorName,
                group: donor?.group || record.group,
                donationCreated: record.donationCreated,
            });
        });
    }

    function parseDate(value) {
        return value ? new Date(`${value}T12:00:00`) : null;
    }

    function isoDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function monthStart(value) {
        const date = typeof value === 'string' ? parseDate(value) : value;
        return new Date(date.getFullYear(), date.getMonth(), 1, 12);
    }

    function formatDate(value) {
        const date = parseDate(value);

        if (!date) {
            return labels.notRecorded;
        }

        return new Intl.DateTimeFormat(config.locale === 'my' ? 'my-MM' : 'en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        }).format(date);
    }

    function formatTime(value) {
        const [hour, minute] = String(value || '00:00').split(':').map(Number);
        const date = new Date(2026, 0, 1, hour, minute);

        return new Intl.DateTimeFormat(config.locale === 'my' ? 'my-MM' : 'en-GB', {
            hour: '2-digit',
            minute: '2-digit',
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

    function translatedValue(value) {
        return labels[statusSlug(value)] || value;
    }

    function contributionFor(records) {
        return {
            today: records.filter((record) => (
                record.date === config.today
                && !['Cancelled', 'No-show'].includes(record.status)
            )).length,
            checkedIn: records.filter((record) => (
                record.date === config.today && record.status === 'Checked in'
            )).length,
            pending: records.filter((record) => record.status === 'Pending').length,
        };
    }

    function donorEligibleOn(donor, date) {
        if (!donor || donor.status !== 'Active' || donor.eligibility !== 'Eligible') {
            return false;
        }

        return !donor.nextEligible
            || donor.nextEligible === 'now'
            || (donor.nextEligible !== 'review' && donor.nextEligible <= date);
    }

    function schedulableDonors() {
        return state.donors
            .filter((donor) => donor.status !== 'Inactive')
            .sort((first, second) => first.name.localeCompare(second.name));
    }

    function populateDonorPicker() {
        const donors = schedulableDonors();
        const menu = elements.donorPicker?.querySelector('.bc-filter-dropdown-menu');

        if (!elements.donor || !menu) {
            return;
        }

        const optionMarkup = donors.map((donor) => (
            `<option value="${escapeHtml(donor.id)}">${escapeHtml(donor.name)} · ${escapeHtml(donor.group)} · ${escapeHtml(donor.id)}</option>`
        )).join('');
        const buttonMarkup = donors.map((donor) => `
            <button type="button" role="option" data-value="${escapeHtml(donor.id)}" aria-selected="false">
                <span>${escapeHtml(donor.name)} · ${escapeHtml(donor.group)} · ${escapeHtml(donor.id)}</span>
                <i class="la la-check" aria-hidden="true"></i>
            </button>`).join('');

        elements.donor.innerHTML = `
            <option value="">${escapeHtml(labels.chooseDonorPlaceholder)}</option>
            ${optionMarkup}`;
        menu.innerHTML = `
            <button type="button" role="option" data-value="" aria-selected="true">
                <span>${escapeHtml(donors.length ? labels.chooseDonorPlaceholder : labels.noDonors)}</span>
                <i class="la la-check" aria-hidden="true"></i>
            </button>
            ${buttonMarkup}`;
    }

    function activeCentres() {
        return state.centres
            .filter((centre) => centre.active)
            .sort((first, second) => first.region.localeCompare(second.region)
                || first.township.localeCompare(second.township)
                || first.name.localeCompare(second.name));
    }

    function centreOptionLabel(centre) {
        const area = [centre.township, centre.region].filter(Boolean).join(', ');
        return area ? `${centre.name} · ${area}` : centre.name;
    }

    function populateCentreControls() {
        const active = activeCentres();
        const historicalNames = state.records.map((record) => record.centre).filter(Boolean);
        const filterNames = [...new Set([
            ...state.centres.map((centre) => centre.name),
            ...historicalNames,
        ])].sort((first, second) => first.localeCompare(second));
        const filterWidget = elements.centre?.closest('[data-bc-select]');
        const filterMenu = filterWidget?.querySelector('.bc-filter-dropdown-menu');
        const allCentresLabel = config.locale === 'my' ? 'ဌာနအားလုံး' : 'All centres';

        if (elements.centre && filterMenu) {
            elements.centre.innerHTML = `
                <option value="all">${escapeHtml(allCentresLabel)}</option>
                ${filterNames.map((name) => `<option value="${escapeHtml(name)}">${escapeHtml(name)}</option>`).join('')}`;
            filterMenu.innerHTML = `
                <button type="button" role="option" data-value="all" aria-selected="true">
                    <span>${escapeHtml(allCentresLabel)}</span>
                    <i class="la la-check" aria-hidden="true"></i>
                </button>
                ${filterNames.map((name) => `
                    <button type="button" role="option" data-value="${escapeHtml(name)}" aria-selected="false">
                        <span>${escapeHtml(name)}</span>
                        <i class="la la-check" aria-hidden="true"></i>
                    </button>`).join('')}`;
        }

        if (elements.centreField) {
            elements.centreField.innerHTML = active.length
                ? active.map((centre) => (
                    `<option value="${escapeHtml(centre.name)}">${escapeHtml(centreOptionLabel(centre))}</option>`
                )).join('')
                : `<option value="">${escapeHtml(labels.noCentres)}</option>`;
        }
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
            const options = [...widget.querySelectorAll('[role="option"]')];

            if (!select || !trigger || !menu || !label || options.length === 0) {
                continue;
            }

            const sync = () => {
                const selected = options.find((option) => option.dataset.value === select.value)
                    || options[0];
                label.textContent = selected.querySelector('span')?.textContent.trim()
                    || selected.textContent.trim();

                for (const option of options) {
                    option.setAttribute('aria-selected', String(option === selected));
                }
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
                if (trigger.disabled) {
                    return;
                }

                closeAll(widget);
                widget.classList.add('open');
                trigger.setAttribute('aria-expanded', 'true');
                menu.hidden = false;
            };

            const focusOption = (index) => {
                const normalized = (index + options.length) % options.length;
                options[normalized].focus();
            };

            const choose = (option) => {
                if (!option || trigger.disabled) {
                    return;
                }

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
                const selectedIndex = Math.max(0, options.findIndex(
                    (option) => option.getAttribute('aria-selected') === 'true',
                ));
                focusOption(event.key === 'ArrowUp' ? selectedIndex - 1 : selectedIndex);
            });

            options.forEach((option, index) => {
                option.addEventListener('click', () => choose(option));
                option.addEventListener('keydown', (event) => {
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

    function syncCustomSelect(select) {
        customSelects.get(select)?.sync();
    }

    function filteredRecords() {
        const term = elements.search.value.trim().toLowerCase();

        return state.records
            .filter((record) => {
                const searchable = [
                    record.id,
                    record.donorId,
                    record.donorName,
                    record.group,
                    record.centre,
                    record.purpose,
                    record.status,
                    record.staff,
                    record.source,
                    translatedValue(record.status),
                    translatedValue(record.purpose),
                ].join(' ').toLowerCase();

                if (term && !searchable.includes(term)) {
                    return false;
                }

                if (elements.status.value !== 'all' && record.status !== elements.status.value) {
                    return false;
                }

                if (elements.centre.value !== 'all' && record.centre !== elements.centre.value) {
                    return false;
                }

                if (elements.dateFilter.value === 'today' && record.date !== config.today) {
                    return false;
                }

                if (elements.dateFilter.value === 'upcoming' && record.date <= config.today) {
                    return false;
                }

                if (elements.dateFilter.value === 'past' && record.date >= config.today) {
                    return false;
                }

                return true;
            });
    }

    function rowActions(record) {
        const actions = [
            `<button type="button" data-appointment-action="view" data-appointment-id="${escapeHtml(record.id)}"><i class="la la-eye"></i>${escapeHtml(labels.view)}</button>`,
        ];

        if (record.status === 'Pending') {
            actions.push(`<button type="button" data-appointment-action="edit" data-appointment-id="${escapeHtml(record.id)}"><i class="la la-calendar-alt"></i>${escapeHtml(labels.edit)}</button>`);
            actions.push(`<button type="button" data-appointment-action="confirm" data-appointment-id="${escapeHtml(record.id)}"><i class="la la-check-circle"></i>${escapeHtml(labels.confirm)}</button>`);
        } else if (record.status === 'Confirmed') {
            actions.push(`<button type="button" data-appointment-action="edit" data-appointment-id="${escapeHtml(record.id)}"><i class="la la-calendar-alt"></i>${escapeHtml(labels.edit)}</button>`);
            actions.push(`<button type="button" data-appointment-action="check-in" data-appointment-id="${escapeHtml(record.id)}"><i class="la la-sign-in-alt"></i>${escapeHtml(labels.checkIn)}</button>`);
            actions.push(`<button type="button" data-appointment-action="no-show" data-appointment-id="${escapeHtml(record.id)}"><i class="la la-user-times"></i>${escapeHtml(labels.markNoShow)}</button>`);
        } else if (record.status === 'Checked in') {
            actions.push(`<button type="button" data-appointment-action="complete" data-appointment-id="${escapeHtml(record.id)}"><i class="la la-clipboard-check"></i>${escapeHtml(labels.complete)}</button>`);
        } else if (['Cancelled', 'No-show'].includes(record.status)) {
            actions.push(`<button type="button" data-appointment-action="reopen" data-appointment-id="${escapeHtml(record.id)}"><i class="la la-redo"></i>${escapeHtml(labels.reopen)}</button>`);
        } else if (record.status === 'Completed' && record.purpose === 'Donation' && !record.donationCreated) {
            actions.push(`<button type="button" data-appointment-action="donation" data-appointment-id="${escapeHtml(record.id)}"><i class="la la-tint"></i>${escapeHtml(labels.recordDonation)}</button>`);
        }

        if (activeStatuses.includes(record.status)) {
            actions.push('<span class="bc-row-menu-divider"></span>');
            actions.push(`<button class="bc-row-menu-danger" type="button" data-appointment-action="cancel" data-appointment-id="${escapeHtml(record.id)}"><i class="la la-ban"></i>${escapeHtml(labels.cancelAppointment)}</button>`);
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
                        <i class="la la-calendar-times"></i>
                        <strong>${escapeHtml(labels.noResults)}</strong>
                    </td>
                </tr>`;
        } else {
            elements.body.innerHTML = pageRecords.map((record) => `
                <tr>
                    <td><strong>${escapeHtml(record.id)}</strong></td>
                    <td>
                        <span class="bc-donor-cell">
                            <span class="bc-donor-table-avatar">${escapeHtml(record.donorName.charAt(0).toUpperCase())}</span>
                            <span>${escapeHtml(record.donorName)}</span>
                        </span>
                    </td>
                    <td>${escapeHtml(formatDate(record.date))}</td>
                    <td><strong class="bc-appointment-time">${escapeHtml(formatTime(record.time))}</strong></td>
                    <td>${escapeHtml(record.centre)}</td>
                    <td><span class="bc-status bc-status-${statusSlug(record.status)}">${escapeHtml(translatedValue(record.status))}</span></td>
                    <td class="text-end">
                        <span class="bc-row-menu-wrap">
                            <button class="bc-row-action" type="button" data-appointment-menu="${escapeHtml(record.id)}"
                                    aria-expanded="false"
                                    aria-label="${escapeHtml(interpolate(labels.actionsFor, { appointment: record.id }))}">
                                <i class="la la-ellipsis-h"></i>
                            </button>
                            <span class="bc-row-menu" data-appointment-menu-panel="${escapeHtml(record.id)}" hidden>
                                ${rowActions(record)}
                            </span>
                        </span>
                    </td>
                </tr>`).join('');
        }

        const from = records.length === 0 ? 0 : startIndex + 1;
        const to = Math.min(startIndex + pageSize, records.length);
        elements.resultCount.textContent = interpolate(labels.showingRange, {
            from,
            to,
            total: records.length,
        });
        elements.page.textContent = state.page;
        elements.previous.disabled = state.page <= 1;
        elements.next.disabled = state.page >= totalPages;
        renderMetrics();
    }

    function renderMetrics() {
        const current = contributionFor(state.records);

        for (const key of ['today', 'checkedIn', 'pending']) {
            const element = root.querySelector(`[data-appointment-metric="${key}"] strong`);
            const value = Number(config.summary[key])
                + current[key]
                - originalContribution[key];

            if (element) {
                element.textContent = new Intl.NumberFormat(
                    config.locale === 'my' ? 'my-MM' : 'en-US',
                ).format(Math.max(0, value));
            }
        }
    }

    function renderCalendar() {
        const month = state.calendarDate;
        const year = month.getFullYear();
        const monthIndex = month.getMonth();
        const firstWeekday = new Date(year, monthIndex, 1, 12).getDay();
        const daysInMonth = new Date(year, monthIndex + 1, 0, 12).getDate();
        const previousMonthDays = new Date(year, monthIndex, 0, 12).getDate();
        const records = filteredRecords();
        const locale = config.locale === 'my' ? 'my-MM' : 'en-US';

        elements.calendarMonth.textContent = new Intl.DateTimeFormat(locale, {
            month: 'long',
            year: 'numeric',
        }).format(month);

        elements.calendarWeekdays.innerHTML = Array.from({ length: 7 }, (_, index) => {
            const date = new Date(2026, 7, 2 + index, 12);
            return `<span>${escapeHtml(new Intl.DateTimeFormat(locale, { weekday: 'short' }).format(date))}</span>`;
        }).join('');

        const cells = [];

        for (let index = 0; index < 42; index += 1) {
            let cellYear = year;
            let cellMonth = monthIndex;
            let day = index - firstWeekday + 1;
            let outside = false;

            if (day < 1) {
                cellMonth -= 1;
                day = previousMonthDays + day;
                outside = true;
            } else if (day > daysInMonth) {
                cellMonth += 1;
                day -= daysInMonth;
                outside = true;
            }

            const cellDate = new Date(cellYear, cellMonth, day, 12);
            const dateValue = isoDate(cellDate);
            const appointments = records.filter((record) => record.date === dateValue);
            const visible = appointments.slice(0, 3);
            const remaining = appointments.length - visible.length;
            const classes = [
                'bc-calendar-day',
                outside ? 'is-outside' : '',
                dateValue === config.today ? 'is-today' : '',
            ].filter(Boolean).join(' ');

            cells.push(`
                <article class="${classes}" data-calendar-date="${escapeHtml(dateValue)}">
                    <header>
                        <span>${escapeHtml(new Intl.NumberFormat(locale).format(day))}</span>
                    </header>
                    <div class="bc-calendar-events">
                        ${visible.map((record) => `
                            <button class="bc-calendar-event bc-calendar-event-${statusSlug(record.status)}"
                                    type="button" data-calendar-appointment="${escapeHtml(record.id)}"
                                    title="${escapeHtml(`${formatTime(record.time)} · ${record.donorName} · ${translatedValue(record.status)}`)}">
                                <time>${escapeHtml(formatTime(record.time))}</time>
                                <span>${escapeHtml(record.donorName)}</span>
                            </button>`).join('')}
                        ${remaining > 0 ? `<span class="bc-calendar-more">${escapeHtml(interpolate(labels.moreAppointments, { count: remaining }))}</span>` : ''}
                        ${appointments.length === 0 ? `<span class="bc-calendar-empty">${escapeHtml(labels.calendarEmpty)}</span>` : ''}
                    </div>
                </article>`);
        }

        elements.calendarGrid.innerHTML = cells.join('');
    }

    function setView(view) {
        state.view = view === 'calendar' ? 'calendar' : 'list';
        const calendarActive = state.view === 'calendar';
        elements.tableView.hidden = calendarActive;
        elements.tableFooter.hidden = calendarActive;
        elements.calendarView.hidden = !calendarActive;
        elements.calendarNavigation.hidden = !calendarActive;
        elements.listButton.classList.toggle('active', !calendarActive);
        elements.calendarButton.classList.toggle('active', calendarActive);
        elements.listButton.setAttribute('aria-pressed', String(!calendarActive));
        elements.calendarButton.setAttribute('aria-pressed', String(calendarActive));

        try {
            window.localStorage.setItem(viewStorageKey, state.view);
        } catch (error) {
            // The selected view still works for the current page session.
        }

        if (calendarActive) {
            renderCalendar();
        } else {
            renderRows();
        }
    }

    function closeRowMenus(exceptId = null) {
        root.querySelectorAll('[data-appointment-menu-panel]').forEach((menu) => {
            if (menu.dataset.appointmentMenuPanel === exceptId) {
                return;
            }

            if (window.BloodCareUI?.closeRowMenu) {
                window.BloodCareUI.closeRowMenu(menu);
            } else {
                menu.hidden = true;
                root.querySelector(`[data-appointment-menu="${CSS.escape(menu.dataset.appointmentMenuPanel)}"]`)
                    ?.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function nextReference() {
        const maximum = state.records.reduce((highest, record) => {
            const match = record.id.match(/(\d+)$/);
            return match ? Math.max(highest, Number(match[1])) : highest;
        }, 0);

        return `APT-${String(maximum + 1).padStart(6, '0')}`;
    }

    function setDonorPickerDisabled(disabled) {
        const trigger = elements.donorPicker?.querySelector('.bc-filter-dropdown-trigger');

        if (trigger) {
            trigger.disabled = disabled;
        }

        elements.donorPicker?.classList.toggle('is-disabled', disabled);
    }

    function resetForm() {
        elements.form.reset();
        state.editingId = null;
        elements.editorTitle.textContent = labels.createTitle;
        elements.submit.querySelector('span').textContent = labels.saveAppointment;
        elements.reference.value = nextReference();
        elements.date.value = config.today;
        elements.date.min = config.today;
        elements.time.value = '09:00';
        elements.purpose.value = 'Donation';
        elements.centreField.value = activeCentres()[0]?.name || '';
        elements.formStatus.value = 'Pending';
        elements.source.value = 'Staff';
        elements.staff.value = config.staffName;
        elements.donor.value = '';
        setDonorPickerDisabled(false);
        syncCustomSelect(elements.donor);
        hideFormError();
    }

    function fillForm(record) {
        state.editingId = record.id;
        elements.editorTitle.textContent = labels.editTitle;
        elements.submit.querySelector('span').textContent = labels.saveChanges;
        elements.reference.value = record.id;
        elements.donor.value = record.donorId;
        elements.date.value = record.date;
        elements.date.min = record.date < config.today ? record.date : config.today;
        elements.time.value = record.time;
        elements.purpose.value = record.purpose;
        if (![...elements.centreField.options].some((option) => option.value === record.centre)) {
            const historicalOption = document.createElement('option');
            historicalOption.value = record.centre;
            historicalOption.textContent = `${record.centre} · ${labels.inactive}`;
            elements.centreField.append(historicalOption);
        }
        elements.centreField.value = record.centre;
        elements.formStatus.value = activeStatuses.includes(record.status) ? record.status : 'Pending';
        elements.source.value = record.source;
        elements.staff.value = record.staff;
        elements.notes.value = record.notes;
        setDonorPickerDisabled(true);
        syncCustomSelect(elements.donor);
        hideFormError();
    }

    function showFormError(message) {
        elements.formError.textContent = message;
        elements.formError.hidden = false;
        elements.formError.focus();
    }

    function hideFormError() {
        elements.formError.hidden = true;
        elements.formError.textContent = '';
    }

    function openModal(modal, focusTarget = null) {
        lastFocusedElement = document.activeElement;
        modal.hidden = false;
        document.body.classList.add('bc-modal-open');
        window.requestAnimationFrame(() => {
            (focusTarget || modal.querySelector('button, input, select, textarea'))?.focus();
        });
    }

    function closeModal(modal) {
        modal.hidden = true;

        if (![elements.editor, elements.details, elements.dataSource, elements.centreManager].some(
            (candidate) => candidate && !candidate.hidden,
        )) {
            document.body.classList.remove('bc-modal-open');
        }

        lastFocusedElement?.focus?.();
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

    function auditEvent(record, action) {
        state.history.unshift({
            id: `HIS-${Date.now()}-${Math.floor(Math.random() * 1000)}`,
            type: 'Appointment',
            action,
            reference: record.id,
            donorId: record.donorId,
            donorName: record.donorName,
            staff: config.staffName,
            dateTime: `${config.today}T12:00:00`,
            result: record.status,
            details: `${record.date} ${record.time}; ${record.centre}; ${record.purpose}`,
            source: auditSource,
        });
    }

    function validateRecord(record, editingId = null) {
        const donor = state.donors.find((candidate) => candidate.id === record.donorId);

        if (!donor || donor.status === 'Inactive') {
            return labels.inactiveDonor;
        }

        if (!editingId && record.date < config.today) {
            return labels.pastDate;
        }

        const selectedCentre = state.centres.find((centre) => centre.name === record.centre);

        if (!selectedCentre || !selectedCentre.active) {
            return labels.inactiveCentre;
        }

        if (record.purpose === 'Donation' && !donorEligibleOn(donor, record.date)) {
            return labels.donorNotEligible;
        }

        if (state.records.some((candidate) => (
            candidate.id === record.id && candidate.id !== editingId
        ))) {
            return labels.duplicateReference;
        }

        const activeRecord = (candidate) => activeStatuses.includes(candidate.status);
        const slotConflict = state.records.some((candidate) => (
            candidate.id !== editingId
            && activeRecord(candidate)
            && candidate.date === record.date
            && candidate.time === record.time
            && candidate.centre === record.centre
        ));

        if (slotConflict) {
            return labels.slotConflict;
        }

        const donorConflict = state.records.some((candidate) => (
            candidate.id !== editingId
            && activeRecord(candidate)
            && candidate.donorId === record.donorId
            && candidate.date === record.date
            && candidate.time === record.time
        ));

        if (donorConflict) {
            return labels.donorConflict;
        }

        return '';
    }

    function formRecord() {
        const donor = state.donors.find((candidate) => candidate.id === elements.donor.value);
        const existing = state.records.find((record) => record.id === state.editingId);

        return normalizeRecord({
            ...(existing || {}),
            id: elements.reference.value.trim().toUpperCase(),
            donorId: elements.donor.value,
            donorName: donor?.name || '',
            group: donor?.group || '',
            date: elements.date.value,
            time: elements.time.value,
            purpose: elements.purpose.value,
            centre: elements.centreField.value,
            status: elements.formStatus.value,
            staff: elements.staff.value || config.staffName,
            source: elements.source.value,
            notes: elements.notes.value.trim(),
        });
    }

    function submitForm(event) {
        event.preventDefault();
        hideFormError();

        if (!elements.form.checkValidity()) {
            elements.form.reportValidity();
            return;
        }

        const record = formRecord();
        const error = validateRecord(record, state.editingId);

        if (error) {
            showFormError(error);
            return;
        }

        const url = state.editingId
            ? config.updateUrlTemplate.replace('__REFERENCE__', encodeURIComponent(state.editingId))
            : config.storeUrl;
        const method = state.editingId ? 'PUT' : 'POST';

        apiRequest(url, method, record)
            .then((data) => {
                const reference = data.appointment?.reference || record.id;
                showToast(interpolate(labels.savedMessage, { appointment: reference }));
                window.setTimeout(() => window.location.reload(), 350);
            })
            .catch((requestError) => showFormError(requestError.message));
    }

    function eligibilityLabel(record) {
        const donor = state.donors.find((candidate) => candidate.id === record.donorId);
        return donorEligibleOn(donor, record.date) ? labels.eligible : labels.notEligible;
    }

    function detailActionButtons(record) {
        const actions = [];

        if (record.status === 'Pending') {
            actions.push(`<button class="btn bc-btn-primary" type="button" data-appointment-action="confirm" data-appointment-id="${escapeHtml(record.id)}"><i class="la la-check-circle"></i>${escapeHtml(labels.confirm)}</button>`);
        } else if (record.status === 'Confirmed') {
            actions.push(`<button class="btn bc-btn-primary" type="button" data-appointment-action="check-in" data-appointment-id="${escapeHtml(record.id)}"><i class="la la-sign-in-alt"></i>${escapeHtml(labels.checkIn)}</button>`);
        } else if (record.status === 'Checked in') {
            actions.push(`<button class="btn bc-btn-primary" type="button" data-appointment-action="complete" data-appointment-id="${escapeHtml(record.id)}"><i class="la la-clipboard-check"></i>${escapeHtml(labels.complete)}</button>`);
        } else if (record.status === 'Completed' && record.purpose === 'Donation' && !record.donationCreated) {
            actions.push(`<button class="btn bc-btn-primary" type="button" data-appointment-action="donation" data-appointment-id="${escapeHtml(record.id)}"><i class="la la-tint"></i>${escapeHtml(labels.recordDonation)}</button>`);
        }

        return actions.join('');
    }

    function renderDetails(record) {
        const donor = state.donors.find((candidate) => candidate.id === record.donorId);
        const eligibility = eligibilityLabel(record);
        const eligibilityTone = eligibility === labels.eligible ? 'eligible' : 'deferred';

        elements.detailsContent.innerHTML = `
            <div class="bc-donor-detail-hero">
                <span class="bc-donor-detail-avatar">${escapeHtml(record.donorName.charAt(0).toUpperCase())}</span>
                <div>
                    <h3>${escapeHtml(record.donorName)}</h3>
                    <p>${escapeHtml(record.donorId)} · ${escapeHtml(record.group)} · ${escapeHtml(donor?.phone || labels.notRecorded)}</p>
                    <span class="bc-status bc-status-${statusSlug(record.status)}">${escapeHtml(translatedValue(record.status))}</span>
                    <span class="bc-status bc-status-${eligibilityTone}">${escapeHtml(eligibility)}</span>
                </div>
            </div>
            <div class="bc-unit-detail-grid">
                <div><small>${escapeHtml(labels.appointmentReference)}</small><strong>${escapeHtml(record.id)}</strong></div>
                <div><small>${escapeHtml(labels.appointmentDate)}</small><strong>${escapeHtml(formatDate(record.date))}</strong></div>
                <div><small>${escapeHtml(labels.appointmentTime)}</small><strong>${escapeHtml(formatTime(record.time))}</strong></div>
                <div><small>${escapeHtml(labels.centre)}</small><strong>${escapeHtml(record.centre)}</strong></div>
                <div><small>${escapeHtml(labels.purpose)}</small><strong>${escapeHtml(translatedValue(record.purpose))}</strong></div>
                <div><small>${escapeHtml(labels.source)}</small><strong>${escapeHtml(translatedValue(record.source))}</strong></div>
                <div><small>${escapeHtml(labels.staffMember)}</small><strong>${escapeHtml(record.staff)}</strong></div>
                <div><small>${escapeHtml(labels.recordDonation)}</small><strong>${escapeHtml(record.donationCreated ? labels.donationCreated : labels.notRecorded)}</strong></div>
            </div>
            <div class="bc-unit-detail-note">
                <small>${escapeHtml(labels.notes)}</small>
                <p>${escapeHtml(record.notes || labels.notRecorded)}</p>
            </div>`;

        elements.detailsActions.innerHTML = `
            <button class="btn bc-btn-outline" type="button" data-modal-close>${escapeHtml(labels.close)}</button>
            ${detailActionButtons(record)}`;
    }

    function openDetails(record) {
        state.detailId = record.id;
        renderDetails(record);
        openModal(elements.details);
    }

    async function changeStatus(record, status, confirmation, action, message) {
        if (!await confirmUi(confirmation, action, ['Cancelled', 'No-show'].includes(status))) {
            return;
        }

        try {
            await apiRequest(
                config.statusUrlTemplate.replace('__REFERENCE__', encodeURIComponent(record.id)),
                'PATCH',
                { status },
            );
            showToast(message);
            window.setTimeout(() => window.location.reload(), 350);
        } catch (requestError) {
            showUiMessage(requestError.message, true);
        }
    }

    function handoffToDonation(record) {
        if (record.status !== 'Completed' || record.purpose !== 'Donation') {
            showUiMessage(labels.notReadyForDonation);
            return;
        }

        if (record.donationCreated) {
            showUiMessage(labels.alreadyConverted);
            return;
        }

        saveValue(handoffStorageKey, {
            donorId: record.donorId,
            appointmentReference: record.id,
            donationDate: record.date,
            notes: `Created from completed appointment ${record.id}.`,
        });
        showToast(interpolate(labels.handoffMessage, { appointment: record.id }));
        window.setTimeout(() => {
            window.location.href = config.donationRoute;
        }, 180);
    }

    function handleAction(action, id) {
        const record = state.records.find((candidate) => candidate.id === id);

        if (!record) {
            return;
        }

        closeRowMenus();

        if (action === 'view') {
            openDetails(record);
        } else if (action === 'edit') {
            fillForm(record);
            openModal(elements.editor, elements.date);
        } else if (action === 'confirm') {
            changeStatus(
                record,
                'Confirmed',
                interpolate(labels.confirmBooking, { appointment: record.id, donor: record.donorName }),
                labels.confirm,
                interpolate(labels.confirmedMessage, { appointment: record.id }),
            );
        } else if (action === 'check-in') {
            changeStatus(
                record,
                'Checked in',
                interpolate(labels.confirmCheckIn, { appointment: record.id, donor: record.donorName }),
                labels.checkIn,
                interpolate(labels.checkedInMessage, { donor: record.donorName }),
            );
        } else if (action === 'complete') {
            changeStatus(
                record,
                'Completed',
                interpolate(labels.confirmComplete, { appointment: record.id }),
                labels.complete,
                interpolate(labels.completedMessage, { appointment: record.id }),
            );
        } else if (action === 'no-show') {
            changeStatus(
                record,
                'No-show',
                interpolate(labels.confirmNoShow, { appointment: record.id }),
                labels.markNoShow,
                interpolate(labels.noShowMessage, { appointment: record.id }),
            );
        } else if (action === 'cancel') {
            changeStatus(
                record,
                'Cancelled',
                interpolate(labels.confirmCancel, { appointment: record.id }),
                labels.cancelAppointment,
                interpolate(labels.cancelledMessage, { appointment: record.id }),
            );
        } else if (action === 'reopen') {
            changeStatus(
                record,
                'Pending',
                interpolate(labels.confirmReopen, { appointment: record.id }),
                labels.reopen,
                interpolate(labels.reopenedMessage, { appointment: record.id }),
            );
        } else if (action === 'donation') {
            handoffToDonation(record);
        }
    }

    function nextCentreId() {
        const maximum = state.centres.reduce((highest, centre) => {
            const match = centre.id.match(/(\d+)$/);
            return match ? Math.max(highest, Number(match[1])) : highest;
        }, 0);

        return `CTR-${String(maximum + 1).padStart(4, '0')}`;
    }

    function hideCentreFormError() {
        elements.centreFormError.hidden = true;
        elements.centreFormError.textContent = '';
    }

    function showCentreFormError(message) {
        elements.centreFormError.textContent = message;
        elements.centreFormError.hidden = false;
        elements.centreFormError.focus();
    }

    function resetCentreForm() {
        elements.centreForm.reset();
        elements.centreId.value = '';
        elements.centreActive.checked = true;
        elements.centreFormTitle.textContent = labels.addCentre;
        elements.centreSubmit.querySelector('span').textContent = labels.saveCentre;
        elements.centreSubmit.querySelector('i').className = 'la la-plus-circle';
        elements.centreCancelEdit.hidden = true;
        hideCentreFormError();
    }

    function renderCentreManager() {
        // The backend sends management records newest-first. Keep that order
        // here so a newly added centre stays at the top of the manager.
        const ordered = [...state.centres];
        const activeCount = ordered.filter((centre) => centre.active).length;

        elements.centreCount.textContent = `${activeCount}/${ordered.length}`;

        if (ordered.length === 0) {
            elements.centreList.innerHTML = `
                <div class="bc-centre-empty">
                    <i class="la la-map-marker-alt"></i>
                    <span>${escapeHtml(labels.noCentres)}</span>
                </div>`;
            return;
        }

        elements.centreList.innerHTML = ordered.map((centre) => {
            const area = [centre.township, centre.region].filter(Boolean).join(', ');
            const details = [centre.address, centre.phone, centre.hours].filter(Boolean);
            const statusLabel = centre.active ? labels.active : labels.inactive;
            const toggleLabel = centre.active ? labels.deactivateCentre : labels.activateCentre;

            return `
                <article class="bc-centre-card ${centre.active ? '' : 'is-inactive'}">
                    <header>
                        <span class="bc-centre-card-icon"><i class="la la-hospital"></i></span>
                        <div>
                            <strong>${escapeHtml(centre.name)}</strong>
                            <small>${escapeHtml(area || labels.notRecorded)}</small>
                        </div>
                        <span class="bc-status bc-status-${centre.active ? 'active' : 'inactive'}">
                            ${escapeHtml(statusLabel)}
                        </span>
                    </header>
                    <div class="bc-centre-card-details">
                        ${details.map((detail) => `<span>${escapeHtml(detail)}</span>`).join('')}
                    </div>
                    <footer>
                        <button type="button" data-centre-action="edit" data-centre-id="${escapeHtml(centre.id)}">
                            <i class="la la-pen"></i>${escapeHtml(labels.editCentreAction)}
                        </button>
                        <button type="button" data-centre-action="toggle" data-centre-id="${escapeHtml(centre.id)}">
                            <i class="la ${centre.active ? 'la-pause-circle' : 'la-play-circle'}"></i>${escapeHtml(toggleLabel)}
                        </button>
                    </footer>
                </article>`;
        }).join('');
    }

    function editCentre(centre) {
        elements.centreId.value = centre.id;
        elements.centreName.value = centre.name;
        elements.centreRegion.value = centre.region;
        elements.centreTownship.value = centre.township;
        elements.centreAddress.value = centre.address;
        elements.centrePhone.value = centre.phone;
        elements.centreHours.value = centre.hours;
        elements.centreActive.checked = centre.active;
        elements.centreFormTitle.textContent = labels.editCentre;
        elements.centreSubmit.querySelector('span').textContent = labels.updateCentre;
        elements.centreSubmit.querySelector('i').className = 'la la-save';
        elements.centreCancelEdit.hidden = false;
        hideCentreFormError();
        elements.centreName.focus();
    }

    function reloadAfterCentreChange(message) {
        showToast(message);
        window.setTimeout(() => window.location.reload(), 420);
    }

    function submitCentreForm(event) {
        event.preventDefault();
        hideCentreFormError();

        if (!elements.centreForm.checkValidity()) {
            elements.centreForm.reportValidity();
            return;
        }

        const editingId = elements.centreId.value;
        const centre = normalizeCentre({
            id: editingId,
            name: elements.centreName.value,
            region: elements.centreRegion.value,
            township: elements.centreTownship.value,
            address: elements.centreAddress.value,
            phone: elements.centrePhone.value,
            hours: elements.centreHours.value,
            active: elements.centreActive.checked,
        });
        const url = editingId
            ? config.centreUpdateUrlTemplate.replace('__REFERENCE__', encodeURIComponent(editingId))
            : config.centreStoreUrl;
        const method = editingId ? 'PUT' : 'POST';

        apiRequest(url, method, centre)
            .then(() => reloadAfterCentreChange(interpolate(labels.centreSaved, { centre: centre.name })))
            .catch((requestError) => showCentreFormError(requestError.message));
    }

    async function handleCentreAction(action, id) {
        const centre = state.centres.find((candidate) => candidate.id === id);

        if (!centre) {
            return;
        }

        if (action === 'edit') {
            editCentre(centre);
            return;
        }

        if (action !== 'toggle') {
            return;
        }

        const actionLabel = centre.active ? labels.deactivateCentre : labels.activateCentre;
        const confirmation = interpolate(labels.confirmCentreStatus, {
            action: actionLabel,
            centre: centre.name,
        });

        if (!await confirmUi(confirmation, actionLabel, centre.active)) {
            return;
        }

        const newStatus = !centre.active;
        try {
            await apiRequest(
                config.centreStatusUrlTemplate.replace('__REFERENCE__', encodeURIComponent(centre.id)),
                'PATCH',
                { active: newStatus },
            );
            reloadAfterCentreChange(interpolate(
                newStatus ? labels.centreActivated : labels.centreDeactivated,
                { centre: centre.name },
            ));
        } catch (requestError) {
            showUiMessage(requestError.message, true);
        }
    }

    function resetChanges() {
        window.location.reload();
    }

    function renderAll() {
        renderRows();

        if (state.view === 'calendar') {
            renderCalendar();
        }
    }

    populateDonorPicker();
    populateCentreControls();
    initializeCustomSelects();
    renderAll();
    setView(state.view);

    elements.primaryAction?.addEventListener('click', () => {
        resetForm();
        openModal(elements.editor, elements.donorPicker?.querySelector('.bc-filter-dropdown-trigger'));
    });

    elements.manageCentresButton?.addEventListener('click', () => {
        resetCentreForm();
        renderCentreManager();
        openModal(elements.centreManager, elements.centreName);
    });
    elements.dataSourceButton?.addEventListener('click', () => openModal(elements.dataSource));
    elements.form.addEventListener('submit', submitForm);
    elements.centreForm?.addEventListener('submit', submitCentreForm);
    elements.centreCancelEdit?.addEventListener('click', resetCentreForm);
    elements.centreList?.addEventListener('click', (event) => {
        const actionButton = event.target.closest('[data-centre-action]');

        if (actionButton) {
            handleCentreAction(actionButton.dataset.centreAction, actionButton.dataset.centreId);
        }
    });
    elements.reset?.addEventListener('click', resetChanges);
    elements.listButton.addEventListener('click', () => setView('list'));
    elements.calendarButton.addEventListener('click', () => setView('calendar'));

    elements.calendarPrevious.addEventListener('click', () => {
        state.calendarDate = new Date(
            state.calendarDate.getFullYear(),
            state.calendarDate.getMonth() - 1,
            1,
            12,
        );
        renderCalendar();
    });

    elements.calendarNext.addEventListener('click', () => {
        state.calendarDate = new Date(
            state.calendarDate.getFullYear(),
            state.calendarDate.getMonth() + 1,
            1,
            12,
        );
        renderCalendar();
    });

    elements.calendarToday.addEventListener('click', () => {
        state.calendarDate = monthStart(config.today);
        renderCalendar();
    });

    for (const input of [elements.search, elements.status, elements.centre, elements.dateFilter]) {
        input.addEventListener(input === elements.search ? 'input' : 'change', () => {
            state.page = 1;
            renderAll();
        });
    }

    elements.previous.addEventListener('click', () => {
        if (state.page > 1) {
            state.page -= 1;
            renderRows();
        }
    });

    elements.next.addEventListener('click', () => {
        const totalPages = Math.max(1, Math.ceil(filteredRecords().length / pageSize));

        if (state.page < totalPages) {
            state.page += 1;
            renderRows();
        }
    });

    elements.body.addEventListener('click', (event) => {
        const menuButton = event.target.closest('[data-appointment-menu]');
        const actionButton = event.target.closest('[data-appointment-action]');

        if (menuButton) {
            const id = menuButton.dataset.appointmentMenu;
            const menu = root.querySelector(`[data-appointment-menu-panel="${CSS.escape(id)}"]`);
            const willOpen = menu?.hidden ?? false;
            closeRowMenus(willOpen ? id : null);

            if (menu) {
                if (willOpen && window.BloodCareUI?.openRowMenu) {
                    window.BloodCareUI.openRowMenu(menuButton, menu);
                } else if (!willOpen && window.BloodCareUI?.closeRowMenu) {
                    window.BloodCareUI.closeRowMenu(menu);
                } else {
                    menu.hidden = !willOpen;
                    menuButton.setAttribute('aria-expanded', String(willOpen));
                }
            }
        } else if (actionButton) {
            handleAction(actionButton.dataset.appointmentAction, actionButton.dataset.appointmentId);
        }
    });

    elements.detailsActions.addEventListener('click', (event) => {
        const closeButton = event.target.closest('[data-modal-close]');
        const actionButton = event.target.closest('[data-appointment-action]');

        if (closeButton) {
            closeModal(elements.details);
        } else if (actionButton) {
            handleAction(actionButton.dataset.appointmentAction, actionButton.dataset.appointmentId);
        }
    });

    elements.calendarGrid.addEventListener('click', (event) => {
        const appointment = event.target.closest('[data-calendar-appointment]');

        if (appointment) {
            const record = state.records.find(
                (candidate) => candidate.id === appointment.dataset.calendarAppointment,
            );

            if (record) {
                openDetails(record);
            }
        }
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.bc-row-menu-wrap')) {
            closeRowMenus();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape'
            || document.documentElement.classList.contains('bc-confirm-open')
            || document.documentElement.classList.contains('bc-alert-open')) {
            return;
        }

        closeRowMenus();
        const openModalElement = [
            elements.editor,
            elements.details,
            elements.dataSource,
            elements.centreManager,
        ]
            .find((modal) => modal && !modal.hidden);

        if (openModalElement) {
            closeModal(openModalElement);
        }
    });

    root.querySelectorAll('[data-modal-close]').forEach((button) => {
        if (button.closest('#bc-appointment-details-actions')) {
            return;
        }

        button.addEventListener('click', () => {
            const modal = button.closest('.bc-modal');

            if (modal) {
                closeModal(modal);
            }
        });
    });
})();
