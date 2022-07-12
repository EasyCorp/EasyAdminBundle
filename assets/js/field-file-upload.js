document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.ea-fileupload input[type="file"]').forEach((fileUploadElement) => {
        new FileUploadField(fileUploadElement);
    });
});

class FileUploadField {
    #fieldContainerElement;

    constructor(field) {
        this.field = field;
        this.#fieldContainerElement = this.field.closest('.ea-fileupload');
        this.field.addEventListener('change', this.#updateField.bind(this));
        this.#getFieldDeleteButton().addEventListener('click', this.#resetField.bind(this));
        this.#getRowDeleteButtons().forEach(button => {
            button.addEventListener('click', this.#deleteSingleFieldRow.bind(this));
        });
    }

    #updateField() {
        if (0 === this.field.files.length) {
            return;
        }

        const filename = (1 === this.field.files.length) ? this.field.files[0].name : this.field.files.length + ' ' + this.field.getAttribute('data-files-label');

        let totalSizeInBytes = 0;
        for (const file of this.field.files) {
            totalSizeInBytes += file.size;
        }

        this.#getFieldCustomInput().innerHTML = filename;
        this.#getFieldDeleteButton().style.display = 'block';
        this.#getFieldSizeLabel().childNodes.forEach((fileUploadFileSizeLabelChild) => {
            if (fileUploadFileSizeLabelChild.nodeType === Node.TEXT_NODE) {
                this.#getFieldSizeLabel().removeChild(fileUploadFileSizeLabelChild);
            }
        });
        this.#getFieldSizeLabel().prepend(this.#humanizeFileSize(totalSizeInBytes));
    }

    #resetField() {
        const fieldDeleteCheckbox = this.#fieldContainerElement.querySelector('input[type=checkbox].form-check-input');
        const fieldListOfFiles = this.#fieldContainerElement.querySelector('.fileupload-list');

        if (fieldDeleteCheckbox) {
            fieldDeleteCheckbox.checked = true;
            fieldDeleteCheckbox.click();
        }
        this.field.value = '';
        this.#getFieldCustomInput().innerHTML = '';
        this.#getFieldDeleteButton().style.display = 'none';

        this.#getFieldSizeLabel().childNodes.forEach((fileSizeLabelChild) => {
            if (fileSizeLabelChild.nodeType === Node.TEXT_NODE) {
                this.#getFieldSizeLabel().removeChild(fileSizeLabelChild);
            }
        });

        if (null !== fieldListOfFiles) {
            fieldListOfFiles.style.display = 'none';
        }
    }

    #humanizeFileSize(bytes) {
        const unit = ['B', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y'];
        const factor = Math.trunc(Math.floor(Math.log(bytes) / Math.log(1024)));

        return Math.trunc(bytes / (1024 ** factor)) + unit[factor];
    }

    #getFieldCustomInput() {
        return this.#fieldContainerElement.querySelector('.custom-file-label');
    }

    #getFieldDeleteButton() {
        return this.#fieldContainerElement.querySelector('.ea-fileupload-delete-btn');
    }

    #getRowDeleteButtons() {
        return this.#fieldContainerElement.querySelectorAll('.fileupload-list .file-delete a');
    }

    #getFieldSizeLabel() {
        return this.#fieldContainerElement.querySelector('.input-group-text');
    }

    #deleteSingleFieldRow(event) {
        event.preventDefault();
        event.stopPropagation();

        let row = event.target.closest('tr');
        const filename = row.querySelector('span').innerText.trim();

        const checkboxes = event.target.closest('.ea-fileupload').querySelectorAll('[data-filename]')
        checkboxes.forEach(item => {
            if (item.dataset.filename === filename) {
                item.setAttribute('checked', 'checked')
            }
        });
        row.remove();
    }
}
