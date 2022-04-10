class ColorSchemeHandler {
    #colorSchemeLocalStorageKey;

    constructor() {
        this.#colorSchemeLocalStorageKey = 'ea/colorScheme';
    }

    updateColorScheme() {
        const selectedColorScheme = localStorage.getItem(this.#colorSchemeLocalStorageKey) || 'auto';
        this.#setColorScheme(selectedColorScheme);
    }

    createColorSchemeSelector() {
        if (null === document.querySelector('.dropdown-appearance')) {
            return;
        }

        const currentScheme = localStorage.getItem(this.#colorSchemeLocalStorageKey) || 'auto';
        const selectorOptions = document.querySelectorAll('.dropdown-appearance a[data-ea-color-scheme]');
        const selectorActiveOption = document.querySelector(`.dropdown-appearance a[data-ea-color-scheme="${ currentScheme }"]`);

        selectorOptions.forEach((selector) => { selector.classList.remove('active') });
        selectorActiveOption.classList.add('active');

        selectorOptions.forEach((selector) => {
            selector.addEventListener('click', () => {
                const selectedColorScheme = selector.getAttribute('data-ea-color-scheme');
                this.#setColorScheme(selectedColorScheme);

                selectorOptions.forEach((otherSelector) => { otherSelector.classList.remove('active') });
                selector.classList.add('active');
            });
        });
    }

    #setColorScheme(colorScheme) {
        if ('false' === document.body.getAttribute('data-ea-dark-scheme-is-enabled')) {
            return;
        }

        const resolvedColorScheme = 'auto' === colorScheme
            ? matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
            : colorScheme;

        document.body.classList.remove('ea-light-scheme', 'ea-dark-scheme');
        document.body.classList.add('light' === resolvedColorScheme ? 'ea-light-scheme' : 'ea-dark-scheme');
        localStorage.setItem(this.#colorSchemeLocalStorageKey, colorScheme);
        document.body.style.colorScheme = resolvedColorScheme;
    }
}

const colorSchemeHandler = new ColorSchemeHandler();
// this method needs to be called even before 'DOMContentLoaded' because
// otherwise the page shows an annoying flicker when loading it
colorSchemeHandler.updateColorScheme();
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
    colorSchemeHandler.updateColorScheme();
});

document.addEventListener('DOMContentLoaded', () => {
    colorSchemeHandler.createColorSchemeSelector();
});
