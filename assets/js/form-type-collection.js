import Sortable from 'sortablejs';

const eaCollectionHandler = function (event) {
    document.querySelectorAll('button.field-collection-add-button').forEach((addButton) => {
        let collection = addButton.closest('[data-ea-collection-field]');

        if (!collection || collection.classList.contains('processed')) {
            return;
        }

        EaCollectionProperty.handleAddButton(addButton, collection);
    });
}

const eaSortableHandler = function (event) {
    document.querySelectorAll('.sortable').forEach(function(sortableList) {
        var listObject = new Sortable(sortableList, {
            onSort: function(event) {
                EaCollectionProperty.handleSort(event.target);
            }
        });
    })
}

const eaSortableCollectionBeforeSubmitHandler = function (event) {
    const form = event.target;

    form.querySelectorAll('.sortable').forEach(function (sortableList) {
        EaCollectionProperty.handleSort(sortableList);
    });
}

window.addEventListener('DOMContentLoaded', eaCollectionHandler);
document.addEventListener('ea.collection.item-added', eaCollectionHandler);

window.addEventListener('DOMContentLoaded', eaSortableHandler);
document.addEventListener('submit', eaSortableCollectionBeforeSubmitHandler);

const EaCollectionProperty = {
    handleAddButton: (addButton, collection) => {
        addButton.addEventListener('click', function() {
            // Use a counter to avoid having the same index more than once
            let numItems = parseInt(collection.dataset.numItems);

            // Remove the 'Empty Collection' badge, if present
            const emptyCollectionBadge = this.parentElement.querySelector('.collection-empty');
            if (null !== emptyCollectionBadge) {
                emptyCollectionBadge.remove();
            }

            const formTypeNamePlaceholder = collection.dataset.formTypeNamePlaceholder;
            const labelRegexp = new RegExp(formTypeNamePlaceholder + 'label__', 'g');
            const nameRegexp = new RegExp(formTypeNamePlaceholder, 'g');

            let newItemHtml = collection.dataset.prototype
                .replace(labelRegexp, numItems)
                .replace(nameRegexp, numItems);

            collection.dataset.numItems = ++numItems;
            collection.querySelector('.form-widget .form-widget-compound > div').insertAdjacentHTML('beforeend', newItemHtml);

            document.dispatchEvent(new Event('ea.collection.item-added'));
        });

        collection.classList.add('processed');
    },
    handleSort: function(list) {
        const orderFieldName = list.getAttribute('data-sortable-order-field');

        for (var i = 0; i < list.children.length; i++) {
            const currentItem = list.children[i],
                currentOrderElement = currentItem.querySelector('[id$="' + orderFieldName + '"]');

            if(currentOrderElement) {
                currentOrderElement.value = i;
            } else {
                console.log('Persisting the order of the collection to "' + orderFieldName + '" failed - such field could not be found')
            }
        }
    }
};
