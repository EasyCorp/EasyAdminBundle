window.addEventListener('DOMContentLoaded', function (event) {
    document.querySelectorAll('[data-ea-collection-field]').forEach(function(collection) {
        let addButton = collection.querySelector('button.field-collection-add-button');
        if (null !== addButton) {
            EaCollectionProperty.handleAddButton(addButton, collection);
        }
    });
});

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

            const newItemNumber = numItems + 1;
            const formTypeNamePlaceholder = collection.dataset.formTypeNamePlaceholder;
            const labelRegexp = new RegExp(formTypeNamePlaceholder + 'label__', 'g');
            const nameRegexp = new RegExp(formTypeNamePlaceholder, 'g');

            let newItemHtml = collection.dataset.prototype
                .replace(labelRegexp, newItemNumber)
                .replace(nameRegexp, newItemNumber);

            collection.dataset.numItems = ++numItems;
            collection.querySelector('.form-widget .form-widget-compound > div').insertAdjacentHTML('beforeend', newItemHtml);

            document.dispatchEvent(new Event('ea.collection.item-added'));
        });
    }
};

