(() => {
    'use strict';

    const page = document.querySelector('.bc-profile-page');

    if (!page) {
        return;
    }

    const text = {
        showPassword: page.dataset.showPassword || 'Show password',
        hidePassword: page.dataset.hidePassword || 'Hide password',
        strength: [
            page.dataset.strengthWeak || 'Weak',
            page.dataset.strengthFair || 'Fair',
            page.dataset.strengthGood || 'Good',
            page.dataset.strengthStrong || 'Strong',
        ],
        passwordsMatch: page.dataset.passwordsMatch || 'Passwords match.',
        passwordsMismatch: page.dataset.passwordsMismatch || 'Passwords do not match.',
    };

    const setupPasswordToggles = () => {
        page.querySelectorAll('[data-password-toggle]').forEach((button) => {
            const input = document.getElementById(button.dataset.passwordToggle);
            const icon = button.querySelector('i');

            if (!input) {
                return;
            }

            button.addEventListener('click', () => {
                const willShow = input.type === 'password';
                const label = willShow ? text.hidePassword : text.showPassword;

                input.type = willShow ? 'text' : 'password';
                button.setAttribute('aria-label', label);
                button.title = label;
                button.setAttribute('aria-pressed', String(willShow));

                if (icon) {
                    icon.classList.toggle('la-eye', !willShow);
                    icon.classList.toggle('la-eye-slash', willShow);
                }

                input.focus({ preventScroll: true });
            });
        });
    };

    const passwordPanel = page.querySelector('[data-bc-password-panel]');
    const passwordPanelToggle = page.querySelector('[data-bc-password-panel-toggle]');
    const passwordPanelClose = page.querySelector('[data-bc-password-panel-close]');

    const setPasswordPanelOpen = (open, focus = false) => {
        if (!passwordPanel || !passwordPanelToggle) {
            return;
        }

        passwordPanel.hidden = !open;
        passwordPanelToggle.setAttribute('aria-expanded', String(open));

        if (open && focus) {
            passwordPanel.querySelector('input')?.focus({ preventScroll: true });
        } else if (!open && focus) {
            passwordPanelToggle.focus({ preventScroll: true });
        }
    };

    passwordPanelToggle?.addEventListener('click', () => {
        setPasswordPanelOpen(passwordPanel?.hidden ?? true, true);
    });
    passwordPanelClose?.addEventListener('click', () => setPasswordPanelOpen(false, true));
    setPasswordPanelOpen(page.dataset.openPasswordPanel === 'true');

    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const strengthBar = document.getElementById('bc-password-strength-bar');
    const strengthLabel = document.getElementById('bc-password-strength-label');
    const matchMessage = document.getElementById('bc-password-match');
    const passwordForm = newPassword?.closest('form');

    const getRules = (value) => ({
        length: value.length >= 8,
        lower: /[a-z]/.test(value),
        upper: /[A-Z]/.test(value),
        number: /\d/.test(value),
        symbol: /[^A-Za-z0-9]/.test(value),
    });

    const renderStrength = () => {
        if (!newPassword || !strengthBar || !strengthLabel) {
            return;
        }

        const value = newPassword.value;
        const rules = getRules(value);
        const score = Object.values(rules).filter(Boolean).length;
        const level = score <= 1 ? 0 : score <= 3 ? 1 : score === 4 ? 2 : 3;

        strengthBar.style.setProperty('--bc-password-strength', `${score * 20}%`);
        strengthBar.dataset.level = String(level);
        strengthLabel.textContent = text.strength[level];

        Object.entries(rules).forEach(([rule, complete]) => {
            const item = page.querySelector(`[data-password-rule="${rule}"]`);
            const icon = item?.querySelector('i');

            item?.classList.toggle('is-complete', complete);
            icon?.classList.toggle('la-circle', !complete);
            icon?.classList.toggle('la-check-circle', complete);
        });
    };

    const renderMatch = () => {
        if (!newPassword || !confirmPassword || !matchMessage) {
            return;
        }

        const hasConfirmation = confirmPassword.value.length > 0;
        const matches = hasConfirmation && newPassword.value === confirmPassword.value;

        matchMessage.textContent = hasConfirmation
            ? (matches ? text.passwordsMatch : text.passwordsMismatch)
            : '';
        matchMessage.classList.toggle('is-valid', matches);
        matchMessage.classList.toggle('is-invalid', hasConfirmation && !matches);
        confirmPassword.setCustomValidity(hasConfirmation && !matches ? text.passwordsMismatch : '');
    };

    const preventDoubleSubmit = (form) => {
        form.addEventListener('submit', (event) => {
            if (!form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
                return;
            }

            const submit = form.querySelector('button[type="submit"]');

            if (submit) {
                submit.disabled = true;
                submit.setAttribute('aria-busy', 'true');
            }
        });
    };

    setupPasswordToggles();

    if (newPassword && confirmPassword) {
        newPassword.addEventListener('input', () => {
            renderStrength();
            renderMatch();
        });
        confirmPassword.addEventListener('input', renderMatch);
        renderStrength();
        renderMatch();
    }

    page.querySelectorAll('form').forEach(preventDoubleSubmit);

    passwordForm?.addEventListener('reset', () => {
        window.requestAnimationFrame(() => {
            renderStrength();
            renderMatch();
        });
    });
})();
