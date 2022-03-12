class Autogrow {
    constructor(field) {
        this.field = field;
        this.field.addEventListener('input', this.autogrow.bind(this));
        this.autogrow();
    }

    autogrow() {
        this.field.style.overflow = 'hidden';
        this.field.style.resize = 'none';
        this.field.style.boxSizing = 'border-box';
        this.field.style.height = 'auto';

        // this check is needed because the <textarea> element can be inside a
        // minimizable panel, causing its scrollHeight value to be 0
        if (this.field.scrollHeight > 0) {
            this.field.style.height = this.field.scrollHeight + 'px';
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-ea-textarea-field]').forEach(function (field) {
        new Autogrow(field);
    });
});
