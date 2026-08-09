(() => {
    'use strict';

    const tools = [...document.querySelectorAll('[data-bc-notification-tool][data-count-url]')];
    if (tools.length === 0) return;

    function render(tool, count) {
        const badge = tool.querySelector('[data-bc-notification-count]');
        if (!badge) return;

        badge.textContent = count > 99 ? '99+' : String(count);
        badge.hidden = count < 1;
        const template = tool.dataset.countLabel || '';
        if (template) tool.setAttribute('aria-label', template.replace('__COUNT__', String(count)));
    }

    async function refresh(tool) {
        try {
            const response = await fetch(tool.dataset.countUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            if (!response.ok) return;
            const payload = await response.json();
            const count = Number.parseInt(payload.count, 10);
            if (Number.isFinite(count) && count >= 0) render(tool, count);
        } catch (_) {
            // The badge is progressive enhancement; a temporary network
            // failure must never interrupt the staff workspace.
        }
    }

    function refreshAll() {
        if (document.visibilityState !== 'visible') return;
        tools.forEach((tool) => refresh(tool));
    }

    refreshAll();
    window.setInterval(refreshAll, 10000);
    document.addEventListener('visibilitychange', refreshAll);
    window.addEventListener('focus', refreshAll);
})();
