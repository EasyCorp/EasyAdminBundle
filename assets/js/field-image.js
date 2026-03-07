import * as basicLightbox from 'basiclightbox';

document.addEventListener('DOMContentLoaded', () => {
    initLightboxes();
    initImagePreviews();
});

document.addEventListener('ea.collection.item-added', () => {
    initLightboxes();
    initImagePreviews();
});

function initLightboxes() {
    document.querySelectorAll('.ea-lightbox-thumbnail:not([data-ea-lightbox-initialized])').forEach((element) => {
        element.setAttribute('data-ea-lightbox-initialized', '');
        element.addEventListener('click', (e) => {
            e.preventDefault();
            const lightboxContent = document.querySelector(
                element.getAttribute('data-ea-lightbox-content-selector')
            ).innerHTML;
            const lightbox = basicLightbox.create(lightboxContent);
            lightbox.show();
        });
    });
}

function initImagePreviews() {
    document.querySelectorAll('.ea-imageupload:not([data-ea-imageupload-initialized])').forEach((container) => {
        container.setAttribute('data-ea-imageupload-initialized', '');

        const fileInput = container.querySelector('input[type="file"]');
        const previewContainer = container.querySelector('[data-ea-imageupload-preview]');
        if (!fileInput || !previewContainer) {
            return;
        }

        fileInput.addEventListener('change', () => {
            updateImagePreview(fileInput, previewContainer);
        });

        const deleteButton = container.querySelector('.ea-fileupload-delete-btn');
        if (deleteButton) {
            deleteButton.addEventListener('click', () => {
                previewContainer.replaceChildren();
            });
        }
    });
}

function updateImagePreview(fileInput, container) {
    container.replaceChildren();

    for (const file of fileInput.files) {
        if (!file.type.startsWith('image/')) {
            continue;
        }

        const objectUrl = URL.createObjectURL(file);
        const lightboxId = `ea-lightbox-preview-${Math.random().toString(36).substring(2, 10)}`;

        const link = document.createElement('a');
        link.href = '#';
        link.className = 'ea-lightbox-thumbnail';
        link.setAttribute('data-ea-lightbox-content-selector', `#${lightboxId}`);

        const img = document.createElement('img');
        img.src = objectUrl;
        img.className = 'img-fluid';
        link.appendChild(img);

        const lightboxDiv = document.createElement('div');
        lightboxDiv.id = lightboxId;
        lightboxDiv.className = 'ea-lightbox';

        const lightboxImg = document.createElement('img');
        lightboxImg.src = objectUrl;
        lightboxDiv.appendChild(lightboxImg);

        container.appendChild(link);
        container.appendChild(lightboxDiv);
    }

    initLightboxes();
}
