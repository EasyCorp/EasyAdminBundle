class ColorSchemeHandler {
    #colorSchemeLocalStorageKey;

    constructor() {
        this.#colorSchemeLocalStorageKey = 'ea/colorScheme';
    }

    updateColorScheme() {
        const defaultColorScheme = document.body.getAttribute('data-ea-default-color-scheme');
        const userDefinedColorScheme = localStorage.getItem(this.#colorSchemeLocalStorageKey);
        const selectedColorScheme = userDefinedColorScheme || defaultColorScheme || 'auto';
        this.#setColorScheme(selectedColorScheme);
    }

    createColorSchemeSelector() {
        if (null === document.querySelector('.dropdown-settings a[data-ea-color-scheme]')) {
            return;
        }

        // for responsive design reasons, pages contain two settings dropdowns (and inside them, one
        // scheme selector each) instead of one and the selected scheme must be kept in sync in both
        const colorSchemeSelectors = document.querySelectorAll('.dropdown-settings');
        const currentScheme =
            localStorage.getItem(this.#colorSchemeLocalStorageKey) ||
            document.body.getAttribute('data-ea-default-color-scheme') ||
            'auto';

        // the legacy `active` class is kept in sync with aria-checked for one release
        const setChecked = (el, isChecked) => {
            el.setAttribute('aria-checked', isChecked ? 'true' : 'false');
            el.classList.toggle('active', isChecked);
        };

        colorSchemeSelectors.forEach((colorSchemeSelector) => {
            const selectorOptions = colorSchemeSelector.querySelectorAll(
                'a.dropdown-appearance-item[data-ea-color-scheme]'
            );
            const selectorActiveOption = colorSchemeSelector.querySelector(
                `a.dropdown-appearance-item[data-ea-color-scheme="${currentScheme}"]`
            );

            selectorOptions.forEach((selector) => setChecked(selector, false));
            if (selectorActiveOption) {
                setChecked(selectorActiveOption, true);
            }

            selectorOptions.forEach((selector) => {
                selector.addEventListener('click', (event) => {
                    // the options are href="#" links; following them would scroll to the top and
                    // trigger 'popstate', which makes tabs.js select the first tab
                    event.preventDefault();
                    const selectedColorScheme = selector.getAttribute('data-ea-color-scheme');
                    this.#setColorScheme(selectedColorScheme, true);

                    const allSelectorOptions = document.querySelectorAll(
                        'a.dropdown-appearance-item[data-ea-color-scheme]'
                    );
                    const allSelectorActiveOptions = document.querySelectorAll(
                        `a.dropdown-appearance-item[data-ea-color-scheme="${selectedColorScheme}"]`
                    );
                    allSelectorOptions.forEach((selectorOption) => setChecked(selectorOption, false));
                    allSelectorActiveOptions.forEach((selectorOption) => setChecked(selectorOption, true));
                });
            });
        });
    }

    #setColorScheme(colorScheme, persistInLocalStorage = false) {
        if ('false' === document.body.getAttribute('data-ea-dark-scheme-is-enabled')) {
            colorScheme = 'light';
        }

        const resolvedColorScheme =
            'auto' === colorScheme
                ? matchMedia('(prefers-color-scheme: dark)').matches
                    ? 'dark'
                    : 'light'
                : colorScheme;

        document.body.classList.remove('ea-light-scheme', 'ea-dark-scheme');
        document.body.classList.add('light' === resolvedColorScheme ? 'ea-light-scheme' : 'ea-dark-scheme');

        if (true === persistInLocalStorage) {
            localStorage.setItem(this.#colorSchemeLocalStorageKey, colorScheme);
        }

        document.body.style.colorScheme = resolvedColorScheme;
        // needed for Bootstrap (see https://getbootstrap.com/docs/5.3/customize/color-modes/)
        document.body.setAttribute('data-bs-theme', 'light' === resolvedColorScheme ? 'light' : 'dark');
    }
}

const colorSchemeHandler = new ColorSchemeHandler();
// this method needs to be called even before 'DOMContentLoaded' because
// otherwise the page shows an annoying flicker when loading it
colorSchemeHandler.updateColorScheme();
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    colorSchemeHandler.updateColorScheme();
});

document.addEventListener('DOMContentLoaded', () => {
    colorSchemeHandler.createColorSchemeSelector();
});
