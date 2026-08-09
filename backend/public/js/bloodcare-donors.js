(() => {
    'use strict';

    const root = document.querySelector('[data-bc-module="donors"]');
    const configNode = document.getElementById('bc-donor-config');
    if (!root || !configNode) return;

    const config = JSON.parse(configNode.textContent);
    const labels = config.labels;
    const pageSize = 5;
    const records = config.records.map((record) => ({ ...record }));
    const elements = {
        body: document.getElementById('bc-donor-table-body'),
        search: document.getElementById('bc-donor-search'),
        group: document.getElementById('bc-donor-group'),
        eligibility: document.getElementById('bc-donor-eligibility'),
        status: document.getElementById('bc-donor-status'),
        resultCount: document.getElementById('bc-donor-result-count'),
        page: document.getElementById('bc-donor-page'),
        previous: document.getElementById('bc-donor-prev'),
        next: document.getElementById('bc-donor-next'),
        details: document.getElementById('bc-donor-details'),
        detailsContent: document.getElementById('bc-donor-details-content'),
        detailsEdit: document.getElementById('bc-donor-details-edit'),
        detailsFull: document.getElementById('bc-donor-details-full'),
        dataSourceButton: document.getElementById('bc-data-source-button'),
        dataSource: document.getElementById('bc-donor-data-source-modal'),
        registrationChoiceButton: document.getElementById('bc-donor-register-choice-button'),
        registrationChoice: document.getElementById('bc-donor-registration-choice'),
        toast: document.getElementById('bc-donor-toast'),
    };
    const state = { page: 1 };
    let lastFocusedElement = null;
    let toastTimer = null;

    const endpoint = (template, reference) => template.replace('__REFERENCE__', encodeURIComponent(reference));
    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;').replaceAll("'", '&#039;');
    const interpolate = (template, values) => Object.entries(values).reduce(
        (output, [key, value]) => output.replaceAll(`:${key}`, String(value)), template,
    );
    const statusSlug = (value) => String(value).toLowerCase().replace(/\s+/g, '-');

    function formatDate(value) {
        if (!value || ['now', 'review'].includes(value)) return labels.notRecorded;
        return new Intl.DateTimeFormat(config.locale === 'my' ? 'my-MM' : 'en-GB', {
            day: '2-digit', month: 'short', year: 'numeric',
        }).format(new Date(`${value}T12:00:00`));
    }

    function translatedStatus(value) {
        return labels[String(value).toLowerCase()] || value;
    }

    function nextEligibleText(record) {
        if (record.nextEligible === 'now' || record.eligibility === 'Eligible') return labels.eligibleNow;
        if (record.nextEligible === 'review' || record.eligibility === 'Review') return labels.pendingReview;
        return record.nextEligible
            ? interpolate(labels.deferredUntil, { date: formatDate(record.nextEligible) })
            : labels.notRecorded;
    }

    async function apiRequest(reference, changes) {
        const response = await fetch(endpoint(config.updateUrlTemplate, reference), {
            method: 'PUT',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
            },
            body: JSON.stringify(changes),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Unable to update this donor.');
        }
        return data;
    }

    function payload(record, changes = {}) {
        const updated = { ...record, ...changes };
        return {
            ...updated,
            userId: updated.userId || null,
            identityDocumentType: updated.identityDocumentType || (String(updated.identity).includes('/') ? 'nrc' : 'passport'),
            donationTypePreference: updated.donationTypePreference || 'whole_blood',
            deferralType: updated.deferralType || 'none',
            deferralReason: updated.deferralReason || null,
            deferralEndDate: updated.deferralEndDate || null,
            emergencyContact: updated.emergencyContact || updated.phone,
            recordInitialScreening: false,
        };
    }

    function filteredRecords() {
        const term = elements.search.value.trim().toLowerCase();
        return records.filter((record) => {
            const haystack = [record.id, record.name, record.group, record.phone, record.email,
                record.identity, record.userName, record.userEmail, record.eligibility, record.status]
                .join(' ').toLowerCase();
            return (!term || haystack.includes(term))
                && (elements.group.value === 'all' || record.group === elements.group.value)
                && (elements.eligibility.value === 'all' || record.eligibility === elements.eligibility.value)
                && (elements.status.value === 'all' || record.status === elements.status.value);
        });
    }

    function rowActions(record) {
        const showUrl = endpoint(config.showUrlTemplate, record.id);
        const editUrl = endpoint(config.editUrlTemplate, record.id);
        const actions = [
            `<button type="button" data-donor-action="preview" data-donor-id="${escapeHtml(record.id)}"><i class="la la-eye"></i>${escapeHtml(labels.preview)}</button>`,
            `<a class="bc-row-menu-link" href="${escapeHtml(showUrl)}"><i class="la la-folder-open"></i>${escapeHtml(labels.view)}</a>`,
            `<a class="bc-row-menu-link" href="${escapeHtml(editUrl)}"><i class="la la-pen"></i>${escapeHtml(labels.edit)}</a>`,
        ];
        if (record.eligibility !== 'Eligible') actions.push(`<button type="button" data-donor-action="eligible" data-donor-id="${escapeHtml(record.id)}"><i class="la la-check-circle"></i>${escapeHtml(labels.markEligible)}</button>`);
        if (record.eligibility !== 'Review') actions.push(`<button type="button" data-donor-action="review" data-donor-id="${escapeHtml(record.id)}"><i class="la la-clipboard-check"></i>${escapeHtml(labels.sendReview)}</button>`);
        if (record.eligibility !== 'Deferred') actions.push(`<a class="bc-row-menu-link" href="${escapeHtml(`${editUrl}?focus=deferral`)}"><i class="la la-calendar-times"></i>${escapeHtml(labels.defer)}</a>`);
        actions.push('<span class="bc-row-menu-divider"></span>');
        actions.push(record.status === 'Inactive'
            ? `<button type="button" data-donor-action="reactivate" data-donor-id="${escapeHtml(record.id)}"><i class="la la-user-check"></i>${escapeHtml(labels.reactivate)}</button>`
            : `<button class="bc-row-menu-danger" type="button" data-donor-action="deactivate" data-donor-id="${escapeHtml(record.id)}"><i class="la la-user-slash"></i>${escapeHtml(labels.deactivate)}</button>`);
        return actions.join('');
    }

    function renderRows() {
        const filtered = filteredRecords();
        const pages = Math.max(1, Math.ceil(filtered.length / pageSize));
        state.page = Math.min(state.page, pages);
        const start = (state.page - 1) * pageSize;
        const pageRecords = filtered.slice(start, start + pageSize);
        elements.body.innerHTML = pageRecords.length ? pageRecords.map((record) => `
            <tr>
                <td><strong>${escapeHtml(record.id)}</strong></td>
                <td><span class="bc-donor-cell"><span class="bc-donor-table-avatar">${escapeHtml(Array.from(record.name)[0]?.toUpperCase() || 'D')}</span><span>${escapeHtml(record.name)}</span></span></td>
                <td><span class="bc-group-badge">${escapeHtml(record.group)}</span></td>
                <td>${escapeHtml(record.lastDonation ? formatDate(record.lastDonation) : labels.notRecorded)}</td>
                <td>${escapeHtml(nextEligibleText(record))}</td>
                <td><span class="bc-status bc-status-${statusSlug(record.status)}">${escapeHtml(translatedStatus(record.status))}</span></td>
                <td class="text-end"><span class="bc-row-menu-wrap"><button class="bc-row-action" type="button" data-donor-menu="${escapeHtml(record.id)}" aria-expanded="false" aria-label="${escapeHtml(interpolate(labels.actionsFor, { donor: record.name }))}"><i class="la la-ellipsis-h"></i></button><span class="bc-row-menu" role="menu" hidden>${rowActions(record)}</span></span></td>
            </tr>`).join('') : `<tr><td class="bc-empty-state" colspan="7"><i class="la la-user-slash"></i><strong>${escapeHtml(labels.noResults)}</strong></td></tr>`;
        elements.resultCount.textContent = interpolate(labels.showingRange, {
            from: filtered.length ? start + 1 : 0,
            to: Math.min(start + pageSize, filtered.length),
            total: filtered.length,
        });
        elements.page.textContent = String(state.page);
        elements.previous.disabled = state.page <= 1;
        elements.next.disabled = state.page >= pages;
    }

    function detailItem(label, value) {
        const displayValue = value === null || value === undefined || value === '' ? labels.notRecorded : value;
        return `<div><small>${escapeHtml(label)}</small><strong>${escapeHtml(displayValue)}</strong></div>`;
    }

    function openPreview(record) {
        const latest = record.latestScreening;
        elements.detailsContent.innerHTML = `
            <div class="bc-donor-detail-hero"><span class="bc-donor-detail-avatar">${escapeHtml(Array.from(record.name)[0]?.toUpperCase() || 'D')}</span><div><h3>${escapeHtml(record.name)}</h3><p>${escapeHtml(record.id)}</p><span class="bc-status bc-status-${statusSlug(record.status)}">${escapeHtml(translatedStatus(record.status))}</span><span class="bc-status bc-status-${statusSlug(record.eligibility)}">${escapeHtml(translatedStatus(record.eligibility))}</span></div></div>
            <div class="bc-unit-detail-grid">
                ${detailItem(labels.bloodGroup, record.group)}
                ${detailItem(labels.totalDonations, record.totalDonations)}
                ${detailItem(labels.phone, record.phone)}
                ${detailItem(labels.email, record.email)}
                ${detailItem(labels.dateOfBirth, formatDate(record.dateOfBirth))}
                ${detailItem(labels.identity, record.identity)}
                ${detailItem(labels.nextEligible, nextEligibleText(record))}
                ${detailItem(labels.linkedUser, record.userName ? `${record.userName} · ${record.userEmail}` : labels.notRecorded)}
                ${detailItem(labels.donationPreference, String(record.donationTypePreference || 'whole_blood').replaceAll('_', ' '))}
                ${detailItem(labels.currentDeferral, record.deferralType === 'none' ? labels.deferralNone : `${record.deferralType}: ${record.deferralReason || ''}`)}
                ${detailItem(labels.latestScreening, latest ? `${latest.reference} · ${latest.date} · ${latest.outcome}` : labels.notRecorded)}
            </div>`;
        elements.detailsEdit.href = endpoint(config.editUrlTemplate, record.id);
        elements.detailsFull.href = endpoint(config.showUrlTemplate, record.id);
        openModal(elements.details, elements.detailsFull);
    }

    function openModal(modal, focus) {
        if (!modal) return;
        lastFocusedElement = document.activeElement;
        modal.hidden = false;
        document.body.classList.add('bc-modal-open');
        window.requestAnimationFrame(() => focus?.focus());
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.hidden = true;
        document.body.classList.remove('bc-modal-open');
        lastFocusedElement?.focus?.();
    }

    function closeMenus(except = null) {
        root.querySelectorAll('.bc-row-menu').forEach((menu) => {
            if (menu === except) return;
            window.BloodCareUI?.closeRowMenu ? window.BloodCareUI.closeRowMenu(menu) : (menu.hidden = true);
        });
    }

    function showToast(message) {
        clearTimeout(toastTimer);
        elements.toast.querySelector('span').textContent = message;
        elements.toast.hidden = false;
        requestAnimationFrame(() => elements.toast.classList.add('show'));
        toastTimer = setTimeout(() => window.location.reload(), 500);
    }

    async function updateRecord(record, changes, message) {
        try {
            await apiRequest(record.id, payload(record, changes));
            showToast(interpolate(message, { donor: record.name }));
        } catch (error) {
            window.BloodCareUI?.showNotice(error.message, { error: true });
        }
    }

    function initializeFilterSelects() {
        root.querySelectorAll('[data-bc-select]').forEach((widget) => {
            const select = widget.querySelector('select');
            const trigger = widget.querySelector('.bc-filter-dropdown-trigger');
            const menu = widget.querySelector('.bc-filter-dropdown-menu');
            const label = widget.querySelector('[data-bc-select-label]');
            if (!select || !trigger || !menu || !label) return;
            const options = [...menu.querySelectorAll('[role="option"]')];
            const sync = () => {
                const selected = options.find((option) => option.dataset.value === select.value) || options[0];
                label.textContent = selected.querySelector('span')?.textContent.trim() || selected.textContent.trim();
                options.forEach((option) => option.setAttribute('aria-selected', String(option === selected)));
            };
            trigger.addEventListener('click', () => {
                const opening = menu.hidden;
                root.querySelectorAll('.bc-filter-dropdown-menu').forEach((other) => { if (other !== menu) other.hidden = true; });
                menu.hidden = !opening;
                trigger.setAttribute('aria-expanded', String(opening));
            });
            options.forEach((option) => option.addEventListener('click', () => {
                select.value = option.dataset.value;
                menu.hidden = true;
                trigger.setAttribute('aria-expanded', 'false');
                sync();
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }));
            sync();
        });
    }

    for (const field of [elements.search, elements.group, elements.eligibility, elements.status]) {
        field?.addEventListener(field === elements.search ? 'input' : 'change', () => { state.page = 1; renderRows(); });
    }
    elements.previous?.addEventListener('click', () => { if (state.page > 1) { state.page -= 1; renderRows(); } });
    elements.next?.addEventListener('click', () => { if (state.page < Math.ceil(filteredRecords().length / pageSize)) { state.page += 1; renderRows(); } });
    elements.dataSourceButton?.addEventListener('click', () => openModal(elements.dataSource));
    elements.registrationChoiceButton?.addEventListener('click', () => {
        openModal(elements.registrationChoice, elements.registrationChoice?.querySelector('[data-bc-registration-choice]'));
    });

    root.addEventListener('click', async (event) => {
        const menuButton = event.target.closest('[data-donor-menu]');
        if (menuButton) {
            const menu = menuButton.closest('.bc-row-menu-wrap')?.querySelector('.bc-row-menu');
            const opening = menu?.hidden;
            closeMenus(menu);
            if (menu && opening) window.BloodCareUI?.openRowMenu ? window.BloodCareUI.openRowMenu(menuButton, menu) : (menu.hidden = false);
            return;
        }
        const action = event.target.closest('[data-donor-action]');
        if (!action) return;
        const record = records.find((candidate) => candidate.id === action.dataset.donorId);
        if (!record) return;
        closeMenus();
        if (action.dataset.donorAction === 'preview') openPreview(record);
        if (action.dataset.donorAction === 'eligible') await updateRecord(record, { eligibility: 'Eligible', nextEligible: 'now', deferralType: 'none', deferralReason: null, deferralEndDate: null, status: record.status === 'Pending' ? 'Active' : record.status }, labels.eligibleMessage);
        if (action.dataset.donorAction === 'review') await updateRecord(record, { eligibility: 'Review', nextEligible: 'review', deferralType: 'none', deferralReason: null, deferralEndDate: null, status: record.status === 'Inactive' ? 'Inactive' : 'Pending' }, labels.reviewMessage);
        if (action.dataset.donorAction === 'deactivate') {
            const confirmed = await window.BloodCareUI?.confirmAction({ message: interpolate(labels.confirmDeactivate, { donor: record.name }), confirmLabel: labels.deactivate, danger: true });
            if (confirmed) await updateRecord(record, { status: 'Inactive' }, labels.deactivatedMessage);
        }
        if (action.dataset.donorAction === 'reactivate') await updateRecord(record, { status: record.eligibility === 'Review' ? 'Pending' : 'Active' }, labels.reactivatedMessage);
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.bc-row-menu-wrap')) closeMenus();
        const closer = event.target.closest('[data-modal-close]');
        if (closer) closeModal(closer.closest('.bc-modal'));
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenus();
            const modal = [elements.details, elements.dataSource, elements.registrationChoice]
                .find((candidate) => candidate && !candidate.hidden);
            if (modal) closeModal(modal);
        }
    });

    initializeFilterSelects();
    renderRows();
})();
