(() => {
    'use strict';

    const modals = [...document.querySelectorAll('[data-bc-hospital-modal]')];
    if (!modals.length) return;

    let lastFocused = null;

    const openModal = (key) => {
        const modal = modals.find((item) => item.dataset.bcHospitalModal === key);
        if (!modal) return;
        lastFocused = document.activeElement;
        modal.hidden = false;
        document.body.classList.add('bc-modal-open');
        window.requestAnimationFrame(() => modal.querySelector('.bc-modal-close, input:not([type="hidden"]), button')?.focus());
    };

    const closeModal = (modal) => {
        if (!modal || modal.hidden) return;
        modal.hidden = true;
        document.body.classList.remove('bc-modal-open');
        if (lastFocused instanceof HTMLElement) lastFocused.focus();
    };

    const currentModal = () => modals.find((item) => !item.hidden) || null;

    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-bc-hospital-open]');
        if (opener) {
            openModal(opener.dataset.bcHospitalOpen);
            return;
        }

        const closer = event.target.closest('[data-modal-close]');
        if (closer) closeModal(closer.closest('[data-bc-hospital-modal]'));
    });

    document.addEventListener('keydown', (event) => {
        const modal = currentModal();
        if (!modal) return;

        if (event.key === 'Escape') {
            event.preventDefault();
            closeModal(modal);
            return;
        }

        if (event.key !== 'Tab') return;
        const focusable = [...modal.querySelectorAll('button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), a[href]')]
            .filter((item) => item.getClientRects().length > 0);
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
    });
})();
