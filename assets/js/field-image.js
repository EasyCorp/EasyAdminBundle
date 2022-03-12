import * as basicLightbox from 'basiclightbox';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.ea-lightbox-thumbnail').forEach((imageElement) => {
        new Image(imageElement);
    });
});

class Image {
    constructor(field) {
        this.field = field;
        this.field.addEventListener('click', this.#renderLightbox.bind(this));
    }

    #renderLightbox() {
        const lightboxContent = document.querySelector(this.field.getAttribute('data-ea-lightbox-content-selector')).innerHTML;
        const lightbox = basicLightbox.create(lightboxContent);
        lightbox.show();
    }
}
