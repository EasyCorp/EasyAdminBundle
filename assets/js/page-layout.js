// the width configured in PHP always wins; the width selected by users with the layout
// toggles is only remembered when there's no configured width
const eaLayoutWidth = (layoutPart) => {
    const configuredWidth = document.body.getAttribute(`data-ea-${layoutPart}-width`);
    if ('true' === document.body.getAttribute(`data-ea-${layoutPart}-width-configured`)) {
        return configuredWidth;
    }

    return localStorage.getItem(`ea/${layoutPart}/width`) || configuredWidth;
};
document.body.classList.add(
    `ea-content-width-${eaLayoutWidth('content')}`,
    `ea-sidebar-width-${eaLayoutWidth('sidebar')}`
);

// Bootstrap removes `.show` and destroys Popper synchronously when hiding a dropdown, so a
// CSS-only exit animation can't play: `hide.bs.dropdown` is prevented, the exit animation
// plays while `.show` and Popper positioning are still in place, then Bootstrap finishes hiding
(() => {
    const EXIT_CLASS = 'ea-dropdown-hiding';
    const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

    // same lookup as Bootstrap (the menu is next to the toggle), plus the ActionMenu overlay;
    // action groups use a '.btn-group' wrapper instead of '.dropdown'
    const menuFor = (toggle) =>
        toggle.parentElement?.querySelector(':scope > .dropdown-menu, :scope > .dropdown-overlay > .dropdown-menu');

    document.addEventListener('hide.bs.dropdown', (event) => {
        if (reducedMotionQuery.matches || undefined === window.bootstrap) {
            return;
        }

        const menu = menuFor(event.target);
        if (!menu) {
            return;
        }

        // the hide() call of finish() fires this event again
        if ('1' === menu.dataset.eaAllowHide) {
            delete menu.dataset.eaAllowHide;
            menu.classList.remove(EXIT_CLASS);

            return;
        }

        event.preventDefault();
        if (menu.classList.contains(EXIT_CLASS)) {
            return;
        }
        menu.classList.add(EXIT_CLASS);

        const onAnimationEnd = (animationEvent) => {
            // the animations of nested submenus bubble up to this menu
            if (animationEvent.target === menu) {
                finish();
            }
        };
        const finish = () => {
            clearTimeout(fallback);
            menu.removeEventListener('animationend', onAnimationEnd);
            // the menu was hidden without animation in the meantime
            if (!menu.classList.contains(EXIT_CLASS)) {
                return;
            }

            menu.dataset.eaAllowHide = '1';
            window.bootstrap.Dropdown.getOrCreateInstance(event.target).hide();
        };
        const fallback = setTimeout(finish, 250); // in case animationend never fires
        menu.addEventListener('animationend', onAnimationEnd);
    });

    const TOGGLE = '[data-bs-toggle="dropdown"]';
    const SUBMENU_HOVER_DELAY = 100;
    const dropdown = (toggle) => window.bootstrap.Dropdown.getOrCreateInstance(toggle);
    const isSubmenuToggle = (toggle) => null !== toggle.closest('.dropdown-menu');

    // switching from a submenu to another one closes the first one at once: with the exit
    // animation, both submenus would be visible at the same time for a moment
    const hideWithoutAnimation = (toggle) => {
        const menu = menuFor(toggle);
        if (menu) {
            menu.dataset.eaAllowHide = '1';
        }
        dropdown(toggle).hide();
    };
    const hideSubmenus = (menu, except = null) => {
        menu.querySelectorAll(':scope > li > .dropdown-toggle.show, :scope > li > div > .dropdown-toggle.show').forEach(
            (toggle) => {
                if (toggle !== except) {
                    hideWithoutAnimation(toggle);
                }
            }
        );
    };

    const menuItems = (menu) =>
        [...menu.querySelectorAll('.dropdown-item:not(.disabled, :disabled, .dropdown-toggle-split)')].filter(
            (item) => item.closest('.dropdown-menu') === menu
        );
    const openSubmenu = (toggle, focusFirstItem) => {
        hideSubmenus(toggle.closest('.dropdown-menu'), toggle);
        dropdown(toggle).show();
        if (focusFirstItem) {
            menuItems(menuFor(toggle))[0]?.focus();
        }
    };

    // menus open on pointerdown instead of on click, so they feel much faster; Bootstrap's
    // delegated listeners use the capture phase on document, so these listen on window to run first
    let toggleOpenedOnPointerDown = null;
    window.addEventListener(
        'pointerdown',
        (event) => {
            const toggle = event.target.closest?.(TOGGLE);
            if (
                undefined === window.bootstrap ||
                // with touch, a pointerdown can be the start of a scroll: those menus open on click
                'mouse' !== event.pointerType ||
                0 !== event.button ||
                event.ctrlKey ||
                !toggle ||
                isSubmenuToggle(toggle) ||
                toggle.matches(':disabled, .disabled')
            ) {
                return;
            }

            toggleOpenedOnPointerDown = toggle;
            // toggle the menu after the default action of mousedown, so the pointer focuses the
            // toggle first; otherwise the focus() call of Bootstrap displays the focus ring
            setTimeout(() => {
                document.querySelectorAll(`${TOGGLE}.show`).forEach((otherToggle) => {
                    if (otherToggle !== toggle && !isSubmenuToggle(otherToggle)) {
                        dropdown(otherToggle).hide();
                    }
                });
                dropdown(toggle).toggle();
            });
        },
        true
    );
    window.addEventListener('pointercancel', () => {
        toggleOpenedOnPointerDown = null;
    });
    window.addEventListener(
        'click',
        (event) => {
            const toggle = event.target.closest?.(TOGGLE);
            // keyboard clicks (detail = 0) are never preceded by the pointerdown that opened the menu,
            // even if the flag wasn't reset (e.g. when the pointer was released outside the window)
            const openedOnPointerDown = null !== toggle && toggle === toggleOpenedOnPointerDown && 0 !== event.detail;
            toggleOpenedOnPointerDown = null;
            if (undefined === window.bootstrap || !toggle) {
                return;
            }

            // the click that follows the pointerdown would toggle the menu again
            if (openedOnPointerDown) {
                event.preventDefault();
                event.stopPropagation();

                return;
            }

            // Bootstrap closes the parent menu when clicking a submenu toggle (because the click
            // happens inside it), so submenus are opened here
            if (isSubmenuToggle(toggle)) {
                event.preventDefault();
                event.stopPropagation();
                openSubmenu(toggle, 0 === event.detail);
            }
        },
        true
    );

    // Bootstrap's arrow keys open any submenu toggle they reach and stay inside that submenu, so
    // the items after it can't be reached; here, up and down only move through the items of the
    // current menu, and the horizontal arrows open and close submenus
    window.addEventListener(
        'keydown',
        (event) => {
            const item = event.target.closest?.('.dropdown-menu.show .dropdown-item');
            if (undefined === window.bootstrap || !item || event.altKey || event.ctrlKey || event.metaKey) {
                return;
            }

            const menu = item.closest('.dropdown-menu');
            const isRtl = 'rtl' === getComputedStyle(item).direction;
            const openKey = isRtl ? 'ArrowLeft' : 'ArrowRight';
            const closeKey = isRtl ? 'ArrowRight' : 'ArrowLeft';

            if ('ArrowDown' === event.key || 'ArrowUp' === event.key) {
                const items = menuItems(menu);
                // the split toggle isn't a stop of its own: it shares the row with its main action
                const current = item.matches('.dropdown-toggle-split') ? item.previousElementSibling : item;
                const next = items[items.indexOf(current) + ('ArrowDown' === event.key ? 1 : -1)];
                if (next) {
                    hideSubmenus(menu);
                    next.focus();
                }
            } else if (openKey === event.key) {
                const toggle = item.matches(TOGGLE)
                    ? item
                    : item.parentElement.querySelector(':scope > .dropdown-toggle-split');
                if (toggle) {
                    openSubmenu(toggle, true);
                }
            } else if (closeKey === event.key) {
                const parentToggle = menu.parentElement.querySelector(`:scope > ${TOGGLE}`);
                if (parentToggle && isSubmenuToggle(parentToggle)) {
                    hideWithoutAnimation(parentToggle);
                    parentToggle.focus();
                }
            } else if (' ' === event.key) {
                // items are links, which browsers only activate with Enter
                item.click();
            } else {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
        },
        true
    );

    let hoverTimeout;
    document.addEventListener('pointerover', (event) => {
        const item = event.target.closest?.('.dropdown-menu .dropdown-item');
        if (undefined === window.bootstrap || 'mouse' !== event.pointerType || !item) {
            return;
        }

        clearTimeout(hoverTimeout);
        hoverTimeout = setTimeout(() => {
            const menu = item.closest('.dropdown-menu');
            if (!menu.classList.contains('show') || menu.classList.contains(EXIT_CLASS)) {
                return;
            }

            const isToggle = item.matches(TOGGLE);
            hideSubmenus(menu, isToggle ? item : null);
            if (isToggle && !item.classList.contains('show')) {
                dropdown(item).show();
            }
        }, SUBMENU_HOVER_DELAY);
    });

    // otherwise, submenus would still be open the next time their parent menu opens
    document.addEventListener('hidden.bs.dropdown', (event) => {
        menuFor(event.target)
            ?.querySelectorAll(`${TOGGLE}.show`)
            .forEach((toggle) => hideWithoutAnimation(toggle));
    });
})();
