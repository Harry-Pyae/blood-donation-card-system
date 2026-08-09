(() => {
    'use strict';

    const root = document.querySelector('[data-bc-module="donations"]');
    const configNode = document.getElementById('bc-donation-config');

    if (!root || !configNode) {
        return;
    }

    const config = JSON.parse(configNode.textContent);
    const labels = config.labels;
    const storageKey = 'bloodcare.donations.interactive.v1';
    const donorStorageKey = 'bloodcare.donors.interactive.v1';
    const inventoryStorageKey = 'bloodcare.inventory.interactive.v1';
    const historyStorageKey = 'bloodcare.history.interactive.v1';
    const appointmentStorageKey = 'bloodcare.appointments.interactive.v1';
    const appointmentHandoffKey = 'bloodcare.donation.prefill.v1';
    const backupStorageKey = 'bloodcare.donations.donor-backups.v1';
    const auditSource = 'donations-database';
    const pageSize = 5;
    const originalRecords = config.records.map(normalizeRecord);
    const originalContribution = contributionFor(originalRecords);

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
            throw new Error(validation || data.message || 'Unable to save the donation.');
        }
        return data;
    }

    function donationPayload(record) {
        return {
            donorId: record.donorId,
            donationDate: record.donationDate,
            group: record.group,
            donationType: record.donationType || 'whole_blood',
            quantity: record.quantity,
            screeningResult: record.screeningResult,
            status: record.status,
            appointmentReference: record.appointmentReference || null,
            screeningReference: record.screeningReference || null,
            bagUnit: record.bagUnit,
            expiryDate: record.expiryDate,
            location: record.location,
            notes: record.notes || '',
        };
    }

    const elements = {
        body: document.getElementById('bc-donation-table-body'),
        search: document.getElementById('bc-donation-search'),
        group: document.getElementById('bc-donation-group'),
        status: document.getElementById('bc-donation-status'),
        date: document.getElementById('bc-donation-date'),
        resultCount: document.getElementById('bc-donation-result-count'),
        page: document.getElementById('bc-donation-page'),
        previous: document.getElementById('bc-donation-prev'),
        next: document.getElementById('bc-donation-next'),
        export: document.getElementById('bc-donation-export'),
        primaryAction: document.getElementById('bc-primary-action'),
        dataSourceButton: document.getElementById('bc-data-source-button'),
        editor: document.getElementById('bc-donation-editor'),
        editorTitle: document.getElementById('bc-donation-editor-title'),
        details: document.getElementById('bc-donation-details'),
        dataSource: document.getElementById('bc-donation-data-source-modal'),
        form: document.getElementById('bc-donation-form'),
        formError: document.getElementById('bc-donation-form-error'),
        donor: document.getElementById('bc-donation-donor'),
        donorPicker: document.querySelector('[data-bc-donor-select]'),
        donationId: document.getElementById('bc-donation-id'),
        appointment: document.getElementById('bc-donation-appointment'),
        screeningReference: document.getElementById('bc-donation-screening-reference'),
        donationDate: document.getElementById('bc-donation-date-field'),
        formGroup: document.getElementById('bc-donation-form-group'),
        donationType: document.getElementById('bc-donation-type'),
        quantity: document.getElementById('bc-donation-quantity'),
        screening: document.getElementById('bc-donation-screening'),
        formStatus: document.getElementById('bc-donation-status-field'),
        unit: document.getElementById('bc-donation-unit'),
        expiry: document.getElementById('bc-donation-expiry'),
        location: document.getElementById('bc-donation-location'),
        staff: document.getElementById('bc-donation-staff'),
        notes: document.getElementById('bc-donation-notes'),
        submit: document.getElementById('bc-donation-submit'),
        detailsContent: document.getElementById('bc-donation-details-content'),
        print: document.getElementById('bc-donation-print'),
        reset: document.getElementById('bc-reset-donations'),
        toast: document.getElementById('bc-donation-toast'),
    };

    const state = {
        records: readRecords(),
        donors: readDonors(),
        inventory: readInventory(),
        appointments: readAppointments(),
        screenings: readScreenings(),
        history: readHistory(),
        backups: readBackups(),
        page: 1,
        editingId: null,
        detailId: null,
    };

    state.records = synchronizeDonorDetails(state.records, state.donors);

    const customSelects = new Map();
    let lastFocusedElement = null;
    let toastTimer = null;

    function normalizeRecord(record) {
        return {
            id: String(record.id || ''),
            donorId: String(record.donorId || ''),
            donorName: String(record.donorName || ''),
            group: String(record.group || ''),
            donationType: String(record.donationType || 'whole_blood'),
            quantity: Number(record.quantity || 450),
            donationDate: String(record.donationDate || config.today),
            staff: String(record.staff || config.staffName),
            screeningResult: String(record.screeningResult || 'Pending'),
            status: String(record.status || 'Screening'),
            appointmentReference: String(record.appointmentReference || ''),
            screeningReference: String(record.screeningReference || ''),
            bagUnit: String(record.bagUnit || ''),
            expiryDate: String(record.expiryDate || ''),
            location: String(record.location || 'Cold room A'),
            notes: String(record.notes || ''),
            inventorySynced: Boolean(record.inventorySynced),
            donorSynced: Boolean(record.donorSynced),
        };
    }

    function normalizeDonor(donor) {
        return {
            ...donor,
            id: String(donor.id || ''),
            name: String(donor.name || ''),
            group: String(donor.group || ''),
            phone: String(donor.phone || ''),
            eligibility: String(donor.eligibility || 'Review'),
            status: String(donor.status || 'Pending'),
            lastDonation: donor.lastDonation || null,
            nextEligible: donor.nextEligible || 'review',
            notes: String(donor.notes || ''),
        };
    }

    function normalizeInventory(record) {
        return {
            ...record,
            id: String(record.id || ''),
            group: String(record.group || ''),
            collected: String(record.collected || ''),
            expires: String(record.expires || ''),
            location: String(record.location || 'Cold room A'),
            status: String(record.status || 'Available'),
            note: String(record.note || ''),
        };
    }

    function normalizeAppointment(record) {
        return {
            id: String(record.id || ''),
            donorId: String(record.donorId || ''),
            date: String(record.date || ''),
            centre: String(record.centre || ''),
            status: String(record.status || ''),
            donationId: record.donationId ? String(record.donationId) : null,
        };
    }

    function readArray(key, fallback, normalize) {
        return fallback.map(normalize);
    }

    function readRecords() {
        return readArray(storageKey, config.records, normalizeRecord)
            .filter((record) => record.id && record.donorId);
    }

    function readDonors() {
        return readArray(donorStorageKey, config.donors, normalizeDonor)
            .filter((donor) => donor.id);
    }

    function readInventory() {
        return readArray(inventoryStorageKey, config.inventoryRecords, normalizeInventory)
            .filter((record) => record.id);
    }

    function readAppointments() {
        return readArray(appointmentStorageKey, config.appointments || [], normalizeAppointment)
            .filter((record) => record.id && record.donorId);
    }

    function readScreenings() {
        return (config.screenings || []).map((record) => ({
            id: String(record.id || ''),
            donorId: String(record.donorId || ''),
            date: String(record.date || ''),
            outcome: String(record.outcome || ''),
            centre: String(record.centre || ''),
            nextScreeningDate: String(record.nextScreeningDate || ''),
        })).filter((record) => record.id && record.donorId);
    }

    function readHistory() {
        return [];
    }

    function readBackups() {
        return {};
    }

    function saveValue(key, value) {
        // Laravel and MySQL are the source of truth.
    }

    function saveConnectedData() {
        // Saved by authenticated Laravel routes.
    }

    function markAppointmentConverted(reference) {
        // The backend completes the linked appointment in the same transaction.
    }

    function synchronizeDonorDetails(records, donors) {
        return records.map((record) => {
            const donor = donors.find((candidate) => candidate.id === record.donorId);

            return donor
                ? normalizeRecord({
                    ...record,
                    donorName: donor.name,
                    group: donor.group,
                })
                : normalizeRecord(record);
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

    function addDays(value, days) {
        const date = parseDate(value);

        if (!date) {
            return '';
        }

        date.setDate(date.getDate() + Number(days));
        return isoDate(date);
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

    function translatedScreening(result) {
        return labels[statusSlug(result)] || result;
    }

    function contributionFor(records) {
        const currentMonth = config.today.slice(0, 7);

        return {
            month: records.filter((record) => record.donationDate.startsWith(currentMonth)).length,
            accepted: records.filter((record) => record.status === 'Accepted').length,
            review: records.filter((record) => record.status === 'Screening').length,
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

    function eligibleDonors() {
        return state.donors
            .filter((donor) => donorEligibleOn(donor, config.today))
            .sort((first, second) => first.name.localeCompare(second.name));
    }

    function populateDonorPicker() {
        const donors = eligibleDonors();
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
                <span>${escapeHtml(donors.length ? labels.chooseDonorPlaceholder : labels.noEligibleDonors)}</span>
                <i class="la la-check" aria-hidden="true"></i>
            </button>
            ${buttonMarkup}`;
    }

    function refreshAppointmentChoices(selectedReference = '') {
        const donorId = elements.donor.value;
        const appointments = state.appointments.filter((appointment) => (
            appointment.donorId === donorId
            && (!appointment.donationId || appointment.donationId === state.editingId)
        ));
        const selectedExists = appointments.some(
            (appointment) => appointment.id === selectedReference,
        );

        elements.appointment.innerHTML = [
            `<option value="">${escapeHtml(labels.noLinkedAppointment)}</option>`,
            ...appointments.map((appointment) => (
                `<option value="${escapeHtml(appointment.id)}">${escapeHtml([
                    appointment.id,
                    appointment.date ? formatDate(appointment.date) : '',
                    appointment.centre,
                    appointment.status,
                ].filter(Boolean).join(' · '))}</option>`
            )),
        ].join('');
        elements.appointment.value = selectedExists ? selectedReference : '';
        elements.appointment.disabled = !donorId;
        elements.appointment.dispatchEvent(new Event('change', { bubbles: true }));

        return selectedExists || !selectedReference;
    }

    function refreshScreeningChoices(selectedReference = '') {
        const donorId = elements.donor.value;
        const screenings = state.screenings.filter((screening) => screening.donorId === donorId);
        const selectedExists = screenings.some((screening) => screening.id === selectedReference);

        elements.screeningReference.innerHTML = [
            `<option value="">${escapeHtml(labels.noLinkedScreening)}</option>`,
            ...screenings.map((screening) => (
                `<option value="${escapeHtml(screening.id)}">${escapeHtml([
                    screening.id,
                    screening.date ? formatDate(screening.date) : '',
                    screening.centre,
                ].filter(Boolean).join(' · '))}</option>`
            )),
        ].join('');
        elements.screeningReference.value = selectedExists ? selectedReference : '';
        elements.screeningReference.disabled = !donorId;
        elements.screeningReference.dispatchEvent(new Event('change', { bubbles: true }));

        return selectedExists || !selectedReference;
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
                if (trigger.disabled) {
                    return;
                }

                closeAll(widget);
                widget.classList.add('open');
                trigger.setAttribute('aria-expanded', 'true');
                menu.hidden = false;
                menu.scrollTop = 0;
            };

            const focusOption = (index) => {
                const options = orderedOptions();
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

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            const openWidget = widgets.find((widget) => widget.classList.contains('open'));
            const select = openWidget?.querySelector('select');

            if (select && customSelects.has(select)) {
                event.preventDefault();
                customSelects.get(select).close(true);
            }
        });
    }

    function syncCustomSelect(select) {
        customSelects.get(select)?.sync();
    }

    function filteredRecords() {
        const term = elements.search.value.trim().toLowerCase();
        const currentMonth = config.today.slice(0, 7);

        return state.records
            .filter((record) => {
                const searchable = [
                    record.id,
                    record.donorId,
                    record.donorName,
                    record.group,
                    record.bagUnit,
                    record.appointmentReference,
                    record.staff,
                    record.status,
                    record.screeningResult,
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

                if (elements.date.value === 'today' && record.donationDate !== config.today) {
                    return false;
                }

                if (elements.date.value === 'month' && !record.donationDate.startsWith(currentMonth)) {
                    return false;
                }

                if (elements.date.value === 'earlier' && record.donationDate.startsWith(currentMonth)) {
                    return false;
                }

                return true;
            });
    }

    function rowActions(record) {
        const actions = [
            `<button type="button" data-donation-action="view" data-donation-id="${escapeHtml(record.id)}"><i class="la la-eye"></i>${escapeHtml(labels.view)}</button>`,
        ];

        if (record.status === 'Screening') {
            actions.push(`<button type="button" data-donation-action="edit" data-donation-id="${escapeHtml(record.id)}"><i class="la la-clipboard-check"></i>${escapeHtml(labels.edit)}</button>`);
            actions.push('<span class="bc-row-menu-divider"></span>');
            actions.push(`<button type="button" data-donation-action="accept" data-donation-id="${escapeHtml(record.id)}"><i class="la la-check-circle"></i>${escapeHtml(labels.accept)}</button>`);
            actions.push(`<button class="bc-row-menu-danger" type="button" data-donation-action="reject" data-donation-id="${escapeHtml(record.id)}"><i class="la la-times-circle"></i>${escapeHtml(labels.reject)}</button>`);
        } else {
            actions.push(`<button type="button" data-donation-action="print" data-donation-id="${escapeHtml(record.id)}"><i class="la la-print"></i>${escapeHtml(labels.printReceipt)}</button>`);
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
                        <i class="la la-search"></i>
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
                    <td><span class="bc-group-badge">${escapeHtml(record.group)}</span></td>
                    <td>${escapeHtml(record.quantity)} ml</td>
                    <td>${escapeHtml(formatDate(record.donationDate))}</td>
                    <td><span class="bc-status bc-status-${statusSlug(record.status)}">${escapeHtml(translatedStatus(record.status))}</span></td>
                    <td class="text-end">
                        <span class="bc-row-menu-wrap">
                            <button class="bc-row-action" type="button" data-donation-menu="${escapeHtml(record.id)}"
                                    aria-expanded="false"
                                    aria-label="${escapeHtml(interpolate(labels.actionsFor, { donation: record.id }))}">
                                <i class="la la-ellipsis-h"></i>
                            </button>
                            <span class="bc-row-menu" data-donation-menu-panel="${escapeHtml(record.id)}" hidden>
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

        for (const key of ['month', 'accepted', 'review']) {
            const element = root.querySelector(`[data-donation-metric="${key}"] strong`);
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

    function closeRowMenus(exceptId = null) {
        root.querySelectorAll('[data-donation-menu-panel]').forEach((menu) => {
            if (menu.dataset.donationMenuPanel === exceptId) {
                return;
            }

            if (window.BloodCareUI?.closeRowMenu) {
                window.BloodCareUI.closeRowMenu(menu);
            } else {
                menu.hidden = true;
                root.querySelector(`[data-donation-menu="${CSS.escape(menu.dataset.donationMenuPanel)}"]`)
                    ?.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function generateReference(prefix, records, field) {
        const maximum = records.reduce((highest, record) => {
            const match = String(record[field] || '').match(/(\d+)$/);
            return match ? Math.max(highest, Number(match[1])) : highest;
        }, 0);

        return `${prefix}${String(maximum + 1).padStart(6, '0')}`;
    }

    function nextDonationId() {
        return generateReference('DON-', state.records, 'id');
    }

    function nextUnitId() {
        const records = [
            ...state.inventory.map((record) => ({ reference: record.id })),
            ...state.records.map((record) => ({ reference: record.bagUnit })),
        ];
        return generateReference('BU-', records, 'reference');
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
        elements.editorTitle.textContent = labels.recordTitle;
        elements.submit.querySelector('span').textContent = labels.saveRecord;
        elements.donationId.value = nextDonationId();
        elements.donationDate.value = config.today;
        elements.expiry.value = addDays(config.today, config.wholeBloodExpiryDays);
        elements.unit.value = nextUnitId();
        elements.staff.value = config.staffName;
        elements.quantity.value = '450';
        elements.donationType.value = 'whole_blood';
        elements.screening.value = 'Passed';
        elements.formStatus.value = 'Accepted';
        elements.donor.value = '';
        elements.formGroup.value = '';
        refreshAppointmentChoices();
        refreshScreeningChoices();
        setDonorPickerDisabled(false);
        syncCustomSelect(elements.donor);
        hideFormError();
    }

    function fillForm(record) {
        state.editingId = record.id;
        elements.editorTitle.textContent = labels.editTitle;
        elements.submit.querySelector('span').textContent = labels.completeReview;
        elements.donationId.value = record.id;
        elements.donor.value = record.donorId;
        refreshAppointmentChoices(record.appointmentReference);
        refreshScreeningChoices(record.screeningReference);
        elements.donationDate.value = record.donationDate;
        elements.formGroup.value = record.group;
        elements.donationType.value = record.donationType;
        elements.quantity.value = String(record.quantity);
        elements.screening.value = record.screeningResult;
        elements.formStatus.value = record.status;
        elements.unit.value = record.bagUnit;
        elements.expiry.value = record.expiryDate;
        elements.location.value = record.location;
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

        if (![elements.editor, elements.details, elements.dataSource].some(
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
        // The backend writes the permanent activity log.
    }

    function addInventoryUnit(record) {
        if (state.inventory.some((unit) => unit.id === record.bagUnit)) {
            return false;
        }

        state.inventory.unshift(normalizeInventory({
            id: record.bagUnit,
            group: record.group,
            collected: record.donationDate,
            expires: record.expiryDate,
            location: record.location,
            status: 'Available',
            note: `Received from donation ${record.id}.`,
            sourceDonationId: record.id,
        }));
        record.inventorySynced = true;
        return true;
    }

    function updateDonorAfterAcceptance(record) {
        const index = state.donors.findIndex((donor) => donor.id === record.donorId);

        if (index < 0) {
            return;
        }

        const donor = state.donors[index];

        if (!state.backups[donor.id]) {
            state.backups[donor.id] = { ...donor };
        }

        const workflowNote = `Donation ${record.id} accepted on ${record.donationDate}.`;
        const eligibilityWaitDays = Number(
            config.donationIntervalsDays?.[record.donationType]
            ?? config.eligibilityWaitDays
            ?? 90,
        );
        state.donors[index] = normalizeDonor({
            ...donor,
            lastDonation: record.donationDate,
            nextEligible: addDays(record.donationDate, eligibilityWaitDays),
            eligibility: 'Deferred',
            status: 'Active',
            notes: donor.notes.includes(workflowNote)
                ? donor.notes
                : [donor.notes, workflowNote].filter(Boolean).join(' '),
        });
        record.donorSynced = true;
    }

    function acceptRecord(record, shouldAudit = true) {
        if (!record.inventorySynced && !addInventoryUnit(record)) {
            return false;
        }

        if (!record.donorSynced) {
            updateDonorAfterAcceptance(record);
        }

        record.status = 'Accepted';
        record.screeningResult = 'Passed';

        if (shouldAudit) {
            auditEvent(record, 'Donation accepted');
        }

        return true;
    }

    function rejectRecord(record, shouldAudit = true) {
        record.status = 'Rejected';
        record.screeningResult = 'Failed';

        if (shouldAudit) {
            auditEvent(record, 'Donation rejected');
        }
    }

    function validateRecord(record, editingId = null) {
        const donor = state.donors.find((candidate) => candidate.id === record.donorId);

        if (!donorEligibleOn(donor, record.donationDate)) {
            return labels.donorNotEligible;
        }

        if (record.expiryDate <= record.donationDate) {
            return labels.invalidDates;
        }

        if ((record.status === 'Accepted' && record.screeningResult !== 'Passed')
            || (record.status === 'Rejected' && record.screeningResult !== 'Failed')) {
            return labels.invalidResult;
        }

        if (record.status === 'Accepted' && !record.screeningReference) {
            return labels.screeningUnavailable;
        }

        if (state.records.some((candidate) => (
            candidate.id === record.id && candidate.id !== editingId
        ))) {
            return labels.duplicateDonation;
        }

        const duplicateDonationUnit = state.records.some((candidate) => (
            candidate.bagUnit === record.bagUnit && candidate.id !== editingId
        ));
        const duplicateInventoryUnit = state.inventory.some((candidate) => (
            candidate.id === record.bagUnit
            && candidate.sourceDonationId !== editingId
            && !state.records.some((existing) => (
                existing.id === editingId
                && existing.inventorySynced
                && existing.bagUnit === candidate.id
            ))
        ));

        if (duplicateDonationUnit || duplicateInventoryUnit) {
            return labels.duplicateUnit;
        }

        return '';
    }

    function formRecord() {
        const donor = state.donors.find((candidate) => candidate.id === elements.donor.value);
        const existing = state.records.find((record) => record.id === state.editingId);

        return normalizeRecord({
            ...(existing || {}),
            id: elements.donationId.value.trim().toUpperCase(),
            donorId: elements.donor.value,
            donorName: donor?.name || '',
            group: donor?.group || elements.formGroup.value,
            donationType: elements.donationType.value,
            quantity: Number(elements.quantity.value),
            donationDate: elements.donationDate.value,
            staff: elements.staff.value.trim() || config.staffName,
            screeningResult: elements.screening.value,
            status: elements.formStatus.value,
            appointmentReference: elements.appointment.value.trim().toUpperCase(),
            screeningReference: elements.screeningReference.value.trim().toUpperCase(),
            bagUnit: elements.unit.value.trim().toUpperCase(),
            expiryDate: elements.expiry.value,
            location: elements.location.value,
            notes: elements.notes.value.trim(),
        });
    }

    async function submitForm(event) {
        event.preventDefault();
        hideFormError();

        if (!elements.donor.value) {
            showFormError(labels.donorNotEligible);
            return;
        }
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

        try {
            const isEditing = Boolean(state.editingId);
            const url = isEditing
                ? endpoint(config.updateUrlTemplate, state.editingId)
                : config.storeUrl;
            await apiRequest(url, isEditing ? 'PUT' : 'POST', donationPayload(record));
            closeModal(elements.editor);
            const message = record.status === 'Accepted'
                ? interpolate(labels.acceptedMessage, { donation: record.id, unit: record.bagUnit })
                : record.status === 'Rejected'
                    ? interpolate(labels.rejectedMessage, { donation: record.id })
                    : interpolate(labels.savedMessage, { donation: record.id });
            showToast(message);
            window.setTimeout(() => window.location.reload(), 350);
        } catch (error) {
            showFormError(error.message);
        }
    }

    function donorNextEligible(record) {
        const donor = state.donors.find((candidate) => candidate.id === record.donorId);
        return record.status === 'Accepted' && donor?.nextEligible
            ? formatDate(donor.nextEligible)
            : labels.notApplicable;
    }

    function renderDetails(record) {
        const inventoryUnit = record.status === 'Accepted' && record.inventorySynced
            ? record.bagUnit
            : labels.notApplicable;

        elements.detailsContent.innerHTML = `
            <article class="bc-donation-receipt">
                <header class="bc-donation-receipt-header">
                    <div class="bc-donation-receipt-brand">
                        <span><i class="la la-tint"></i></span>
                        <div>
                            <strong>BloodCare</strong>
                            <small>${escapeHtml(labels.receiptSubtitle)}</small>
                        </div>
                    </div>
                    <div class="bc-donation-receipt-reference">
                        <small>${escapeHtml(labels.donationId)}</small>
                        <strong>${escapeHtml(record.id)}</strong>
                    </div>
                </header>

                <div class="bc-donation-receipt-outcome">
                    <div>
                        <small>${escapeHtml(record.donorId)}</small>
                        <h3>${escapeHtml(record.donorName)}</h3>
                    </div>
                    <span class="bc-group-badge">${escapeHtml(record.group)}</span>
                    <span class="bc-status bc-status-${statusSlug(record.status)}">${escapeHtml(translatedStatus(record.status))}</span>
                </div>

                <div class="bc-donation-receipt-grid">
                    <div><small>${escapeHtml(labels.donationDate)}</small><strong>${escapeHtml(formatDate(record.donationDate))}</strong></div>
                    <div><small>${escapeHtml(labels.quantity)}</small><strong>${escapeHtml(record.quantity)} ml</strong></div>
                    <div><small>${escapeHtml(labels.screeningResult)}</small><strong>${escapeHtml(translatedScreening(record.screeningResult))}</strong></div>
                    <div><small>${escapeHtml(labels.bagUnit)}</small><strong>${escapeHtml(record.bagUnit)}</strong></div>
                    <div><small>${escapeHtml(labels.expiryDate)}</small><strong>${escapeHtml(formatDate(record.expiryDate))}</strong></div>
                    <div><small>${escapeHtml(labels.location)}</small><strong>${escapeHtml(record.location)}</strong></div>
                    <div><small>${escapeHtml(labels.appointmentReference)}</small><strong>${escapeHtml(record.appointmentReference || labels.notRecorded)}</strong></div>
                    <div><small>${escapeHtml(labels.screeningReference)}</small><strong>${escapeHtml(record.screeningReference || labels.notRecorded)}</strong></div>
                    <div><small>${escapeHtml(labels.donationType)}</small><strong>${escapeHtml(record.donationType.replaceAll('_', ' '))}</strong></div>
                    <div><small>${escapeHtml(labels.staffMember)}</small><strong>${escapeHtml(record.staff)}</strong></div>
                    <div><small>${escapeHtml(labels.inventoryUnit)}</small><strong>${escapeHtml(inventoryUnit)}</strong></div>
                    <div><small>${escapeHtml(labels.nextEligible)}</small><strong>${escapeHtml(donorNextEligible(record))}</strong></div>
                </div>

                <div class="bc-donation-receipt-note">
                    <small>${escapeHtml(labels.notes)}</small>
                    <p>${escapeHtml(record.notes || labels.notRecorded)}</p>
                </div>
            </article>`;
    }

    function openDetails(record, printAfterOpen = false) {
        state.detailId = record.id;
        renderDetails(record);
        openModal(elements.details, elements.print);

        if (printAfterOpen) {
            window.setTimeout(printReceipt, 120);
        }
    }

    function printReceipt() {
        if (!state.detailId) {
            return;
        }

        document.documentElement.classList.add('bc-print-donation');
        const cleanUp = () => document.documentElement.classList.remove('bc-print-donation');
        window.addEventListener('afterprint', cleanUp, { once: true });
        window.print();
        window.setTimeout(cleanUp, 1200);
    }

    async function acceptById(id) {
        const record = state.records.find((candidate) => candidate.id === id);
        if (!record || record.status !== 'Screening') {
            return;
        }
        if (!await confirmUi(interpolate(labels.confirmAccept, {
            donation: record.id,
            unit: record.bagUnit,
            donor: record.donorName,
        }), labels.accept)) {
            return;
        }

        try {
            await apiRequest(endpoint(config.statusUrlTemplate, id), 'PATCH', { status: 'Accepted' });
            showToast(interpolate(labels.acceptedMessage, { donation: record.id, unit: record.bagUnit }));
            window.setTimeout(() => window.location.reload(), 350);
        } catch (error) {
            showUiMessage(error.message, true);
        }
    }

    async function rejectById(id) {
        const record = state.records.find((candidate) => candidate.id === id);
        if (!record || record.status !== 'Screening') {
            return;
        }
        if (!await confirmUi(
            interpolate(labels.confirmReject, { donation: record.id }),
            labels.reject,
            true,
        )) {
            return;
        }

        try {
            await apiRequest(endpoint(config.statusUrlTemplate, id), 'PATCH', { status: 'Rejected' });
            showToast(interpolate(labels.rejectedMessage, { donation: record.id }));
            window.setTimeout(() => window.location.reload(), 350);
        } catch (error) {
            showUiMessage(error.message, true);
        }
    }

    function csvEscape(value) {
        const text = String(value ?? '');
        return `"${text.replaceAll('"', '""')}"`;
    }

    function exportCsv() {
        const rows = filteredRecords();
        const content = [
            labels.csvColumns.map(csvEscape).join(','),
            ...rows.map((record) => [
                record.id,
                record.donorId,
                record.donorName,
                record.group,
                record.quantity,
                record.donationDate,
                translatedScreening(record.screeningResult),
                translatedStatus(record.status),
                record.bagUnit,
                record.expiryDate,
                record.location,
                record.staff,
                record.appointmentReference,
                record.notes,
            ].map(csvEscape).join(',')),
        ].join('\r\n');
        const blob = new Blob([`\uFEFF${content}`], { type: 'text/csv;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `bloodcare-donations-${config.today}.csv`;
        document.body.append(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
        showToast(labels.exportedMessage);
    }

    function resetConnectedChanges() {
        window.location.reload();
    }

    populateDonorPicker();
    refreshAppointmentChoices();
    refreshScreeningChoices();
    initializeCustomSelects();
    renderRows();

    function consumeAppointmentHandoff() {
        let handoff = null;

        try {
            handoff = JSON.parse(window.localStorage.getItem(appointmentHandoffKey));
            window.localStorage.removeItem(appointmentHandoffKey);
        } catch (error) {
            handoff = null;
        }

        if (!handoff || typeof handoff !== 'object') {
            return;
        }

        const donorOption = [...elements.donor.options].some(
            (option) => option.value === String(handoff.donorId || ''),
        );

        if (!donorOption) {
            showUiMessage(labels.donorNotEligible);
            return;
        }

        resetForm();
        elements.donor.value = String(handoff.donorId || '');
        const appointmentAvailable = refreshAppointmentChoices(
            String(handoff.appointmentReference || ''),
        );
        refreshScreeningChoices();
        elements.donationDate.value = String(handoff.donationDate || config.today);
        elements.expiry.value = addDays(
            elements.donationDate.value,
            config.wholeBloodExpiryDays,
        );
        elements.notes.value = String(handoff.notes || '');
        elements.formGroup.value = state.donors.find(
            (donor) => donor.id === elements.donor.value,
        )?.group || '';
        syncCustomSelect(elements.donor);
        openModal(elements.editor, elements.screening);

        if (!appointmentAvailable) {
            showUiMessage(labels.appointmentUnavailable);
        }
    }

    consumeAppointmentHandoff();

    elements.primaryAction?.addEventListener('click', () => {
        resetForm();
        openModal(elements.editor, elements.donorPicker?.querySelector('.bc-filter-dropdown-trigger'));
    });

    elements.dataSourceButton?.addEventListener('click', () => {
        openModal(elements.dataSource);
    });

    elements.form.addEventListener('submit', submitForm);
    elements.export?.addEventListener('click', exportCsv);
    elements.print?.addEventListener('click', printReceipt);
    elements.reset?.addEventListener('click', resetConnectedChanges);

    elements.donor.addEventListener('change', () => {
        const donor = state.donors.find((candidate) => candidate.id === elements.donor.value);
        elements.formGroup.value = donor?.group || '';
        refreshAppointmentChoices();
        refreshScreeningChoices();
    });

    elements.donationDate.addEventListener('change', () => {
        if (!state.editingId && elements.donationDate.value) {
            elements.expiry.value = addDays(
                elements.donationDate.value,
                config.wholeBloodExpiryDays,
            );
        }
    });

    elements.formStatus.addEventListener('change', () => {
        if (elements.formStatus.value === 'Accepted') {
            elements.screening.value = 'Passed';
        } else if (elements.formStatus.value === 'Rejected') {
            elements.screening.value = 'Failed';
        } else {
            elements.screening.value = 'Pending';
        }
    });

    for (const input of [elements.search, elements.group, elements.status, elements.date]) {
        input.addEventListener(input === elements.search ? 'input' : 'change', () => {
            state.page = 1;
            renderRows();
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
        const menuButton = event.target.closest('[data-donation-menu]');

        if (menuButton) {
            const id = menuButton.dataset.donationMenu;
            const panel = root.querySelector(`[data-donation-menu-panel="${CSS.escape(id)}"]`);
            const shouldOpen = panel?.hidden;
            closeRowMenus();

            if (panel && shouldOpen) {
                if (window.BloodCareUI?.openRowMenu) {
                    window.BloodCareUI.openRowMenu(menuButton, panel);
                } else {
                    panel.hidden = false;
                    menuButton.setAttribute('aria-expanded', 'true');
                }
            }

            return;
        }

        const actionButton = event.target.closest('[data-donation-action]');

        if (!actionButton) {
            return;
        }

        const id = actionButton.dataset.donationId;
        const record = state.records.find((candidate) => candidate.id === id);
        closeRowMenus();

        if (!record) {
            return;
        }

        switch (actionButton.dataset.donationAction) {
            case 'view':
                openDetails(record);
                break;
            case 'print':
                openDetails(record, true);
                break;
            case 'edit':
                fillForm(record);
                openModal(elements.editor, elements.screening);
                break;
            case 'accept':
                acceptById(id);
                break;
            case 'reject':
                rejectById(id);
                break;
            default:
                break;
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
        const openModalElement = [elements.editor, elements.details, elements.dataSource]
            .find((modal) => modal && !modal.hidden);

        if (openModalElement) {
            closeModal(openModalElement);
        }
    });

    root.querySelectorAll('[data-modal-close]').forEach((button) => {
        button.addEventListener('click', () => {
            const modal = button.closest('.bc-modal');

            if (modal) {
                closeModal(modal);
            }
        });
    });
})();
