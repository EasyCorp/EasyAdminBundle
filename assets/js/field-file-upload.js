import { toggleVisibilityClasses } from './helpers';

const initFileUploadFields = (root) => {
    root.querySelectorAll('.ea-fileupload input[type="file"]').forEach((fileUploadElement) => {
        if (fileUploadElement.dataset.eaFileuploadInitialized === '1') {
            return;
        }
        fileUploadElement.dataset.eaFileuploadInitialized = '1';
        new FileUploadField(fileUploadElement);
    });
};

window.addEventListener('DOMContentLoaded', () => initFileUploadFields(document));
document.addEventListener('ea.collection.item-added', (event) => {
    // only initialise the file upload fields contained in the newly added collection item:
    // re-running the query on the whole document would re-attach listeners to already
    // processed fields and double-fire their handlers (see #6637). The dataset flag
    // already protects against double-init if `event.detail.newElement` is missing.
    initFileUploadFields(event?.detail?.newElement ?? document);
});

class FileUploadField {
    #fieldContainerElement;

    constructor(field) {
        this.field = field;
        this.#fieldContainerElement = this.field.closest('.ea-fileupload');
        this.field.addEventListener('change', this.#updateField.bind(this));

        const deleteButton = this.#getFieldDeleteButton();
        if (deleteButton) {
            deleteButton.addEventListener('click', this.#resetField.bind(this));
        }
    }

    #updateField() {
        if (0 === this.field.files.length) {
            return;
        }

        const filename =
            1 === this.field.files.length
                ? this.field.files[0].name
                : `${this.field.files.length} ${this.field.getAttribute('data-files-label')}`;

        let totalSizeInBytes = 0;
        for (const file of this.field.files) {
            totalSizeInBytes += file.size;
        }

        this.#getFieldCustomInput().textContent = filename;
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
        this.#getFieldCustomInput().textContent = '';
        toggleVisibilityClasses(this.#getFieldDeleteButton(), true);

        this.#getFieldSizeLabel().childNodes.forEach((fileSizeLabelChild) => {
            if (fileSizeLabelChild.nodeType === Node.TEXT_NODE) {
                this.#getFieldSizeLabel().removeChild(fileSizeLabelChild);
            }
        });

        if (null !== fieldListOfFiles) {
            toggleVisibilityClasses(fieldListOfFiles, true);
        }
    }

    #humanizeFileSize(bytes) {
        const unit = ['B', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y'];
        const factor = Math.trunc(Math.floor(Math.log(bytes) / Math.log(1024)));

        return Math.trunc(bytes / 1024 ** factor) + unit[factor];
    }

    #getFieldCustomInput() {
        return this.#fieldContainerElement.querySelector('.custom-file-label');
    }

    #getFieldDeleteButton() {
        return this.#fieldContainerElement.querySelector('.ea-fileupload-delete-btn');
    }

    #getFieldSizeLabel() {
        return this.#fieldContainerElement.querySelector('.input-group-text');
    }
}
