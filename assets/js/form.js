import DirtyForm from 'dirty-form';

document.addEventListener('DOMContentLoaded', () => {
    new Form();
});

class Form {
    constructor() {
        this.#persistSelectedTab();
        this.#createUnsavedFormChangesWarning();
        this.#createFieldsWithErrors();
        this.#preventMultipleFormSubmission();
    }

    #persistSelectedTab() {
        // the ID of the selected tab is appended as a hash in the URL to persist it;
        // if the URL has a hash, try to look for a tab with that ID and show it
        const urlHash = window.location.hash;
        if (urlHash) {
            const selectedTabPaneId = urlHash.substring(1); // remove the leading '#' from the hash
            const selectedTabId = `tablist-${ selectedTabPaneId }`;
            this.#setTabAsActive(selectedTabId);
        }

        // update the page anchor when the selected tab changes
        document.querySelectorAll('a[data-bs-toggle="tab"]').forEach((tabElement) => {
            tabElement.addEventListener('shown.bs.tab', function (event) {
                const urlHash = '#' + event.target.getAttribute('href').substring(1);
                history.pushState({}, '', urlHash);
            });
        });
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
            const that = this;
            document.querySelector('.ea-edit, .ea-new').querySelectorAll('[type="submit"]').forEach((button) => {
                button.addEventListener('click', function onSubmitButtonsClick(clickEvent) {
                    let formHasErrors = false;

                    // Remove all error counter badges
                    document.querySelectorAll('.form-tabs-tablist .nav-item .badge-danger.badge').forEach( (badge) => {
                        badge.parentElement.removeChild(badge);
                    });

                    if (null !== form.getAttribute('novalidate')) {
                        return;
                    }

                    form.querySelectorAll('input, select, textarea').forEach((input) => {
                        if (!input.disabled && !input.validity.valid) {
                            formHasErrors = true;

                            // Visual feedback for tabs
                            // Adding a badge with a error count next to the tab label
                            const formTab = input.closest('div.tab-pane');
                            if (formTab) {
                                // Match tab link either by "data-bs-target" attribute or by href linking to the id anchor
                                const navLinkTab = document.querySelector(`[data-bs-target="#${ formTab.id }"], a[href="#${ formTab.id }"]`);

                                if (navLinkTab) {
                                    navLinkTab.classList.add('has-error');

                                    const badge = navLinkTab.querySelector('.badge');
                                    if (badge) {
                                        // Increment number of error
                                        badge.textContent = (parseInt(badge.textContent) + 1).toString();
                                    } else {
                                        // Create a new badge
                                        let newErrorBadge = document.createElement('span');
                                        newErrorBadge.classList.add('badge', 'badge-danger');
                                        newErrorBadge.textContent = '1';
                                        navLinkTab.appendChild(newErrorBadge);
                                    }
                                }
                            }

                            // Visual feedback for group
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
                        const firstTabWithErrors = document.querySelector('.form-tabs-tablist .nav-tabs .nav-item .nav-link.has-error');
                        if (null !== firstTabWithErrors) {
                            that.#setTabAsActive(firstTabWithErrors.id);
                        }

                        document.dispatchEvent(new CustomEvent('ea.form.error', {
                            cancelable: true,
                            detail: { page: pageName, form: form }
                        }));
                    }
                });
            });

            form.addEventListener('submit', (submitEvent) => {
                const eaEvent = new CustomEvent('ea.form.submit', {
                    cancelable: true,
                    detail: { page: pageName, form: form }
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

    #setTabAsActive(tabItemId) {
        const tabElement = document.getElementById(tabItemId);
        if (!tabElement) {
            return;
        }

        const Tab = bootstrap.Tab;
        const bootstrapTab = new Tab(tabElement);
        // when showing a tab, Bootstrap hides all the other tabs automatically
        bootstrapTab.show();
    }

    #preventMultipleFormSubmission() {
        ['.ea-new-form', '.ea-edit-form'].forEach((formSelector) => {
            const form = document.querySelector(formSelector);
            if (null === form) {
                return;
            }

            form.addEventListener('submit', () => {
                // this timeout is needed to include the disabled button into the submitted form
                setTimeout(() => {
                    const submitButtons = document.querySelector('.ea-edit, .ea-new').querySelectorAll('[type="submit"]');
                    submitButtons.forEach((button) => {
                        button.setAttribute('disabled', 'disabled');
                    });
                }, 1);
            }, false);
        });
    }
}
