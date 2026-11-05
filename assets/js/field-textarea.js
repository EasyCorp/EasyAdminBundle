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

        // the <textarea> can be inside a hidden tab or a collapsed panel; its computed width
        // is 'auto' there, so the mirror would compute a wrong height
        if (0 === this.field.getClientRects().length) {
            return;
        }

        // measuring a mirror avoids jumps when editing content in large textareas
        // (see https://github.com/EasyCorp/EasyAdminBundle/issues/7258)
        // TODO: remove this when `field-sizing: content` is widely supported (https://caniuse.com/?search=field-sizing)
        if (!this.mirror) {
            this.createMirror();
        }

        this.mirror.style.width = window.getComputedStyle(this.field).width;
        this.mirror.value = this.field.value;
        this.field.style.height = `${this.mirror.scrollHeight}px`;
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
