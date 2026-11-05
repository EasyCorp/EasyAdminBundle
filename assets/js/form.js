import DirtyForm from 'dirty-form';
import { setTabAsActive } from './tabs';

document.addEventListener('DOMContentLoaded', () => {
    new Form();
});

class Form {
    constructor() {
        this.#createUnsavedFormChangesWarning();
        this.#createFieldsWithErrors();
        this.#preventMultipleFormSubmission();
    }

    #createUnsavedFormChangesWarning() {
        ['.ea-new-form', '.ea-edit-form'].forEach((formSelector) => {
            const form = document.querySelector(formSelector);
            if (null === form) {
                return;
            }

            // although DirtyForm supports passing a custom message to display,
            // modern browsers don't allow to display custom messages to protect users
            new DirtyForm(form);
        });
    }

    #createFieldsWithErrors() {
        const handleFieldsWithErrors = (form, pageName) => {
            // errors are intercepted before submit to avoid the browser error "An invalid form control
            // with name='...' is not focusable" (e.g. for fields inside hidden tabs); the 'has-error'
            // class of form groups is removed when clicking on them to support custom/complex fields
            //
            // the default submit buttons live outside the <form> element and are associated to it
            // via the HTML 'form' attribute; that's why this uses form.elements (which includes
            // those buttons) instead of form.querySelectorAll() (which only finds descendants)
            Array.from(form.elements)
                .filter((element) => 'submit' === element.type)
                .forEach((button) => {
                    button.addEventListener('click', function onSubmitButtonsClick(clickEvent) {
                        let formHasErrors = false;

                        // '.tab-nav-item-badge' are the custom badges configured for tabs, not error counters
                        document
                            .querySelectorAll(
                                '.form-tabs-tablist .nav-item .badge-danger.badge:not(.tab-nav-item-badge), .form-fieldset-title-content .badge-danger.badge'
                            )
                            .forEach((badge) => {
                                badge.parentElement.removeChild(badge);
                            });
                        document.querySelectorAll('.form-fieldset.has-fieldset-error').forEach((fieldset) => {
                            fieldset.classList.remove('has-fieldset-error');
                        });

                        if (null !== form.getAttribute('novalidate')) {
                            return;
                        }

                        form.querySelectorAll('input, select, textarea').forEach((input) => {
                            if (!input.disabled && !input.validity.valid) {
                                formHasErrors = true;

                                const formTab = input.closest('div.tab-pane');
                                if (formTab) {
                                    const navLinkTab = document.querySelector(
                                        `[data-bs-target="#${formTab.id}"], a[href="#${formTab.id}"]`
                                    );

                                    if (navLinkTab) {
                                        navLinkTab.classList.add('has-error');

                                        const badge = navLinkTab.querySelector('.badge:not(.tab-nav-item-badge)');
                                        if (badge) {
                                            badge.textContent = (Number.parseInt(badge.textContent) + 1).toString();
                                        } else {
                                            const newErrorBadge = document.createElement('span');
                                            newErrorBadge.classList.add('badge', 'badge-danger');
                                            newErrorBadge.textContent = '1';
                                            navLinkTab.appendChild(newErrorBadge);
                                        }
                                    }
                                }

                                const formFieldset = input.closest('div.form-fieldset');
                                if (formFieldset) {
                                    const fieldsetTitleContent =
                                        formFieldset.querySelector('.form-fieldset-title-content');

                                    formFieldset.classList.add('has-fieldset-error');

                                    if (fieldsetTitleContent) {
                                        const badge = fieldsetTitleContent.querySelector('.badge');
                                        if (badge) {
                                            badge.textContent = (Number.parseInt(badge.textContent) + 1).toString();
                                        } else {
                                            const newErrorBadge = document.createElement('span');
                                            newErrorBadge.classList.add('badge', 'badge-danger');
                                            newErrorBadge.textContent = '1';
                                            fieldsetTitleContent.appendChild(newErrorBadge);
                                        }
                                    }
                                }

                                const formGroup = input.closest('div.form-group');
                                formGroup?.classList.add('has-error');
                                formGroup?.addEventListener('click', () => formGroup.classList.remove('has-error'), {
                                    once: true,
                                });
                            }
                        });

                        if (formHasErrors) {
                            clickEvent.preventDefault();
                            clickEvent.stopPropagation();

                            const firstTabWithErrors = document.querySelector(
                                '.form-tabs-tablist .nav-tabs .nav-item .nav-link.has-error'
                            );
                            if (null !== firstTabWithErrors) {
                                setTabAsActive(firstTabWithErrors.id);
                            }

                            document
                                .querySelectorAll('.form-fieldset.has-fieldset-error')
                                .forEach((fieldsetWithErrors) => {
                                    const collapsedBody = fieldsetWithErrors.querySelector(
                                        '.form-fieldset-body.collapse:not(.show)'
                                    );
                                    if (collapsedBody) {
                                        bootstrap.Collapse.getOrCreateInstance(collapsedBody, { toggle: false }).show();
                                        const collapseToggle =
                                            fieldsetWithErrors.querySelector('.form-fieldset-collapse');
                                        if (collapseToggle) {
                                            collapseToggle.classList.remove('collapsed');
                                            collapseToggle.setAttribute('aria-expanded', 'true');
                                        }
                                    }
                                });

                            document.dispatchEvent(
                                new CustomEvent('ea.form.error', {
                                    cancelable: true,
                                    detail: { page: pageName, form: form },
                                })
                            );
                        }
                    });
                });

            form.addEventListener('submit', (submitEvent) => {
                const eaEvent = new CustomEvent('ea.form.submit', {
                    cancelable: true,
                    detail: { page: pageName, form: form },
                });
                const eaEventResult = document.dispatchEvent(eaEvent);
                if (false === eaEventResult) {
                    submitEvent.preventDefault();
                    submitEvent.stopPropagation();
                }
            });
        };

        ['.ea-new-form', '.ea-edit-form'].forEach((formSelector) => {
            const form = document.querySelector(formSelector);
            if (null !== form) {
                handleFieldsWithErrors(form, formSelector.includes('-new-') ? 'new' : 'edit');
            }
        });
    }

    #preventMultipleFormSubmission() {
        ['.ea-new-form', '.ea-edit-form'].forEach((formSelector) => {
            const form = document.querySelector(formSelector);
            if (null === form) {
                return;
            }

            form.addEventListener(
                'submit',
                (submitEvent) => {
                    if (submitEvent.defaultPrevented) {
                        return;
                    }

                    // this timeout is needed to include the disabled button into the submitted form
                    setTimeout(() => {
                        Array.from(form.elements)
                            .filter((element) => 'submit' === element.type)
                            .forEach((button) => {
                                button.setAttribute('disabled', 'disabled');
                            });
                    }, 1);
                },
                false
            );
        });
    }
}
