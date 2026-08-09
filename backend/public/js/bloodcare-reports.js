(() => {
    'use strict';

    const root = document.querySelector('[data-bc-module="reports"]');
    const configNode = document.getElementById('bc-report-config');

    if (!root || !configNode) {
        return;
    }

    const config = JSON.parse(configNode.textContent);
    const labels = config.labels;
    const bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    const elements = {
        period: document.getElementById('bc-report-period'),
        group: document.getElementById('bc-report-group'),
        refresh: document.getElementById('bc-report-refresh'),
        exportButton: document.getElementById('bc-report-export-button'),
        dataSourceButton: document.getElementById('bc-report-data-source-button'),
        donationChart: document.getElementById('bc-report-donation-chart'),
        stockList: document.getElementById('bc-report-stock-list'),
        readinessDonut: document.getElementById('bc-report-readiness-donut'),
        readinessTotal: document.getElementById('bc-report-readiness-total'),
        readinessLegend: document.getElementById('bc-report-readiness-legend'),
        outcomeList: document.getElementById('bc-report-outcome-list'),
        centreBody: document.getElementById('bc-report-centre-body'),
        centreSearch: document.getElementById('bc-report-centre-search'),
        centreCount: document.getElementById('bc-report-centre-count'),
        insights: document.getElementById('bc-report-insights'),
        exportModal: document.getElementById('bc-report-export-modal'),
        dataSourceModal: document.getElementById('bc-report-data-source-modal'),
        toast: document.getElementById('bc-report-toast'),
    };
    const state = {
        period: 'month',
        group: 'all',
        centreSearch: '',
        datasets: { donors: null, donations: null, inventory: null, appointments: null, users: null, centres: null },
        report: null,
    };
    let lastFocusedElement = null;
    let toastTimer = null;

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

    function formatNumber(value) {
        return new Intl.NumberFormat(config.locale === 'my' ? 'my-MM' : 'en-US')
            .format(Math.max(0, Math.round(Number(value) || 0)));
    }

    function formatMonth(monthKey) {
        const date = new Date(`${monthKey}-01T00:00:00`);
        return new Intl.DateTimeFormat(config.locale === 'my' ? 'my-MM' : 'en-US', {
            month: 'short',
        }).format(date);
    }

    function formatDate(value) {
        const date = new Date(`${value}T00:00:00`);
        return new Intl.DateTimeFormat(config.locale === 'my' ? 'my-MM' : 'en-GB', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        }).format(date);
    }

    function normalizeGroup(value) {
        return bloodGroups.includes(String(value)) ? String(value) : '';
    }

    function monthKey(value) {
        return /^\d{4}-\d{2}/.test(String(value)) ? String(value).slice(0, 7) : '';
    }

    function visibleMonths() {
        const allMonths = Object.keys(config.baseline.donationTrend).sort();

        if (state.period === 'month') {
            return allMonths.slice(-1);
        }

        if (state.period === '90') {
            return allMonths.slice(-3);
        }

        return allMonths;
    }

    function recordMatchesGroup(record) {
        return state.group === 'all' || normalizeGroup(record.group) === state.group;
    }

    function donationContribution(records, months, group = 'all') {
        const result = Object.fromEntries(months.map((month) => [month, 0]));

        for (const record of records || []) {
            const key = monthKey(record.donationDate);

            if (!(key in result) || (group !== 'all' && normalizeGroup(record.group) !== group)) {
                continue;
            }

            result[key] += 1;
        }

        return result;
    }

    function currentDonationTrend(months) {
        const currentRecords = state.datasets.donations;
        const baselineGroupTotal = Number(config.baseline.donationTrend[months.at(-1)] || 1);
        const groupShare = state.group === 'all'
            ? 1
            : Number(config.baseline.donationsByGroup[state.group] || 0) / baselineGroupTotal;

        const trend = Object.fromEntries(months.map((month) => [
            month,
            Math.max(0, Math.round(Number(config.baseline.donationTrend[month] || 0) * groupShare)),
        ]));

        if (!currentRecords) {
            return trend;
        }

        const current = donationContribution(currentRecords, months, state.group);
        const sample = state.group === 'all'
            ? Object.fromEntries(months.map((month) => [
                month,
                Number(config.sampleContributions.donationsByMonth[month] || 0),
            ]))
            : Object.fromEntries(months.map((month) => [
                month,
                Number(config.sampleContributions.donationsMonthlyGroup[month]?.[state.group] || 0),
            ]));

        for (const month of months) {
            trend[month] = Math.max(0, trend[month] + current[month] - sample[month]);
        }

        return trend;
    }

    function donorSummary() {
        const selectedBaseline = state.group === 'all'
            ? Number(config.baseline.donorsTotal)
            : Number(config.baseline.donorsByGroup[state.group] || 0);
        const current = state.datasets.donors;

        if (!current) {
            return selectedBaseline;
        }

        const currentCount = current.filter(recordMatchesGroup).length;
        const sampleCount = state.group === 'all'
            ? Number(config.sampleContributions.donors.total)
            : Number(config.sampleContributions.donors.byGroup[state.group] || 0);

        return Math.max(0, selectedBaseline + currentCount - sampleCount);
    }

    function inventorySummary() {
        const selectedBaseline = state.group === 'all'
            ? Number(config.baseline.availableUnits)
            : Number(config.baseline.inventoryByGroup[state.group] || 0);
        const current = state.datasets.inventory;

        if (!current) {
            return selectedBaseline;
        }

        const usable = current.filter((record) => (
            record.status === 'Available'
            && (!record.expires || new Date(`${record.expires}T23:59:59`) >= new Date(`${config.today}T00:00:00`))
            && recordMatchesGroup(record)
        )).length;
        const sample = state.group === 'all'
            ? Number(config.sampleContributions.availableInventory.total)
            : Number(config.sampleContributions.availableInventory.byGroup[state.group] || 0);

        return Math.max(0, selectedBaseline + usable - sample);
    }

    function inventoryByGroup() {
        const result = { ...config.baseline.inventoryByGroup };
        const current = state.datasets.inventory;

        if (current) {
            const contributions = Object.fromEntries(bloodGroups.map((group) => [group, 0]));

            for (const record of current) {
                const group = normalizeGroup(record.group);
                const usable = record.status === 'Available'
                    && (!record.expires || new Date(`${record.expires}T23:59:59`) >= new Date(`${config.today}T00:00:00`));

                if (group && usable) {
                    contributions[group] += 1;
                }
            }

            for (const group of bloodGroups) {
                result[group] = Math.max(
                    0,
                    Number(result[group] || 0)
                    + contributions[group]
                    - Number(config.sampleContributions.availableInventory.byGroup[group] || 0),
                );
            }
        }

        return state.group === 'all'
            ? result
            : { [state.group]: result[state.group] };
    }

    function readinessSummary() {
        const result = { ...config.baseline.donorReadiness };
        const current = state.datasets.donors;

        if (state.group !== 'all') {
            const selectedTotal = Number(config.baseline.donorsByGroup[state.group] || 0);
            const overallTotal = Math.max(1, Number(config.baseline.donorsTotal));

            for (const status of ['Eligible', 'Deferred', 'Review']) {
                result[status] = Math.round(Number(result[status] || 0) * selectedTotal / overallTotal);
            }
        }

        if (!current) {
            return result;
        }

        const records = current.filter(recordMatchesGroup);
        const contribution = { Eligible: 0, Deferred: 0, Review: 0 };

        for (const record of records) {
            const status = ['Eligible', 'Deferred'].includes(record.eligibility)
                ? record.eligibility
                : 'Review';
            contribution[status] += 1;
        }

        if (state.group === 'all') {
            for (const status of Object.keys(result)) {
                result[status] = Math.max(
                    0,
                    Number(result[status])
                    + contribution[status]
                    - Number(config.sampleContributions.donors.readiness[status] || 0),
                );
            }
        }

        return result;
    }

    function appointmentOutcomes() {
        const periodFactor = state.period === 'month' ? 1 : state.period === '90' ? 3 : 6;
        const groupFactor = state.group === 'all'
            ? 1
            : Number(config.baseline.donorsByGroup[state.group] || 0)
                / Math.max(1, Number(config.baseline.donorsTotal));
        const result = Object.fromEntries(
            Object.entries(config.baseline.appointmentOutcomes).map(([status, value]) => [
                status,
                Math.max(0, Math.round(Number(value) * periodFactor * groupFactor)),
            ]),
        );
        const current = state.datasets.appointments;

        if (!current || state.period !== 'month' || state.group !== 'all') {
            return result;
        }

        const contribution = { Completed: 0, Confirmed: 0, Pending: 0, Cancelled: 0, 'No-show': 0 };

        for (const record of current) {
            if (record.status in contribution) {
                contribution[record.status] += 1;
            }
        }

        for (const status of Object.keys(result)) {
            result[status] = Math.max(
                0,
                result[status]
                + contribution[status]
                - Number(config.sampleContributions.appointments[status] || 0),
            );
        }

        return result;
    }

    function centreActivity() {
        const records = state.datasets.appointments;

        if (!records) {
            return config.baseline.centreActivity.map((centre) => ({ ...centre }));
        }

        const grouped = new Map();

        for (const record of records.filter(recordMatchesGroup)) {
            const centre = String(record.centre || '').trim();

            if (!centre) {
                continue;
            }

            const value = grouped.get(centre) || { name: centre, bookings: 0, attended: 0 };
            value.bookings += 1;

            if (['Checked in', 'Completed'].includes(record.status)) {
                value.attended += 1;
            }

            grouped.set(centre, value);
        }

        return [...grouped.values()].sort((left, right) => right.bookings - left.bookings);
    }

    function buildReport() {
        const months = visibleMonths();
        const donationTrend = currentDonationTrend(months);
        const appointments = appointmentOutcomes();
        const appointmentTotal = Object.values(appointments).reduce((sum, value) => sum + value, 0);
        const completionRate = appointmentTotal
            ? Math.round(Number(appointments.Completed || 0) / appointmentTotal * 100)
            : 0;

        return {
            months,
            donationTrend,
            metrics: {
                donors: donorSummary(),
                donations: Object.values(donationTrend).reduce((sum, value) => sum + value, 0),
                inventory: inventorySummary(),
                completion: completionRate,
            },
            stock: inventoryByGroup(),
            readiness: readinessSummary(),
            appointments,
            centres: centreActivity(),
        };
    }

    function renderMetrics() {
        for (const [key, value] of Object.entries(state.report.metrics)) {
            const output = root.querySelector(`[data-report-metric="${key}"]`);

            if (output) {
                output.textContent = key === 'completion' ? `${formatNumber(value)}%` : formatNumber(value);
            }
        }
    }

    function renderDonationChart() {
        const values = Object.values(state.report.donationTrend);
        const max = Math.max(1, ...values);

        elements.donationChart.innerHTML = Object.entries(state.report.donationTrend).map(([month, value]) => `
            <div class="bc-report-column">
                <span class="bc-report-column-value">${formatNumber(value)}</span>
                <span class="bc-report-column-track">
                    <span style="height:${Math.max(6, value / max * 100)}%"></span>
                </span>
                <strong>${escapeHtml(formatMonth(month))}</strong>
            </div>
        `).join('');
    }

    function renderStock() {
        elements.stockList.innerHTML = Object.entries(state.report.stock).map(([group, value]) => {
            const target = Number(config.baseline.inventoryTargets[group] || 1);
            const percent = Math.min(100, Math.round(value / target * 100));
            const tone = percent < 40 ? 'critical' : percent < 60 ? 'low' : 'healthy';

            return `
                <div class="bc-report-stock-row bc-report-stock-${tone}">
                    <strong>${escapeHtml(group)}</strong>
                    <span class="bc-report-progress"><span style="width:${percent}%"></span></span>
                    <span><b>${formatNumber(value)}</b> ${escapeHtml(labels.units)}
                        <small>${formatNumber(percent)}% ${escapeHtml(labels.target)}</small>
                    </span>
                </div>
            `;
        }).join('');
    }

    function renderReadiness() {
        const readiness = state.report.readiness;
        const total = Math.max(1, Object.values(readiness).reduce((sum, value) => sum + value, 0));
        const eligiblePercent = Number(readiness.Eligible || 0) / total * 100;
        const deferredPercent = Number(readiness.Deferred || 0) / total * 100;
        const reviewStart = eligiblePercent + deferredPercent;

        elements.readinessDonut.style.background = `conic-gradient(
            var(--bc-admin-green) 0 ${eligiblePercent}%,
            var(--bc-admin-blue) ${eligiblePercent}% ${reviewStart}%,
            var(--bc-admin-amber) ${reviewStart}% 100%
        )`;
        elements.readinessTotal.textContent = formatNumber(total);

        const names = {
            Eligible: labels.eligible,
            Deferred: labels.deferred,
            Review: labels.review,
        };
        elements.readinessLegend.innerHTML = ['Eligible', 'Deferred', 'Review'].map((status) => {
            const value = Number(readiness[status] || 0);
            const percent = Math.round(value / total * 100);

            return `
                <div class="bc-report-legend-${status.toLowerCase()}">
                    <span></span>
                    <p><strong>${escapeHtml(names[status])}</strong><small>${formatNumber(value)} · ${formatNumber(percent)}%</small></p>
                </div>
            `;
        }).join('');
    }

    function renderOutcomes() {
        const outcomes = state.report.appointments;
        const max = Math.max(1, ...Object.values(outcomes));
        const names = {
            Completed: labels.completed,
            Confirmed: labels.confirmed,
            Pending: labels.pending,
            Cancelled: labels.cancelled,
            'No-show': labels.noShow,
        };

        elements.outcomeList.innerHTML = Object.entries(outcomes).map(([status, value]) => `
            <div class="bc-report-outcome-row bc-report-outcome-${status.toLowerCase().replace(/[^a-z]+/g, '-')}">
                <span><strong>${escapeHtml(names[status] || status)}</strong><b>${formatNumber(value)}</b></span>
                <span class="bc-report-progress"><span style="width:${Math.max(2, value / max * 100)}%"></span></span>
            </div>
        `).join('');
    }

    function renderCentres() {
        const centres = state.report.centres;
        const term = state.centreSearch.trim().toLocaleLowerCase();
        const visibleCentres = term
            ? centres.filter((centre) => String(centre.name || '').toLocaleLowerCase().includes(term))
            : centres;

        if (elements.centreCount) {
            elements.centreCount.textContent = interpolate(labels.showingCentres, {
                count: formatNumber(visibleCentres.length),
                total: formatNumber(centres.length),
            });
        }

        if (!centres.length) {
            elements.centreBody.innerHTML = `
                <tr><td colspan="4"><div class="bc-report-empty">${escapeHtml(labels.noCentreData)}</div></td></tr>
            `;
            return;
        }

        if (!visibleCentres.length) {
            elements.centreBody.innerHTML = `
                <tr><td colspan="4"><div class="bc-report-empty">${escapeHtml(labels.noCentreSearchResults)}</div></td></tr>
            `;
            return;
        }

        elements.centreBody.innerHTML = visibleCentres.map((centre) => {
            const rate = centre.bookings ? Math.round(centre.attended / centre.bookings * 100) : 0;

            return `
                <tr>
                    <td><span class="bc-report-centre-name"><i class="la la-map-marker-alt"></i><strong>${escapeHtml(centre.name)}</strong></span></td>
                    <td>${formatNumber(centre.bookings)}</td>
                    <td>${formatNumber(centre.attended)}</td>
                    <td><span class="bc-report-rate"><span style="width:${rate}%"></span></span><strong>${formatNumber(rate)}%</strong></td>
                </tr>
            `;
        }).join('');
    }

    function renderInsights() {
        const stockEntries = Object.entries(state.report.stock);
        const criticalCount = stockEntries.filter(([group, value]) => (
            value / Math.max(1, Number(config.baseline.inventoryTargets[group] || 1)) < 0.4
        )).length;
        const reviewCount = Number(state.report.readiness.Review || 0);
        const screeningCount = state.datasets.donations
            ? state.datasets.donations.filter((record) => record.status === 'Screening').length
            : 12;
        const pendingCount = Number(state.report.appointments.Pending || 0);
        const insights = [
            {
                tone: criticalCount ? 'danger' : 'success',
                icon: criticalCount ? 'la-exclamation-triangle' : 'la-check-circle',
                text: criticalCount
                    ? interpolate(labels.criticalStock, { count: formatNumber(criticalCount) })
                    : labels.healthyStock,
            },
            {
                tone: reviewCount ? 'warning' : 'success',
                icon: 'la-user-clock',
                text: reviewCount
                    ? interpolate(labels.pendingReviews, { count: formatNumber(reviewCount) })
                    : labels.noAttention,
            },
            {
                tone: screeningCount ? 'warning' : 'success',
                icon: 'la-microscope',
                text: screeningCount
                    ? interpolate(labels.screeningQueue, { count: formatNumber(screeningCount) })
                    : labels.noAttention,
            },
            {
                tone: pendingCount ? 'info' : 'success',
                icon: 'la-calendar-day',
                text: pendingCount
                    ? interpolate(labels.appointmentQueue, { count: formatNumber(pendingCount) })
                    : labels.noAttention,
            },
        ];

        elements.insights.innerHTML = insights.map((insight) => `
            <div class="bc-report-insight bc-report-insight-${insight.tone}">
                <span><i class="la ${insight.icon}"></i></span>
                <p>${escapeHtml(insight.text)}</p>
            </div>
        `).join('');
    }

    function render() {
        state.report = buildReport();
        renderMetrics();
        renderDonationChart();
        renderStock();
        renderReadiness();
        renderOutcomes();
        renderCentres();
        renderInsights();
    }

    function initializeCustomSelects() {
        const widgets = [...root.querySelectorAll('[data-bc-report-select]')];
        const closeAll = (except = null) => {
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
        };

        for (const widget of widgets) {
            const select = widget.querySelector('select');
            const trigger = widget.querySelector('.bc-filter-dropdown-trigger');
            const menu = widget.querySelector('.bc-filter-dropdown-menu');
            const output = widget.querySelector('[data-bc-select-label]');
            const options = [...widget.querySelectorAll('[role="option"]')];

            if (!select || !trigger || !menu || !output) {
                continue;
            }

            const sync = () => {
                const selected = options.find((option) => option.dataset.value === select.value) || options[0];
                output.textContent = selected.querySelector('span')?.textContent.trim() || selected.textContent.trim();
                options.forEach((option) => option.setAttribute('aria-selected', String(option === selected)));
            };
            const close = () => {
                widget.classList.remove('open');
                trigger.setAttribute('aria-expanded', 'false');
                menu.hidden = true;
            };

            trigger.addEventListener('click', () => {
                const opening = menu.hidden;
                closeAll(widget);
                widget.classList.toggle('open', opening);
                trigger.setAttribute('aria-expanded', String(opening));
                menu.hidden = !opening;
            });

            for (const option of options) {
                option.addEventListener('click', () => {
                    select.value = option.dataset.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    sync();
                    close();
                    trigger.focus();
                });
            }

            trigger.addEventListener('keydown', (event) => {
                if (['ArrowDown', 'Enter', ' '].includes(event.key) && menu.hidden) {
                    event.preventDefault();
                    trigger.click();
                    options.find((option) => option.getAttribute('aria-selected') === 'true')?.focus();
                }
            });
            menu.addEventListener('keydown', (event) => {
                const index = options.indexOf(document.activeElement);
                if (event.key === 'Escape') {
                    close();
                    trigger.focus();
                } else if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    options[(index + 1 + options.length) % options.length].focus();
                } else if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    options[(index - 1 + options.length) % options.length].focus();
                }
            });
            select.addEventListener('change', sync);
            sync();
        }

        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-bc-report-select]')) {
                closeAll();
            }
        });
    }

    function initializeSectionNavigation() {
        const buttons = [...root.querySelectorAll('[data-bc-report-section-button]')];
        const panels = [...root.querySelectorAll('[data-bc-report-section-panel]')];

        if (!buttons.length || !panels.length) {
            return;
        }

        const activate = (key, moveFocus = false) => {
            const activeButton = buttons.find((button) => button.dataset.bcReportSectionButton === key);
            const activePanel = panels.find((panel) => panel.dataset.bcReportSectionPanel === key);

            if (!activeButton || !activePanel) {
                return;
            }

            for (const button of buttons) {
                const active = button === activeButton;
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-selected', String(active));
                button.tabIndex = active ? 0 : -1;
            }

            for (const panel of panels) {
                panel.hidden = panel !== activePanel;
            }

            if (moveFocus) {
                activeButton.focus();
            }
        };

        buttons.forEach((button, index) => {
            button.addEventListener('click', () => activate(button.dataset.bcReportSectionButton));
            button.addEventListener('keydown', (event) => {
                let nextIndex = null;

                if (event.key === 'ArrowRight') nextIndex = (index + 1) % buttons.length;
                if (event.key === 'ArrowLeft') nextIndex = (index - 1 + buttons.length) % buttons.length;
                if (event.key === 'Home') nextIndex = 0;
                if (event.key === 'End') nextIndex = buttons.length - 1;

                if (nextIndex !== null) {
                    event.preventDefault();
                    activate(buttons[nextIndex].dataset.bcReportSectionButton, true);
                }
            });
        });

        activate(buttons.find((button) => button.classList.contains('is-active'))?.dataset.bcReportSectionButton || 'summary');
    }

    function openModal(modal, trigger) {
        if (!modal) {
            return;
        }
        lastFocusedElement = trigger || document.activeElement;
        modal.hidden = false;
        document.body.classList.add('bc-modal-open');
        modal.querySelector('button:not([disabled])')?.focus();
    }

    function closeModal(modal) {
        if (!modal) {
            return;
        }
        modal.hidden = true;
        document.body.classList.remove('bc-modal-open');
        lastFocusedElement?.focus?.();
    }

    function showToast(message) {
        window.clearTimeout(toastTimer);
        elements.toast.querySelector('span').textContent = message;
        elements.toast.hidden = false;
        toastTimer = window.setTimeout(() => {
            elements.toast.hidden = true;
        }, 3200);
    }

    function csvCell(value) {
        return `"${String(value ?? '').replaceAll('"', '""')}"`;
    }

    function reportRows() {
        const rows = [
            [labels.summary, ''],
            [labels.metric, labels.value],
            [labels.metricDonors, state.report.metrics.donors],
            [labels.metricDonations, state.report.metrics.donations],
            [labels.metricInventory, state.report.metrics.inventory],
            [labels.metricCompletion, `${state.report.metrics.completion}%`],
            [],
            [labels.monthlyActivity, ''],
            [labels.month, labels.records],
            ...Object.entries(state.report.donationTrend).map(([month, value]) => [formatMonth(month), value]),
            [],
            [labels.stockSummary, ''],
            [labels.bloodGroup, labels.units, labels.target, labels.percentage],
            ...Object.entries(state.report.stock).map(([group, value]) => {
                const target = Number(config.baseline.inventoryTargets[group] || 1);
                return [group, value, target, `${Math.round(value / target * 100)}%`];
            }),
            [],
            [labels.readinessSummary, ''],
            [labels.status, labels.donors],
            ...Object.entries(state.report.readiness).map(([status, value]) => [
                { Eligible: labels.eligible, Deferred: labels.deferred, Review: labels.review }[status] || status,
                value,
            ]),
            [],
            [labels.appointmentSummary, ''],
            [labels.status, labels.records],
            ...Object.entries(state.report.appointments).map(([status, value]) => [
                {
                    Completed: labels.completed,
                    Confirmed: labels.confirmed,
                    Pending: labels.pending,
                    Cancelled: labels.cancelled,
                    'No-show': labels.noShow,
                }[status] || status,
                value,
            ]),
            [],
            [labels.centreSummary, ''],
            [labels.centre, labels.bookings, labels.attended, labels.attendanceRate],
            ...state.report.centres.map((centre) => [
                centre.name,
                centre.bookings,
                centre.attended,
                `${centre.bookings ? Math.round(centre.attended / centre.bookings * 100) : 0}%`,
            ]),
        ];

        return rows;
    }

    function downloadCsv() {
        const prefix = `\uFEFF${interpolate(labels.reportGenerated, { date: formatDate(config.today) })}\r\n`;
        const scope = interpolate(labels.reportScope, {
            period: labels.periods[state.period],
            group: state.group === 'all' ? labels.allGroups : state.group,
        });
        const csv = prefix + `${csvCell(scope)}\r\n\r\n`
            + reportRows().map((row) => row.map(csvCell).join(',')).join('\r\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `bloodcare-report-${config.today}.csv`;
        document.body.append(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
        closeModal(elements.exportModal);
        showToast(labels.exported);
    }

    function printReport() {
        const printWindow = window.open('', '_blank');

        if (!printWindow) {
            return;
        }

        printWindow.opener = null;
        const scope = interpolate(labels.reportScope, {
            period: labels.periods[state.period],
            group: state.group === 'all' ? labels.allGroups : state.group,
        });
        const sections = reportRows().map((row) => `
            <tr>${row.map((cell) => `<td>${escapeHtml(cell)}</td>`).join('')}</tr>
        `).join('');

        printWindow.document.write(`<!doctype html>
            <html lang="${escapeHtml(config.locale)}"><head><meta charset="utf-8">
            <title>BloodCare — ${escapeHtml(labels.summary)}</title>
            <style>
                body{font:16px/1.55 Arial,sans-serif;color:#202636;margin:38px}
                header{border-bottom:3px solid #d91f3a;margin-bottom:24px;padding-bottom:16px}
                h1{font-size:28px;margin:0;color:#b5112b}p{margin:5px 0;color:#5f6879}
                table{border-collapse:collapse;width:100%}td{border-bottom:1px solid #e3e6ec;padding:9px 10px}
                tr:has(td:first-child:not(:empty):last-child){font-weight:800;background:#fff0f2}
                @media print{body{margin:16mm}}
            </style></head><body>
            <header><h1>BloodCare — ${escapeHtml(labels.summary)}</h1>
            <p>${escapeHtml(scope)}</p>
            <p>${escapeHtml(interpolate(labels.reportGenerated, { date: formatDate(config.today) }))}</p></header>
            <table>${sections}</table>
            <script>window.addEventListener('load',()=>window.print());<\/script>
            </body></html>`);
        printWindow.document.close();
        closeModal(elements.exportModal);
    }

    elements.period.addEventListener('change', () => {
        state.period = elements.period.value;
        render();
    });
    elements.group.addEventListener('change', () => {
        state.group = elements.group.value;
        render();
    });
    elements.centreSearch?.addEventListener('input', () => {
        state.centreSearch = elements.centreSearch.value;
        renderCentres();
    });
    elements.refresh.addEventListener('click', () => {
        window.location.reload();
    });
    elements.exportButton.addEventListener('click', (event) => openModal(elements.exportModal, event.currentTarget));
    elements.dataSourceButton.addEventListener('click', (event) => openModal(elements.dataSourceModal, event.currentTarget));
    root.addEventListener('click', (event) => {
        const close = event.target.closest('[data-modal-close]');
        if (close) {
            closeModal(close.closest('.bc-modal'));
            return;
        }

        const output = event.target.closest('[data-report-output]');
        if (output?.dataset.reportOutput === 'csv') {
            downloadCsv();
        } else if (output?.dataset.reportOutput === 'print') {
            printReport();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            const modal = [...root.querySelectorAll('.bc-modal')].find((candidate) => !candidate.hidden);
            closeModal(modal);
        }
    });

    initializeCustomSelects();
    initializeSectionNavigation();
    render();
})();
