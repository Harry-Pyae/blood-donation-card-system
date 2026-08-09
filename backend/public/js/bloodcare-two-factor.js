(() => {
    'use strict';

    const qrTarget = document.querySelector('[data-bc-two-factor-qr]');
    if (qrTarget && typeof window.qrcode === 'function') {
        try {
            const value = qrTarget.getAttribute('data-bc-two-factor-qr') || '';
            const qr = window.qrcode(0, 'M');
            qr.addData(value);
            qr.make();
            qrTarget.innerHTML = qr.createSvgTag(5, 3);
        } catch (error) {
            qrTarget.classList.add('is-unavailable');
        }
    }

    const copyButton = document.querySelector('[data-bc-copy-recovery]');
    const codes = document.querySelector('[data-bc-recovery-codes]');
    copyButton?.addEventListener('click', async () => {
        if (!codes) return;
        const value = Array.from(codes.querySelectorAll('code')).map((item) => item.textContent?.trim() || '').filter(Boolean).join('\n');
        try {
            await navigator.clipboard.writeText(value);
            const label = copyButton.querySelector('span');
            if (label) label.textContent = copyButton.getAttribute('data-copy-success') || label.textContent;
        } catch (error) {
            window.prompt('Copy recovery codes', value);
        }
    });
})();
