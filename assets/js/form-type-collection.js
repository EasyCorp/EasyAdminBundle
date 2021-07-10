const eaCollectionHandler = function (event) {
    document.querySelectorAll('button.field-collection-add-button').forEach((addButton) => {
        let collection = addButton.closest('[data-ea-collection-field]');

        if (!collection || collection.classList.contains('processed')) {
            return;
        }

        EaCollectionProperty.handleAddButton(addButton, collection);
    });
}

window.addEventListener('DOMContentLoaded', eaCollectionHandler);
document.addEventListener('ea.collection.item-added', eaCollectionHandler);

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
            const isArrayCollection = collection.classList.contains('field-array');
            const newItemInsertionSelector = isArrayCollection ? 'legend.col-form-label + .form-widget > div' : '.form-widget .accordion > div';
            const collectionItemsWrapper = collection.querySelector(newItemInsertionSelector);

            collectionItemsWrapper.insertAdjacentHTML('beforeend', newItemHtml);
            // for complex collections of items, show the newly added item as not collapsed
            if (!isArrayCollection) {
                const collectionItems = collectionItemsWrapper.querySelectorAll('.accordion-item');
                const lastElement = collectionItems[collectionItems.length - 1];
                const lastElementCollapseButton = lastElement.querySelector('.accordion-button');
                lastElementCollapseButton.classList.remove('collapsed');
                const lastElementBody = lastElement.querySelector('.accordion-collapse');
                lastElementBody.classList.add('show');
            }

            document.dispatchEvent(new Event('ea.collection.item-added'));
        });

        collection.classList.add('processed');
    }
};
