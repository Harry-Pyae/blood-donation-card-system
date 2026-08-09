(() => {
    'use strict';

    const root = document.querySelector('[data-bc-donor-form], [data-bc-donor-details]');
    const configNode = document.getElementById('bc-donor-form-config');
    const config = configNode ? JSON.parse(configNode.textContent) : { townships: {}, labels: {} };

    if (!root) {
        return;
    }

    const sectionNavigations = root.querySelectorAll('[data-bc-section-nav]');

    sectionNavigations.forEach((navigation) => {
        const scope = navigation.closest('[data-bc-section-scope]') || root;
        const buttons = [...navigation.querySelectorAll('[data-bc-section-button]')];
        const panels = [...scope.querySelectorAll(':scope > [data-bc-section-panel]')];
        const registrationFlow = scope.hasAttribute('data-bc-registration-flow');
        const form = root.querySelector('form.bc-record-form');
        const nextButton = root.querySelector('[data-bc-registration-next]');
        const backButton = root.querySelector('[data-bc-registration-back]');
        const saveButton = root.querySelector('[data-bc-registration-save]');

        if (!buttons.length || !panels.length) return;

        let activeIndex = 0;
        let unlockedIndex = registrationFlow ? 0 : panels.length - 1;

        const syncRegistrationControls = () => {
            if (!registrationFlow) return;

            buttons.forEach((button, index) => {
                const locked = index > unlockedIndex;
                button.disabled = locked;
                button.setAttribute('aria-disabled', String(locked));
            });

            if (backButton) backButton.hidden = activeIndex === 0;
            if (nextButton) nextButton.hidden = activeIndex >= panels.length - 1;
            if (saveButton) saveButton.hidden = activeIndex !== panels.length - 1;
        };

        const activate = (section, options = {}) => {
            const selectedPanel = panels.find((panel) => panel.dataset.bcSectionPanel === section) || panels[0];
            const selectedSection = selectedPanel.dataset.bcSectionPanel;
            const selectedIndex = panels.indexOf(selectedPanel);

            if (registrationFlow && selectedIndex > unlockedIndex && !options.force) return false;
            activeIndex = selectedIndex;

            panels.forEach((panel) => {
                const active = panel === selectedPanel;
                panel.hidden = !active;
                panel.setAttribute('aria-hidden', String(!active));
            });

            buttons.forEach((button) => {
                const active = button.dataset.bcSectionTarget === selectedSection;
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-selected', String(active));
                button.tabIndex = active && !button.disabled ? 0 : -1;
            });

            syncRegistrationControls();

            if (options.focus) {
                buttons.find((button) => button.dataset.bcSectionTarget === selectedSection)?.focus();
            }

            if (options.hash) {
                const selectedHash = selectedPanel.dataset.bcSectionHash;
                if (selectedHash) history.replaceState(null, '', `#${selectedHash}`);
            }

            return true;
        };

        const panelFields = (index) => {
            const fields = [...panels[index].querySelectorAll('input, select, textarea')];
            if (registrationFlow && index === 0) {
                fields.unshift(...root.querySelectorAll('[data-bc-linked-user-section] input, [data-bc-linked-user-section] select, [data-bc-linked-user-section] textarea'));
            }

            return [...new Set(fields)].filter((field) => !field.disabled && !field.hidden);
        };

        const validateCurrentPanel = () => {
            const invalidFields = panelFields(activeIndex).filter((field) => !field.validity.valid);
            if (!invalidFields.length) return true;

            if (window.BloodCareUI?.showValidationAlert) {
                window.BloodCareUI.showValidationAlert(invalidFields);
            } else {
                invalidFields[0].reportValidity();
            }
            return false;
        };

        const advance = () => {
            if (!registrationFlow || activeIndex >= panels.length - 1 || !validateCurrentPanel()) return;
            unlockedIndex = Math.max(unlockedIndex, activeIndex + 1);
            syncRegistrationControls();
            activate(panels[activeIndex + 1].dataset.bcSectionPanel, { focus: true, hash: true, force: true });
        };

        buttons.forEach((button, index) => {
            button.addEventListener('click', () => {
                if (button.disabled) return;
                if (registrationFlow && index > activeIndex && !validateCurrentPanel()) return;
                activate(button.dataset.bcSectionTarget, { hash: true });
            });
            button.addEventListener('keydown', (event) => {
                if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
                event.preventDefault();
                const availableButtons = registrationFlow ? buttons.slice(0, unlockedIndex + 1) : buttons;
                const availableIndex = availableButtons.indexOf(button);
                let nextIndex = index;
                if (event.key === 'ArrowRight') nextIndex = (availableIndex + 1) % availableButtons.length;
                if (event.key === 'ArrowLeft') nextIndex = (availableIndex - 1 + availableButtons.length) % availableButtons.length;
                if (event.key === 'Home') nextIndex = 0;
                if (event.key === 'End') nextIndex = availableButtons.length - 1;
                if (registrationFlow && nextIndex > activeIndex && !validateCurrentPanel()) return;
                activate(availableButtons[nextIndex].dataset.bcSectionTarget, { focus: true, hash: true });
            });
        });

        scope.addEventListener('invalid', (event) => {
            const panel = event.target.closest('[data-bc-section-panel]');
            if (panel) activate(panel.dataset.bcSectionPanel);
        }, true);

        const invalidPanel = panels.find((panel) => panel.querySelector('[aria-invalid="true"], .is-invalid'));
        const hashPanel = panels.find((panel) => panel.dataset.bcSectionHash === window.location.hash.slice(1));
        const initialSection = registrationFlow
            ? (invalidPanel?.dataset.bcSectionPanel || scope.dataset.bcInitialSection || panels[0].dataset.bcSectionPanel)
            : (hashPanel?.dataset.bcSectionPanel
                || invalidPanel?.dataset.bcSectionPanel
                || scope.dataset.bcInitialSection
                || buttons.find((button) => button.classList.contains('is-active'))?.dataset.bcSectionTarget
                || panels[0].dataset.bcSectionPanel);

        if (registrationFlow) {
            const initialIndex = Math.max(0, panels.findIndex((panel) => panel.dataset.bcSectionPanel === initialSection));
            unlockedIndex = initialIndex;
        }

        scope.classList.add('bc-section-scope-ready');
        syncRegistrationControls();
        activate(initialSection, { force: true });

        nextButton?.addEventListener('click', advance);
        backButton?.addEventListener('click', () => {
            if (activeIndex <= 0) return;
            activate(panels[activeIndex - 1].dataset.bcSectionPanel, { focus: true, hash: true, force: true });
        });
        form?.addEventListener('submit', (event) => {
            if (!registrationFlow || activeIndex >= panels.length - 1) return;
            event.preventDefault();
            advance();
        });

        window.addEventListener('hashchange', () => {
            const selectedByHash = panels.find((panel) => panel.dataset.bcSectionHash === window.location.hash.slice(1));
            if (selectedByHash) activate(selectedByHash.dataset.bcSectionPanel);
        });
    });

    const documentType = root.querySelector('#bc-donor-document-type');
    const nrcFields = root.querySelector('#bc-donor-nrc-fields');
    const passportFields = root.querySelector('#bc-donor-passport-fields');
    const nrcState = root.querySelector('#bc-donor-nrc-state');
    const nrcTownship = root.querySelector('#bc-donor-nrc-township');

    function townshipLabel(option) {
        const english = option.display || option.value || '';
        return String(config.locale || '').startsWith('my')
            ? `${option.myanmarCode || ''} — ${option.myanmarName || ''} (${english})`
            : `${english} (${option.myanmarCode || ''}) — ${option.myanmarName || ''}`;
    }

    function populateTownships() {
        if (!nrcState || !nrcTownship) return;
        const selected = nrcTownship.dataset.selected || nrcTownship.value;
        const options = config.townships?.[nrcState.value] || [];
        nrcTownship.replaceChildren(new Option(
            nrcState.value ? config.labels.selectTownship : config.labels.selectStateFirst,
            '',
        ));
        options.forEach((option) => nrcTownship.add(new Option(townshipLabel(option), option.value)));
        if ([...nrcTownship.options].some((option) => option.value === selected)) nrcTownship.value = selected;
        nrcTownship.dataset.selected = '';
        nrcTownship.disabled = !nrcState.value || documentType?.value !== 'nrc';
    }

    function syncIdentity() {
        if (!documentType) return;
        const usingNrc = documentType.value === 'nrc';
        if (nrcFields) nrcFields.hidden = !usingNrc;
        if (passportFields) passportFields.hidden = usingNrc;
        nrcFields?.querySelectorAll('input, select').forEach((field) => {
            field.disabled = !usingNrc;
            field.required = usingNrc;
        });
        passportFields?.querySelectorAll('input').forEach((field) => {
            field.disabled = usingNrc;
            field.required = !usingNrc;
        });
        if (usingNrc) populateTownships();
    }

    function syncDeferral(select, reason, end) {
        if (!select) return;
        const deferred = select.value !== 'none';
        const temporary = select.value === 'temporary';
        if (reason) {
            reason.disabled = !deferred;
            reason.required = deferred;
            if (!deferred) reason.value = '';
        }
        if (end) {
            end.disabled = !temporary;
            end.required = temporary;
            if (!temporary) end.value = '';
        }
    }

    const currentDeferral = root.querySelector('#bc-deferral-type');
    const currentReason = root.querySelector('#bc-deferral-reason');
    const currentEnd = root.querySelector('#bc-deferral-end');
    const screeningOutcome = root.querySelector('#bc-screening-outcome');
    const screeningDeferral = root.querySelector('#bc-screening-deferral-type');
    const screeningReason = root.querySelector('#bc-screening-deferral-reason');
    const screeningEnd = root.querySelector('#bc-screening-deferral-end');

    function syncScreeningOutcome() {
        if (!screeningOutcome || !screeningDeferral) return;
        if (screeningToggle && !screeningToggle.checked) return;
        const needsDeferral = ['Deferred', 'Failed'].includes(screeningOutcome.value);
        if (!needsDeferral) screeningDeferral.value = 'none';
        screeningDeferral.disabled = false;
        syncDeferral(screeningDeferral, screeningReason, screeningEnd);
    }

    const screeningToggle = root.querySelector('#bc-record-initial-screening');
    const screeningFields = root.querySelector('#bc-initial-screening-fields');
    function syncInitialScreening() {
        if (!screeningToggle || !screeningFields) return;
        screeningFields.hidden = false;
        screeningFields.classList.toggle('is-disabled', !screeningToggle.checked);
        screeningFields.querySelectorAll('input, select, textarea').forEach((field) => {
            field.disabled = !screeningToggle.checked;
        });
        if (screeningToggle.checked) syncScreeningOutcome();
    }

    root.querySelectorAll('[data-bc-risk-card]').forEach((card) => {
        const flag = card.querySelector('input[type="checkbox"]');
        const details = card.querySelector('[data-bc-risk-details]');
        const sync = () => {
            details.hidden = !flag.checked;
            details.querySelectorAll('textarea, input').forEach((field) => { field.disabled = !flag.checked; });
        };
        flag.addEventListener('change', sync);
        sync();
    });

    root.querySelectorAll('[data-bc-weight-converter]').forEach((converter) => {
        const poundsInput = converter.querySelector('[data-bc-weight-pounds]');
        const applyButton = converter.querySelector('[data-bc-weight-apply]');
        const result = converter.querySelector('[data-bc-weight-result]');
        const weightField = converter.closest('.bc-weight-field')?.querySelector('[name="weightKg"]');
        const waitingLabel = result?.textContent || '';
        let convertedKilograms = null;

        const calculate = () => {
            const pounds = Number.parseFloat(poundsInput?.value || '');
            const minimum = Number.parseFloat(poundsInput?.min || '0');
            const maximum = Number.parseFloat(poundsInput?.max || 'Infinity');
            const valid = Number.isFinite(pounds) && pounds >= minimum && pounds <= maximum;

            convertedKilograms = valid ? Math.round((pounds * 0.45359237) * 100) / 100 : null;
            if (applyButton) applyButton.disabled = convertedKilograms === null;
            if (result) {
                result.textContent = convertedKilograms === null
                    ? waitingLabel
                    : `${pounds.toFixed(1)} lb = ${convertedKilograms.toFixed(2)} kg`;
                result.classList.toggle('has-value', convertedKilograms !== null);
            }
        };

        poundsInput?.addEventListener('input', calculate);
        applyButton?.addEventListener('click', () => {
            calculate();
            if (convertedKilograms === null || !weightField) return;
            weightField.value = convertedKilograms.toFixed(2);
            weightField.dispatchEvent(new Event('input', { bubbles: true }));
            weightField.dispatchEvent(new Event('change', { bubbles: true }));
            weightField.focus();
        });
        calculate();
    });

    let historyModalTrigger = null;

    function openHistoryModal(modal, trigger) {
        if (!modal) return;
        historyModalTrigger = trigger;
        modal.hidden = false;
        document.body.classList.add('bc-modal-open');
        modal.querySelector('[data-modal-close], [role="dialog"]')?.focus();
    }

    function closeHistoryModal(modal) {
        if (!modal) return;
        modal.hidden = true;
        if (![...document.querySelectorAll('.bc-modal')].some((candidate) => !candidate.hidden)) {
            document.body.classList.remove('bc-modal-open');
        }
        historyModalTrigger?.focus();
        historyModalTrigger = null;
    }

    root.querySelectorAll('[data-bc-history-details-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const modal = document.getElementById(trigger.dataset.bcHistoryDetailsOpen || '');
            openHistoryModal(modal, trigger);
        });
    });

    root.querySelectorAll('[data-bc-history-modal] [data-modal-close]').forEach((button) => {
        button.addEventListener('click', () => closeHistoryModal(button.closest('[data-bc-history-modal]')));
    });

    root.querySelectorAll('[data-bc-history-delete]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const submitButton = event.submitter || form.querySelector('button[type="submit"]');
            const confirmed = await window.BloodCareUI?.confirmAction({
                message: form.dataset.confirmMessage || '',
                confirmLabel: submitButton?.textContent.trim() || '',
                danger: true,
            });
            if (confirmed) form.submit();
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const openModal = [...root.querySelectorAll('[data-bc-history-modal]')].find((modal) => !modal.hidden);
        if (openModal) closeHistoryModal(openModal);
    });

    const userSelect = root.querySelector('#bc-donor-user-id');
    const myanmarPhoneLocalPart = (value) => {
        const digits = String(value || '').replace(/[^0-9]/g, '');
        if (digits.startsWith('95')) return digits.slice(2);
        if (digits.startsWith('0')) return digits.slice(1);
        return digits;
    };

    const phoneLocalField = root.querySelector('[name="phoneLocal"]');
    const phoneFullField = root.querySelector('[name="phone"][data-bc-phone-full]');
    const syncFullPhone = () => {
        if (!phoneLocalField || !phoneFullField) return;
        const local = String(phoneLocalField.value || '').replace(/[^0-9]/g, '');
        phoneFullField.value = local ? `+95 ${local}` : '';
    };

    phoneLocalField?.addEventListener('input', syncFullPhone);
    root.querySelector('form.bc-record-form')?.addEventListener('submit', syncFullPhone);
    syncFullPhone();

    const prefillLinkedUser = () => {
        if (!userSelect?.value) return;
        const selectedOption = userSelect.selectedOptions[0];
        if (!selectedOption) return;

        const values = {
            name: selectedOption.dataset.userName || '',
            email: selectedOption.dataset.userEmail || '',
            phoneLocal: myanmarPhoneLocalPart(selectedOption.dataset.userPhone || ''),
            emergencyContact: selectedOption.dataset.userPhone || '',
        };

        Object.entries(values).forEach(([name, value]) => {
            const field = root.querySelector(`[name="${name}"]`);
            if (!field) return;
            field.value = value;
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
        });
    };

    userSelect?.addEventListener('change', prefillLinkedUser);
    if (userSelect?.value && root.dataset.bcHasServerErrors !== 'true') prefillLinkedUser();

    const printLanguageMenu = root.querySelector('[data-bc-print-language-menu]');
    const printSheets = [...root.querySelectorAll('[data-bc-donor-print-sheet]')];
    const printButtons = [...root.querySelectorAll('[data-bc-print-donor-locale]')];

    const clearPrintMode = () => {
        document.documentElement.classList.remove('bc-print-donor-form');
        printSheets.forEach((sheet) => { sheet.hidden = true; });
    };

    printButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const locale = button.dataset.bcPrintDonorLocale;
            const sheet = printSheets.find((candidate) => candidate.dataset.bcDonorPrintSheet === locale);
            if (!sheet) return;

            printSheets.forEach((candidate) => { candidate.hidden = candidate !== sheet; });
            printLanguageMenu?.removeAttribute('open');
            document.documentElement.classList.add('bc-print-donor-form');
            window.setTimeout(() => window.print(), 30);
        });
    });

    window.addEventListener('afterprint', clearPrintMode);

    documentType?.addEventListener('change', syncIdentity);
    nrcState?.addEventListener('change', populateTownships);
    currentDeferral?.addEventListener('change', () => syncDeferral(currentDeferral, currentReason, currentEnd));
    screeningOutcome?.addEventListener('change', syncScreeningOutcome);
    screeningDeferral?.addEventListener('change', () => syncDeferral(screeningDeferral, screeningReason, screeningEnd));
    screeningToggle?.addEventListener('change', syncInitialScreening);

    syncIdentity();
    syncDeferral(currentDeferral, currentReason, currentEnd);
    syncInitialScreening();
    syncScreeningOutcome();
})();
