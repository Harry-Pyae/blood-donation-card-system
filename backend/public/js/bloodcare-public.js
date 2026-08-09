document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('[data-public-header]');
    const toggle = document.querySelector('[data-nav-toggle]');
    const nav = document.querySelector('[data-public-nav]');
    const themeToggle = document.querySelector('[data-public-theme-toggle]');
    const languageForm = document.querySelector('[data-public-language-form]');
    const languageMenu = document.querySelector('[data-public-language-menu]');
    const desktopNavigation = window.matchMedia('(min-width: 1181px)');

    if (header && toggle && nav) {
        const closeNavigation = (restoreFocus = false) => {
            header.classList.remove('nav-is-open');
            document.body.classList.remove('public-nav-open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', toggle.dataset.openLabel || 'Open navigation');

            if (restoreFocus) {
                toggle.focus();
            }
        };

        toggle.addEventListener('click', () => {
            const isOpen = header.classList.toggle('nav-is-open');
            document.body.classList.toggle('public-nav-open', isOpen);
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            toggle.setAttribute(
                'aria-label',
                isOpen
                    ? (toggle.dataset.closeLabel || 'Close navigation')
                    : (toggle.dataset.openLabel || 'Open navigation'),
            );
        });

        nav.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => closeNavigation());
        });

        document.addEventListener('click', (event) => {
            if (header.classList.contains('nav-is-open') && !header.contains(event.target)) {
                closeNavigation();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && header.classList.contains('nav-is-open')) {
                closeNavigation(true);
            }
        });

        desktopNavigation.addEventListener('change', (event) => {
            if (event.matches) {
                closeNavigation();
            }
        });
    }

    if (themeToggle) {
        const themeStorageKey = 'bloodcare.public.theme.v1';
        const themeMeta = document.querySelector('meta[name="theme-color"]');

        const applyTheme = (theme, persist = false) => {
            const normalized = theme === 'dark' ? 'dark' : 'light';
            const nextLabel = normalized === 'dark'
                ? themeToggle.dataset.lightLabel
                : themeToggle.dataset.darkLabel;

            document.documentElement.dataset.theme = normalized;
            themeToggle.setAttribute('aria-label', nextLabel || '');
            themeToggle.setAttribute('title', nextLabel || '');
            themeMeta?.setAttribute('content', normalized === 'dark' ? '#111827' : '#d91f3a');

            if (persist) {
                try {
                    window.localStorage.setItem(themeStorageKey, normalized);
                } catch (error) {
                    // The selected theme still applies for the current page.
                }
            }
        };

        applyTheme(document.documentElement.dataset.theme);

        themeToggle.addEventListener('click', () => {
            applyTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark', true);
        });
    }

    if (languageForm && languageMenu) {
        document.addEventListener('click', (event) => {
            if (languageMenu.open && !languageForm.contains(event.target)) {
                languageMenu.open = false;
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && languageMenu.open) {
                languageMenu.open = false;
                languageMenu.querySelector('summary')?.focus();
            }
        });

        languageForm.addEventListener('submit', (event) => {
            const submitter = event.submitter;

            if (submitter) {
                submitter.setAttribute('aria-busy', 'true');
            }
        });
    }

    const revealItems = document.querySelectorAll('[data-reveal]');

    if ('IntersectionObserver' in window && revealItems.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        revealItems.forEach((item) => observer.observe(item));
    } else {
        revealItems.forEach((item) => item.classList.add('is-visible'));
    }

    const homeConfigNode = document.getElementById('bc-public-home-config');

    if (homeConfigNode) {
        initializeHome(JSON.parse(homeConfigNode.textContent));
    }

    const appointmentForm = document.querySelector('[data-public-appointment-form]');
    const appointmentConfigNode = document.getElementById('bc-public-appointment-config');

    if (appointmentForm && appointmentConfigNode) {
        initializeAppointment(
            appointmentForm,
            JSON.parse(appointmentConfigNode.textContent),
        );
    }

    const registrationForm = document.querySelector('[data-public-registration-form]');

    if (registrationForm) {
        initializeRegistration(registrationForm);
    }

    const eligibilityChecker = document.querySelector('[data-eligibility-checker]');
    const eligibilityConfigNode = document.getElementById('bc-public-eligibility-config');

    if (eligibilityChecker && eligibilityConfigNode) {
        initializeEligibilityChecker(
            eligibilityChecker,
            JSON.parse(eligibilityConfigNode.textContent),
        );
    }

    const lookupForm = document.querySelector('[data-public-lookup-form]');

    if (lookupForm) {
        initializeLookupForm(lookupForm);
    }

    function initializeLookupForm(form) {
        const submit = form.querySelector('[data-lookup-submit]');
        const label = form.querySelector('[data-lookup-submit-label]');
        const errorSummary = document.querySelector('[data-lookup-error-summary]');

        errorSummary?.focus();

        form.addEventListener('submit', (event) => {
            if (!form.checkValidity()) {
                return;
            }

            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.submitting = 'true';

            if (submit) {
                submit.setAttribute('aria-busy', 'true');
                submit.disabled = true;
            }

            if (label) {
                label.textContent = form.dataset.submittingLabel || label.textContent;
            }
        });
    }

    function initializeAppointment(form, config) {
        const requestValue = '__request__';
        const labels = config.labels || {};
        const centreSelect = form.querySelector('[data-public-centre-select]');
        const requestFields = form.querySelector('[data-location-request-fields]');
        const requestInputs = [...form.querySelectorAll('[data-location-request-input]')];
        const slotFields = [...form.querySelectorAll('[data-appointment-slot-field]')];
        const dateInput = form.querySelector('#appointment_date');
        const timeInput = form.querySelector('#appointment_time');
        const regionInput = form.querySelector('#requested_region');
        const townshipInput = form.querySelector('#requested_township');
        const submitButton = form.querySelector('[data-public-appointment-submit]');
        const submitLabel = form.querySelector('[data-public-appointment-submit-label]');
        const notesInput = form.querySelector('[data-appointment-notes]');
        const notesCount = form.querySelector('[data-appointment-notes-count]');
        const errorSummary = form.querySelector('[data-appointment-error-summary]');
        const sections = [...form.querySelectorAll('[data-booking-section]')];
        const steps = [...form.querySelectorAll('[data-booking-step]')];
        const reviewCentre = form.querySelector('[data-review-centre]');
        const reviewDate = form.querySelector('[data-review-date]');
        const reviewTime = form.querySelector('[data-review-time]');
        const reviewLocation = form.querySelector('[data-review-location]');
        const selectedValue = centreSelect?.value || '';
        const requestedCentre = new URLSearchParams(window.location.search).get('centre') || '';

        if (
            !centreSelect
            || !requestFields
            || !dateInput
            || !timeInput
            || !submitLabel
        ) {
            return;
        }

        const centres = Array.isArray(config.centres) ? config.centres : [];

        const activeCentres = centres
            .filter((centre) => centre && centre.active !== false && String(centre.name || '').trim())
            .sort((first, second) => String(first.region || '').localeCompare(String(second.region || ''))
                || String(first.township || '').localeCompare(String(second.township || ''))
                || String(first.name || '').localeCompare(String(second.name || '')));

        centreSelect.replaceChildren();

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = labels.chooseCentre || 'Choose an active centre';
        centreSelect.append(placeholder);

        for (const centre of activeCentres) {
            const option = document.createElement('option');
            const name = String(centre.name || '').trim();
            const area = [centre.township, centre.region].filter(Boolean).join(', ');
            option.value = name;
            option.textContent = area ? `${name} · ${area}` : name;
            centreSelect.append(option);
        }

        const requestOption = document.createElement('option');
        requestOption.value = requestValue;
        requestOption.textContent = labels.requestLocation || 'No nearby centre — request my location';
        centreSelect.append(requestOption);

        const preferredValue = selectedValue || requestedCentre;

        if ([...centreSelect.options].some((option) => option.value === preferredValue)) {
            centreSelect.value = preferredValue;
        }

        const fieldIsComplete = (field) => {
            if (field.type === 'checkbox') {
                return field.checked;
            }

            return String(field.value || '').trim() !== '' && field.validity.valid;
        };

        const requiredFieldsFor = (section) => (
            [...section.querySelectorAll('[required]:not(:disabled)')]
        );

        const sectionIsComplete = (section) => {
            const requiredFields = requiredFieldsFor(section);
            return requiredFields.length > 0 && requiredFields.every(fieldIsComplete);
        };

        const firstIncompleteField = (section) => (
            requiredFieldsFor(section).find((field) => !fieldIsComplete(field)) || null
        );

        const canAccessSection = (sectionNumber) => sections
            .filter((section) => Number(section.dataset.bookingSection) < sectionNumber)
            .every(sectionIsComplete);

        let currentStep = 1;

        const setCurrentStep = (sectionNumber) => {
            currentStep = sectionNumber;

            for (const step of steps) {
                const number = Number(step.dataset.bookingStep);
                const section = sections.find(
                    (candidate) => Number(candidate.dataset.bookingSection) === number,
                );

                step.classList.toggle('active', number === sectionNumber);
                step.classList.toggle('is-complete', Boolean(section && sectionIsComplete(section)));
            }
        };

        const refreshSectionAccess = (preferredStep = currentStep) => {
            for (const section of sections) {
                const number = Number(section.dataset.bookingSection);
                const locked = !canAccessSection(number);
                section.classList.toggle('is-locked', locked);
                section.setAttribute('aria-disabled', String(locked));

                let notice = section.querySelector('[data-booking-lock-notice]');

                if (number > 1 && !notice) {
                    notice = document.createElement('div');
                    notice.className = 'booking-section-lock';
                    notice.dataset.bookingLockNotice = '';
                    notice.setAttribute('role', 'note');
                    notice.innerHTML = '<span aria-hidden="true">!</span><p></p>';
                    section.prepend(notice);
                }

                if (notice) {
                    notice.hidden = !locked;
                    notice.querySelector('p').textContent = labels.completePreviousMessage
                        || 'Complete the previous section before continuing.';
                }
            }

            const accessibleStep = canAccessSection(preferredStep)
                ? preferredStep
                : Number(sections.find((section) => !sectionIsComplete(section))?.dataset.bookingSection || 1);
            setCurrentStep(accessibleStep);
        };

        const showSectionLockedAlert = (sectionNumber) => {
            const blockedBy = sections
                .filter((section) => Number(section.dataset.bookingSection) < sectionNumber)
                .find((section) => !sectionIsComplete(section));
            const firstField = blockedBy ? firstIncompleteField(blockedBy) : null;

            if (window.BloodCareUI?.showValidationAlert) {
                window.BloodCareUI.showValidationAlert(
                    firstField ? [firstField] : [],
                    labels.completePreviousMessage || 'Complete the previous section before continuing.',
                    labels.completePreviousTitle || 'Complete the previous section first',
                );
            } else {
                firstField?.focus();
            }
        };

        const formatDate = (value) => {
            if (!value) {
                return labels.notSelected || 'Not selected yet';
            }

            const parsed = new Date(`${value}T12:00:00`);

            if (Number.isNaN(parsed.getTime())) {
                return value;
            }

            return new Intl.DateTimeFormat(config.locale || 'en-GB', {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            }).format(parsed);
        };

        const updateReview = () => {
            const requestingLocation = centreSelect.value === requestValue;
            const empty = labels.notSelected || 'Not selected yet';

            if (reviewCentre) {
                reviewCentre.textContent = requestingLocation
                    ? (labels.locationRequest || 'Location request')
                    : (centreSelect.selectedOptions[0]?.textContent || empty);
            }

            if (reviewDate) {
                reviewDate.textContent = requestingLocation ? '—' : formatDate(dateInput.value);
            }

            if (reviewTime) {
                reviewTime.textContent = requestingLocation ? '—' : (timeInput.value || empty);
            }

            if (reviewLocation) {
                const location = [regionInput?.value, townshipInput?.value]
                    .map((value) => String(value || '').trim())
                    .filter(Boolean)
                    .join(labels.locationSeparator || ' · ');
                reviewLocation.hidden = !requestingLocation;
                reviewLocation.textContent = location || empty;
            }
        };

        const updateNotesCount = () => {
            if (notesInput && notesCount) {
                notesCount.textContent = `${notesInput.value.length} / ${notesInput.maxLength}`;
            }
        };

        const updateBookingMode = () => {
            const requestingLocation = centreSelect.value === requestValue;
            requestFields.hidden = !requestingLocation;

            for (const input of requestInputs) {
                input.required = requestingLocation;
                input.disabled = !requestingLocation;
            }

            for (const field of slotFields) {
                field.hidden = requestingLocation;
            }

            dateInput.required = !requestingLocation;
            dateInput.disabled = requestingLocation;
            timeInput.required = !requestingLocation;
            timeInput.disabled = requestingLocation;
            submitLabel.textContent = requestingLocation
                ? (labels.sendLocationRequest || 'Send location request')
                : (labels.reviewAppointment || 'Review appointment');
            updateReview();
            refreshSectionAccess(currentStep);
        };

        for (const section of sections) {
            const sectionNumber = Number(section.dataset.bookingSection);

            const guardSection = (event) => {
                if (canAccessSection(sectionNumber)) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                showSectionLockedAlert(sectionNumber);
            };

            section.addEventListener('pointerdown', guardSection, true);
            section.addEventListener('focusin', (event) => {
                if (!canAccessSection(sectionNumber)) {
                    guardSection(event);
                    return;
                }

                setCurrentStep(sectionNumber);
            }, true);
            section.addEventListener('input', () => {
                setCurrentStep(sectionNumber);
                refreshSectionAccess(sectionNumber);
                updateReview();
            });
            section.addEventListener('change', () => {
                setCurrentStep(sectionNumber);
                refreshSectionAccess(sectionNumber);
                updateReview();
            });
        }

        centreSelect.addEventListener('change', updateBookingMode);
        notesInput?.addEventListener('input', updateNotesCount);
        updateBookingMode();
        updateNotesCount();
        refreshSectionAccess(1);

        if (errorSummary) {
            window.requestAnimationFrame(() => errorSummary.focus());
        }

        form.addEventListener('submit', () => {
            if (!form.checkValidity() || !submitButton || submitButton.disabled) {
                return;
            }

            submitButton.disabled = true;
            submitButton.setAttribute('aria-busy', 'true');
            submitLabel.textContent = form.dataset.submittingLabel || submitLabel.textContent;
        });
    }

    function initializeRegistration(form) {
        const sections = [...form.querySelectorAll('[data-registration-section]')];
        const steps = [...form.querySelectorAll('[data-registration-step]')];
        const submitButton = form.querySelector('[data-registration-submit]');
        const submitLabel = form.querySelector('[data-registration-submit-label]');
        const notesInput = form.querySelector('[data-character-count-input]');
        const notesCount = form.querySelector('[data-character-count]');
        const errorSummary = form.querySelector('[data-registration-error-summary]');
        const nrcConfigNode = document.getElementById('bc-public-nrc-config');
        const phoneLocal = form.querySelector('[name="phone_local"]');
        const phoneFull = form.querySelector('[name="phone"][data-public-phone-full]');

        const syncFullPhone = () => {
            if (!phoneLocal || !phoneFull) return;
            const local = String(phoneLocal.value || '').replace(/[^0-9]/g, '');
            phoneFull.value = local ? `+95 ${local}` : '';
        };

        const isFilled = (field) => {
            if (field.type === 'checkbox' || field.type === 'radio') {
                return field.checked;
            }

            return String(field.value || '').trim() !== '' && field.validity.valid;
        };

        const sectionIsComplete = (section) => {
            const requiredFields = [...section.querySelectorAll('[required]:not(:disabled)')];
            return requiredFields.length > 0 && requiredFields.every(isFilled);
        };

        const setCurrentStep = (sectionNumber) => {
            for (const step of steps) {
                const number = Number(step.dataset.registrationStep);
                const section = sections.find(
                    (candidate) => Number(candidate.dataset.registrationSection) === number,
                );

                step.classList.toggle('active', number === sectionNumber);
                step.classList.toggle('is-complete', Boolean(section && sectionIsComplete(section)));
            }
        };

        const updateCharacterCount = () => {
            if (!notesInput || !notesCount) {
                return;
            }

            notesCount.textContent = `${notesInput.value.length} / ${notesInput.maxLength}`;
        };

        for (const section of sections) {
            section.addEventListener('focusin', () => {
                setCurrentStep(Number(section.dataset.registrationSection));
            });
            section.addEventListener('input', () => {
                setCurrentStep(Number(section.dataset.registrationSection));
            });
            section.addEventListener('change', () => {
                setCurrentStep(Number(section.dataset.registrationSection));
            });
        }

        notesInput?.addEventListener('input', updateCharacterCount);
        phoneLocal?.addEventListener('input', syncFullPhone);
        updateCharacterCount();
        syncFullPhone();

        if (nrcConfigNode) {
            initializeNrcFields(form, JSON.parse(nrcConfigNode.textContent));
        }

        setCurrentStep(1);

        if (errorSummary) {
            window.requestAnimationFrame(() => errorSummary.focus());
        }

        form.addEventListener('submit', () => {
            syncFullPhone();
            if (!form.checkValidity() || !submitButton || submitButton.disabled) {
                return;
            }

            submitButton.disabled = true;
            submitButton.setAttribute('aria-busy', 'true');

            if (submitLabel) {
                submitLabel.textContent = form.dataset.submittingLabel || submitLabel.textContent;
            }
        });
    }

    function initializeNrcFields(form, config) {
        const documentType = form.querySelector('[data-identity-document-type]');
        const nrcFields = form.querySelector('[data-nrc-fields]');
        const passportFields = form.querySelector('[data-passport-fields]');
        const state = form.querySelector('[data-nrc-state]');
        const township = form.querySelector('[data-nrc-township]');
        const type = form.querySelector('[data-nrc-type]');
        const serial = form.querySelector('[data-nrc-serial]');
        const preview = form.querySelector('[data-nrc-preview]');
        const townships = config.townships || {};
        const labels = config.labels || {};
        const isMyanmar = document.documentElement.lang.toLowerCase().startsWith('my');
        const myanmarDigits = {
            '၀': '0',
            '၁': '1',
            '၂': '2',
            '၃': '3',
            '၄': '4',
            '၅': '5',
            '၆': '6',
            '၇': '7',
            '၈': '8',
            '၉': '9',
        };

        if (
            !documentType
            || !nrcFields
            || !passportFields
            || !state
            || !township
            || !type
            || !serial
            || !preview
        ) {
            return;
        }

        const townshipLabel = (option) => {
            const english = option.display || option.value || '';
            const myanmarCode = option.myanmarCode || '';
            const myanmarName = option.myanmarName || '';

            if (isMyanmar) {
                return `${myanmarCode} — ${myanmarName} (${english})`;
            }

            return `${english} (${myanmarCode}) — ${myanmarName}`;
        };

        const populateTownships = (selectedValue = '') => {
            const selectedState = state.value;
            const options = Array.isArray(townships[selectedState])
                ? townships[selectedState]
                : [];
            const placeholder = document.createElement('option');

            township.replaceChildren();
            placeholder.value = '';
            placeholder.textContent = selectedState
                ? (labels.selectTownship || 'Select township code')
                : (labels.selectStateFirst || 'Select a state or region first');
            township.append(placeholder);

            for (const optionData of options) {
                const option = document.createElement('option');
                option.value = optionData.value;
                option.textContent = townshipLabel(optionData);
                option.dataset.display = optionData.display || optionData.value;
                township.append(option);
            }

            if ([...township.options].some((option) => option.value === selectedValue)) {
                township.value = selectedValue;
            }

            township.disabled = documentType.value !== 'nrc' || selectedState === '';
        };

        const toggleGroup = (container, enabled) => {
            container.hidden = !enabled;
            container.querySelectorAll('input, select').forEach((field) => {
                field.disabled = !enabled;
                field.required = enabled;
            });
        };

        const updatePreview = () => {
            const townshipDisplay = township.selectedOptions[0]?.dataset.display || '';
            const cleanSerial = [...serial.value]
                .map((character) => myanmarDigits[character] || character)
                .join('')
                .replace(/\D/g, '')
                .slice(0, 6);
            serial.value = cleanSerial;

            preview.textContent = state.value
                && township.value
                && townshipDisplay
                && type.value
                && cleanSerial.length === 6
                ? `${state.value}/${townshipDisplay}(${type.value})${cleanSerial}`
                : (labels.previewEmpty || 'Complete all four NRC fields');
        };

        const updateDocumentMode = () => {
            const usingNrc = documentType.value === 'nrc';
            toggleGroup(nrcFields, usingNrc);
            toggleGroup(passportFields, !usingNrc);
            populateTownships(township.value || township.dataset.selectedValue || '');
            updatePreview();
        };

        const initialTownship = township.dataset.selectedValue || township.value;
        populateTownships(initialTownship);
        township.dataset.selectedValue = '';
        updateDocumentMode();

        documentType.addEventListener('change', updateDocumentMode);
        state.addEventListener('change', () => {
            populateTownships();
            updatePreview();
        });
        township.addEventListener('change', updatePreview);
        type.addEventListener('change', updatePreview);
        serial.addEventListener('input', updatePreview);
    }

    function initializeEligibilityChecker(form, config) {
        const questions = [...form.querySelectorAll('[data-eligibility-question]')];
        const result = form.querySelector('[data-eligibility-result]');
        const title = form.querySelector('[data-eligibility-result-title]');
        const textNode = form.querySelector('[data-eligibility-result-text]');
        const link = form.querySelector('[data-eligibility-result-link]');
        const labels = config.labels || {};

        const showResult = (state, heading, text) => {
            result.hidden = false;
            result.dataset.state = state;
            title.textContent = heading;
            textNode.textContent = text;
            link.hidden = state === 'incomplete';
            result.focus();
        };

        form.addEventListener('submit', (event) => {
            event.preventDefault();

            const answers = questions.map(
                (question) => question.querySelector('input[type="radio"]:checked')?.value || '',
            );

            questions.forEach((question, index) => {
                question.classList.toggle('has-error', !answers[index]);
            });

            if (answers.some((answer) => !answer)) {
                showResult('incomplete', labels.answerAllTitle, labels.answerAllText);
                questions.find((question, index) => !answers[index])
                    ?.querySelector('input[type="radio"]')
                    ?.focus();
                return;
            }

            if (answers.some((answer) => answer === 'yes')) {
                showResult('review', labels.reviewTitle, labels.reviewText);
            } else {
                showResult('ready', labels.readyTitle, labels.readyText);
            }
        });

        form.addEventListener('change', (event) => {
            event.target.closest('[data-eligibility-question]')?.classList.remove('has-error');
        });

        form.addEventListener('reset', () => {
            window.setTimeout(() => {
                result.hidden = true;
                delete result.dataset.state;
                questions.forEach((question) => question.classList.remove('has-error'));
            }, 0);
        });
    }

    function initializeHome(config) {
        const formatNumber = new Intl.NumberFormat(config.locale === 'my' ? 'my-MM' : 'en-US');
        const priorityContainer = document.querySelector('[data-public-priority-groups]');
        const prioritySource = document.querySelector('[data-public-priority-source]');
        const centreList = document.querySelector('[data-public-centre-list]');
        const statsSource = document.querySelector('[data-public-stats-source]');
        const counts = config.inventoryCounts || {};
        const priorityGroups = Array.isArray(config.priorityGroups) ? config.priorityGroups : [];
        const centres = Array.isArray(config.centres) ? config.centres : [];

        if (priorityContainer && priorityGroups.length > 0) {
            priorityContainer.replaceChildren(...priorityGroups.map((group) => {
                const pill = document.createElement('span');
                pill.className = 'blood-pill';
                pill.textContent = String(group).replace('-', '−');
                pill.title = `${formatNumber.format(Number(counts[group] || 0))} available`;
                return pill;
            }));
        }

        if (prioritySource) {
            prioritySource.textContent = config.labels.livePriorities;
        }

        for (const [name, value] of Object.entries(config.stats || {})) {
            const element = document.querySelector(`[data-public-stat="${name}"]`);

            if (element && Number.isFinite(Number(value))) {
                element.textContent = formatNumber.format(Number(value));
            }
        }

        if (statsSource) {
            statsSource.textContent = config.labels.liveTotals;
        }

        if (centreList && centres.length > 0) {
            centreList.innerHTML = centres.slice(0, 6).map((centre) => {
                const name = String(centre.name || '').trim();
                const area = [centre.township, centre.region].filter(Boolean).join(' · ');
                const detail = area || centre.address || centre.hours || config.labels.locationUnavailable;
                const bookingUrl = centre.bookingUrl || config.bookingUrl;

                return `
                    <article class="public-centre-card is-visible">
                        <span class="public-centre-icon" aria-hidden="true">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                                <path d="M20 10c0 5.5-8 11-8 11s-8-5.5-8-11a8 8 0 1 1 16 0Z" stroke="currentColor" stroke-width="1.8"/>
                                <circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.8"/>
                            </svg>
                        </span>
                        <div>
                            <h3>${escapeHtml(name)}</h3>
                            <p>${escapeHtml(detail)}</p>
                            <small>${escapeHtml(config.labels.activeCentre)}</small>
                        </div>
                        <a href="${escapeAttribute(bookingUrl)}" aria-label="${escapeAttribute(`${config.labels.bookAt}: ${name}`)}">
                            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M5 12h14M14 7l5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </article>
                `;
            }).join('');
        }
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function escapeAttribute(value) {
        return escapeHtml(value).replaceAll('`', '&#096;');
    }
});
