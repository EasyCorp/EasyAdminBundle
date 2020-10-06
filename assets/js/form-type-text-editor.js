require('../css/form-type-text-editor.css');

import 'trix/dist/trix';

// copied from https://github.com/chromium/chromium/search?p=1&q=2507943997699731163
const requiredFieldMessage = {
    'ar': 'يُرجى ملء هذا الحقل.',
    'bg': 'Моля, попълнете това поле',
    'ca': 'Empleneu aquest camp',
    'cs': 'Vyplňte prosím toto pole',
    'da': 'Udfyld dette felt',
    'de': 'Füllen Sie dieses Feld aus',
    'el': 'Συμπληρώστε αυτό το πεδίο',
    'en': 'Please fill in this field',
    'es': 'Completa este campo',
    'eu': 'Bete eremu hau',
    'fa': 'لطفاً این قسمت را تکمیل کنید.',
    'fi': 'Täytä tämä kenttä',
    'fr': 'Veuillez renseigner ce champ',
    'gl': 'Completa este campo',
    'hr': 'Ispunite ovo polje',
    'hu': 'Kérjük, töltse ki ezt a mezőt',
    'it': 'Compila questo campo',
    'lt': 'Užpildykite šį lauką',
    'nl': 'Vul dit veld in',
    'no': 'Vennligst fyll ut dette feltet',
    'pl': 'Wypełnij to pole',
    'pt': 'Preencha este campo',
    'pt_BR': 'Preencha este campo',
    'ro': 'Completează acest câmp',
    'ru': 'Заполните это поле',
    'sl': 'Izpolnite to polje',
    'sr_RS': 'Попуните ово поље',
    'sv': 'Fyll i det här fältet',
    'tr': 'Lütfen bu alanı doldurun',
    'uk': 'Заповніть це поле',
    'zh_CN': '请填写此字段',
};

document.addEventListener('DOMContentLoaded', () => {
    // TrixEditor works by storing the original content in a hidden <textarea> and creating a new <trix-editor> element
    // When the original field is required and its content is empty, browsers try to add a validation error, but it
    // fails because the element is hidden ("An invalid form control with name=”foo" is not focusable")
    // That's why we remove the HTML required attribute and store it in a custom attribute that will be check later
    document.querySelectorAll('textarea.ea-text-editor-content').forEach((trixContentElement) => {
        const isTrixFieldRequired = 'required' === trixContentElement.getAttribute('required') ? 'true' : 'false';
        trixContentElement.setAttribute('data-ea-trix-is-required', isTrixFieldRequired);
        trixContentElement.removeAttribute('required');
    });

    // Because of the way TrixEditor works, the jquery.are-you-sure plugin cannot detect changes to these fields automatically,
    // so we manually trigger the plugin when the content changes.
    document.addEventListener('trix-change', function (event) {
        $(event.target).closest('form').trigger('checkform.areYouSure');
    });

    document.addEventListener('ea.form.submit', (formEvent) => {
        const entityForm = formEvent.detail.form;
        entityForm.querySelectorAll('textarea.ea-text-editor-content').forEach(function (trixContentElement) {
            const isTrixFieldRequired = 'true' === trixContentElement.getAttribute('data-ea-trix-is-required');
            const trixEditorElement = entityForm.querySelector(`trix-editor[input=${trixContentElement.id}]`);
            // an empty Trix editor field is not really empty; it contains a "\n" character (%0A = HTML encoded)
            const isTrixEditorEmpty = '%0A' === escape(trixEditorElement.editor.getDocument().toString());

            if (isTrixFieldRequired && isTrixEditorEmpty) {
                const formGroup = trixContentElement.closest('div.form-group');
                formGroup.classList.add('has-error');
                formGroup.addEventListener('click', function onFormGroupClick() {
                    formGroup.classList.remove('has-error');
                    formGroup.removeEventListener('click', onFormGroupClick);
                });

                const errorMessage = requiredFieldMessage[document.querySelector('html').getAttribute('lang')] || 'Please fill in this field';
                let errorElement = document.createElement('div');
                errorElement.classList.add('invalid-feedback', 'd-block');
                errorElement.innerHTML = `<span class="form-error-message">${ errorMessage }</span>`;
                trixContentElement.closest('.form-widget').append(errorElement);

                formEvent.preventDefault();
            }
        });
    });
});
