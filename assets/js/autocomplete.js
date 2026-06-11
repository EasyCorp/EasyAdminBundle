import TomSelect from 'tom-select/dist/js/tom-select.complete.min';

export default class Autocomplete {
    create(element) {
        // this avoids initializing the same field twice (TomSelect shows an error otherwise)
        if (element.classList.contains('tomselected')) {
            return;
        }

        // TomSelect only works with <select> and <input> elements
        if ('SELECT' !== element.tagName && 'INPUT' !== element.tagName) {
            return;
        }

        const autocompleteEndpointUrl = element.getAttribute('data-ea-autocomplete-endpoint-url');
        if (null !== autocompleteEndpointUrl) {
            return this.#createAutocompleteWithRemoteData(element, autocompleteEndpointUrl);
        }

        const renderOptionsAsHtml = 'true' === element.getAttribute('data-ea-autocomplete-render-items-as-html');
        if (renderOptionsAsHtml) {
            return this.#createAutocompleteWithHtmlContents(element);
        }

        return this.#createAutocomplete(element);
    }

    #getCommonConfig(element) {
        const config = {
            render: {
                no_results: (data, escape) =>
                    `<div class="no-results">${element.getAttribute('data-ea-i18n-no-results-found')}</div>`,
            },
            plugins: {
                dropdown_input: {},
            },
        };

        if (null === element.getAttribute('required') && null === element.getAttribute('disabled')) {
            config.plugins.clear_button = { title: '' };
        }

        if (null !== element.getAttribute('multiple')) {
            config.plugins.remove_button = { title: '' };
        }

        if (null !== element.getAttribute('data-ea-autocomplete-endpoint-url')) {
            config.plugins.virtual_scroll = {};
        }

        if ('true' === element.getAttribute('data-ea-autocomplete-allow-item-create')) {
            config.create = true;
        }

        return config;
    }

    #createAutocomplete(element) {
        let config = this.#mergeObjects(this.#getCommonConfig(element), {
            maxOptions: null,
        });

        if (this.#hasPreferredChoices(element)) {
            const { options, optgroups } = this.#extractOptionsWithOptgroups(element, false);
            config = this.#mergeObjects(config, {
                options: options,
                optgroups: optgroups,
                optgroupField: 'optgroup',
                lockOptgroupOrder: true,
                valueField: 'value',
                labelField: 'text',
                searchField: ['text'],
            });
        }

        return this.#initializeTomSelect(element, config);
    }

    #createAutocompleteWithHtmlContents(element) {
        let config = this.#mergeObjects(this.#getCommonConfig(element), {
            valueField: 'value',
            labelField: 'label_raw',
            searchField: ['label_text'],
            maxOptions: null,
            render: {
                item: (item, escape) => `<div>${item.label_raw}</div>`,
                option: (item, escape) => `<div>${item.label_raw}</div>`,
            },
        });

        if (this.#hasPreferredChoices(element)) {
            const { options, optgroups } = this.#extractOptionsWithOptgroups(element, true);
            config = this.#mergeObjects(config, {
                options: options,
                optgroups: optgroups,
                optgroupField: 'optgroup',
                lockOptgroupOrder: true,
            });
        } else {
            config.options = this.#extractOptions(element, true);
        }

        return this.#initializeTomSelect(element, config);
    }

    #createAutocompleteWithRemoteData(element, autocompleteEndpointUrl) {
        const renderOptionsAsHtml = 'true' === element.getAttribute('data-ea-autocomplete-render-items-as-html');
        const config = this.#mergeObjects(this.#getCommonConfig(element), {
            valueField: 'entityId',
            labelField: 'entityAsString',
            searchField: ['entityAsString'],
            firstUrl: (query) => {
                return `${autocompleteEndpointUrl}&query=${encodeURIComponent(query)}`;
            },
            // VERY IMPORTANT: use 'function (query, callback) { ... }' instead of the
            // '(query, callback) => { ... }' syntax because, otherwise,
            // the 'this.XXX' calls inside of this method fail
            load: function (query, callback) {
                const url = this.getUrl(query);
                fetch(url)
                    .then((response) => response.json())
                    // important: next_url must be set before invoking callback()
                    .then((json) => {
                        this.setNextUrl(query, json.next_page);
                        callback(json.results);
                    })
                    .catch(() => callback());
            },
            preload: 'focus',
            maxOptions: null,
            // on remote calls, we don't want tomselect to further filter the results by "entityAsString"
            // this override causes all results to be returned with the sorting from the server
            score: (search) => (item) => 1,
            render: {
                option: (item, escape) =>
                    `<div>${renderOptionsAsHtml ? item.entityAsString : escape(item.entityAsString)}</div>`,
                item: (item, escape) =>
                    `<div>${renderOptionsAsHtml ? item.entityAsString : escape(item.entityAsString)}</div>`,
                loading_more: (data, escape) =>
                    `<div class="loading-more-results">${element.getAttribute('data-ea-i18n-loading-more-results')}</div>`,
                no_more_results: (data, escape) =>
                    `<div class="no-more-results">${element.getAttribute('data-ea-i18n-no-more-results')}</div>`,
                no_results: (data, escape) =>
                    `<div class="no-results">${element.getAttribute('data-ea-i18n-no-results-found')}</div>`,
            },
        });

        return this.#initializeTomSelect(element, config);
    }

    #initializeTomSelect(element, config) {
        element.dispatchEvent(
            new CustomEvent('ea.autocomplete.pre-connect', { detail: { config, prefix: 'autocomplete' }, bubbles: true })
        );

        const tomSelect = new TomSelect(element, config);

        element.dispatchEvent(
            new CustomEvent('ea.autocomplete.connect', { detail: { tomSelect, config, prefix: 'autocomplete' }, bubbles: true })
        );

        return tomSelect;
    }

    #stripTags(string) {
        return string.replace(/(<([^>]+)>)/gi, '');
    }

    #mergeObjects(object1, object2) {
        return { ...object1, ...object2 };
    }

    #hasPreferredChoices(element) {
        for (let i = 0; i < element.options.length; i++) {
            if (this.#isPreferredChoicesSeparator(element.options[i])) {
                return true;
            }
        }
        return false;
    }

    #isPreferredChoicesSeparator(option) {
        // Symfony renders preferred_choices with a disabled separator option containing only dashes
        return option.disabled && option.text.trim().match(/^-+$/);
    }

    #extractOptions(element, withHtmlSupport) {
        const options = [];
        for (let i = 0; i < element.options.length; i++) {
            const opt = element.options[i];
            if (opt.value === '' || this.#isPreferredChoicesSeparator(opt)) {
                continue;
            }

            if (withHtmlSupport) {
                options.push({
                    value: opt.value,
                    label_text: this.#stripTags(opt.text),
                    label_raw: opt.text,
                });
            } else {
                options.push({
                    value: opt.value,
                    text: opt.text,
                });
            }
        }
        return options;
    }

    #extractOptionsWithOptgroups(element, withHtmlSupport) {
        const options = [];
        const optgroups = [
            { value: 'preferred', label: '', $order: 1 },
            { value: 'regular', label: '', $order: 2 }
        ];

        let foundSeparator = false;
        const seenValues = new Set();

        for (let i = 0; i < element.options.length; i++) {
            const opt = element.options[i];

            if (this.#isPreferredChoicesSeparator(opt)) {
                foundSeparator = true;
                continue;
            }

            // skip empty placeholder options
            if (opt.value === '') {
                continue;
            }

            // avoid duplicates (preferred choices appear twice in the HTML)
            if (foundSeparator && seenValues.has(opt.value)) {
                continue;
            }

            const optionData = withHtmlSupport
                ? {
                    value: opt.value,
                    label_text: this.#stripTags(opt.text),
                    label_raw: opt.text,
                    optgroup: foundSeparator ? 'regular' : 'preferred'
                }
                : {
                    value: opt.value,
                    text: opt.text,
                    optgroup: foundSeparator ? 'regular' : 'preferred'
                };

            options.push(optionData);

            if (!foundSeparator) {
                seenValues.add(opt.value);
            }
        }

        return { options, optgroups };
    }
}
