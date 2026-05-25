import DirtyForm from 'dirty-form';

document.addEventListener('DOMContentLoaded', () => {
    new Form();
});

class Form {
    #isNavigatingHistory = false;

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
            // Intercept errors before submit to avoid browser error "An invalid form control with name='...' is not focusable."
            //
            // Adding visual feedback for invalid fields: any ".form-group" with invalid fields
            // receives "has-error" class. The class is removed on click on the ".form-group"
            // itself to support custom/complex fields.
            //
            // Adding visual error counter feedback for invalid fields inside form tabs (visible or not)
            document
                .querySelector('.ea-edit, .ea-new')
                .querySelectorAll('[type="submit"]')
                .forEach((button) => {
                    button.addEventListener('click', function onSubmitButtonsClick(clickEvent) {
                        let formHasErrors = false;

                        // remove all error counter badges (tabs and fieldsets)
                        document
                            .querySelectorAll(
                                '.form-tabs-tablist .nav-item .badge-danger.badge, .form-fieldset-title-content .badge-danger.badge'
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

                                // visual feedback for tabs: adding a badge with a error count next to the tab label
                                const formTab = input.closest('div.tab-pane');
                                if (formTab) {
                                    // match tab link either by "data-bs-target" attribute or by href linking to the id anchor
                                    const navLinkTab = document.querySelector(
                                        `[data-bs-target="#${formTab.id}"], a[href="#${formTab.id}"]`
                                    );

                                    if (navLinkTab) {
                                        navLinkTab.classList.add('has-error');

                                        const badge = navLinkTab.querySelector('.badge');
                                        if (badge) {
                                            // increment number of error
                                            badge.textContent = (Number.parseInt(badge.textContent) + 1).toString();
                                        } else {
                                            // create a new badge
                                            const newErrorBadge = document.createElement('span');
                                            newErrorBadge.classList.add('badge', 'badge-danger');
                                            newErrorBadge.textContent = '1';
                                            navLinkTab.appendChild(newErrorBadge);
                                        }
                                    }
                                }

                                // visual feedback for fieldsets
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

                                // visual feedback for group
                                const formGroup = input.closest('div.form-group');
                                formGroup.classList.add('has-error');

                                formGroup.addEventListener('click', function onFormGroupClick() {
                                    formGroup.classList.remove('has-error');
                                    formGroup.removeEventListener('click', onFormGroupClick);
                                });
                            }
                        });

                        if (formHasErrors) {
                            clickEvent.preventDefault();
                            clickEvent.stopPropagation();

                            // set as active the first tab with errors
                            const firstTabWithErrors = document.querySelector(
                                '.form-tabs-tablist .nav-tabs .nav-item .nav-link.has-error'
                            );
                            if (null !== firstTabWithErrors) {
                                // Activate (show) the first tab with errors
                                const Tab = bootstrap.Tab;
                                const bootstrapTab = new Tab(firstTabWithErrors);
                                bootstrapTab.show();
                            }

                            // auto-expand all collapsed fieldsets with errors
                            document
                                .querySelectorAll('.form-fieldset.has-fieldset-error')
                                .forEach((fieldsetWithErrors) => {
                                    const collapsedBody = fieldsetWithErrors.querySelector(
                                        '.form-fieldset-body.collapse:not(.show)'
                                    );
                                    if (collapsedBody) {
                                        const Collapse = bootstrap.Collapse;
                                        new Collapse(collapsedBody, { toggle: true });
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
                () => {
                    // this timeout is needed to include the disabled button into the submitted form
                    setTimeout(() => {
                        const submitButtons = document
                            .querySelector('.ea-edit, .ea-new')
                            .querySelectorAll('[type="submit"]');
                        submitButtons.forEach((button) => {
                            button.setAttribute('disabled', 'disabled');
                        });
                    }, 1);
                },
                false
            );
        });
    }
}
