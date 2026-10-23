const eaCollectionHandler = (event) => {
    document.querySelectorAll('button.field-collection-add-button').forEach((addButton) => {
        const collection = addButton.closest('[data-ea-collection-field]');

        if (!collection || collection.dataset.eaProcessed) {
            return;
        }

        EaCollectionProperty.handleAddButton(addButton, collection);
        EaCollectionProperty.updateCollectionItemCssClasses(collection);
    });

    document.querySelectorAll('button.field-collection-delete-button').forEach((deleteButton) => {
        if (deleteButton.dataset.eaProcessed) {
            return;
        }
        deleteButton.dataset.eaProcessed = 'true';
        deleteButton.addEventListener('click', () => {
            const collection = deleteButton.closest('[data-ea-collection-field]');
            const item = deleteButton.closest('.field-collection-item');

            item.remove();
            document.dispatchEvent(
                new CustomEvent('ea.collection.item-removed', { detail: { deletedElement: item, collection } })
            );

            EaCollectionProperty.updateCollectionItemCssClasses(collection);
        });
    });
};

window.addEventListener('DOMContentLoaded', eaCollectionHandler);
document.addEventListener('ea.collection.item-added', eaCollectionHandler);

const EaCollectionProperty = {
    // Nested collections inside unsaved entries are embedded in parent prototypes.
    // Use each parent's actual prototype_name, which users can customize.
    findPrototype: (doc, targetFormId) => {
        const escapeRegex = (part) => part.replace(/[.*+?^$()|[\]{}\\]/g, '\\$&');
        const visit = (fields, parentPlaceholders = []) => {
            for (const field of fields) {
                const template = field.dataset.prototype;
                if (!template) {
                    continue;
                }

                const candidateId = field.dataset.eaCollectionFormId;
                if (candidateId) {
                    const names = parentPlaceholders.map(escapeRegex);
                    const namePattern = names.length ? new RegExp(names.join('|'), 'g') : null;
                    const parts = namePattern ? candidateId.split(namePattern) : [candidateId];
                    const pattern = parts.map(escapeRegex).join('(\\d+)');
                    const matches = new RegExp('^' + pattern + '$').exec(targetFormId);
                    if (matches) {
                        const matchedPlaceholders = namePattern ? candidateId.match(namePattern) ?? [] : [];
                        return matchedPlaceholders.reduce(
                            (html, placeholder, index) => html.replaceAll(placeholder, matches[index + 1]),
                            template
                        );
                    }
                }

                const nestedDoc = new DOMParser().parseFromString(template, 'text/html');
                const placeholder = field.dataset.formTypeNamePlaceholder;
                const nestedPrototype = visit(
                    nestedDoc.querySelectorAll('[data-ea-collection-field]'),
                    placeholder ? [...parentPlaceholders, placeholder] : parentPlaceholders
                );
                if (nestedPrototype) {
                    return nestedPrototype;
                }
            }

            return null;
        };

        return visit(doc.querySelectorAll('[data-ea-collection-field]'));
    },
    handleAddButton: (addButton, collection) => {
        addButton.addEventListener('click', async function () {
            if (collection.dataset.eaLazyPrototype === 'true') {
                this.disabled = true;
                try {
                    const url = new URL(window.location.href);
                    url.searchParams.set('__ea_collection_prototype', collection.dataset.eaCollectionPrototypeField);
                    const response = await fetch(url, { credentials: 'same-origin' });
                    if (!response.ok) {
                        throw new Error(`Failed to load collection prototype (HTTP ${response.status}).`);
                    }

                    const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const prototype = EaCollectionProperty.findPrototype(doc, collection.dataset.eaCollectionFormId);
                    if (!prototype) {
                        throw new Error('The collection prototype was not found in the response.');
                    }

                    collection.dataset.prototype = prototype;
                    collection.dataset.eaLazyPrototype = 'false';
                } catch (error) {
                    console.error(error);
                    window.alert(error.message);
                    return;
                } finally {
                    this.disabled = false;
                }
            }

            const isArrayCollection = collection.classList.contains('field-array');
            // Use a counter to avoid having the same index more than once
            let numItems = Number.parseInt(collection.dataset.numItems);

            // Remove the 'Empty Collection' badge, if present
            const emptyCollectionBadge = this.parentElement.querySelector('.collection-empty');
            if (null !== emptyCollectionBadge) {
                emptyCollectionBadge.outerHTML = isArrayCollection
                    ? '<div class="ea-form-collection-items"></div>'
                    : '<div class="ea-form-collection-items"><div class="accordion"><div class="form-widget-compound"><div data-empty-collection></div></div></div></div>';
            }

            const formTypeNamePlaceholder = collection.dataset.formTypeNamePlaceholder;
            const labelRegexp = new RegExp(`${formTypeNamePlaceholder}label__`, 'g');
            const nameRegexp = new RegExp(formTypeNamePlaceholder, 'g');

            const newItemHtml = collection.dataset.prototype
                .replace(labelRegexp, numItems)
                .replace(nameRegexp, numItems);

            collection.dataset.numItems = ++numItems;
            const newItemInsertionSelector = isArrayCollection
                ? '.ea-form-collection-items'
                : '.ea-form-collection-items .accordion > .form-widget-compound [data-empty-collection]';
            const collectionItemsWrapper = collection.querySelector(newItemInsertionSelector);

            collectionItemsWrapper.insertAdjacentHTML('beforeend', newItemHtml);

            // Execute JS scripts embedded in prototype
            const collectionItems = collectionItemsWrapper.querySelectorAll('.field-collection-item');
            const lastElement = collectionItems[collectionItems.length - 1];
            lastElement.querySelectorAll('script').forEach((script) => eval(script.innerHTML));

            // for complex collections of items, show the newly added item as not collapsed
            if (!isArrayCollection) {
                EaCollectionProperty.updateCollectionItemCssClasses(collection);
                const lastElementCollapseButton = lastElement.querySelector('.accordion-button');
                lastElementCollapseButton.classList.remove('collapsed');
                const lastElementBody = lastElement.querySelector('.accordion-collapse');
                lastElementBody.classList.add('show');
            }

            document.dispatchEvent(
                new CustomEvent('ea.collection.item-added', { detail: { newElement: lastElement, collection } })
            );
        });

        collection.dataset.eaProcessed = 'true';
    },

    updateCollectionItemCssClasses: (collection) => {
        if (null === collection) {
            return;
        }

        const collectionItems = collection.querySelectorAll('.field-collection-item');
        collectionItems.forEach((item) =>
            item.classList.remove('field-collection-item-first', 'field-collection-item-last')
        );

        const firstElement = collectionItems[0];
        if (undefined === firstElement) {
            return;
        }
        firstElement.classList.add('field-collection-item-first');

        const lastElement = collectionItems[collectionItems.length - 1];
        if (undefined === lastElement) {
            return;
        }
        lastElement.classList.add('field-collection-item-last');
    },
};
