(() => {
    'use strict';

    const root = document.querySelector('[data-bc-module="inventory"]');
    const configNode = document.getElementById('bc-inventory-config');

    if (!root || !configNode) {
        return;
    }

    const config = JSON.parse(configNode.textContent);
    const labels = config.labels;
    const storageKey = 'bloodcare.inventory.interactive.v1';
    const historyStorageKey = 'bloodcare.history.interactive.v1';
    const auditSource = 'inventory-database';
    const pageSize = 5;
    const originalRecords = config.records.map((record) => ({ ...record }));
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
            throw new Error(validation || data.message || 'Unable to save the inventory unit.');
        }
        return data;
    }

    const elements = {
        body: document.getElementById('bc-inventory-table-body'),
        search: document.getElementById('bc-inventory-search'),
        status: document.getElementById('bc-inventory-status'),
        expiry: document.getElementById('bc-inventory-expiry'),
        resultCount: document.getElementById('bc-inventory-result-count'),
        page: document.getElementById('bc-inventory-page'),
        previous: document.getElementById('bc-inventory-prev'),
        next: document.getElementById('bc-inventory-next'),
        primaryAction: document.getElementById('bc-primary-action'),
        dataSourceButton: document.getElementById('bc-data-source-button'),
        editor: document.getElementById('bc-inventory-editor'),
        details: document.getElementById('bc-inventory-details'),
        dataSource: document.getElementById('bc-data-source-modal'),
        form: document.getElementById('bc-inventory-form'),
        formError: document.getElementById('bc-inventory-form-error'),
        existingField: document.querySelector('.bc-existing-unit-field'),
        existingUnit: document.getElementById('bc-existing-unit'),
        unitId: document.getElementById('bc-unit-id'),
        group: document.getElementById('bc-unit-group'),
        collected: document.getElementById('bc-unit-collected'),
        expires: document.getElementById('bc-unit-expires'),
        location: document.getElementById('bc-unit-location'),
        unitStatus: document.getElementById('bc-unit-status'),
        note: document.getElementById('bc-unit-note'),
        detailsContent: document.getElementById('bc-unit-details-content'),
        detailsEdit: document.getElementById('bc-details-edit'),
        reset: document.getElementById('bc-reset-inventory'),
        toast: document.getElementById('bc-inventory-toast'),
    };

    const state = {
        records: readRecords(),
        history: readHistory(),
        page: 1,
        group: 'all',
        editingId: null,
        detailId: null,
        mode: 'add',
    };

    const customSelects = new Map();

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
            const options = [...widget.querySelectorAll('[role="option"]')];

            if (!select || !trigger || !menu || !label || options.length === 0) {
                continue;
            }

            const sync = () => {
                const selected = options.find((option) => option.dataset.value === select.value) || options[0];
                label.textContent = selected.querySelector('span')?.textContent.trim() || selected.textContent.trim();

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
                if (!option) {
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

    function readRecords() {
        return originalRecords.map((record) => ({ ...record }));
    }

    function isValidRecord(record) {
        return record
            && typeof record.id === 'string'
            && typeof record.group === 'string'
            && typeof record.collected === 'string'
            && typeof record.expires === 'string'
            && typeof record.location === 'string'
            && typeof record.status === 'string';
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
        // The backend writes the permanent activity log.
    }

    function parseDate(value) {
        return new Date(`${value}T12:00:00`);
    }

    function daysUntil(value) {
        const milliseconds = parseDate(value).getTime() - parseDate(config.today).getTime();
        return Math.ceil(milliseconds / 86400000);
    }

    function effectiveStatus(record) {
        if (record.status === 'Used' || record.status === 'Discarded') {
            return record.status;
        }

        const remaining = daysUntil(record.expires);

        if (remaining < 0) {
            return 'Expired';
        }

        if (record.status === 'Available' && remaining <= 7) {
            return 'Expiring';
        }

        return record.status;
    }

    function isAvailable(record) {
        return record.status === 'Available' && daysUntil(record.expires) >= 0;
    }

    function isExpiring(record) {
        return !['Used', 'Discarded'].includes(record.status)
            && daysUntil(record.expires) >= 0
            && daysUntil(record.expires) <= 7;
    }

    function contributionFor(records) {
        const groupTotals = {};

        for (const group of Object.keys(config.groupTotals)) {
            groupTotals[group] = 0;
        }

        const result = {
            available: 0,
            reserved: 0,
            expiring: 0,
            groups: groupTotals,
        };

        for (const record of records) {
            if (isAvailable(record)) {
                result.available += 1;
                result.groups[record.group] = (result.groups[record.group] || 0) + 1;
            }

            if (record.status === 'Reserved' && daysUntil(record.expires) >= 0) {
                result.reserved += 1;
            }

            if (isExpiring(record)) {
                result.expiring += 1;
            }
        }

        return result;
    }

    function translatedStatus(status) {
        return labels[status.toLowerCase()] || status;
    }

    function statusSlug(status) {
        return status.toLowerCase().replace(/\s+/g, '-');
    }

    function formatDate(value) {
        const locale = config.locale === 'my' ? 'my-MM' : 'en-GB';

        return new Intl.DateTimeFormat(locale, {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        }).format(parseDate(value));
    }

    function formatDateTime(value) {
        if (!value) {
            return labels.notRecorded;
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return String(value);
        }

        return new Intl.DateTimeFormat(config.locale === 'my' ? 'my-MM' : 'en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(date);
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
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

    function interpolate(template, values) {
        return Object.entries(values).reduce(
            (output, [key, value]) => output.replaceAll(`:${key}`, String(value)),
            template,
        );
    }

    function filteredRecords() {
        const term = elements.search.value.trim().toLowerCase();

        return state.records.filter((record) => {
            const status = effectiveStatus(record);
            const searchable = [
                record.id,
                record.group,
                record.location,
                status,
                translatedStatus(status),
            ].join(' ').toLowerCase();

            if (term && !searchable.includes(term)) {
                return false;
            }

            if (state.group !== 'all' && record.group !== state.group) {
                return false;
            }

            if (elements.status.value !== 'all' && status !== elements.status.value) {
                return false;
            }

            const remaining = daysUntil(record.expires);
            const expiryValue = elements.expiry.value;

            if (expiryValue === 'expired' && remaining >= 0) {
                return false;
            }

            if (expiryValue !== 'all' && expiryValue !== 'expired') {
                const windowDays = Number(expiryValue);

                if (remaining < 0 || remaining > windowDays) {
                    return false;
                }
            }

            return true;
        });
    }

    function rowActions(record) {
        const status = effectiveStatus(record);
        const actions = [
            `<button type="button" data-unit-action="view" data-unit-id="${escapeHtml(record.id)}"><i class="la la-eye"></i>${escapeHtml(labels.view)}</button>`,
            `<button type="button" data-unit-action="edit" data-unit-id="${escapeHtml(record.id)}"><i class="la la-pen"></i>${escapeHtml(labels.edit)}</button>`,
        ];

        if (status === 'Available' || status === 'Expiring') {
            actions.push(`<button type="button" data-unit-action="reserve" data-unit-id="${escapeHtml(record.id)}"><i class="la la-bookmark"></i>${escapeHtml(labels.reserve)}</button>`);
        }

        if (record.status === 'Reserved') {
            actions.push(`<button type="button" data-unit-action="release" data-unit-id="${escapeHtml(record.id)}"><i class="la la-bookmark-o"></i>${escapeHtml(labels.release)}</button>`);
        }

        if (!['Used', 'Discarded', 'Expired'].includes(status)) {
            actions.push('<span class="bc-row-menu-divider"></span>');
            actions.push(`<button type="button" data-unit-action="used" data-unit-id="${escapeHtml(record.id)}"><i class="la la-check-circle"></i>${escapeHtml(labels.markUsed)}</button>`);
            actions.push(`<button class="bc-row-menu-danger" type="button" data-unit-action="discard" data-unit-id="${escapeHtml(record.id)}"><i class="la la-ban"></i>${escapeHtml(labels.discard)}</button>`);
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
            elements.body.innerHTML = pageRecords.map((record) => {
                const status = effectiveStatus(record);

                return `
                    <tr>
                        <td><strong>${escapeHtml(record.id)}</strong></td>
                        <td><span class="bc-group-badge">${escapeHtml(record.group)}</span></td>
                        <td>${escapeHtml(formatDate(record.collected))}</td>
                        <td>${escapeHtml(formatDate(record.expires))}</td>
                        <td>${escapeHtml(record.location)}</td>
                        <td><span class="bc-status bc-status-${statusSlug(status)}">${escapeHtml(translatedStatus(status))}</span></td>
                        <td class="text-end">
                            <div class="bc-row-menu-wrap">
                                <button class="bc-row-action" type="button" data-unit-menu="${escapeHtml(record.id)}"
                                        aria-expanded="false"
                                        aria-label="${escapeHtml(interpolate(labels.actionsFor, { unit: record.id }))}">
                                    <i class="la la-ellipsis-h"></i>
                                </button>
                                <div class="bc-row-menu" hidden>${rowActions(record)}</div>
                            </div>
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
        elements.previous.disabled = state.page === 1;
        elements.next.disabled = state.page === totalPages;
    }

    function renderSummary() {
        const current = contributionFor(state.records);
        const values = {
            available: config.summary.available + current.available - originalContribution.available,
            reserved: config.summary.reserved + current.reserved - originalContribution.reserved,
            expiring: config.summary.expiring + current.expiring - originalContribution.expiring,
        };

        for (const [key, value] of Object.entries(values)) {
            const metric = document.querySelector(`[data-inventory-metric="${key}"] strong`);

            if (metric) {
                metric.textContent = String(Math.max(0, value));
            }
        }

        for (const card of document.querySelectorAll('[data-blood-group]')) {
            const group = card.dataset.bloodGroup;
            const base = config.groupTotals[group] || 0;
            const total = Math.max(0, base + (current.groups[group] || 0) - (originalContribution.groups[group] || 0));
            const target = config.groupTargets?.[group] || 50;
            const percent = Math.min(100, Math.round((total / target) * 100));
            const tone = percent < 30 ? 'critical' : (percent < 50 ? 'low' : 'healthy');

            card.classList.remove('bc-stock-healthy', 'bc-stock-low', 'bc-stock-critical');
            card.classList.add(`bc-stock-${tone}`);
            card.querySelector('.bc-blood-units').innerHTML = `${total} <small>${escapeHtml(labels.units)}</small>`;
            card.querySelector('.bc-blood-card-top span').textContent = labels[tone];
            card.querySelector('.bc-stock-bar span').style.width = `${percent}%`;
        }
    }

    function render() {
        renderRows();
        renderSummary();
    }

    function closeMenus(except = null) {
        for (const menu of root.querySelectorAll('.bc-row-menu')) {
            if (menu !== except) {
                if (window.BloodCareUI?.closeRowMenu) {
                    window.BloodCareUI.closeRowMenu(menu);
                } else {
                    menu.hidden = true;
                    menu.previousElementSibling?.setAttribute('aria-expanded', 'false');
                }
            }
        }
    }

    function openModal(modal) {
        if (!modal) {
            return;
        }

        closeMenus();
        modal.hidden = false;
        document.body.classList.add('bc-modal-open');
        window.setTimeout(() => {
            modal.querySelector('input:not([readonly]), select, button')?.focus();
        }, 0);
    }

    function closeModal(modal) {
        if (!modal) {
            return;
        }

        modal.hidden = true;

        if (![...document.querySelectorAll('.bc-modal')].some((item) => !item.hidden)) {
            document.body.classList.remove('bc-modal-open');
        }
    }

    function closeAllModals() {
        document.querySelectorAll('.bc-modal').forEach(closeModal);
    }

    function nextUnitId() {
        const highest = state.records.reduce((maximum, record) => {
            const number = Number(record.id.replace(/\D/g, ''));
            return Number.isFinite(number) ? Math.max(maximum, number) : maximum;
        }, 0);

        return `BU-${String(highest + 1).padStart(6, '0')}`;
    }

    function dateAfter(value, days) {
        const date = parseDate(value);
        date.setDate(date.getDate() + days);
        return date.toISOString().slice(0, 10);
    }

    function refreshExistingOptions(selectedId = null) {
        const records = [...state.records].sort((a, b) => b.id.localeCompare(a.id));
        elements.existingUnit.innerHTML = records.map((record) => (
            `<option value="${escapeHtml(record.id)}">${escapeHtml(record.id)} · ${escapeHtml(record.group)} · ${escapeHtml(translatedStatus(effectiveStatus(record)))}</option>`
        )).join('');

        if (selectedId && state.records.some((record) => record.id === selectedId)) {
            elements.existingUnit.value = selectedId;
        }
    }

    function fillForm(record) {
        if (!record) {
            return;
        }

        elements.unitId.value = record.id;
        elements.group.value = record.group;
        elements.collected.value = record.collected;
        elements.expires.value = record.expires;
        elements.location.value = record.location;
        elements.unitStatus.value = ['Available', 'Reserved', 'Used', 'Discarded', 'Quarantined'].includes(record.status)
            ? record.status
            : 'Available';
        elements.note.value = record.note || '';

        for (const select of [elements.group, elements.location, elements.unitStatus]) {
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function setMode(mode, selectedId = null) {
        state.mode = mode;
        state.editingId = mode === 'update'
            ? (selectedId || elements.existingUnit.value || state.records[0]?.id)
            : null;

        document.querySelectorAll('[data-adjustment-mode]').forEach((button) => {
            button.classList.toggle('active', button.dataset.adjustmentMode === mode);
        });

        elements.existingField.hidden = mode !== 'update';
        elements.unitId.readOnly = mode === 'update';
        elements.group.disabled = mode === 'update';

        if (mode === 'add') {
            elements.form.reset();
            elements.unitId.value = nextUnitId();
            elements.collected.value = config.today;
            elements.expires.value = dateAfter(config.today, 42);
            elements.unitStatus.value = 'Available';
            elements.formError.hidden = true;
        } else {
            refreshExistingOptions(state.editingId);
            elements.existingUnit.value = state.editingId;
            fillForm(state.records.find((record) => record.id === state.editingId));
        }
    }

    function openEditor(mode = 'add', id = null) {
        setMode(mode, id);
        openModal(elements.editor);
    }

    function detailField(label, value, extraClass = '') {
        return `<div class="${escapeHtml(extraClass)}"><small>${escapeHtml(label)}</small><strong>${escapeHtml(value || labels.notRecorded)}</strong></div>`;
    }

    function testTone(tone) {
        return ['negative', 'reactive', 'not_required', 'not_tested', 'confirmed'].includes(tone)
            ? tone.replace('_', '-')
            : 'neutral';
    }

    function renderTestGroup(title, tests) {
        return `
            <div class="bc-unit-test-group">
                <h4>${escapeHtml(title)}</h4>
                <div class="bc-unit-test-grid">
                    ${(tests || []).map((test) => `
                        <div class="bc-unit-test-card is-${testTone(test.tone)}">
                            <span>${escapeHtml(test.label)}</span>
                            <strong>${escapeHtml(test.resultLabel || labels.notRecorded)}</strong>
                        </div>
                    `).join('')}
                </div>
            </div>`;
    }

    function renderLaboratory(details) {
        const lab = details.laboratory;

        if (!lab) {
            const message = details.laboratoryPending ? labels.labPending : labels.labUnavailable;
            const tone = details.laboratoryPending ? 'warning' : 'neutral';
            return `<div class="bc-unit-detail-empty is-${tone}"><i class="la la-flask"></i><p>${escapeHtml(message)}</p></div>`;
        }

        const metadata = [
            [labels.labReference, lab.reference],
            [labels.releaseDecision, lab.releaseLabel],
            [labels.testedAt, formatDateTime(lab.testedAt)],
            [labels.releasedAt, formatDateTime(lab.releasedAt)],
            [labels.testedBy, lab.testedBy || labels.notRecorded],
            [labels.releasedBy, lab.releasedBy || labels.notRecorded],
        ];

        return `
            ${lab.inheritedFromDonation ? `<div class="bc-unit-inherited-note"><i class="la la-link"></i><span>${escapeHtml(labels.inheritedLab)}</span></div>` : ''}
            <div class="bc-unit-detail-grid bc-unit-lab-meta">
                ${metadata.map(([label, value]) => detailField(label, value)).join('')}
            </div>
            ${renderTestGroup(labels.mandatoryTti, lab.mandatoryTests)}
            ${renderTestGroup(labels.immunohematology, lab.immunohematology)}
            ${renderTestGroup(labels.regionalTti, lab.regionalTests)}
            <div class="bc-unit-detail-note">
                <small>${escapeHtml(labels.labNotes)}</small>
                <p>${escapeHtml(lab.notes || labels.notRecorded)}</p>
            </div>`;
    }

    function renderLineageItem(item, role) {
        const modifiers = (item.modifiers || []).join(' · ') || labels.noModifiers;
        const content = `
            <span class="bc-unit-lineage-role">${escapeHtml(role)}</span>
            <strong>${escapeHtml(item.id)}</strong>
            <span>${escapeHtml(item.component)} · ${escapeHtml(item.statusLabel)}</span>
            <small>${escapeHtml(item.expires ? formatDate(item.expires) : labels.notRecorded)} · ${escapeHtml(modifiers)}</small>`;

        return item.traceUrl
            ? `<a class="bc-unit-lineage-item" href="${escapeHtml(item.traceUrl)}" target="_blank" rel="noopener">${content}<i class="la la-external-link-alt"></i></a>`
            : `<div class="bc-unit-lineage-item">${content}</div>`;
    }

    function renderLineage(details) {
        const ancestors = details.lineage?.ancestors || [];
        const descendants = details.lineage?.descendants || [];
        const current = details.unit;
        const modifiers = (current.modifiers || []).join(' · ') || labels.noModifiers;

        return `
            <div class="bc-unit-lineage-block">
                <h4>${escapeHtml(labels.sourceUnits)}</h4>
                ${ancestors.length
                    ? `<div class="bc-unit-lineage-list">${ancestors.map((item) => renderLineageItem(item, labels.sourceUnits)).join('')}</div>`
                    : `<p class="bc-unit-lineage-empty">${escapeHtml(labels.noSourceUnits)}</p>`}
            </div>
            <div class="bc-unit-lineage-current">
                <span>${escapeHtml(labels.currentUnit)}</span>
                <strong>${escapeHtml(current.id)}</strong>
                <p>${escapeHtml(current.component)} · ${escapeHtml(current.statusLabel)}</p>
                <small>${escapeHtml(modifiers)}</small>
            </div>
            <div class="bc-unit-lineage-block">
                <h4>${escapeHtml(labels.derivedUnits)}</h4>
                ${descendants.length
                    ? `<div class="bc-unit-lineage-list">${descendants.map((item) => renderLineageItem(item, labels.derivedUnits)).join('')}</div>`
                    : `<p class="bc-unit-lineage-empty">${escapeHtml(labels.noDerivedUnits)}</p>`}
            </div>`;
    }

    function renderTraceHistory(history) {
        const safeTone = (tone) => ['done', 'warning', 'danger', 'neutral'].includes(tone) ? tone : 'neutral';

        return `<div class="bc-unit-timeline">
            ${(history || []).map((event) => `
                <div class="bc-unit-timeline-event is-${safeTone(event.tone)}">
                    <span class="bc-unit-timeline-dot"><i class="la la-check"></i></span>
                    <div>
                        <small>${escapeHtml(formatDateTime(event.date))}</small>
                        <strong>${escapeHtml(event.label)}</strong>
                        <p>${escapeHtml(event.detail)}</p>
                    </div>
                </div>
            `).join('')}
        </div>`;
    }

    function renderHaemovigilance(reports) {
        if (!reports || reports.length === 0) {
            return `<div class="bc-unit-detail-empty is-neutral"><i class="la la-shield-alt"></i><p>${escapeHtml(labels.noHaemovigilance)}</p></div>`;
        }

        return `<div class="bc-unit-haemo-list">
            ${reports.map((report) => `<article class="bc-unit-haemo-case">
                <header><strong>${escapeHtml(report.reference)}</strong><span class="bc-status bc-haemo-status-${escapeHtml(report.status)}">${escapeHtml(report.statusLabel)}</span></header>
                <div class="bc-unit-detail-grid">
                    ${detailField(labels.haemoSeverity, report.severityLabel)}
                    ${detailField(labels.haemoSuspectedType, report.suspectedTypeLabel)}
                    ${detailField(labels.haemoFinalType, report.reactionTypeLabel)}
                    ${detailField(labels.haemoImputability, report.imputabilityLabel)}
                    ${detailField(labels.haemoOutcome, report.outcomeLabel)}
                    ${detailField(labels.haemoOccurredAt, formatDateTime(report.occurredAt))}
                    ${detailField(labels.haemoReviewedAt, formatDateTime(report.reviewedAt))}
                    ${detailField(labels.haemoClosedAt, formatDateTime(report.closedAt))}
                </div>
            </article>`).join('')}
        </div>`;
    }

    function renderDetailedRecord(details) {
        const unit = details.unit;
        const modifiers = (unit.modifiers || []).join(' · ') || labels.noModifiers;
        const overviewFields = [
            [document.querySelector('.bc-module-table th:nth-child(1)')?.textContent || 'Unit number', unit.id],
            [document.querySelector('.bc-module-table th:nth-child(2)')?.textContent || 'Blood group', unit.bloodGroup],
            [labels.component, unit.component],
            [document.querySelector('.bc-module-table th:nth-child(3)')?.textContent || 'Collected', unit.collected ? formatDate(unit.collected) : labels.notRecorded],
            [document.querySelector('.bc-module-table th:nth-child(4)')?.textContent || 'Expires', unit.expires ? formatDate(unit.expires) : labels.notRecorded],
            [document.querySelector('.bc-module-table th:nth-child(5)')?.textContent || 'Location', unit.location],
            [document.querySelector('.bc-module-table th:nth-child(6)')?.textContent || 'Status', unit.statusLabel],
            [labels.componentModifiers, modifiers],
            [labels.donationReference, unit.donationReference || labels.notRecorded],
            [labels.donationCentre, unit.donationCentre || labels.notRecorded],
        ];

        elements.detailsContent.innerHTML = `
            <section class="bc-unit-detail-section">
                <div class="bc-unit-detail-section-heading">
                    <span><i class="la la-box"></i></span>
                    <div><h3>${escapeHtml(labels.detailsOverview)}</h3></div>
                </div>
                <div class="bc-unit-detail-grid bc-unit-overview-grid">
                    ${overviewFields.map(([label, value]) => detailField(label, value)).join('')}
                </div>
                <div class="bc-unit-detail-note">
                    <small>${escapeHtml(document.querySelector('label[for="bc-unit-note"] span')?.textContent || 'Note')}</small>
                    <p>${escapeHtml(unit.note || labels.notRecorded)}</p>
                </div>
            </section>

            <section class="bc-unit-detail-section">
                <div class="bc-unit-detail-section-heading">
                    <span><i class="la la-flask"></i></span>
                    <div><h3>${escapeHtml(labels.detailsLaboratory)}</h3><p>${escapeHtml(labels.detailsLaboratoryHelp)}</p></div>
                </div>
                ${renderLaboratory(details)}
            </section>

            <section class="bc-unit-detail-section">
                <div class="bc-unit-detail-section-heading">
                    <span><i class="la la-sitemap"></i></span>
                    <div><h3>${escapeHtml(labels.detailsLineage)}</h3><p>${escapeHtml(labels.detailsLineageHelp)}</p></div>
                </div>
                ${renderLineage(details)}
            </section>

            <section class="bc-unit-detail-section">
                <div class="bc-unit-detail-section-heading">
                    <span><i class="la la-shield-alt"></i></span>
                    <div><h3>${escapeHtml(labels.detailsHaemovigilance)}</h3><p>${escapeHtml(labels.detailsHaemovigilanceHelp)}</p></div>
                </div>
                ${renderHaemovigilance(details.haemovigilance)}
            </section>

            <section class="bc-unit-detail-section">
                <div class="bc-unit-detail-section-heading">
                    <span><i class="la la-history"></i></span>
                    <div><h3>${escapeHtml(labels.detailsTraceHistory)}</h3><p>${escapeHtml(labels.detailsTraceHelp)}</p></div>
                </div>
                ${renderTraceHistory(details.traceHistory)}
            </section>

            <div class="bc-unit-trace-card">
                <span class="bc-unit-trace-qr" data-bc-unit-qr></span>
                <div>
                    <small>${escapeHtml(labels.traceQrTitle)}</small>
                    <strong>${escapeHtml(unit.id)}</strong>
                    <p>${escapeHtml(labels.traceQrHelp)}</p>
                    ${details.traceUrl ? `<a class="btn bc-btn-outline" href="${escapeHtml(details.traceUrl)}" target="_blank" rel="noopener">${escapeHtml(labels.openTrace)} <i class="la la-external-link-alt"></i></a>` : ''}
                </div>
            </div>`;

        renderQr(elements.detailsContent.querySelector('[data-bc-unit-qr]'), details.traceUrl || '');
    }

    async function showDetails(id) {
        const record = state.records.find((item) => item.id === id);

        if (!record) {
            return;
        }

        state.detailId = id;
        elements.detailsContent.innerHTML = `
            <div class="bc-unit-detail-loading"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span><strong>${escapeHtml(labels.loadingDetails)}</strong></div>`;
        openModal(elements.details);

        try {
            const details = await apiRequest(endpoint(config.detailsUrlTemplate, id), 'GET');
            if (state.detailId === id) {
                renderDetailedRecord(details);
            }
        } catch (error) {
            elements.detailsContent.innerHTML = `<div class="bc-unit-detail-empty is-danger"><i class="la la-exclamation-triangle"></i><p>${escapeHtml(labels.detailsLoadError)}</p></div>`;
            showUiMessage(error.message || labels.detailsLoadError, true);
        }
    }

    async function updateRecordStatus(id, status, message, auditAction) {
        const record = state.records.find((item) => item.id === id);
        if (!record) {
            return;
        }

        try {
            await apiRequest(endpoint(config.statusUrlTemplate, id), 'PATCH', { status });
            showToast(interpolate(message, { unit: id }));
            window.setTimeout(() => window.location.reload(), 350);
        } catch (error) {
            showUiMessage(error.message, true);
        }
    }

    let toastTimer = null;

    function showToast(message) {
        window.clearTimeout(toastTimer);
        elements.toast.querySelector('span').textContent = message;
        elements.toast.hidden = false;
        elements.toast.classList.add('show');
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

    [elements.status, elements.expiry].forEach((select) => {
        select.addEventListener('change', () => {
            state.page = 1;
            renderRows();
        });
    });

    elements.previous.addEventListener('click', () => {
        state.page = Math.max(1, state.page - 1);
        renderRows();
    });

    elements.next.addEventListener('click', () => {
        state.page += 1;
        renderRows();
    });

    document.querySelectorAll('[data-blood-group]').forEach((card) => {
        const activate = () => {
            state.group = state.group === card.dataset.bloodGroup ? 'all' : card.dataset.bloodGroup;
            state.page = 1;

            document.querySelectorAll('[data-blood-group]').forEach((item) => {
                const active = state.group === item.dataset.bloodGroup;
                item.classList.toggle('bc-blood-card-active', active);
                item.setAttribute('aria-pressed', String(active));
            });

            renderRows();
        };

        card.addEventListener('click', activate);
        card.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                activate();
            }
        });
    });

    elements.primaryAction.addEventListener('click', () => openEditor('add'));
    elements.dataSourceButton.addEventListener('click', () => openModal(elements.dataSource));

    document.addEventListener('click', async (event) => {
        const menuButton = event.target.closest('[data-unit-menu]');
        const actionButton = event.target.closest('[data-unit-action]');

        if (menuButton) {
            const menu = menuButton.nextElementSibling;
            const shouldOpen = menu.hidden;
            closeMenus(menu);

            if (shouldOpen && window.BloodCareUI?.openRowMenu) {
                window.BloodCareUI.openRowMenu(menuButton, menu);
            } else if (!shouldOpen && window.BloodCareUI?.closeRowMenu) {
                window.BloodCareUI.closeRowMenu(menu);
            } else {
                menu.hidden = !shouldOpen;
                menuButton.setAttribute('aria-expanded', String(shouldOpen));
            }
            return;
        }

        if (actionButton) {
            const id = actionButton.dataset.unitId;
            const action = actionButton.dataset.unitAction;
            closeMenus();

            if (action === 'view') {
                showDetails(id);
            } else if (action === 'edit') {
                openEditor('update', id);
            } else if (action === 'reserve') {
                updateRecordStatus(id, 'Reserved', labels.reservedMessage, 'Inventory unit reserved');
            } else if (action === 'release') {
                updateRecordStatus(id, 'Available', labels.releasedMessage, 'Inventory reservation released');
            } else if (action === 'used') {
                if (await confirmUi(interpolate(labels.confirmUsed, { unit: id }), labels.markUsed, true)) {
                    updateRecordStatus(id, 'Used', labels.usedMessage, 'Inventory unit used');
                }
            } else if (action === 'discard') {
                if (await confirmUi(interpolate(labels.confirmDiscard, { unit: id }), labels.discard, true)) {
                    updateRecordStatus(id, 'Discarded', labels.discardedMessage, 'Inventory unit discarded');
                }
            }

            return;
        }

        if (!event.target.closest('.bc-row-menu-wrap')) {
            closeMenus();
        }

        const closeButton = event.target.closest('[data-modal-close]');

        if (closeButton) {
            closeModal(closeButton.closest('.bc-modal'));
        }
    });

    document.querySelectorAll('[data-adjustment-mode]').forEach((button) => {
        button.addEventListener('click', () => setMode(button.dataset.adjustmentMode));
    });

    elements.existingUnit.addEventListener('change', () => {
        state.editingId = elements.existingUnit.value;
        fillForm(state.records.find((record) => record.id === state.editingId));
    });

    elements.form.addEventListener('submit', async (event) => {
        event.preventDefault();
        elements.formError.hidden = true;
        elements.unitId.setCustomValidity('');
        elements.expires.setCustomValidity('');

        const id = elements.unitId.value.trim().toUpperCase();
        if (state.mode === 'add' && state.records.some((record) => record.id === id)) {
            elements.unitId.setCustomValidity(labels.duplicateUnit);
            elements.formError.textContent = labels.duplicateUnit;
            elements.formError.hidden = false;
            elements.unitId.reportValidity();
            return;
        }
        if (parseDate(elements.expires.value) <= parseDate(elements.collected.value)) {
            elements.expires.setCustomValidity(labels.invalidDates);
            elements.formError.textContent = labels.invalidDates;
            elements.formError.hidden = false;
            elements.expires.reportValidity();
            return;
        }
        if (!elements.form.reportValidity()) {
            return;
        }

        const record = {
            id,
            group: elements.group.value,
            collected: elements.collected.value,
            expires: elements.expires.value,
            location: elements.location.value,
            status: elements.unitStatus.value,
            note: elements.note.value.trim(),
        };

        try {
            const isEditing = state.mode === 'update' && state.editingId;
            const url = isEditing
                ? endpoint(config.updateUrlTemplate, state.editingId)
                : config.storeUrl;
            await apiRequest(url, isEditing ? 'PUT' : 'POST', record);
            closeModal(elements.editor);
            showToast(interpolate(labels.saved, { unit: id }));
            window.setTimeout(() => window.location.reload(), 350);
        } catch (error) {
            elements.formError.textContent = error.message;
            elements.formError.hidden = false;
            elements.formError.focus();
        }
    });

    elements.detailsEdit.addEventListener('click', () => {
        const id = state.detailId;
        closeModal(elements.details);
        openEditor('update', id);
    });

    elements.reset?.addEventListener('click', () => {
        window.location.reload();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape'
            && !document.documentElement.classList.contains('bc-confirm-open')
            && !document.documentElement.classList.contains('bc-alert-open')) {
            closeMenus();
            const openModals = [...document.querySelectorAll('.bc-modal')].filter((modal) => !modal.hidden);
            closeModal(openModals.at(-1));
        }
    });

    initializeCustomSelects();
    refreshExistingOptions();
    render();

    const requestedUnit = new URLSearchParams(window.location.search).get('unit')?.trim().toUpperCase();
    if (requestedUnit && state.records.some((record) => record.id === requestedUnit)) {
        window.setTimeout(() => showDetails(requestedUnit), 0);
    }
})();
