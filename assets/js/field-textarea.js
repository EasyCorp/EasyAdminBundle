class Autogrow {
    constructor(field) {
        this.field = field;
        this.mirror = null;
        this.field.addEventListener('input', this.autogrow.bind(this));
        this.autogrow();
    }

    autogrow() {
        this.field.style.overflow = 'hidden';
        this.field.style.resize = 'none';
        this.field.style.boxSizing = 'border-box';

        // this is needed to avoid jumps when editing content in large textareas
        // see https://github.com/EasyCorp/EasyAdminBundle/issues/7258
        // TODO: remove this when `field-sizing: content` is widely supported (https://caniuse.com/?search=field-sizing)
        if (!this.mirror) {
            this.createMirror();
        }

        // sync width (use computedStyle to get actual rendered width)
        this.mirror.style.width = window.getComputedStyle(this.field).width;

        // copy content to mirror
        this.mirror.value = this.field.value;

        const newHeight = this.mirror.scrollHeight;

        // this check is needed because the <textarea> element can be inside a
        // minimizable panel, causing its scrollHeight value to be 0
        if (newHeight > 0) {
            this.field.style.height = `${newHeight}px`;
        }
    }

    createMirror() {
        this.mirror = document.createElement('textarea');

        // position off-screen but rendered (so we can measure it)
        this.mirror.style.position = 'absolute';
        this.mirror.style.top = '-9999px';
        this.mirror.style.left = '-9999px';
        this.mirror.style.visibility = 'hidden';
        this.mirror.style.pointerEvents = 'none';
        this.mirror.tabIndex = -1;
        this.mirror.setAttribute('aria-hidden', 'true');

        // copy the rows attribute to ensure minimum height is respected
        if (this.field.hasAttribute('rows')) {
            this.mirror.setAttribute('rows', this.field.getAttribute('rows'));
        }

        // copy computed styles that affect text layout
        const styles = window.getComputedStyle(this.field);
        const stylesToCopy = [
            'fontFamily',
            'fontSize',
            'fontWeight',
            'fontStyle',
            'letterSpacing',
            'lineHeight',
            'textTransform',
            'wordWrap',
            'wordSpacing',
            'paddingTop',
            'paddingRight',
            'paddingBottom',
            'paddingLeft',
            'borderTopWidth',
            'borderRightWidth',
            'borderBottomWidth',
            'borderLeftWidth',
            'boxSizing',
        ];
        stylesToCopy.forEach((prop) => {
            this.mirror.style[prop] = styles[prop];
        });

        document.body.appendChild(this.mirror);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-ea-textarea-field]').forEach((field) => {
        new Autogrow(field);
    });
});
