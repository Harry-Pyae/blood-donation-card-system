(() => {
    'use strict';

    const modals = [...document.querySelectorAll('[data-bc-clinical-modal]')];
    if (!modals.length) return;

    let returnFocus = null;

    const visibleModal = () => modals.find((modal) => !modal.hidden) || null;

    function closeModal(modal, restoreFocus = true) {
        if (!modal || modal.hidden) return;

        modal.hidden = true;
        if (!visibleModal()) document.body.classList.remove('bc-modal-open');

        if (restoreFocus && returnFocus instanceof HTMLElement) {
            returnFocus.focus();
            returnFocus = null;
        }
    }

    function openModal(key) {
        const modal = modals.find((candidate) => candidate.dataset.bcClinicalModal === String(key));
        if (!modal) return;

        const active = visibleModal();
        if (!active) {
            returnFocus = document.activeElement;
        } else if (active !== modal) {
            closeModal(active, false);
        }

        modal.hidden = false;
        document.body.classList.add('bc-modal-open');
        window.requestAnimationFrame(() => (modal.querySelector('.bc-modal-close') || modal.querySelector('[tabindex="-1"]'))?.focus());
    }

    const focusableElements = (modal) => [...modal.querySelectorAll(
        'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
    )].filter((element) => !element.hidden && element.getClientRects().length > 0);

    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-bc-clinical-open]');
        if (opener) {
            openModal(opener.dataset.bcClinicalOpen);
            return;
        }

        const closer = event.target.closest('[data-bc-clinical-close]');
        if (closer) closeModal(closer.closest('[data-bc-clinical-modal]'));
    });

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('.bc-lab-modal-form');
        if (!form) return;

        if (form.dataset.bcSubmitting === 'true') {
            event.preventDefault();
            return;
        }

        form.dataset.bcSubmitting = 'true';
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.setAttribute('aria-disabled', 'true');
        }
    });

    document.addEventListener('keydown', (event) => {
        const modal = visibleModal();
        if (!modal) return;

        if (event.key === 'Escape') {
            const select = event.target.closest('[data-bc-national-select]');
            if (select?.classList.contains('open')) return;

            event.preventDefault();
            closeModal(modal);
            return;
        }

        if (event.key !== 'Tab') return;
        const focusable = focusableElements(modal);
        if (!focusable.length) return;

        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }, true);
})();
