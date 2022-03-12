document.addEventListener('DOMContentLoaded', () => {
    // toggle switches are only created in index page (i.e. in datagrid tables) because
    // in other pages they act like simple checkboxes or labels. Only in index page
    // the toggle switch can change the value of an entity propert via Ajax requests
    document.querySelectorAll('td.field-boolean .form-switch input[type="checkbox"]').forEach((toggleField) => {
        new ToggleSwitch(toggleField);
    });
});

class ToggleSwitch {
    constructor(field) {
        this.field = field;
        this.field.addEventListener('change', this.#updateFieldValue.bind(this));
    }

    #updateFieldValue() {
        const newValue = this.field.checked;
        const toggleUrl = this.field.getAttribute('data-toggle-url') + "&newValue=" + newValue.toString();

        fetch(toggleUrl, {
            method: 'PATCH',
            // the XMLHttpRequest header is needed to keep compatibility with the previous code, which didn't use the Fetch API
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then((response) => {
            if (!response.ok) {
                this.#disableField();
            }

            return response.text();
        })
        .then(() => { /* do nothing else when the toggle request is successful */ })
        .catch(() => this.#disableField());
    }

    // used in case of error, to restore the original toggle field value and disable it
    #disableField() {
        this.field.checked = !this.field.checked;
        this.field.disabled = true;
        this.field.closest('.form-switch').classList.add('disabled');
    }
}
