(() => {
    'use strict';

    const root = document.querySelector('[data-bc-module="users"]');
    const configNode = document.getElementById('bc-user-config');

    if (!root || !configNode) {
        return;
    }

    const config = JSON.parse(configNode.textContent);
    const labels = config.labels;
    const storageKey = 'bloodcare.users.interactive.v1';
    const historyStorageKey = 'bloodcare.history.interactive.v1';
    const auditSource = 'users-database';
    const pageSize = 5;
    const originalRecords = config.records.map(normalizeRecord);

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
            body: JSON.stringify(payload),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const validation = Object.values(data.errors || {}).flat().join(' ');
            throw new Error(validation || data.message || 'Unable to update the account.');
        }
        return data;
    }

    const elements = {
        body: document.getElementById('bc-user-table-body'),
        search: document.getElementById('bc-user-search'),
        roleFilter: document.getElementById('bc-user-role'),
        statusFilter: document.getElementById('bc-user-status'),
        resultCount: document.getElementById('bc-user-result-count'),
        page: document.getElementById('bc-user-page'),
        previous: document.getElementById('bc-user-prev'),
        next: document.getElementById('bc-user-next'),
        dataSourceButton: document.getElementById('bc-data-source-button'),
        details: document.getElementById('bc-user-details'),
        detailsContent: document.getElementById('bc-user-details-content'),
        roleModal: document.getElementById('bc-user-role-modal'),
        roleForm: document.getElementById('bc-user-role-form'),
        roleAccount: document.getElementById('bc-user-role-account'),
        roleTitle: document.getElementById('bc-user-role-title'),
        roleHelp: document.getElementById('bc-user-role-help'),
        roleSubmit: document.getElementById('bc-user-role-submit'),
        roleSubmitLabel: document.getElementById('bc-user-role-submit-label'),
        confirm: document.getElementById('bc-user-confirm'),
        confirmTitle: document.getElementById('bc-user-confirm-title'),
        confirmMessage: document.getElementById('bc-user-confirm-message'),
        confirmIcon: document.getElementById('bc-user-confirm-icon'),
        confirmSubmit: document.getElementById('bc-user-confirm-submit'),
        dataSource: document.getElementById('bc-user-data-source-modal'),
        reset: document.getElementById('bc-reset-users'),
        toast: document.getElementById('bc-user-toast'),
    };

    const state = {
        records: readRecords(),
        history: readHistory(),
        page: 1,
        roleId: null,
        roleMode: 'edit',
    };

    let lastFocusedElement = null;
    let pendingConfirmation = null;
    let toastTimer = null;
    let activeRowMenuTrigger = null;

    const rowMenuPortal = document.createElement('div');
    rowMenuPortal.className = 'bc-row-menu bc-row-menu-floating bc-row-menu-portal';
    rowMenuPortal.setAttribute('role', 'menu');
    rowMenuPortal.hidden = true;
    document.body.appendChild(rowMenuPortal);

    function normalizeRecord(record) {
        return {
            id: String(record.id || ''),
            name: String(record.name || ''),
            email: String(record.email || ''),
            role: ['User', 'System Staff', 'System Admin', 'Lab Staff', 'Lab Admin'].includes(record.role) ? record.role : 'User',
            status: ['Active', 'Pending', 'Rejected', 'Banned'].includes(record.status) ? record.status : 'Pending',
            phone: String(record.phone || ''),
            jobTitle: String(record.jobTitle || ''),
            workplace: String(record.workplace || ''),
            registrationNote: String(record.registrationNote || ''),
            approvedAt: record.approvedAt || null,
            joinedAt: record.joinedAt || config.today,
            lastLogin: record.lastLogin || null,
            current: Boolean(record.current || record.id === config.currentUserId),
        };
    }

    function readRecords() {
        return originalRecords.map((record) => ({ ...record }));
    }

    function readHistory() {
        return [];
    }

    function saveRecords() {
        // Laravel and MySQL are the source of truth.
    }

    function auditDateTime() {
        const now = new Date();
        const time = [now.getHours(), now.getMinutes(), now.getSeconds()]
            .map((value) => String(value).padStart(2, '0'))
            .join(':');
        return `${config.today}T${time}`;
    }

    function auditEvent(record, action, details) {
        // The backend writes the permanent activity log.
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

    function slug(value) {
        return String(value).toLowerCase().replace(/\s+/g, '-');
    }

    function roleLabel(role) {
        return ({
            'User': labels.userRole,
            'System Staff': labels.systemStaffRole,
            'System Admin': labels.systemAdminRole,
            'Lab Staff': labels.labStaffRole,
            'Lab Admin': labels.labAdminRole,
        })[role] || role;
    }

    function statusLabel(status) {
        return labels[slug(status)] || status;
    }

    function parseDate(value) {
        return value ? new Date(value.includes('T') ? value : `${value}T00:00:00`) : null;
    }

    function formatDate(value, includeTime = false) {
        const date = parseDate(value);

        if (!date) {
            return labels.never;
        }

        return new Intl.DateTimeFormat(config.locale === 'my' ? 'my-MM' : 'en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            ...(includeTime ? { hour: '2-digit', minute: '2-digit' } : {}),
        }).format(date);
    }

    function initializeCustomSelects() {
        const widgets = [...root.querySelectorAll('[data-bc-user-select]')];

        function closeAll(except = null) {
            for (const widget of widgets) {
                if (widget === except) {
                    continue;
                }

                widget.classList.remove('open');
                widget.querySelector('.bc-filter-dropdown-trigger')?.setAttribute('aria-expanded', 'false');
                const menu = widget.querySelector('.bc-filter-dropdown-menu');

                if (menu) {
                    menu.hidden = true;
                }
            }
        }

        for (const widget of widgets) {
            const select = widget.querySelector('select');
            const trigger = widget.querySelector('.bc-filter-dropdown-trigger');
            const menu = widget.querySelector('.bc-filter-dropdown-menu');
            const output = widget.querySelector('[data-bc-select-label]');
            const options = [...widget.querySelectorAll('[role="option"]')];

            if (!select || !trigger || !menu || !output) {
                continue;
            }

            function sync() {
                const selected = options.find((option) => option.dataset.value === select.value) || options[0];
                output.textContent = selected.querySelector('span')?.textContent.trim() || selected.textContent.trim();
                options.forEach((option) => option.setAttribute('aria-selected', String(option === selected)));
            }

            function close(restoreFocus = false) {
                widget.classList.remove('open');
                trigger.setAttribute('aria-expanded', 'false');
                menu.hidden = true;

                if (restoreFocus) {
                    trigger.focus();
                }
            }

            function open() {
                closeAll(widget);
                widget.classList.add('open');
                trigger.setAttribute('aria-expanded', 'true');
                menu.hidden = false;
                options.find((option) => option.getAttribute('aria-selected') === 'true')?.focus();
            }

            function choose(option) {
                select.value = option.dataset.value;
                sync();
                close(true);
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }

            trigger.addEventListener('click', () => (menu.hidden ? open() : close()));
            trigger.addEventListener('keydown', (event) => {
                if (!['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
                    return;
                }

                event.preventDefault();
                open();
            });

            options.forEach((option, index) => {
                option.addEventListener('click', () => choose(option));
                option.addEventListener('keydown', (event) => {
                    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                        event.preventDefault();
                        const direction = event.key === 'ArrowDown' ? 1 : -1;
                        options[(index + direction + options.length) % options.length].focus();
                    } else if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        choose(option);
                    } else if (event.key === 'Escape') {
                        event.preventDefault();
                        close(true);
                    }
                });
            });

            select.addEventListener('change', sync);
            sync();
        }

        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-bc-user-select]')) {
                closeAll();
            }
        });
    }

    function filteredRecords() {
        const term = elements.search.value.trim().toLowerCase();

        return state.records.filter((record) => {
            const searchable = [
                record.id,
                record.name,
                record.email,
                record.role,
                record.status,
                roleLabel(record.role),
                statusLabel(record.status),
            ].join(' ').toLowerCase();

            return (!term || searchable.includes(term))
                && (elements.roleFilter.value === 'all' || record.role === elements.roleFilter.value)
                && (elements.statusFilter.value === 'all' || record.status === elements.statusFilter.value);
        });
    }

    function rowActions(record) {
        const actions = [
            `<button type="button" data-user-action="view" data-user-id="${escapeHtml(record.id)}"><i class="la la-eye"></i>${escapeHtml(labels.view)}</button>`,
        ];

        if (!record.current) {
            if (record.status === 'Pending' || record.status === 'Rejected') {
                actions.push(`<button type="button" data-user-action="approve" data-user-id="${escapeHtml(record.id)}"><i class="la la-user-check"></i>${escapeHtml(labels.approve)}</button>`);
                if (record.status === 'Pending') {
                    actions.push(`<button class="bc-row-menu-danger" type="button" data-user-action="reject" data-user-id="${escapeHtml(record.id)}"><i class="la la-user-times"></i>${escapeHtml(labels.reject)}</button>`);
                }
            } else {
                actions.push(`<button type="button" data-user-action="role" data-user-id="${escapeHtml(record.id)}"><i class="la la-user-tag"></i>${escapeHtml(labels.manageRole)}</button>`);
            }
            actions.push('<span class="bc-row-menu-divider"></span>');

            if (record.status === 'Banned') {
                actions.push(`<button type="button" data-user-action="restore" data-user-id="${escapeHtml(record.id)}"><i class="la la-user-check"></i>${escapeHtml(labels.restore)}</button>`);
            } else {
                actions.push(`<button class="bc-row-menu-danger" type="button" data-user-action="ban" data-user-id="${escapeHtml(record.id)}"><i class="la la-user-slash"></i>${escapeHtml(labels.ban)}</button>`);
            }
        }

        return actions.join('');
    }

    function renderRows() {
        const records = filteredRecords();
        const totalPages = Math.max(1, Math.ceil(records.length / pageSize));
        state.page = Math.min(state.page, totalPages);
        const start = (state.page - 1) * pageSize;
        const pageRecords = records.slice(start, start + pageSize);

        if (pageRecords.length === 0) {
            elements.body.innerHTML = `
                <tr><td class="bc-empty-state" colspan="7">
                    <i class="la la-user-shield"></i><strong>${escapeHtml(labels.noResults)}</strong>
                </td></tr>`;
        } else {
            elements.body.innerHTML = pageRecords.map((record) => {
                const initial = Array.from(record.name.trim())[0]?.toUpperCase() || 'U';

                return `
                    <tr>
                        <td>
                            <span class="bc-donor-cell">
                                <span class="bc-donor-table-avatar">${escapeHtml(initial)}</span>
                                <span class="bc-user-name-cell">
                                    <strong>${escapeHtml(record.name)}</strong>
                                    ${record.current ? `<small>${escapeHtml(labels.currentSession)}</small>` : ''}
                                </span>
                            </span>
                        </td>
                        <td><span class="bc-user-email">${escapeHtml(record.email)}</span></td>
                        <td><span class="bc-user-role bc-user-role-${slug(record.role)}">${escapeHtml(roleLabel(record.role))}</span></td>
                        <td><span class="bc-status bc-status-${slug(record.status)}">${escapeHtml(statusLabel(record.status))}</span></td>
                        <td>${escapeHtml(formatDate(record.lastLogin, true))}</td>
                        <td>${escapeHtml(formatDate(record.joinedAt))}</td>
                        <td class="text-end">
                            <span class="bc-row-menu-wrap">
                                <button class="bc-row-action" type="button" data-user-menu="${escapeHtml(record.id)}"
                                        aria-haspopup="menu"
                                        aria-expanded="false"
                                        aria-label="${escapeHtml(interpolate(labels.openActions, { user: record.name }))}">
                                    <i class="la la-ellipsis-h"></i>
                                </button>
                            </span>
                        </td>
                    </tr>`;
            }).join('');
        }

        const from = records.length ? start + 1 : 0;
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
        const values = {
            total: state.records.length,
            privileged: state.records.filter(
                (record) => record.status === 'Active' && ['System Staff', 'System Admin', 'Lab Staff', 'Lab Admin'].includes(record.role),
            ).length,
            pending: state.records.filter((record) => record.status === 'Pending').length,
            banned: state.records.filter((record) => record.status === 'Banned').length,
        };

        for (const metric of root.querySelectorAll('[data-user-metric]')) {
            metric.querySelector('strong').textContent = new Intl.NumberFormat(
                config.locale === 'my' ? 'my-MM' : 'en-US',
            ).format(values[metric.dataset.userMetric] || 0);
        }
    }

    function render() {
        closeRowMenus();
        renderRows();
        renderMetrics();
    }

    function resetRowMenuPosition() {
        rowMenuPortal.style.removeProperty('top');
        rowMenuPortal.style.removeProperty('left');
        rowMenuPortal.style.removeProperty('visibility');
    }

    function closeRowMenus() {
        rowMenuPortal.hidden = true;
        rowMenuPortal.replaceChildren();
        resetRowMenuPosition();

        if (activeRowMenuTrigger) {
            activeRowMenuTrigger.setAttribute('aria-expanded', 'false');
            activeRowMenuTrigger = null;
        }
    }

    function openRowMenu(record, trigger) {
        if (!record || !trigger) {
            return;
        }

        const isSameOpenMenu = activeRowMenuTrigger === trigger && !rowMenuPortal.hidden;
        closeRowMenus();

        if (isSameOpenMenu) {
            return;
        }

        rowMenuPortal.innerHTML = rowActions(record);
        rowMenuPortal.hidden = false;
        rowMenuPortal.style.visibility = 'hidden';
        rowMenuPortal.style.top = '0px';
        rowMenuPortal.style.left = '0px';

        const triggerRect = trigger.getBoundingClientRect();
        const menuRect = rowMenuPortal.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        const margin = 12;
        const gap = 8;
        const spaceBelow = viewportHeight - triggerRect.bottom - margin;
        const spaceAbove = triggerRect.top - margin;
        const openBelow = spaceBelow >= menuRect.height + gap || spaceBelow >= spaceAbove;
        const desiredTop = openBelow
            ? triggerRect.bottom + gap
            : triggerRect.top - menuRect.height - gap;
        const top = Math.min(
            Math.max(margin, desiredTop),
            Math.max(margin, viewportHeight - menuRect.height - margin),
        );
        const desiredLeft = triggerRect.right - menuRect.width;
        const left = Math.min(
            Math.max(margin, desiredLeft),
            Math.max(margin, viewportWidth - menuRect.width - margin),
        );

        rowMenuPortal.style.top = `${Math.round(top)}px`;
        rowMenuPortal.style.left = `${Math.round(left)}px`;
        rowMenuPortal.style.visibility = 'visible';
        trigger.setAttribute('aria-expanded', 'true');
        activeRowMenuTrigger = trigger;

        rowMenuPortal.querySelector('button')?.focus({ preventScroll: true });
    }

    function openModal(modal, focusTarget = null) {
        if (!modal) {
            return;
        }

        lastFocusedElement = document.activeElement;
        modal.hidden = false;
        document.body.classList.add('bc-modal-open');
        window.requestAnimationFrame(() => {
            (focusTarget || modal.querySelector('button, input'))?.focus();
        });
    }

    function closeModal(modal, restoreFocus = true) {
        if (!modal || modal.hidden) {
            return;
        }

        modal.hidden = true;

        if (![elements.details, elements.roleModal, elements.confirm, elements.dataSource]
            .some((candidate) => candidate && !candidate.hidden)) {
            document.body.classList.remove('bc-modal-open');
        }

        if (restoreFocus && lastFocusedElement instanceof HTMLElement) {
            lastFocusedElement.focus();
        }
    }

    function closeAllModals() {
        const modal = [elements.details, elements.roleModal, elements.confirm, elements.dataSource]
            .find((candidate) => candidate && !candidate.hidden);

        if (modal) {
            closeModal(modal);
        }
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
            }, 200);
        }, 3400);
    }

    function openDetails(record) {
        const initial = Array.from(record.name.trim())[0]?.toUpperCase() || 'U';
        elements.detailsContent.innerHTML = `
            <div class="bc-user-detail-hero">
                <span class="bc-user-detail-avatar">${escapeHtml(initial)}</span>
                <span>
                    <strong>${escapeHtml(record.name)}</strong>
                    <small>${escapeHtml(record.email)}</small>
                    ${record.current ? `<em>${escapeHtml(labels.currentSession)}</em>` : ''}
                </span>
            </div>
            <dl class="bc-user-detail-grid">
                <div class="bc-user-detail-card">
                    <dt>${escapeHtml(labels.accountId)}</dt>
                    <dd>${escapeHtml(record.id)}</dd>
                </div>
                <div class="bc-user-detail-card">
                    <dt>${escapeHtml(labels.role)}</dt>
                    <dd><span class="bc-user-detail-pill bc-user-detail-role-${escapeHtml(slug(record.role))}">${escapeHtml(roleLabel(record.role))}</span></dd>
                </div>
                <div class="bc-user-detail-card">
                    <dt>${escapeHtml(labels.status)}</dt>
                    <dd><span class="bc-user-detail-pill bc-user-detail-status-${escapeHtml(slug(record.status))}">${escapeHtml(statusLabel(record.status))}</span></dd>
                </div>
                <div class="bc-user-detail-card">
                    <dt>${escapeHtml(labels.joined)}</dt>
                    <dd>${escapeHtml(formatDate(record.joinedAt))}</dd>
                </div>
                <div class="bc-user-detail-card">
                    <dt>${escapeHtml(labels.lastLogin)}</dt>
                    <dd>${escapeHtml(formatDate(record.lastLogin, true))}</dd>
                </div>
                <div class="bc-user-detail-card">
                    <dt>${escapeHtml(labels.email)}</dt>
                    <dd>${escapeHtml(record.email)}</dd>
                </div>
                <div class="bc-user-detail-card">
                    <dt>${escapeHtml(labels.phone)}</dt>
                    <dd>${escapeHtml(record.phone || '—')}</dd>
                </div>
                <div class="bc-user-detail-card">
                    <dt>${escapeHtml(labels.jobTitle)}</dt>
                    <dd>${escapeHtml(record.jobTitle || '—')}</dd>
                </div>
                <div class="bc-user-detail-card">
                    <dt>${escapeHtml(labels.workplace)}</dt>
                    <dd>${escapeHtml(record.workplace || '—')}</dd>
                </div>
                <div class="bc-user-detail-card">
                    <dt>${escapeHtml(labels.approvedAt)}</dt>
                    <dd>${escapeHtml(formatDate(record.approvedAt, true))}</dd>
                </div>
                <div class="bc-user-detail-card bc-user-detail-wide">
                    <dt>${escapeHtml(labels.registrationNote)}</dt>
                    <dd>${escapeHtml(record.registrationNote || '—')}</dd>
                </div>
            </dl>`;
        openModal(elements.details);
    }

    function openRoleEditor(record, mode = 'edit') {
        if (record.current) {
            showToast(labels.protectCurrent);
            return;
        }

        state.roleId = record.id;
        state.roleMode = mode;
        elements.roleAccount.textContent = `${record.name} · ${record.email}`;
        const approving = mode === 'approve';

        elements.roleTitle.textContent = approving ? labels.approveTitle : labels.roleTitle;
        elements.roleHelp.textContent = approving ? labels.approveRoleHelp : labels.roleHelp;
        elements.roleSubmitLabel.textContent = approving ? labels.approve : labels.saveRole;
        elements.roleSubmit.querySelector('i').className = approving ? 'la la-user-check' : 'la la-save';

        elements.roleForm.querySelectorAll('[data-role-choice]').forEach((choice) => {
            choice.hidden = approving && choice.dataset.roleChoice === 'User';
        });

        const requestedRole = approving && !['System Staff', 'System Admin', 'Lab Staff', 'Lab Admin'].includes(record.role)
            ? 'System Staff'
            : record.role;
        const selected = elements.roleForm.querySelector(`input[value="${CSS.escape(requestedRole)}"]`);

        if (selected) {
            selected.checked = true;
        }

        openModal(elements.roleModal, selected);
    }

    function activeAdminCount() {
        return state.records.filter(
            (record) => record.role === 'System Admin' && record.status === 'Active',
        ).length;
    }

    function requestConfirmation({
        title,
        message,
        confirmLabel,
        icon = 'la-check-circle',
        danger = false,
        onConfirm,
    }) {
        pendingConfirmation = onConfirm;
        elements.confirmTitle.textContent = title;
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

    async function updateApproval(record, approval, role = null) {
        if (record.current) {
            showToast(labels.protectCurrent);
            return;
        }

        try {
            await apiRequest(endpoint(config.approvalUrlTemplate, record.id), 'PATCH', {
                approval,
                ...(role ? { role } : {}),
            });
            const message = approval === 'Approved' && role
                ? interpolate(labels.approvedRoleMessage, { user: record.name, role: roleLabel(role) })
                : interpolate(approval === 'Approved' ? labels.approvedMessage : labels.rejectedMessage, {
                    user: record.name,
                });
            showToast(message);
            window.setTimeout(() => window.location.reload(), 350);
        } catch (error) {
            showToast(error.message);
        }
    }

    async function updateStatus(record, status) {
        if (record.current) {
            showToast(labels.protectCurrent);
            return;
        }
        if (status === 'Banned' && record.role === 'System Admin'
            && record.status === 'Active' && activeAdminCount() <= 1) {
            showToast(labels.protectLastAdmin);
            return;
        }

        try {
            await apiRequest(endpoint(config.statusUrlTemplate, record.id), 'PATCH', { status });
            showToast(interpolate(status === 'Banned' ? labels.bannedMessage : labels.restoredMessage, {
                user: record.name,
            }));
            window.setTimeout(() => window.location.reload(), 350);
        } catch (error) {
            showToast(error.message);
        }
    }

    function handleRowAction(action, id) {
        const record = state.records.find((candidate) => candidate.id === id);

        if (!record) {
            return;
        }

        closeRowMenus();

        if (action === 'view') {
            openDetails(record);
        } else if (action === 'role') {
            openRoleEditor(record);
        } else if (action === 'ban') {
            if (record.current) {
                showToast(labels.protectCurrent);
                return;
            }

            if (record.role === 'System Admin' && record.status === 'Active' && activeAdminCount() <= 1) {
                showToast(labels.protectLastAdmin);
                return;
            }

            requestConfirmation({
                title: labels.banTitle,
                message: interpolate(labels.confirmBan, { user: record.name }),
                confirmLabel: labels.ban,
                icon: 'la-user-slash',
                danger: true,
                onConfirm: () => updateStatus(record, 'Banned'),
            });
        } else if (action === 'restore') {
            requestConfirmation({
                title: labels.restoreTitle,
                message: interpolate(labels.confirmRestore, { user: record.name }),
                confirmLabel: labels.restore,
                icon: 'la-user-check',
                onConfirm: () => updateStatus(record, 'Active'),
            });
        } else if (action === 'approve') {
            openRoleEditor(record, 'approve');
        } else if (action === 'reject') {
            requestConfirmation({
                title: labels.rejectTitle,
                message: interpolate(labels.confirmReject, { user: record.name }),
                confirmLabel: labels.reject,
                icon: 'la-user-times',
                danger: true,
                onConfirm: () => updateApproval(record, 'Rejected'),
            });
        }
    }

    async function handleRoleSubmit(event) {
        event.preventDefault();
        const record = state.records.find((candidate) => candidate.id === state.roleId);
        const newRole = new FormData(elements.roleForm).get('role');
        if (!record || !['User', 'System Staff', 'System Admin', 'Lab Staff', 'Lab Admin'].includes(newRole)) {
            return;
        }
        if (record.current) {
            closeModal(elements.roleModal, false);
            showToast(labels.protectCurrent);
            return;
        }
        if (record.role === 'System Admin' && newRole !== 'System Admin'
            && record.status === 'Active' && activeAdminCount() <= 1) {
            closeModal(elements.roleModal, false);
            showToast(labels.protectLastAdmin);
            return;
        }

        try {
            if (state.roleMode === 'approve') {
                closeModal(elements.roleModal, false);
                await updateApproval(record, 'Approved', newRole);
            } else {
                await apiRequest(endpoint(config.roleUrlTemplate, record.id), 'PATCH', { role: newRole });
                closeModal(elements.roleModal, false);
                showToast(interpolate(labels.roleUpdated, { user: record.name, role: roleLabel(newRole) }));
                window.setTimeout(() => window.location.reload(), 350);
            }
        } catch (error) {
            closeModal(elements.roleModal, false);
            showToast(error.message);
        }
    }

    function resetUsers() {
        window.location.reload();
    }

    elements.search?.addEventListener('input', () => {
        state.page = 1;
        renderRows();
    });

    for (const select of [elements.roleFilter, elements.statusFilter]) {
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
        const pages = Math.max(1, Math.ceil(filteredRecords().length / pageSize));

        if (state.page < pages) {
            state.page += 1;
            renderRows();
        }
    });

    elements.dataSourceButton?.addEventListener('click', () => openModal(elements.dataSource));
    elements.roleForm?.addEventListener('submit', handleRoleSubmit);
    elements.confirmSubmit?.addEventListener('click', confirmPendingAction);
    elements.reset?.addEventListener('click', () => {
        closeModal(elements.dataSource, false);
        requestConfirmation({
            title: labels.resetTitle,
            message: labels.confirmReset,
            confirmLabel: labels.resetSample,
            icon: 'la-undo',
            danger: true,
            onConfirm: resetUsers,
        });
    });

    root.addEventListener('click', (event) => {
        const menuButton = event.target.closest('[data-user-menu]');

        if (!menuButton) {
            return;
        }

        const record = state.records.find((candidate) => candidate.id === menuButton.dataset.userMenu);
        openRowMenu(record, menuButton);
    });

    document.addEventListener('click', (event) => {
        const actionButton = event.target.closest('.bc-row-menu-portal [data-user-action]');

        if (actionButton) {
            const { userAction, userId } = actionButton.dataset;
            closeRowMenus();
            handleRowAction(userAction, userId);
            return;
        }

        if (!event.target.closest('[data-user-menu]') && !event.target.closest('.bc-row-menu-portal')) {
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

    window.addEventListener('resize', () => closeRowMenus());

    document.addEventListener('scroll', (event) => {
        const target = event.target;

        if (target instanceof Element && target.closest('.bc-row-menu')) {
            return;
        }

        closeRowMenus();
    }, true);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeRowMenus();
            pendingConfirmation = null;
            closeAllModals();
        }
    });

    initializeCustomSelects();
    render();
})();
