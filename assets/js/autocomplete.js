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
        const autocomplete = this;
        let config = this.#mergeObjects(this.#getCommonConfig(element), {
            valueField: 'entityId',
            labelField: 'entityAsString',
            searchField: ['entityAsString'],
            // each result may define an 'entityGroup' (the group_by option of the form type)
            // to render it under an <optgroup>-like header in the dropdown
            optgroupField: 'entityGroup',
            lockOptgroupOrder: true,
            firstUrl: (query) => {
                const url = new URL(autocompleteEndpointUrl);
                url.searchParams.append(`query`, query);
                this.#resolveAutocompleteDependencyFields(element).forEach((dependency) => {
                    url.searchParams.append(`autocompleteDependsOn[${dependency.path}]`, dependency.element.value);
                });

                return url.toString();
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
                        callback(json.results, autocomplete.#extractOptgroupsFromResults(json.results));
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

        // when using group_by, the server renders the selected items inside <optgroup> elements;
        // extract them keyed by the group LABEL so they merge with the groups of the Ajax results
        // (TomSelect's own DOM import assigns numeric ids to these groups, which would duplicate the headers)
        if (null !== element.querySelector('optgroup')) {
            const { options, optgroups } = this.#extractRemoteOptionsWithOptgroups(element);
            config = this.#mergeObjects(config, { options: options, optgroups: optgroups });
        }

        const tomSelect = this.#initializeTomSelect(element, config);

        this.#bindAutocompleteDependencyFieldListeners(tomSelect);

        return tomSelect;
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

    #extractOptgroupsFromResults(results) {
        const optgroups = [];
        const seenGroups = new Set();

        (results || []).forEach((result) => {
            const groups = Array.isArray(result.entityGroup) ? result.entityGroup : [result.entityGroup];
            groups.forEach((group) => {
                if (null === group || undefined === group || seenGroups.has(group)) {
                    return;
                }

                seenGroups.add(group);
                // 'value' is the field TomSelect uses as the optgroup id (optgroupValueField)
                optgroups.push({ value: group, label: group });
            });
        });

        return optgroups;
    }

    #extractRemoteOptionsWithOptgroups(element) {
        const options = [];
        const optgroups = [];
        const seenGroups = new Set();

        const addOption = (opt, groupLabel) => {
            // skip empty placeholder options
            if ('' === opt.value) {
                return;
            }

            // use textContent (like TomSelect's own DOM import): with renderAsHtml, the HTML
            // markup is stored escaped as the option text and rendered raw later by render.item
            const optionData = { ...opt.dataset, entityId: opt.value, entityAsString: opt.textContent };
            if (undefined !== groupLabel) {
                optionData.entityGroup = groupLabel;
            }
            options.push(optionData);
        };

        for (let i = 0; i < element.children.length; i++) {
            const child = element.children[i];
            if ('OPTGROUP' === child.tagName) {
                if (!seenGroups.has(child.label)) {
                    seenGroups.add(child.label);
                    optgroups.push({ value: child.label, label: child.label });
                }
                for (let j = 0; j < child.children.length; j++) {
                    addOption(child.children[j], child.label);
                }
            } else if ('OPTION' === child.tagName) {
                addOption(child);
            }
        }

        return { options, optgroups };
    }

    #bindAutocompleteDependencyFieldListeners(tomSelect) {
        this.#resolveAutocompleteDependencyFields(tomSelect.input).forEach((dependency) => {
            dependency.element.addEventListener('change', () => {
                tomSelect.clear();
                tomSelect.clearOptions();
                tomSelect.clearOptionGroups();
                tomSelect.clearPagination();
                tomSelect.wrapper.classList.remove('preloaded');
            });
        });
    }

    #resolveAutocompleteDependencyFields(element) {
        const dependsOn = JSON.parse(element.getAttribute('data-ea-autocomplete-depends-on') || '[]');
        const form = element.closest('form');

        if (null === form) {
            return [];
        }

        return dependsOn.map((field) => {
            const fieldId = `${form.name}_${field.replaceAll('.', '_')}`;
            const fieldElement = document.getElementById(fieldId) ?? document.getElementById(`${fieldId}_autocomplete`);

            if (null === fieldElement) {
                console.error(`No field found for autocomplete dependency "${field}".`);
                return null;
            }

            return {
                path: field,
                element: fieldElement,
            };
        })
        .filter((dependencyField) => null !== dependencyField);
    }
}
