const eaCollectionHandler = function (event) {
    document.querySelectorAll('button.field-collection-add-button').forEach(function(addButton) {

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
    handleAddButton: function(addButton, collection) {
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
    }
};
