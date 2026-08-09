(() => {
    'use strict';

    const ready = (callback) => {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    };

    ready(() => {
        const isMyanmar = document.documentElement.lang.toLowerCase().startsWith('my');
        const copy = isMyanmar
            ? {
                chooseDate: 'ရက်ရွေးရန်',
                calendar: 'ရက်စွဲရွေးချယ်ရန်',
                previousMonth: 'ယခင်လ',
                nextMonth: 'နောက်လ',
                today: 'ယနေ့',
                clear: 'ရှင်းရန်',
                close: 'ပိတ်ရန်',
                selected: 'ရွေးထားသောရက်',
                openOptions: 'ရွေးချယ်စရာများ ဖွင့်ရန်',
                month: 'လ ရွေးရန်',
                year: 'နှစ် ရွေးရန်',
            }
            : {
                chooseDate: 'Choose a date',
                calendar: 'Choose a date',
                previousMonth: 'Previous month',
                nextMonth: 'Next month',
                today: 'Today',
                clear: 'Clear',
                close: 'Close',
                selected: 'Selected date',
                openOptions: 'Open options',
                month: 'Choose month',
                year: 'Choose year',
            };

        initializeGlobalExperience(isMyanmar ? {
            loadingTitle: 'စာမျက်နှာ ပြင်ဆင်နေသည်',
            loadingText: 'ခဏလေး စောင့်ပါ…',
            validationTitle: 'လိုအပ်သော အချက်အလက် ဖြည့်ပါ',
            validationMessage: 'ဆက်မလုပ်မီ မဖြစ်မနေလိုအပ်သော အကွက်များကို ပြည့်စုံအောင် ဖြည့်ပါ။',
            fieldsLabel: 'စစ်ဆေးရန် အကွက်များ',
            noticeTitle: 'အသိပေးချက်',
            errorTitle: 'လုပ်ဆောင်ချက် မပြီးမြောက်နိုင်ပါ',
            confirmationTitle: 'လုပ်ဆောင်ချက် အတည်ပြုရန်',
            confirm: 'အတည်ပြုမည်',
            cancel: 'မလုပ်တော့ပါ',
            close: 'နားလည်ပါပြီ',
        } : {
            loadingTitle: 'Preparing the page',
            loadingText: 'Please wait a moment…',
            validationTitle: 'Complete the required information',
            validationMessage: 'Fill in the required fields before continuing.',
            fieldsLabel: 'Fields to check',
            noticeTitle: 'Notice',
            errorTitle: 'Unable to complete the action',
            confirmationTitle: 'Confirm action',
            confirm: 'Confirm',
            cancel: 'Cancel',
            close: 'Got it',
        });

        initializeDatePickers(copy, isMyanmar ? 'my-MM' : 'en-GB');
        initializeSelectControls(copy);
    });

    function initializeGlobalExperience(copy) {
        const root = document.documentElement;
        const activeTokens = new Set();
        const delayedTokens = new Map();
        const tokenWatchdogs = new Map();
        let invalidFields = new Set();
        let invalidFrame = 0;
        let navigationWatchdog = 0;

        const ensureLoader = () => {
            let loader = document.getElementById('bc-global-loader');

            if (!loader) {
                loader = document.createElement('div');
                loader.id = 'bc-global-loader';
                loader.className = 'bc-global-loader';
                loader.setAttribute('aria-hidden', 'true');
                loader.innerHTML = `
                    <div class="bc-loader-panel" role="status" aria-live="polite">
                        <span class="bc-loader-spinner" aria-hidden="true"></span>
                        <div class="bc-loader-copy">
                            <strong></strong>
                            <span></span>
                        </div>
                        <div class="bc-loader-skeleton" aria-hidden="true"><i></i><i></i><i></i></div>
                    </div>`;
                document.body.prepend(loader);
            }

            loader.querySelector('.bc-loader-copy strong').textContent = copy.loadingTitle;
            loader.querySelector('.bc-loader-copy span').textContent = copy.loadingText;
            return loader;
        };

        const ensureNavigationProgress = () => {
            let progress = document.getElementById('bc-navigation-progress');

            if (!progress) {
                progress = document.createElement('div');
                progress.id = 'bc-navigation-progress';
                progress.className = 'bc-navigation-progress';
                progress.setAttribute('aria-hidden', 'true');
                document.body.prepend(progress);
            }

            return progress;
        };

        const loader = ensureLoader();
        const navigationProgress = ensureNavigationProgress();

        const renderLoadingState = () => {
            const active = activeTokens.size > 0 || root.classList.contains('bc-page-loading');
            root.classList.toggle('bc-loading-active', active);
            loader.setAttribute('aria-hidden', String(!active));
        };

        const endLoading = (token) => {
            if (token) {
                activeTokens.delete(token);

                const timer = delayedTokens.get(token);
                if (timer) {
                    window.clearTimeout(timer);
                    delayedTokens.delete(token);
                }

                const watchdog = tokenWatchdogs.get(token);
                if (watchdog) {
                    window.clearTimeout(watchdog);
                    tokenWatchdogs.delete(token);
                }
            } else {
                activeTokens.clear();
                delayedTokens.forEach((timer) => window.clearTimeout(timer));
                delayedTokens.clear();
                tokenWatchdogs.forEach((timer) => window.clearTimeout(timer));
                tokenWatchdogs.clear();
            }

            renderLoadingState();
        };

        const beginLoading = (immediate = false, maximumDuration = 15000) => {
            const token = Symbol('bloodcare-loading');
            activeTokens.add(token);

            if (immediate) {
                renderLoadingState();
            } else {
                delayedTokens.set(token, window.setTimeout(() => {
                    delayedTokens.delete(token);
                    if (activeTokens.has(token)) {
                        renderLoadingState();
                    }
                }, 260));
            }

            tokenWatchdogs.set(token, window.setTimeout(() => endLoading(token), maximumDuration));

            return token;
        };

        const beginNavigation = () => {
            window.clearTimeout(navigationWatchdog);
            root.classList.add('bc-navigation-pending');
            navigationProgress.setAttribute('aria-hidden', 'false');
            navigationWatchdog = window.setTimeout(endNavigation, 5000);
        };

        const endNavigation = () => {
            window.clearTimeout(navigationWatchdog);
            navigationWatchdog = 0;
            root.classList.remove('bc-navigation-pending');
            navigationProgress.setAttribute('aria-hidden', 'true');
        };

        const finishInitialLoad = () => {
            root.classList.remove('bc-page-loading');
            endLoading();
            endNavigation();
        };

        if (document.readyState === 'complete') {
            finishInitialLoad();
        } else {
            window.addEventListener('load', finishInitialLoad, { once: true });
            document.addEventListener('DOMContentLoaded', () => {
                root.classList.remove('bc-page-loading');
                renderLoadingState();
            }, { once: true });
        }
        window.setTimeout(finishInitialLoad, 3500);

        const ensureAlert = () => {
            let alert = document.getElementById('bc-global-alert');

            if (!alert) {
                alert = document.createElement('div');
                alert.id = 'bc-global-alert';
                alert.className = 'bc-global-alert';
                alert.hidden = true;
                alert.innerHTML = `
                    <div class="bc-global-alert-backdrop" data-bc-alert-close></div>
                    <section class="bc-global-alert-panel" role="alertdialog" aria-modal="true" aria-labelledby="bc-global-alert-title" aria-describedby="bc-global-alert-message">
                        <span class="bc-global-alert-icon" aria-hidden="true">!</span>
                        <h2 id="bc-global-alert-title"></h2>
                        <p id="bc-global-alert-message"></p>
                        <div class="bc-global-alert-fields" hidden>
                            <strong></strong>
                            <ul></ul>
                        </div>
                        <button class="bc-global-alert-close" type="button" data-bc-alert-close></button>
                    </section>`;
                document.body.append(alert);
            }

            return alert;
        };

        const alert = ensureAlert();
        let alertFocusTarget = null;

        const ensureConfirm = () => {
            let confirm = document.getElementById('bc-global-confirm');

            if (!confirm) {
                confirm = document.createElement('div');
                confirm.id = 'bc-global-confirm';
                confirm.className = 'bc-global-confirm';
                confirm.hidden = true;
                confirm.innerHTML = `
                    <div class="bc-global-confirm-backdrop" data-bc-confirm-cancel></div>
                    <section class="bc-global-confirm-panel" role="alertdialog" aria-modal="true" aria-labelledby="bc-global-confirm-title" aria-describedby="bc-global-confirm-message">
                        <span class="bc-global-confirm-icon" aria-hidden="true">?</span>
                        <h2 id="bc-global-confirm-title"></h2>
                        <p id="bc-global-confirm-message"></p>
                        <div class="bc-global-confirm-actions">
                            <button class="bc-global-confirm-cancel" type="button" data-bc-confirm-cancel></button>
                            <button class="bc-global-confirm-submit" type="button" data-bc-confirm-submit></button>
                        </div>
                    </section>`;
                document.body.append(confirm);
            }

            return confirm;
        };

        const confirmDialog = ensureConfirm();
        let confirmFocusTarget = null;
        let confirmResolver = null;
        let activeRowMenu = null;

        const resetRowMenuPosition = (menu) => {
            if (!menu) {
                return;
            }

            menu.classList.remove('bc-row-menu-floating');
            menu.removeAttribute('data-bc-floating-row-menu');

            for (const property of ['position', 'top', 'right', 'bottom', 'left', 'width', 'maxHeight', 'visibility']) {
                menu.style.removeProperty(property);
            }
        };

        const closeRowMenu = (menu = activeRowMenu?.menu, restoreFocus = false) => {
            if (!menu) {
                return;
            }

            const trigger = activeRowMenu?.menu === menu
                ? activeRowMenu.trigger
                : menu.closest('.bc-row-menu-wrap')?.querySelector('.bc-row-action');

            menu.hidden = true;
            resetRowMenuPosition(menu);
            trigger?.setAttribute('aria-expanded', 'false');

            if (activeRowMenu?.menu === menu) {
                activeRowMenu.tablePanel?.classList.remove('bc-row-menu-open');
                activeRowMenu.tableViewport?.classList.remove('bc-row-menu-open');
                activeRowMenu = null;
            }

            if (restoreFocus) {
                trigger?.focus({ preventScroll: true });
            }
        };

        const openRowMenu = (trigger, menu) => {
            if (!trigger || !menu) {
                return false;
            }

            const sameMenuIsOpen = activeRowMenu?.menu === menu && !menu.hidden;

            if (activeRowMenu?.menu) {
                closeRowMenu(activeRowMenu.menu);
            }

            if (sameMenuIsOpen) {
                return false;
            }

            const margin = 12;
            const gap = 8;
            menu.classList.add('bc-row-menu-floating');
            menu.setAttribute('data-bc-floating-row-menu', 'true');
            menu.hidden = false;
            menu.style.setProperty('position', 'fixed', 'important');
            menu.style.setProperty('top', '0px', 'important');
            menu.style.setProperty('right', 'auto', 'important');
            menu.style.setProperty('bottom', 'auto', 'important');
            menu.style.setProperty('left', '0px', 'important');
            menu.style.setProperty('visibility', 'hidden');

            const triggerRect = trigger.getBoundingClientRect();
            const menuRect = menu.getBoundingClientRect();
            const viewportWidth = document.documentElement.clientWidth;
            const viewportHeight = document.documentElement.clientHeight;
            const spaceBelow = Math.max(0, viewportHeight - triggerRect.bottom - margin);
            const spaceAbove = Math.max(0, triggerRect.top - margin);
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

            menu.style.setProperty('top', `${Math.round(top)}px`, 'important');
            menu.style.setProperty('left', `${Math.round(left)}px`, 'important');
            menu.style.setProperty('visibility', 'visible');
            trigger.setAttribute('aria-expanded', 'true');
            const tablePanel = trigger.closest('.bc-module-table-panel');
            const tableViewport = trigger.closest('.table-responsive');
            tablePanel?.classList.add('bc-row-menu-open');
            tableViewport?.classList.add('bc-row-menu-open');
            activeRowMenu = { trigger, menu, tablePanel, tableViewport };

            return true;
        };

        const closeAlert = () => {
            if (alert.hidden) {
                return;
            }

            alert.hidden = true;
            root.classList.remove('bc-alert-open');
            const target = alertFocusTarget;
            alertFocusTarget = null;
            window.requestAnimationFrame(() => target?.focus({ preventScroll: false }));
        };

        alert.querySelectorAll('[data-bc-alert-close]').forEach((button) => {
            button.addEventListener('click', closeAlert);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !alert.hidden) {
                event.preventDefault();
                closeAlert();
            }
        });

        const fieldLabel = (field) => {
            const displayLabel = field.dataset.bcFieldLabel;
            const explicit = field.getAttribute('aria-label');
            const labelElement = field.labels?.[0] || field.closest('label');
            const conciseLabel = labelElement?.querySelector(':scope > span, :scope > legend')?.textContent || '';
            const label = labelElement?.textContent || '';
            const fallback = (field.name || field.id || '')
                .replace(/^bc-/, '')
                .replace(/[_-]+/g, ' ')
                .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
                .replace(/\bid\b/gi, 'ID')
                .replace(/\b\w/g, (character) => character.toUpperCase());

            return (displayLabel || explicit || conciseLabel || label || fallback)
                .replace(/\s*\*\s*$/, '')
                .replace(/\s+/g, ' ')
                .trim();
        };

        const showAlert = (title, message, fields = [], focusTarget = null) => {
            const fieldContainer = alert.querySelector('.bc-global-alert-fields');
            const fieldList = fieldContainer.querySelector('ul');
            const uniqueLabels = [...new Set(fields.map(fieldLabel).filter(Boolean))].slice(0, 6);

            alert.querySelector('#bc-global-alert-title').textContent = title || copy.validationTitle;
            alert.querySelector('#bc-global-alert-message').textContent = message || copy.validationMessage;
            fieldContainer.querySelector('strong').textContent = copy.fieldsLabel;
            fieldContainer.hidden = uniqueLabels.length === 0;
            fieldList.replaceChildren(...uniqueLabels.map((label) => {
                const item = document.createElement('li');
                item.textContent = label;
                return item;
            }));
            alert.querySelector('.bc-global-alert-close').textContent = copy.close;
            alertFocusTarget = focusTarget || fields[0] || document.activeElement;
            alert.hidden = false;
            root.classList.add('bc-alert-open');
            window.requestAnimationFrame(() => alert.querySelector('.bc-global-alert-close').focus());
        };

        const showValidationAlert = (fields = [], message = '', title = '') => {
            const first = fields[0] || null;
            fields.forEach((field) => {
                field.setAttribute('aria-invalid', 'true');
                field.classList.add('bc-field-invalid');
            });
            showAlert(title || copy.validationTitle, message || copy.validationMessage, fields, first);
        };

        const showNotice = (message, options = {}) => {
            showAlert(
                options.title || (options.error ? copy.errorTitle : copy.noticeTitle),
                message,
                [],
                options.focusTarget || document.activeElement,
            );
        };

        const closeConfirm = (accepted = false) => {
            if (confirmDialog.hidden) {
                return;
            }

            confirmDialog.hidden = true;
            root.classList.remove('bc-confirm-open');
            const resolver = confirmResolver;
            const target = confirmFocusTarget;
            confirmResolver = null;
            confirmFocusTarget = null;
            resolver?.(accepted);
            window.requestAnimationFrame(() => target?.focus({ preventScroll: false }));
        };

        confirmDialog.querySelectorAll('[data-bc-confirm-cancel]').forEach((button) => {
            button.addEventListener('click', () => closeConfirm(false));
        });
        confirmDialog.querySelector('[data-bc-confirm-submit]').addEventListener('click', () => closeConfirm(true));

        const confirmAction = (options = {}) => new Promise((resolve) => {
            if (confirmResolver) {
                closeConfirm(false);
            }

            const submit = confirmDialog.querySelector('[data-bc-confirm-submit]');
            const cancel = confirmDialog.querySelector('.bc-global-confirm-cancel');
            const icon = confirmDialog.querySelector('.bc-global-confirm-icon');
            const danger = Boolean(options.danger);

            confirmDialog.querySelector('#bc-global-confirm-title').textContent = options.title || copy.confirmationTitle;
            confirmDialog.querySelector('#bc-global-confirm-message').textContent = options.message || '';
            submit.textContent = options.confirmLabel || copy.confirm;
            cancel.textContent = options.cancelLabel || copy.cancel;
            submit.classList.toggle('is-danger', danger);
            icon.classList.toggle('is-danger', danger);
            confirmFocusTarget = options.focusTarget || document.activeElement;
            confirmResolver = resolve;
            confirmDialog.hidden = false;
            root.classList.add('bc-confirm-open');
            window.requestAnimationFrame(() => cancel.focus());
        });

        document.addEventListener('keydown', (event) => {
            if (confirmDialog.hidden) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                closeConfirm(false);
                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            const controls = [...confirmDialog.querySelectorAll('button:not(:disabled)')];
            const first = controls[0];
            const last = controls.at(-1);

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last?.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first?.focus();
            }
        });

        window.BloodCareUI = {
            beginLoading,
            endLoading,
            showAlert,
            showNotice,
            showValidationAlert,
            confirmAction,
            openRowMenu,
            closeRowMenu,
        };

        window.addEventListener('resize', () => closeRowMenu());
        document.addEventListener('scroll', (event) => {
            if (activeRowMenu?.menu?.contains(event.target)) {
                return;
            }

            closeRowMenu();
        }, true);

        document.addEventListener('invalid', (event) => {
            const field = event.target;

            if (!(field instanceof HTMLInputElement)
                && !(field instanceof HTMLSelectElement)
                && !(field instanceof HTMLTextAreaElement)) {
                return;
            }

            event.preventDefault();
            invalidFields.add(field);
            field.setAttribute('aria-invalid', 'true');
            field.classList.add('bc-field-invalid');
            window.cancelAnimationFrame(invalidFrame);
            invalidFrame = window.requestAnimationFrame(() => {
                const fields = [...invalidFields].filter((candidate) => !candidate.disabled);
                invalidFields = new Set();
                showValidationAlert(fields);
            });
        }, true);

        const clearInvalidState = (event) => {
            const field = event.target;
            if (field?.matches?.('input, select, textarea') && field.validity.valid) {
                field.removeAttribute('aria-invalid');
                field.classList.remove('bc-field-invalid');
            }
        };
        document.addEventListener('input', clearInvalidState, true);
        document.addEventListener('change', clearInvalidState, true);

        document.addEventListener('submit', (event) => {
            const form = event.target;
            window.setTimeout(() => {
                if (!(form instanceof HTMLFormElement)
                    || event.defaultPrevented
                    || form.dataset.noGlobalLoading === 'true'
                    || !form.checkValidity()) {
                    return;
                }
                beginLoading(false, 12000);
            }, 0);
        }, true);

        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[href]');

            if (!link
                || event.button !== 0
                || event.metaKey
                || event.ctrlKey
                || event.shiftKey
                || event.altKey
                || link.target === '_blank'
                || link.hasAttribute('download')
                || link.dataset.noGlobalLoading === 'true') {
                return;
            }

            let destination;
            try {
                destination = new URL(link.href, window.location.href);
            } catch (error) {
                return;
            }

            if (destination.origin !== window.location.origin
                || (destination.pathname === window.location.pathname
                    && destination.search === window.location.search
                    && destination.hash)) {
                return;
            }

            window.setTimeout(() => {
                if (!event.defaultPrevented) {
                    beginNavigation();
                }
            }, 0);
        }, true);

        window.addEventListener('pageshow', finishInitialLoad);
        window.addEventListener('pagehide', endNavigation);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                finishInitialLoad();
            }
        });

        if (window.fetch) {
            const originalFetch = window.fetch.bind(window);
            window.fetch = (...args) => {
                const token = beginLoading(false, 15000);
                return originalFetch(...args).finally(() => endLoading(token));
            };
        }

        if (window.XMLHttpRequest) {
            const originalSend = XMLHttpRequest.prototype.send;
            XMLHttpRequest.prototype.send = function (...args) {
                const token = beginLoading(false, 15000);
                this.addEventListener('loadend', () => endLoading(token), { once: true });
                return originalSend.apply(this, args);
            };
        }
    }

    function initializeSelectControls(copy) {
        const publicMain = document.getElementById('main-content');
        const candidates = [
            ...(publicMain
                ? publicMain.querySelectorAll('select:not([multiple]):not([data-bc-native-select]):not(.visually-hidden)')
                : []),
            ...document.querySelectorAll('select.form-select:not([multiple]):not([data-bc-native-select]):not(.visually-hidden)'),
            ...document.querySelectorAll('select[data-bc-styled-select]:not([multiple])'),
        ];
        const selects = [...new Set(candidates)];
        const widgets = [];

        const closeAll = (except = null) => {
            for (const widget of widgets) {
                if (widget !== except) {
                    widget.close();
                }
            }
        };

        const repositionOpenMenus = () => {
            for (const widget of widgets) {
                if (widget.isOpen()) {
                    widget.position();
                }
            }
        };

        for (const select of selects) {
            if (select.dataset.bcSelectEnhanced === 'true') {
                continue;
            }

            select.dataset.bcSelectEnhanced = 'true';
            select.classList.add('bc-enhanced-native');

            const wrapper = document.createElement('div');
            wrapper.className = 'bc-public-select';
            select.before(wrapper);
            wrapper.append(select);

            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'bc-public-select-trigger';
            trigger.setAttribute('aria-haspopup', 'listbox');
            trigger.setAttribute('aria-expanded', 'false');
            trigger.setAttribute('aria-label', copy.openOptions);
            trigger.innerHTML = '<span data-bc-public-select-label></span><span class="bc-public-select-chevron" aria-hidden="true"></span>';

            const menu = document.createElement('div');
            menu.className = 'bc-public-select-menu';
            menu.setAttribute('role', 'listbox');
            menu.hidden = true;
            wrapper.append(trigger, menu);

            let optionButtons = [];
            let mutationFrame = 0;
            let widget = null;

            const selectedOption = () => select.selectedOptions[0] || select.options[0] || null;

            const syncTrigger = () => {
                const selected = selectedOption();
                const label = trigger.querySelector('[data-bc-public-select-label]');
                label.textContent = selected?.textContent.trim() || '';
                trigger.disabled = select.disabled;
                trigger.classList.toggle('is-placeholder', !select.value);

                for (const button of optionButtons) {
                    const active = button.dataset.value === select.value;
                    button.setAttribute('aria-selected', String(active));
                    button.classList.toggle('is-selected', active);
                }
            };

            const resetFloatingStyles = () => {
                for (const property of ['position', 'top', 'right', 'bottom', 'left', 'width', 'maxHeight']) {
                    menu.style[property] = '';
                }
            };

            const position = () => {
                if (menu.hidden) {
                    return;
                }

                if (window.matchMedia('(max-width: 620px)').matches) {
                    resetFloatingStyles();
                    return;
                }

                const margin = 12;
                const gap = 8;
                const triggerRect = trigger.getBoundingClientRect();
                const viewportWidth = document.documentElement.clientWidth;
                const viewportHeight = document.documentElement.clientHeight;
                const width = Math.min(Math.max(triggerRect.width, 180), viewportWidth - (margin * 2));
                const roomBelow = Math.max(0, viewportHeight - triggerRect.bottom - gap - margin);
                const roomAbove = Math.max(0, triggerRect.top - gap - margin);
                const openBelow = roomBelow >= 220 || roomBelow >= roomAbove;
                const availableHeight = Math.max(120, Math.min(340, openBelow ? roomBelow : roomAbove));

                menu.style.position = 'fixed';
                menu.style.right = 'auto';
                menu.style.bottom = 'auto';
                menu.style.width = `${width}px`;
                menu.style.maxHeight = `${availableHeight}px`;

                const menuHeight = menu.getBoundingClientRect().height;
                const left = Math.min(
                    Math.max(margin, triggerRect.left),
                    Math.max(margin, viewportWidth - width - margin),
                );
                const desiredTop = openBelow
                    ? triggerRect.bottom + gap
                    : triggerRect.top - gap - menuHeight;
                const top = Math.min(
                    Math.max(margin, desiredTop),
                    Math.max(margin, viewportHeight - menuHeight - margin),
                );

                menu.style.left = `${left}px`;
                menu.style.top = `${top}px`;
                wrapper.classList.toggle('opens-up', !openBelow);
            };

            const close = (restoreFocus = false) => {
                wrapper.classList.remove('is-open', 'opens-up');
                trigger.setAttribute('aria-expanded', 'false');
                menu.hidden = true;
                resetFloatingStyles();

                if (menu.parentElement !== wrapper) {
                    wrapper.append(menu);
                }

                if (restoreFocus) {
                    trigger.focus();
                }
            };

            const choose = (button) => {
                if (button.disabled) {
                    return;
                }

                select.value = button.dataset.value;
                select.dispatchEvent(new Event('input', { bubbles: true }));
                select.dispatchEvent(new Event('change', { bubbles: true }));
                syncTrigger();
                close(true);
            };

            const buildMenu = () => {
                const options = [...select.options];
                const chosen = selectedOption();
                const ordered = chosen
                    ? [chosen, ...options.filter((option) => option !== chosen)]
                    : options;

                menu.replaceChildren();
                optionButtons = ordered.map((option) => {
                    const button = document.createElement('button');
                    const text = document.createElement('span');
                    const check = document.createElement('span');

                    button.type = 'button';
                    button.setAttribute('role', 'option');
                    button.dataset.value = option.value;
                    button.disabled = option.disabled;
                    text.textContent = option.textContent.trim();
                    check.className = 'bc-public-select-check';
                    check.setAttribute('aria-hidden', 'true');
                    check.textContent = '✓';
                    button.append(text, check);
                    button.addEventListener('click', () => choose(button));
                    button.addEventListener('keydown', (event) => {
                        const index = optionButtons.indexOf(button);

                        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                            event.preventDefault();
                            const offset = event.key === 'ArrowDown' ? 1 : -1;
                            optionButtons[(index + offset + optionButtons.length) % optionButtons.length]?.focus();
                        } else if (event.key === 'Home' || event.key === 'End') {
                            event.preventDefault();
                            optionButtons[event.key === 'Home' ? 0 : optionButtons.length - 1]?.focus();
                        } else if (event.key === 'Escape') {
                            event.preventDefault();
                            close(true);
                        } else if (event.key === 'Tab') {
                            close();
                        }
                    });
                    menu.append(button);
                    return button;
                });

                syncTrigger();
            };

            const open = () => {
                if (select.disabled) {
                    return;
                }

                closeAll(widget);
                buildMenu();
                document.body.append(menu);
                menu.hidden = false;
                wrapper.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');
                menu.scrollTop = 0;

                requestAnimationFrame(() => {
                    position();
                    optionButtons.find((button) => button.getAttribute('aria-selected') === 'true' && !button.disabled)?.focus();
                });
            };

            widget = {
                wrapper,
                menu,
                close,
                position,
                isOpen: () => wrapper.classList.contains('is-open') && !menu.hidden,
            };
            widgets.push(widget);

            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                wrapper.classList.contains('is-open') ? close() : open();
            });
            trigger.addEventListener('keydown', (event) => {
                if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
                    event.preventDefault();
                    open();
                } else if (event.key === 'Escape') {
                    close();
                }
            });
            select.addEventListener('change', () => {
                if (select.validity.valid) {
                    trigger.setAttribute('aria-invalid', 'false');
                }
                syncTrigger();
            });
            select.addEventListener('focus', () => trigger.focus());
            select.addEventListener('invalid', () => {
                trigger.setAttribute('aria-invalid', 'true');
                requestAnimationFrame(() => trigger.focus());
            });
            select.form?.addEventListener('reset', () => window.setTimeout(syncTrigger));

            const observer = new MutationObserver(() => {
                cancelAnimationFrame(mutationFrame);
                mutationFrame = requestAnimationFrame(() => {
                    buildMenu();
                    if (!menu.hidden) {
                        menu.scrollTop = 0;
                        position();
                    }
                });
            });
            observer.observe(select, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['disabled', 'selected', 'label'],
            });

            const modal = select.closest('.bc-modal');

            if (modal) {
                const modalObserver = new MutationObserver(() => {
                    if (!modal.hidden) {
                        requestAnimationFrame(syncTrigger);
                    }
                });
                modalObserver.observe(modal, { attributes: true, attributeFilter: ['hidden'] });
            }

            buildMenu();
        }

        document.addEventListener('click', (event) => {
            if (!event.target.closest('.bc-public-select') && !event.target.closest('.bc-public-select-menu')) {
                closeAll();
            }
        });
        document.addEventListener('bloodcare:close-selects', () => closeAll());
        document.addEventListener('scroll', repositionOpenMenus, true);
        window.addEventListener('resize', repositionOpenMenus);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeAll();
            }
        });
    }

    function initializeDatePickers(copy, locale) {
        const inputs = [...document.querySelectorAll('input[type="date"]:not([data-bc-native-date])')];
        const widgets = [];
        const isoPattern = /^\d{4}-\d{2}-\d{2}$/;
        let activeWidget = null;

        const fromIso = (value) => {
            if (!isoPattern.test(value || '')) {
                return null;
            }

            const [year, month, day] = value.split('-').map(Number);
            const date = new Date(year, month - 1, day, 12);
            return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day
                ? date
                : null;
        };

        const toIso = (date) => [
            date.getFullYear(),
            String(date.getMonth() + 1).padStart(2, '0'),
            String(date.getDate()).padStart(2, '0'),
        ].join('-');

        const startOfMonth = (date) => new Date(date.getFullYear(), date.getMonth(), 1, 12);
        const addMonths = (date, amount) => new Date(date.getFullYear(), date.getMonth() + amount, 1, 12);
        const today = new Date();
        today.setHours(12, 0, 0, 0);

        for (const input of inputs) {
            input.dataset.bcDateEnhanced = 'true';
            input.classList.add('bc-enhanced-native-date');

            const wrapper = document.createElement('div');
            wrapper.className = 'bc-date-control';
            input.before(wrapper);
            wrapper.append(input);

            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'bc-date-trigger';
            trigger.setAttribute('aria-haspopup', 'dialog');
            trigger.setAttribute('aria-expanded', 'false');
            trigger.innerHTML = '<span class="bc-date-trigger-icon" aria-hidden="true"></span><span data-bc-date-label></span><span class="bc-date-trigger-chevron" aria-hidden="true"></span>';

            const panel = document.createElement('div');
            panel.className = 'bc-date-panel';
            panel.setAttribute('role', 'dialog');
            panel.setAttribute('aria-modal', 'false');
            panel.setAttribute('aria-label', copy.calendar);
            panel.hidden = true;
            panel.innerHTML = `
                <div class="bc-date-heading">
                    <button type="button" data-bc-date-previous aria-label="${copy.previousMonth}">‹</button>
                    <div class="bc-date-heading-selects">
                        <select data-bc-date-month-select data-bc-styled-select aria-label="${copy.month}"></select>
                        <select data-bc-date-year-select data-bc-styled-select aria-label="${copy.year}"></select>
                    </div>
                    <strong class="bc-date-current-label" data-bc-date-month aria-live="polite"></strong>
                    <button type="button" data-bc-date-next aria-label="${copy.nextMonth}">›</button>
                </div>
                <div class="bc-date-weekdays" aria-hidden="true"></div>
                <div class="bc-date-days" role="grid"></div>
                <div class="bc-date-actions">
                    <button type="button" data-bc-date-today>${copy.today}</button>
                    <button type="button" data-bc-date-clear>${copy.clear}</button>
                    <button type="button" data-bc-date-close>${copy.close}</button>
                </div>
            `;
            wrapper.append(trigger, panel);

            const monthLabel = panel.querySelector('[data-bc-date-month]');
            const monthSelect = panel.querySelector('[data-bc-date-month-select]');
            const yearSelect = panel.querySelector('[data-bc-date-year-select]');
            const weekdayGrid = panel.querySelector('.bc-date-weekdays');
            const dayGrid = panel.querySelector('.bc-date-days');
            const previous = panel.querySelector('[data-bc-date-previous]');
            const next = panel.querySelector('[data-bc-date-next]');
            const todayButton = panel.querySelector('[data-bc-date-today]');
            const clearButton = panel.querySelector('[data-bc-date-clear]');
            const closeButton = panel.querySelector('[data-bc-date-close]');
            const dateFormatter = new Intl.DateTimeFormat(locale, { day: 'numeric', month: 'long', year: 'numeric' });
            const monthFormatter = new Intl.DateTimeFormat(locale, { month: 'long', year: 'numeric' });
            const weekdayFormatter = new Intl.DateTimeFormat(locale, { weekday: 'narrow' });
            const monthNameFormatter = new Intl.DateTimeFormat(locale, { month: 'long' });
            const isBirthDate = input.dataset.bcDatePurpose === 'birth'
                || /(?:date[_-]?of[_-]?birth|birth)/i.test(`${input.name} ${input.id}`);
            const initialDate = fromIso(input.value)
                || (isBirthDate
                    ? new Date(today.getFullYear() - 25, today.getMonth(), 1, 12)
                    : (fromIso(input.min) || today));
            let viewDate = startOfMonth(initialDate);

            for (let month = 0; month < 12; month += 1) {
                const option = document.createElement('option');
                option.value = String(month);
                option.textContent = monthNameFormatter.format(new Date(2024, month, 1, 12));
                monthSelect.append(option);
            }

            for (let offset = 0; offset < 7; offset += 1) {
                const weekday = new Date(2024, 0, 1 + offset, 12);
                const item = document.createElement('span');
                item.textContent = weekdayFormatter.format(weekday);
                weekdayGrid.append(item);
            }

            const bounds = () => ({
                min: fromIso(input.min),
                max: fromIso(input.max),
            });

            const allowed = (date) => {
                const { min, max } = bounds();
                return (!min || date >= min) && (!max || date <= max);
            };

            const yearLimits = () => {
                const { min, max } = bounds();
                const currentYear = today.getFullYear();
                const minimum = min?.getFullYear() ?? (isBirthDate ? currentYear - 120 : currentYear - 25);
                const maximum = max?.getFullYear() ?? (isBirthDate ? currentYear : currentYear + 30);

                return {
                    minimum: Math.min(minimum, maximum),
                    maximum: Math.max(minimum, maximum),
                };
            };

            const populateYearOptions = () => {
                const { minimum, maximum } = yearLimits();
                const selectedYear = viewDate.getFullYear();
                const years = [];

                if (isBirthDate) {
                    for (let year = maximum; year >= minimum; year -= 1) {
                        years.push(year);
                    }
                } else {
                    for (let year = minimum; year <= maximum; year += 1) {
                        years.push(year);
                    }
                }

                yearSelect.replaceChildren(...years.map((year) => {
                    const option = document.createElement('option');
                    option.value = String(year);
                    option.textContent = new Intl.NumberFormat(locale, { useGrouping: false }).format(year);
                    return option;
                }));

                if (years.includes(selectedYear)) {
                    yearSelect.value = String(selectedYear);
                }
            };

            const syncHeadingControls = () => {
                populateYearOptions();
                monthSelect.value = String(viewDate.getMonth());
                yearSelect.value = String(viewDate.getFullYear());

                const { min, max } = bounds();
                for (const option of monthSelect.options) {
                    const candidate = new Date(viewDate.getFullYear(), Number(option.value), 1, 12);
                    const candidateEnd = new Date(viewDate.getFullYear(), Number(option.value) + 1, 0, 12);
                    option.disabled = Boolean((min && candidateEnd < min) || (max && candidate > max));
                }
            };

            const syncTrigger = () => {
                const selected = fromIso(input.value);
                const label = trigger.querySelector('[data-bc-date-label]');
                label.textContent = selected ? dateFormatter.format(selected) : copy.chooseDate;
                trigger.classList.toggle('is-placeholder', !selected);
                trigger.disabled = input.disabled || input.readOnly;
            };

            const resetPanelPosition = () => {
                for (const property of ['position', 'top', 'right', 'bottom', 'left', 'width', 'maxHeight', 'overflowY']) {
                    panel.style[property] = '';
                }
            };

            const positionPanel = () => {
                if (panel.hidden) {
                    return;
                }

                if (window.matchMedia('(max-width: 620px)').matches) {
                    resetPanelPosition();
                    return;
                }

                const margin = 12;
                const gap = 8;
                const triggerRect = trigger.getBoundingClientRect();
                const viewportWidth = document.documentElement.clientWidth;
                const viewportHeight = document.documentElement.clientHeight;
                const width = Math.min(410, viewportWidth - (margin * 2));
                const roomBelow = Math.max(0, viewportHeight - triggerRect.bottom - gap - margin);
                const roomAbove = Math.max(0, triggerRect.top - gap - margin);

                panel.style.position = 'fixed';
                panel.style.right = 'auto';
                panel.style.bottom = 'auto';
                panel.style.width = `${width}px`;
                panel.style.maxHeight = `${Math.max(260, Math.min(viewportHeight - (margin * 2), 520))}px`;
                panel.style.overflowY = 'auto';

                let panelHeight = panel.getBoundingClientRect().height;
                const openBelow = roomBelow >= panelHeight || roomBelow >= roomAbove;
                const availableHeight = Math.max(240, openBelow ? roomBelow : roomAbove);
                panel.style.maxHeight = `${Math.min(520, availableHeight)}px`;
                panelHeight = panel.getBoundingClientRect().height;

                const left = Math.min(
                    Math.max(margin, triggerRect.left),
                    Math.max(margin, viewportWidth - width - margin),
                );
                const desiredTop = openBelow
                    ? triggerRect.bottom + gap
                    : triggerRect.top - gap - panelHeight;
                const top = Math.min(
                    Math.max(margin, desiredTop),
                    Math.max(margin, viewportHeight - panelHeight - margin),
                );

                panel.style.left = `${left}px`;
                panel.style.top = `${top}px`;
                wrapper.classList.toggle('opens-up', !openBelow);
            };

            const close = (restoreFocus = false) => {
                wrapper.classList.remove('is-open', 'opens-up');
                trigger.setAttribute('aria-expanded', 'false');
                panel.hidden = true;
                resetPanelPosition();
                document.dispatchEvent(new CustomEvent('bloodcare:close-selects'));

                if (panel.parentElement !== wrapper) {
                    wrapper.append(panel);
                }

                if (activeWidget === widget) {
                    activeWidget = null;
                }

                if (restoreFocus) {
                    trigger.focus();
                }
            };

            const choose = (date) => {
                if (!allowed(date)) {
                    return;
                }

                input.value = toIso(date);
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                syncTrigger();
                close(true);
            };

            const render = () => {
                const selected = fromIso(input.value);
                const firstDay = startOfMonth(viewDate);
                const mondayOffset = (firstDay.getDay() + 6) % 7;
                const gridStart = new Date(firstDay);
                gridStart.setDate(firstDay.getDate() - mondayOffset);
                monthLabel.textContent = monthFormatter.format(viewDate);
                syncHeadingControls();
                dayGrid.replaceChildren();

                for (let index = 0; index < 42; index += 1) {
                    const date = new Date(gridStart);
                    date.setDate(gridStart.getDate() + index);
                    const button = document.createElement('button');
                    const iso = toIso(date);
                    const isSelected = selected && iso === toIso(selected);
                    const isToday = iso === toIso(today);

                    button.type = 'button';
                    button.textContent = new Intl.NumberFormat(locale, { useGrouping: false }).format(date.getDate());
                    button.dataset.date = iso;
                    button.setAttribute('role', 'gridcell');
                    button.setAttribute('aria-label', dateFormatter.format(date));
                    button.setAttribute('aria-selected', String(Boolean(isSelected)));
                    button.disabled = !allowed(date);
                    button.classList.toggle('is-outside', date.getMonth() !== viewDate.getMonth());
                    button.classList.toggle('is-today', isToday);
                    button.classList.toggle('is-selected', Boolean(isSelected));
                    button.addEventListener('click', () => choose(date));
                    dayGrid.append(button);
                }

                const { min, max } = bounds();
                previous.disabled = Boolean(min && addMonths(viewDate, -1) < startOfMonth(min));
                next.disabled = Boolean(max && addMonths(viewDate, 1) > startOfMonth(max));
                todayButton.disabled = !allowed(today);
                clearButton.hidden = input.required;
            };

            const open = () => {
                if (trigger.disabled) {
                    return;
                }

                activeWidget?.close();
                syncTrigger();
                viewDate = startOfMonth(
                    fromIso(input.value)
                    || (isBirthDate
                        ? new Date(today.getFullYear() - 25, today.getMonth(), 1, 12)
                        : (fromIso(input.min) || today)),
                );
                render();
                document.body.append(panel);
                panel.hidden = false;
                wrapper.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');
                activeWidget = widget;

                requestAnimationFrame(() => {
                    positionPanel();
                    panel.querySelector('.is-selected:not(:disabled), .is-today:not(:disabled), .bc-date-days button:not(:disabled)')?.focus();
                });
            };

            const widget = { wrapper, panel, close, position: positionPanel };
            widgets.push(widget);

            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                wrapper.classList.contains('is-open') ? close() : open();
            });
            monthSelect.addEventListener('change', () => {
                viewDate = new Date(Number(yearSelect.value), Number(monthSelect.value), 1, 12);
                render();
                monthSelect.focus();
            });
            yearSelect.addEventListener('change', () => {
                viewDate = new Date(Number(yearSelect.value), Number(monthSelect.value), 1, 12);
                render();
                yearSelect.focus();
            });
            previous.addEventListener('click', () => {
                viewDate = addMonths(viewDate, -1);
                render();
                previous.focus();
            });
            next.addEventListener('click', () => {
                viewDate = addMonths(viewDate, 1);
                render();
                next.focus();
            });
            todayButton.addEventListener('click', () => choose(today));
            clearButton.addEventListener('click', () => {
                input.value = '';
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                syncTrigger();
                close(true);
            });
            closeButton.addEventListener('click', () => close(true));
            panel.addEventListener('keydown', (event) => {
                const current = event.target.closest('[data-date]');

                if (event.key === 'Escape') {
                    event.preventDefault();
                    close(true);
                    return;
                }

                if (!current) {
                    return;
                }

                const offsets = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };
                let nextDate = fromIso(current.dataset.date);

                if (Object.hasOwn(offsets, event.key)) {
                    nextDate.setDate(nextDate.getDate() + offsets[event.key]);
                } else if (event.key === 'Home' || event.key === 'End') {
                    const weekday = (nextDate.getDay() + 6) % 7;
                    nextDate.setDate(nextDate.getDate() + (event.key === 'Home' ? -weekday : 6 - weekday));
                } else if (event.key === 'PageUp' || event.key === 'PageDown') {
                    nextDate = addMonths(nextDate, event.key === 'PageUp' ? -1 : 1);
                } else {
                    return;
                }

                event.preventDefault();

                if (!allowed(nextDate)) {
                    return;
                }

                if (nextDate.getMonth() !== viewDate.getMonth() || nextDate.getFullYear() !== viewDate.getFullYear()) {
                    viewDate = startOfMonth(nextDate);
                    render();
                }

                dayGrid.querySelector(`[data-date="${toIso(nextDate)}"]`)?.focus();
            });
            input.addEventListener('input', syncTrigger);
            input.addEventListener('change', () => {
                if (input.validity.valid) {
                    trigger.setAttribute('aria-invalid', 'false');
                }
                syncTrigger();
            });
            input.addEventListener('focus', () => trigger.focus());
            input.addEventListener('invalid', () => {
                trigger.setAttribute('aria-invalid', 'true');
                requestAnimationFrame(() => trigger.focus());
            });
            input.form?.addEventListener('reset', () => setTimeout(syncTrigger));
            input.form?.addEventListener('change', () => requestAnimationFrame(syncTrigger));

            const observer = new MutationObserver(syncTrigger);
            observer.observe(input, { attributes: true, attributeFilter: ['disabled', 'readonly', 'min', 'max', 'required', 'value'] });

            const modal = input.closest('.bc-modal');

            if (modal) {
                const modalObserver = new MutationObserver(() => {
                    if (!modal.hidden) {
                        requestAnimationFrame(syncTrigger);
                    }
                });
                modalObserver.observe(modal, { attributes: true, attributeFilter: ['hidden'] });
            }

            syncTrigger();
        }

        document.addEventListener('click', (event) => {
            if (activeWidget
                && !activeWidget.wrapper.contains(event.target)
                && !activeWidget.panel.contains(event.target)) {
                activeWidget.close();
            }
        });
        document.addEventListener('scroll', () => activeWidget?.position(), true);
        window.addEventListener('resize', () => activeWidget?.position());

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && activeWidget) {
                activeWidget.close(true);
            }
        });
    }
})();
