document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.field-boolean .ea-switch input[type="checkbox"]').forEach((toggleField) => {
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
        const toggleUrl = `${this.field.getAttribute('data-toggle-url')}&newValue=${newValue.toString()}`;

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
            .catch(() => this.#disableField());
    }

    #disableField() {
        this.field.checked = !this.field.checked;
        this.field.disabled = true;
        this.field.closest('.ea-switch').classList.add('ea-switch-disabled');
    }
}
