document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-ea-fileupload-field]').forEach((container) => {
        new FileUploadField(container);
    });
});

document.addEventListener('ea.collection.item-added', (event) => {
    event.detail.newElement.querySelectorAll('[data-ea-fileupload-field]').forEach((container) => {
        new FileUploadField(container);
    });
});

class FileUploadField {
    #container;
    #fileInput;
    #cardsContainer;
    #addButton;
    #clearAllButton;
    #deleteCheckbox;
    #deletedFilesInput;
    #isMultiple;
    /** @type {File[]} Accumulated selected files in multiple mode */
    #selectedFiles = [];

    constructor(container) {
        this.#container = container;
        this.#fileInput = container.querySelector('[data-ea-fileupload-input]');
        this.#cardsContainer = container.querySelector('[data-ea-fileupload-cards]');
        this.#addButton = container.querySelector('[data-ea-fileupload-add]');
        this.#clearAllButton = container.querySelector('[data-ea-fileupload-clear-all]');
        this.#deleteCheckbox = container.querySelector('input[type=checkbox].form-check-input');
        this.#deletedFilesInput = container.querySelector('[id$="_deleted_files"]');
        this.#isMultiple = container.hasAttribute('data-multiple');

        this.#bindEvents();
    }

    #bindEvents() {
        this.#addButton?.addEventListener('click', () => this.#fileInput.click());

        this.#fileInput.addEventListener('change', () => this.#onFilesSelected());

        this.#clearAllButton?.addEventListener('click', () => this.#clearAll());

        this.#cardsContainer?.addEventListener('click', (e) => {
            const deleteBtn = e.target.closest('[data-ea-fileupload-delete-card]');
            if (deleteBtn) {
                this.#deleteCard(deleteBtn.closest('[data-ea-fileupload-card]'));
            }
        });
    }

    #onFilesSelected() {
        if (0 === this.#fileInput.files.length) {
            return;
        }

        const newFiles = Array.from(this.#fileInput.files);

        if (!this.#isMultiple) {
            this.#revokeAllObjectUrls();
            this.#removeAllCards();
            this.#selectedFiles = newFiles;
        } else {
            // each selection replaces the FileList of the input, so files are accumulated here
            this.#selectedFiles = this.#selectedFiles.concat(newFiles);
            this.#syncFileInput();
        }

        const startIndex = this.#selectedFiles.length - newFiles.length;
        for (let i = 0; i < newFiles.length; i++) {
            const objectUrl = URL.createObjectURL(newFiles[i]);
            const card = this.#createCardElement(newFiles[i], objectUrl, startIndex + i);
            this.#cardsContainer.appendChild(card);
        }

        this.#updateToolbarVisibility();
    }

    #deleteCard(cardElement) {
        if (!cardElement) {
            return;
        }

        const fileName = cardElement.getAttribute('data-filename');

        if (fileName) {
            this.#addToDeletedFiles(fileName);
        } else {
            const fileIndex = Number.parseInt(cardElement.getAttribute('data-file-index'), 10);
            if (!Number.isNaN(fileIndex) && fileIndex >= 0 && fileIndex < this.#selectedFiles.length) {
                this.#selectedFiles.splice(fileIndex, 1);
                this.#syncFileInput();
                this.#reindexNewCards();
            }
        }

        const thumbnail = cardElement.querySelector('.ea-fileupload-card-thumbnail');
        if (thumbnail?.src.startsWith('blob:')) {
            URL.revokeObjectURL(thumbnail.src);
        }

        cardElement.remove();
        this.#updateToolbarVisibility();
    }

    #clearAll() {
        if (this.#deleteCheckbox) {
            this.#deleteCheckbox.checked = true;
        }

        if (this.#deletedFilesInput) {
            this.#deletedFilesInput.value = '';
        }

        this.#selectedFiles = [];
        this.#fileInput.value = '';

        this.#revokeAllObjectUrls();
        this.#removeAllCards();
        this.#updateToolbarVisibility();
    }

    // a FileList can't be modified, so a new one is created with DataTransfer
    #syncFileInput() {
        const dt = new DataTransfer();
        for (const file of this.#selectedFiles) {
            dt.items.add(file);
        }
        this.#fileInput.files = dt.files;
    }

    #addToDeletedFiles(fileName) {
        if (!this.#deletedFilesInput) {
            return;
        }

        let deletedFiles = [];
        try {
            deletedFiles = JSON.parse(this.#deletedFilesInput.value) || [];
        } catch {
            deletedFiles = [];
        }

        if (!deletedFiles.includes(fileName)) {
            deletedFiles.push(fileName);
        }

        this.#deletedFilesInput.value = JSON.stringify(deletedFiles);
    }

    // the 'data-file-index' of new cards must match their position in #selectedFiles
    #reindexNewCards() {
        let index = 0;
        this.#cardsContainer.querySelectorAll('[data-ea-fileupload-card][data-new-file]').forEach((card) => {
            card.setAttribute('data-file-index', String(index++));
        });
    }

    #removeAllCards() {
        this.#cardsContainer.querySelectorAll('[data-ea-fileupload-card]').forEach((card) => card.remove());
    }

    #revokeAllObjectUrls() {
        this.#cardsContainer.querySelectorAll('.ea-fileupload-card-thumbnail').forEach((thumbnail) => {
            if (thumbnail.src.startsWith('blob:')) {
                URL.revokeObjectURL(thumbnail.src);
            }
        });
    }

    #updateToolbarVisibility() {
        const hasCards = this.#cardsContainer.querySelectorAll('[data-ea-fileupload-card]').length > 0;

        if (this.#addButton) {
            if (this.#isMultiple) {
                this.#addButton.classList.remove('d-none');
            } else {
                this.#addButton.classList.toggle('d-none', hasCards);
            }
        }

        if (this.#clearAllButton) {
            this.#clearAllButton.classList.toggle('d-none', !hasCards);
        }
    }

    #createCardElement(file, objectUrl, fileIndex) {
        const card = document.createElement('div');
        card.className = 'ea-fileupload-card';
        card.setAttribute('data-ea-fileupload-card', '');
        card.setAttribute('data-new-file', '');
        card.setAttribute('data-file-index', String(fileIndex));

        const preview = document.createElement('div');
        preview.className = 'ea-fileupload-card-preview';

        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = objectUrl;
            img.alt = file.name;
            img.className = 'ea-fileupload-card-thumbnail';
            preview.appendChild(img);
        } else {
            const iconFragment = this.#parseIconHtml(this.#container.getAttribute('data-icon-generic'));
            if (iconFragment) {
                preview.appendChild(iconFragment);
            }
        }
        card.appendChild(preview);

        const info = document.createElement('div');
        info.className = 'ea-fileupload-card-info';

        const nameSpan = document.createElement('span');
        nameSpan.className = 'ea-fileupload-card-name';
        nameSpan.textContent = file.name;
        info.appendChild(nameSpan);

        const sizeSpan = document.createElement('span');
        sizeSpan.className = 'ea-fileupload-card-size';
        sizeSpan.textContent = this.#humanizeFileSize(file.size);
        info.appendChild(sizeSpan);

        card.appendChild(info);

        const actions = document.createElement('div');
        actions.className = 'ea-fileupload-card-actions';

        if (this.#container.hasAttribute('data-allow-delete')) {
            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'ea-fileupload-action-btn ea-fileupload-action-delete';
            deleteBtn.setAttribute('data-ea-fileupload-delete-card', '');
            const deleteIcon = this.#parseIconHtml(this.#container.getAttribute('data-icon-delete'));
            if (deleteIcon) {
                deleteBtn.appendChild(deleteIcon);
            }
            actions.appendChild(deleteBtn);
        }

        card.appendChild(actions);

        return card;
    }

    // the HTML is not sanitized: it comes from a trusted, server-rendered data attribute
    #parseIconHtml(htmlString) {
        if (!htmlString) {
            return null;
        }

        const doc = new DOMParser().parseFromString(htmlString, 'text/html');
        const fragment = document.createDocumentFragment();
        while (doc.body.firstChild) {
            fragment.appendChild(doc.body.firstChild);
        }

        return fragment;
    }

    #humanizeFileSize(bytes) {
        const units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        if (0 === bytes) {
            return '0 B';
        }

        const factor = Math.floor(Math.log(bytes) / Math.log(1024));

        if (0 === factor) {
            return `${Math.trunc(bytes)} ${units[0]}`;
        }

        const scaledValue = Math.round((bytes / 1024 ** factor) * 10) / 10;
        const formatted = scaledValue % 1 === 0 ? scaledValue.toFixed(0) : scaledValue.toFixed(1);

        return `${formatted} ${units[factor]}`;
    }
}
