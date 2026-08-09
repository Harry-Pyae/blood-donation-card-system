(() => {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const registerPage = document.querySelector('.bc-register-page');

        for (const button of document.querySelectorAll('[data-password-toggle]')) {
            const input = document.getElementById(button.dataset.passwordTarget || '');
            if (!input) continue;

            button.addEventListener('click', () => {
                const revealing = input.type === 'password';
                input.type = revealing ? 'text' : 'password';
                button.setAttribute('aria-pressed', String(revealing));
                button.setAttribute('aria-label', revealing
                    ? (registerPage?.dataset.hidePassword || button.dataset.hideLabel || 'Hide password')
                    : (registerPage?.dataset.showPassword || button.dataset.showLabel || 'Show password'));
                const icon = button.querySelector('i');
                if (icon) icon.className = revealing ? 'la la-eye-slash' : 'la la-eye';
                input.focus();
            });
        }

        if (!registerPage) {
            return;
        }

        const password = document.getElementById('register-password');
        const confirmation = document.getElementById('password-confirmation');
        const strengthBar = document.getElementById('bc-register-password-strength-bar');
        const strengthLabel = document.getElementById('bc-register-password-strength-label');
        const matchMessage = document.getElementById('bc-register-password-match');

        if (!password || !confirmation || !strengthBar || !strengthLabel || !matchMessage) {
            return;
        }

        const strengthLabels = [
            registerPage.dataset.strengthWeak || 'Weak',
            registerPage.dataset.strengthFair || 'Fair',
            registerPage.dataset.strengthGood || 'Good',
            registerPage.dataset.strengthStrong || 'Strong',
        ];
        const invalidPasswordMessage = registerPage.dataset.passwordInvalid
            || 'Use at least 8 characters with uppercase, lowercase and a number.';
        const passwordsMatchMessage = registerPage.dataset.passwordsMatch || 'Passwords match.';
        const passwordsMismatchMessage = registerPage.dataset.passwordsMismatch || 'Passwords do not match.';

        const getRules = (value) => ({
            length: value.length >= 8,
            lower: /[a-z]/.test(value),
            upper: /[A-Z]/.test(value),
            number: /\d/.test(value),
        });

        const renderPassword = () => {
            const rules = getRules(password.value);
            const score = Object.values(rules).filter(Boolean).length;
            const level = Math.max(0, score - 1);

            strengthBar.style.setProperty('--bc-password-strength', `${score * 25}%`);
            strengthBar.dataset.level = String(level);
            strengthLabel.textContent = strengthLabels[level];

            Object.entries(rules).forEach(([rule, complete]) => {
                const item = registerPage.querySelector(`[data-register-password-rule="${rule}"]`);
                const icon = item?.querySelector('i');

                item?.classList.toggle('is-complete', complete);
                icon?.classList.toggle('la-circle', !complete);
                icon?.classList.toggle('la-check-circle', complete);
            });

            const isValid = Object.values(rules).every(Boolean);
            password.setCustomValidity(password.value.length > 0 && !isValid ? invalidPasswordMessage : '');
            password.setAttribute('aria-invalid', String(password.value.length > 0 && !isValid));
        };

        const renderMatch = () => {
            const hasConfirmation = confirmation.value.length > 0;
            const matches = hasConfirmation && password.value === confirmation.value;

            matchMessage.textContent = hasConfirmation
                ? (matches ? passwordsMatchMessage : passwordsMismatchMessage)
                : '';
            matchMessage.classList.toggle('is-valid', matches);
            matchMessage.classList.toggle('is-invalid', hasConfirmation && !matches);
            confirmation.setCustomValidity(hasConfirmation && !matches ? passwordsMismatchMessage : '');
            confirmation.setAttribute('aria-invalid', String(hasConfirmation && !matches));
        };

        password.addEventListener('input', () => {
            renderPassword();
            renderMatch();
        });
        confirmation.addEventListener('input', renderMatch);

        renderPassword();
        renderMatch();
    });
})();
