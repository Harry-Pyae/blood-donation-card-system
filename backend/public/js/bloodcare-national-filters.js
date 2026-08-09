(() => {
    'use strict';

    const widgets = [...document.querySelectorAll('[data-bc-national-select]')];

    if (widgets.length === 0) {
        return;
    }

    function closeAll(except = null) {
        for (const widget of widgets) {
            if (widget === except) {
                continue;
            }

            widget.classList.remove('open');
            widget.querySelector('.bc-filter-dropdown-trigger')?.setAttribute('aria-expanded', 'false');

            const menu = widget.querySelector('.bc-filter-dropdown-menu');
            if (menu) {
                menu.hidden = true;
            }
        }
    }

    for (const widget of widgets) {
        const select = widget.querySelector('select');
        const trigger = widget.querySelector('.bc-filter-dropdown-trigger');
        const menu = widget.querySelector('.bc-filter-dropdown-menu');
        const output = widget.querySelector('[data-bc-select-label]');

        if (!select || !trigger || !menu || !output) {
            continue;
        }

        const optionButtons = () => [...menu.querySelectorAll('[role="option"]')];

        function selectedButton() {
            return optionButtons().find((option) => option.dataset.value === select.value) || optionButtons()[0];
        }

        function sync() {
            const selected = selectedButton();
            if (!selected) {
                return;
            }

            output.textContent = selected.querySelector('span')?.textContent.trim() || selected.textContent.trim();
            optionButtons().forEach((option) => option.setAttribute('aria-selected', String(option === selected)));
        }

        function close(restoreFocus = false) {
            widget.classList.remove('open');
            trigger.setAttribute('aria-expanded', 'false');
            menu.hidden = true;

            if (restoreFocus) {
                trigger.focus();
            }
        }

        function open() {
            closeAll(widget);
            sync();

            const selected = selectedButton();
            if (selected && menu.firstElementChild !== selected) {
                menu.prepend(selected);
            }

            widget.classList.add('open');
            trigger.setAttribute('aria-expanded', 'true');
            menu.hidden = false;
            menu.scrollTop = 0;
            selected?.focus({ preventScroll: true });
        }

        function choose(option) {
            select.value = option.dataset.value;
            sync();
            close(true);
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        trigger.addEventListener('click', () => (menu.hidden ? open() : close()));
        trigger.addEventListener('keydown', (event) => {
            if (!['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
                return;
            }

            event.preventDefault();
            open();
        });

        menu.addEventListener('click', (event) => {
            const option = event.target.closest('[role="option"]');
            if (option) {
                choose(option);
            }
        });

        menu.addEventListener('keydown', (event) => {
            const current = event.target.closest('[role="option"]');
            if (!current) {
                return;
            }

            const options = optionButtons();
            const index = options.indexOf(current);

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                const direction = event.key === 'ArrowDown' ? 1 : -1;
                options[(index + direction + options.length) % options.length].focus();
            } else if (event.key === 'Home' || event.key === 'End') {
                event.preventDefault();
                options[event.key === 'Home' ? 0 : options.length - 1].focus();
            } else if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                choose(current);
            } else if (event.key === 'Escape') {
                event.preventDefault();
                close(true);
            }
        });

        widget.addEventListener('focusout', (event) => {
            if (!event.relatedTarget || !widget.contains(event.relatedTarget)) {
                close();
            }
        });

        select.addEventListener('change', sync);
        sync();
    }

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-bc-national-select]')) {
            closeAll();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAll();
        }
    });
})();
