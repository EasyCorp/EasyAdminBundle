const slugify = require('slugify');
slugify.extend({
    $: '',
    '%': '',
    '&': '',
    '<': '',
    '>': '',
    '|': '',
    '¢': '',
    '£': '',
    '¤': '',
    '¥': '',
    '₠': '',
    '₢': '',
    '₣': '',
    '₤': '',
    '₥': '',
    '₦': '',
    '₧': '',
    '₨': '',
    '₩': '',
    '₪': '',
    '₫': '',
    '€': '',
    '₭': '',
    '₮': '',
    '₯': '',
    '₰': '',
    '₱': '',
    '₲': '',
    '₳': '',
    '₴': '',
    '₵': '',
    '₸': '',
    '₹': '',
    '₽': '',
    '₿': '',
    '∂': '',
    '∆': '',
    '∑': '',
    '∞': '',
    '♥': '',
    元: '',
    円: '',
    '﷼': '',
});

class Slugger {
    constructor(field) {
        this.field = field;
        this.setTargetElement();
        this.locked = true;
        this.field.setAttribute('readonly', 'readonly');

        if ('' === this.field.value) {
            this.currentSlug = '';
            this.updateValue();
            this.listenTarget();
        } else {
            this.currentSlug = this.field.value;
        }

        this.appendLockButton();
    }

    setTargetElement() {
        const fieldNames = JSON.parse(this.field.dataset.target);
        this.targets = [];

        for (const name of fieldNames) {
            const target = document.getElementById(name);

            if (null === target) {
                throw `Wrong target specified for slug widget ("${name}").`;
            }

            this.targets.push(target);
        }
    }

    /**
     * Append a "lock" button to control slug behaviour (auto or manual)
     */
    appendLockButton() {
        this.lockButton = this.field.parentNode.querySelector('button');
        this.lockButton.addEventListener('click', () => {
            if (this.locked) {
                const confirmMessage = this.field.dataset.confirmText || null;
                if (null === confirmMessage) {
                    this.unlock();
                } else {
                    const formattedConfirmMessage = decodeURIComponent(
                        JSON.parse(`"${confirmMessage.replace(/\"/g, '\\"')}"`)
                    );
                    if (true === confirm(formattedConfirmMessage)) {
                        this.unlock();
                    }
                }
            } else {
                this.lock();
            }
        });
    }

    /**
     * Unlock the widget input (manual mode)
     */
    unlock() {
        this.locked = false;
        this.lockButton.innerHTML = this.lockButton.getAttribute('data-icon-unlocked');
        this.field.removeAttribute('readonly');
    }

    /**
     * Lock the widget input (auto mode)
     */
    lock() {
        this.locked = true;
        this.lockButton.innerHTML = this.lockButton.getAttribute('data-icon-locked');

        // Locking it back changes the value either to default value, or recomputes it
        if ('' !== this.currentSlug) {
            this.field.value = this.currentSlug;
        } else {
            this.updateValue();
        }

        this.field.setAttribute('readonly', 'readonly');
    }

    updateValue() {
        this.field.value = slugify(this.targets.map((target) => target.value).join('-'), {
            remove: /[^A-Za-z0-9\s-]/g,
            lower: true,
            strict: true,
        });
    }

    /**
     * Observe the target field and slug it
     */
    listenTarget() {
        for (const target of this.targets) {
            target.addEventListener('input', () => {
                if ('readonly' === this.field.getAttribute('readonly')) {
                    this.updateValue();
                }
            });
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-ea-slug-field]').forEach((field) => {
        new Slugger(field);
    });
});
