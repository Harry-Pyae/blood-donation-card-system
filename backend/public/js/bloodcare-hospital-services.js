(() => {
    'use strict';

    const modals = Array.from(document.querySelectorAll('[data-bc-request-modal]'));

    if (!modals.length) {
        return;
    }

    let lastFocusedElement = null;

    function visibleModal() {
        return modals.find((modal) => !modal.hidden) || null;
    }

    function closeModal(modal, restoreFocus = true) {
        if (!modal || modal.hidden) {
            return;
        }

        modal.hidden = true;
        document.body.classList.remove('bc-modal-open');

        if (restoreFocus && lastFocusedElement instanceof HTMLElement) {
            lastFocusedElement.focus();
        }
    }

    function openModal(requestId, rejectMode = false) {
        const modal = modals.find((candidate) => candidate.dataset.bcRequestModal === String(requestId));

        if (!modal) {
            return;
        }

        const active = visibleModal();
        if (active && active !== modal) {
            closeModal(active, false);
        }

        lastFocusedElement = document.activeElement;
        modal.hidden = false;
        document.body.classList.add('bc-modal-open');

        window.requestAnimationFrame(() => {
            const target = rejectMode
                ? modal.querySelector('[data-bc-reject-reason]')
                : modal.querySelector('.bc-modal-close');
            (target || modal.querySelector('.bc-request-detail-dialog'))?.focus();
        });
    }

    function focusableElements(modal) {
        return Array.from(modal.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
        )).filter((element) => !element.hidden && element.getClientRects().length > 0);
    }

    document.addEventListener('click', (event) => {
        const openButton = event.target.closest('[data-bc-request-open]');
        if (openButton) {
            openModal(openButton.dataset.bcRequestOpen);
            return;
        }

        const rejectButton = event.target.closest('[data-bc-request-reject]');
        if (rejectButton) {
            openModal(rejectButton.dataset.bcRequestReject, true);
            return;
        }

        const closeButton = event.target.closest('[data-modal-close]');
        if (closeButton) {
            const modal = closeButton.closest('[data-bc-request-modal]');
            if (modal) {
                closeModal(modal);
            }
        }
    });

    document.addEventListener('keydown', (event) => {
        const modal = visibleModal();
        if (!modal) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            closeModal(modal);
            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const focusable = focusableElements(modal);
        if (!focusable.length) {
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });
})();
