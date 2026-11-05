require('../css/form-type-text-editor.css');

import Trix from 'trix/dist/trix.esm';

// Provide Trix variable globally to allow custom backend pages to use it
window.Trix = Trix;

// DOMContentLoaded is too late because Trix editors are already initialized by then (and the custom
// config must be applied before that); this event is fired once per editor, including the ones
// added later (e.g. in collections), so the fields are processed again each time
let textEditorField = null;
document.addEventListener('trix-before-initialize', () => {
    textEditorField ??= new TextEditorField();
    textEditorField.processFields();
});

class TextEditorField {
    constructor() {
        this.#enableFormChangesDetection();
        this.#handleFormSubmission();
        document.addEventListener('trix-change', () => {
            this.#markInvalidFormFields();
        });
    }

    processFields() {
        this.#processRequiredAttribute();
        // without the 'required' attribute, the custom validity makes form.js mark the
        // empty required fields (and their tabs) as invalid
        this.#markInvalidFormFields();
    }

    #processRequiredAttribute() {
        // the content of Trix is stored in a hidden <textarea>; when it's required and empty, browsers fail
        // to display the validation error ("An invalid form control with name='foo' is not focusable"),
        // so the 'required' attribute is replaced by a custom attribute checked later
        document.querySelectorAll('textarea.ea-text-editor-content').forEach((trixContentElement) => {
            const trixEditorConfig = JSON.parse(trixContentElement.getAttribute('data-trix-editor-config'));
            if (null !== trixEditorConfig) {
                Trix.config = this.#mergeObjects(trixEditorConfig, Trix.config);
            }

            // the fields processed before no longer have the 'required' attribute
            if (!trixContentElement.hasAttribute('data-ea-trix-is-required')) {
                const isTrixFieldRequired =
                    'required' === trixContentElement.getAttribute('required') ? 'true' : 'false';
                trixContentElement.setAttribute('data-ea-trix-is-required', isTrixFieldRequired);
                trixContentElement.removeAttribute('required');
            }

            if (trixContentElement.dataset.numberOfRows !== '') {
                const escapedId = CSS.escape(trixContentElement.id);
                const editor = document.querySelector(`trix-editor[input="${escapedId}"].trix-content`);

                if (editor !== null) {
                    // a <style> element instead of an inline style, so strict CSPs (that disallow
                    // `style-src-attr 'unsafe-inline'`) don't strip it; 21px is the average line height
                    const styleElementId = `ea-trix-editor-size-${trixContentElement.id}`;
                    const styleElement = document.getElementById(styleElementId) ?? document.createElement('style');
                    styleElement.id = styleElementId;
                    const nonce = document.querySelector('meta[name="csp-nonce"]')?.getAttribute('content');
                    if (nonce) {
                        styleElement.setAttribute('nonce', nonce);
                    }

                    styleElement.textContent = `trix-editor[input="${escapedId}"] { min-block-size: ${21 * trixContentElement.dataset.numberOfRows}px; }`;
                    if (!styleElement.isConnected) {
                        document.head.appendChild(styleElement);
                    }
                }
            }
        });
    }

    // Trix updates the value of its <textarea> without firing an 'input' event, so DirtyForm
    // (created by form.js) wouldn't detect the changes otherwise
    #enableFormChangesDetection() {
        document.addEventListener('trix-change', (event) => {
            event.target.inputElement?.dispatchEvent(new Event('input'));
        });
    }

    #handleFormSubmission() {
        document.addEventListener('ea.form.submit', (formEvent) => {
            const entityForm = formEvent.detail.form;
            entityForm.querySelectorAll('textarea.ea-text-editor-content').forEach((trixContentElement) => {
                const isTrixFieldRequired = 'true' === trixContentElement.getAttribute('data-ea-trix-is-required');
                const escapedId = CSS.escape(trixContentElement.id);
                const trixEditorElement = entityForm.querySelector(`trix-editor[input="${escapedId}"]`);
                // an empty Trix editor field is not really empty; it contains a "\n" character (%0A = HTML encoded)
                const isTrixEditorEmpty = '%0A' === escape(trixEditorElement.editor.getDocument().toString());

                if (isTrixFieldRequired && isTrixEditorEmpty) {
                    const formGroup = trixContentElement.closest('div.form-group');
                    formGroup.classList.add('has-error');
                    formGroup.addEventListener('click', function onFormGroupClick() {
                        formGroup.classList.remove('has-error');
                        formGroup.removeEventListener('click', onFormGroupClick);
                    });

                    const errorMessage = this.#getLocalizedErrorMessage();
                    const errorElement = document.createElement('div');
                    errorElement.classList.add('invalid-feedback', 'd-block');
                    errorElement.innerHTML = `<span class="form-error-message">${errorMessage}</span>`;
                    trixContentElement.closest('.form-widget').append(errorElement);

                    formEvent.preventDefault();
                }
            });
        });
    }

    #markInvalidFormFields() {
        ['.ea-new-form', '.ea-edit-form'].forEach((formSelector) => {
            const form = document.querySelector(formSelector);
            if (null !== form) {
                form.querySelectorAll('input,select,textarea').forEach((input) => {
                    if (
                        input.hasAttribute('data-ea-trix-is-required') &&
                        input.getAttribute('data-ea-trix-is-required') === 'true' &&
                        input.value === ''
                    ) {
                        input.setCustomValidity('invalid');
                    } else {
                        input.setCustomValidity('');
                    }
                });
            }
        });
    }

    #getLocalizedErrorMessage() {
        // copied from https://github.com/chromium/chromium/search?p=1&q=2507943997699731163
        const requiredFieldMessage = {
            ar: 'يُرجى ملء هذا الحقل.',
            bg: 'Моля, попълнете това поле',
            ca: 'Empleneu aquest camp',
            cs: 'Vyplňte prosím toto pole',
            da: 'Udfyld dette felt',
            de: 'Füllen Sie dieses Feld aus',
            el: 'Συμπληρώστε αυτό το πεδίο',
            en: 'Please fill in this field',
            es: 'Completa este campo',
            eu: 'Bete eremu hau',
            fa: 'لطفاً این قسمت را تکمیل کنید.',
            fi: 'Täytä tämä kenttä',
            fr: 'Veuillez renseigner ce champ',
            gl: 'Completa este campo',
            hr: 'Ispunite ovo polje',
            hu: 'Kérjük, töltse ki ezt a mezőt',
            it: 'Compila questo campo',
            lt: 'Užpildykite šį lauką',
            nl: 'Vul dit veld in',
            no: 'Vennligst fyll ut dette feltet',
            pl: 'Wypełnij to pole',
            pt: 'Preencha este campo',
            pt_BR: 'Preencha este campo',
            ro: 'Completează acest câmp',
            ru: 'Заполните это поле',
            sl: 'Izpolnite to polje',
            sr_RS: 'Попуните ово поље',
            sv: 'Fyll i det här fältet',
            tr: 'Lütfen bu alanı doldurun',
            uk: 'Заповніть це поле',
            zh_CN: '请填写此字段',
        };

        return requiredFieldMessage[document.querySelector('html').getAttribute('lang')] || 'Please fill in this field';
    }

    // copied from https://gist.github.com/ahtcx/0cd94e62691f539160b32ecda18af3d6?permalink_comment_id=3889214#gistcomment-3889214
    #mergeObjects(fromObject, intoObject) {
        for (const [key, val] of Object.entries(fromObject)) {
            if (val !== null && typeof val === 'object') {
                if (intoObject[key] === undefined) {
                    intoObject[key] = new val.__proto__.constructor();
                }
                this.#mergeObjects(val, intoObject[key]);
            } else {
                intoObject[key] = val;
            }
        }

        return intoObject;
    }
}
