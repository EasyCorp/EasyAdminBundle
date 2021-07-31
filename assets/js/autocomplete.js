import TomSelect from "tom-select/dist/js/tom-select.complete.min";
import DirtyForm from "dirty-form";

export default class Autocomplete
{
    create(element) {
        // this avoids initializing the same field twice (TomSelect shows an error otherwise)
        if (element.classList.contains('tomselected')) {
            return;
        }

        this.#handleRequiredHtmlAttribute(element);

        let tomSelect;
        const autocompleteEndpointUrl = element.getAttribute('data-ea-autocomplete-endpoint-url');
        const renderOptionsAsHtml = 'true' === element.getAttribute('data-ea-autocomplete-render-items-as-html');
        if (null !== autocompleteEndpointUrl) {
            tomSelect = this.#createAutocompleteWithRemoteData(element, autocompleteEndpointUrl);
        } else if (renderOptionsAsHtml) {
            tomSelect = this.#createAutocompleteWithHtmlContents(element);
        } else {
            tomSelect = this.#createAutocomplete(element);
        }

        // because of the way TomSelect works, browsers cannot detect changes to these fields automatically,
        // so we manually trigger the plugin when the content changes.
        tomSelect.on('change', (event) => {
            const form = event.target.closest('form');
            if (null === form) {
                return;
            }

            new DirtyForm(form);
        });

        return tomSelect;
    }

    #handleRequiredHtmlAttribute(element) {
        // TomSelect works by hidding the original <select> element and creating a new element
        // When the original field is required and its content is empty, browsers try to add a validation error, but it
        // fails because the element is hidden ("An invalid form control with name=”foo" is not focusable")
        // That's why we remove the HTML required attribute and store it in a custom attribute that will be check later
        const isFieldRequired = 'required' === element.getAttribute('required');
        if (false === isFieldRequired) {
            return;
        }

        element.setAttribute('data-ea-field-autocomplete-is-required', isFieldRequired ? 'true' : 'false');
        element.removeAttribute('required');
    }

    #getCommonConfig(element) {
        const config = {
            plugins: {
                dropdown_input: {},
                // 'input_autogrow': {},
                clear_button: { title: '' },
            }
        };

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
    };

    #createAutocomplete(element) {
        const config = this.#mergeObjects(this.#getCommonConfig(element), {
            maxOptions: element.options.length,
        });

        return new TomSelect(element, config);
    }

    #createAutocompleteWithHtmlContents(element) {
        const autoSelectOptions = [];
        for (let i = 0; i < element.options.length; i++) {
            const label = element.options[i].text;
            const value = element.options[i].value;

            autoSelectOptions.push({
                label_text: this.#stripTags(label),
                label_raw: label,
                value: value,
            });
        }

        const config = this.#mergeObjects(this.#getCommonConfig(element), {
            valueField: 'value',
            labelField: 'label_raw',
            searchField: ['label_text'],
            options: autoSelectOptions,
            maxOptions: element.options.length,
            render: {
                item: function(item, escape) {
                    return `<div>${item.label_raw}</div>`;
                },
                option: function(item, escape) {
                    return `<div>${item.label_raw}</div>`;
                }
            },
        });

        return new TomSelect(element, config);
    }

    #createAutocompleteWithRemoteData(element, autocompleteEndpointUrl) {
        const config = this.#mergeObjects(this.#getCommonConfig(element), {
            valueField: 'entityId',
            labelField: 'entityAsString',
            searchField: ['entityAsString'],
            firstUrl: (query) => {
                return autocompleteEndpointUrl + '&query=' + encodeURIComponent(query);
            },
            // VERY IMPORTANT: use 'function (query, callback) { ... }' instead of the
            // '(query, callback) => { ... }' syntax because, otherwise,
            // the 'this.XXX' calls inside of this method fail
            load: function (query, callback) {
                const url = this.getUrl(query);
                fetch(url)
                    .then(response => response.json())
                    // important: next_url must be set before invoking callback()
                    .then(json => { this.setNextUrl(query, json.next_page); callback(json.results) })
                    .catch(() => callback());
            },
            render: {
                option: function(item, escape) {
                    return `<div>${item.entityAsString}</div>`;
                },
                item: function(item, escape) {
                    return `<div>${item.entityAsString}</div>`;
                }
            },
        });

        return new TomSelect(element, config);
    }

    #stripTags(string) {
        return string.replace(/(<([^>]+)>)/gi, '');
    }

    #mergeObjects(object1, object2) {
        return { ...object1, ...object2 };
    }
}
