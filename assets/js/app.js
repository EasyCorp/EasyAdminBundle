// any CSS you require will output into a single css file (app.css in this case)
require('../css/app.scss');

import bootstrap from 'bootstrap/dist/js/bootstrap.bundle';
import Mark from 'mark.js/src/vanilla';
import DirtyForm from 'dirty-form';
import * as basicLightbox from 'basiclightbox';
import Autocomplete from './autocomplete';

document.addEventListener('DOMContentLoaded', () => {
    App.createMainMenu();
    App.createLayoutResizeControls();
    App.createNavigationToggler();
    App.createSearchHighlight();
    App.createFilters();
    App.createToggleFields();
    App.createAutoCompleteFields();
    App.createBatchActions();
    App.createModalWindowsForDeleteActions();
    App.createPopovers();
    App.createTooltips();
    App.createUnsavedFormChangesWarning();
    App.createNullableFields();
    App.createImageFields();
    App.createFileUploadFields();
    App.createFieldsWithErrors();
    App.preventMultipleFormSubmission();

    document.addEventListener('ea.collection.item-added', () => App.createAutoCompleteFields());
});

const App = (() => {
    const createMainMenu = () => {
        // inspired by https://codepen.io/phileflanagan/pen/mwpQpY
        const menuItemsWithSubmenus = document.querySelectorAll('#main-menu .menu-item.has-submenu');
        menuItemsWithSubmenus.forEach((menuItem) => {
            const menuItemSubmenu = menuItem.querySelector('.submenu');

            // needed because the menu accordion is based on the max-height property.
            // visible elements must be initialized with a explicit max-height; otherwise
            // when you click on them the first time, the animation is not smooth
            if (menuItem.classList.contains('expanded')) {
                menuItemSubmenu.style.maxHeight = menuItemSubmenu.scrollHeight + 'px';
            }

            menuItem.querySelector('.submenu-toggle').addEventListener('click', (event) =>  {
                event.preventDefault();

                // hide other submenus
                menuItemsWithSubmenus.forEach((otherMenuItem) => {
                    if (menuItem === otherMenuItem) {
                        return;
                    }

                    const otherMenuItemSubmenu = otherMenuItem.querySelector('.submenu');
                    if (otherMenuItem.classList.contains('expanded')) {
                        otherMenuItemSubmenu.style.maxHeight = '0px';
                        otherMenuItem.classList.remove('expanded');
                    }
                });

                // toggle the state of this submenu
                if (menuItem.classList.contains('expanded')) {
                    menuItemSubmenu.style.maxHeight = '0px';
                    menuItem.classList.remove('expanded');
                } else {
                    menuItemSubmenu.style.maxHeight = menuItemSubmenu.scrollHeight + 'px';
                    menuItem.classList.add('expanded');
                }
            });
        });
    };

    const createLayoutResizeControls = () => {
        const sidebarResizerHandler = document.getElementById('sidebar-resizer-handler');
        if (null !== sidebarResizerHandler) {
            sidebarResizerHandler.addEventListener('click', () => {
                const oldValue = localStorage.getItem('ea/sidebar/width') || 'normal';
                const newValue = 'normal' == oldValue ? 'compact' : 'normal';

                document.querySelector('body').classList.remove('ea-sidebar-width-' + oldValue);
                document.querySelector('body').classList.add('ea-sidebar-width-' + newValue);
                localStorage.setItem('ea/sidebar/width', newValue);
            });
        }

        const contentResizerHandler = document.getElementById('content-resizer-handler');
        if (null !== contentResizerHandler) {
            contentResizerHandler.addEventListener('click', () => {
                const oldValue = localStorage.getItem('ea/content/width') || 'normal';
                const newValue = 'normal' == oldValue ? 'full' : 'normal';

                document.querySelector('body').classList.remove('ea-content-width-' + oldValue);
                document.querySelector('body').classList.add('ea-content-width-' + newValue);
                localStorage.setItem('ea/content/width', newValue);
            });
        }
    }

    const createNavigationToggler = () => {
        const toggler = document.querySelector('#navigation-toggler');
        const cssClassName = 'ea-mobile-sidebar-visible';
        let modalBackdrop;

        if (null === toggler) {
            return;
        }

        toggler.addEventListener('click', () => {
            document.querySelector('body').classList.toggle(cssClassName);

            if (document.querySelector('body').classList.contains(cssClassName)) {
                modalBackdrop = document.createElement('div');
                modalBackdrop.classList.add('modal-backdrop', 'fade', 'show');
                modalBackdrop.onclick = () => {
                    document.querySelector('body').classList.remove(cssClassName);
                    document.body.removeChild(modalBackdrop);
                    modalBackdrop = null;
                };

                document.body.appendChild(modalBackdrop);
            } else if (modalBackdrop) {
                document.body.removeChild(modalBackdrop);
                modalBackdrop = null;
            }
        });
    };

    const createSearchHighlight = () => {
        const searchElement = document.querySelector('.form-action-search [name="query"]');
        if (null === searchElement) {
            return;
        }

        const searchQuery = searchElement.value;
        if ('' === searchQuery.trim()) {
            return;
        }

        const elementsToHighlight = document.querySelectorAll('table tbody td:not(.actions)');
        const highlighter = new Mark(elementsToHighlight);
        highlighter.mark(searchQuery);
    };

    const createFilters = () => {
        const filterButton = document.querySelector('.datagrid-filters .action-filters-button');
        if (null === filterButton) {
            return;
        }

        const filterModal = document.querySelector(filterButton.getAttribute('data-bs-target'));

        // this is needed to avoid errors when connection is slow
        filterButton.setAttribute('href', filterButton.getAttribute('data-href'));
        filterButton.removeAttribute('data-href');
        filterButton.classList.remove('disabled');

        filterButton.addEventListener('click', (event) => {
            const filterModalBody = filterModal.querySelector('.modal-body');
            filterModalBody.innerHTML = '<div class="fa-3x px-3 py-3 text-muted text-center"><i class="fas fa-circle-notch fa-spin"></i></div>';

            fetch(filterButton.getAttribute('href'))
                .then((response) => { return response.text(); })
                .then((text) => {
                    setInnerHTMLAndRunScripts(filterModalBody, text);
                    App.createAutoCompleteFields();
                })
                .catch((error) => { console.error(error); });

            event.preventDefault();
        });

        const removeFilter = (filterField) => {
            filterField.closest('form').querySelectorAll(`input[name^="filters[${filterField.dataset.filterProperty}]"]`).forEach((filterFieldInput) => {
                filterFieldInput.remove();
            });

            filterField.remove();
        };

        document.querySelector('#modal-clear-button').addEventListener('click', () => {
            filterModal.querySelectorAll('.filter-field').forEach((filterField) => {
                removeFilter(filterField);
            });
            filterModal.querySelector('form').submit();
        });

        document.querySelector('#modal-apply-button').addEventListener('click', () => {
            filterModal.querySelectorAll('.filter-checkbox:not(:checked)').forEach((notAppliedFilter) => {
                removeFilter(notAppliedFilter.closest('.filter-field'));
            });
            filterModal.querySelector('form').submit();
        });
    };

    const createToggleFields = () => {
        const disableToggleField = (toggleField, isChecked) => {
            // in case of error, restore the original toggle field value and disable it
            toggleField.checked = isChecked;
            toggleField.disabled = true;
            toggleField.closest('.custom-switch').classList.add('disabled');
        };

        document.querySelectorAll('td.field-boolean .form-switch input[type="checkbox"]').forEach((toggleField) => {
            toggleField.addEventListener('change', () => {
                const newValue = toggleField.checked;
                const oldValue = !newValue;

                const toggleUrl = toggleField.getAttribute('data-toggle-url') + "&newValue=" + newValue.toString();
                // the XMLHttpRequest header is needed to keep compatibility with the previous code, which didn't use the Fetch API
                fetch(toggleUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
                    .then((response) => {
                        if (!response.ok) {
                            disableToggleField(toggleField, oldValue);
                        }

                        return response.text();
                    })
                    .then(() => { /* do nothing else when the toggle request is successful */ })
                    .catch(() => disableToggleField(toggleField, oldValue));
            });
        });
    };

    const createBatchActions = () => {
        const selectAllCheckbox = document.querySelector('.form-batch-checkbox-all');
        if (null === selectAllCheckbox) {
            return;
        }

        const rowCheckboxes = document.querySelectorAll('input[type="checkbox"].form-batch-checkbox');
        selectAllCheckbox.addEventListener('change', () => {
            rowCheckboxes.forEach((rowCheckbox) => {
                rowCheckbox.checked = selectAllCheckbox.checked;
                rowCheckbox.dispatchEvent(new Event('change'));
            });
        });

        const deselectAllButton = document.querySelector('.deselect-batch-button');
        if (null !== deselectAllButton) {
            deselectAllButton.addEventListener('click', () => {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.dispatchEvent(new Event('change'));
            });
        }

        rowCheckboxes.forEach((rowCheckbox) => {
            rowCheckbox.addEventListener('change', () => {
                const selectedRowCheckboxes =  document.querySelectorAll('input[type="checkbox"].form-batch-checkbox:checked');
                const row = rowCheckbox.closest('tr');
                const content = rowCheckbox.closest('.content');

                if (rowCheckbox.checked) {
                    row.classList.add('selected-row');
                } else {
                    row.classList.remove('selected-row');
                    selectAllCheckbox.checked = false;
                }

                const rowsAreSelected = 0 !== selectedRowCheckboxes.length;
                const contentTitle = document.querySelector('.content-header-title > .title');
                const filters = content.querySelector('.datagrid-filters');
                const globalActions = content.querySelector('.global-actions');
                const batchActions = content.querySelector('.batch-actions');

                if (null !== contentTitle) {
                    contentTitle.style.visibility = rowsAreSelected ? 'hidden' : 'visible';
                }
                if (null !== filters) {
                    filters.style.display = rowsAreSelected ? 'none' : 'block';
                }
                if (null !== globalActions) {
                    globalActions.style.display = rowsAreSelected ? 'none' : 'block';
                }
                if (null !== batchActions) {
                    batchActions.style.display = rowsAreSelected ? 'block' : 'none';
                }
            });
        });

        const modalTitle = document.querySelector('#batch-action-confirmation-title');
        const titleContentWithPlaceholders = modalTitle.textContent;

        document.querySelectorAll('[data-action-batch]').forEach((dataActionBatch) => {
            dataActionBatch.addEventListener('click', (event) => {
                event.preventDefault();

                const actionElement = event.target.tagName.toUpperCase() === 'A' ? event.target : event.target.parentNode;
                const actionName = actionElement.textContent.trim() || actionElement.getAttribute('title');
                const selectedItems = document.querySelectorAll('input[type="checkbox"].form-batch-checkbox:checked');
                modalTitle.textContent = titleContentWithPlaceholders
                    .replace('%action_name%', actionName)
                    .replace('%num_items%', selectedItems.length.toString());

                document.querySelector('#modal-batch-action-button').addEventListener('click', () => {
                    // prevent double submission of the batch action form
                    actionElement.setAttribute('disabled', 'disabled');

                    const batchFormFields = {
                        'batchActionName': actionElement.getAttribute('data-action-name'),
                        'entityFqcn': actionElement.getAttribute('data-entity-fqcn'),
                        'batchActionUrl': actionElement.getAttribute('data-action-url'),
                        'batchActionCsrfToken': actionElement.getAttribute('data-action-csrf-token'),
                    };
                    selectedItems.forEach((item, i) => {
                        batchFormFields[`batchActionEntityIds[${i}]`] = item.value;
                    });

                    const batchForm = document.createElement('form');
                    batchForm.setAttribute('method', 'POST');
                    batchForm.setAttribute('action', actionElement.getAttribute('data-action-url'));
                    for (let fieldName in batchFormFields) {
                        const formField = document.createElement('input');
                        formField.setAttribute('type', 'hidden');
                        formField.setAttribute('name', fieldName);
                        formField.setAttribute('value', batchFormFields[fieldName]);
                        batchForm.appendChild(formField);
                    }

                    document.body.appendChild(batchForm);
                    batchForm.submit();
                });
            });
        });
    };

    const createAutoCompleteFields = () => {
        const autocomplete = new Autocomplete();
        document.querySelectorAll('[data-ea-widget="ea-autocomplete"]').forEach((autocompleteElement) => {
            autocomplete.create(autocompleteElement);
        });
    };

    const createModalWindowsForDeleteActions = () => {
        document.querySelectorAll('.action-delete').forEach((actionElement) => {
            actionElement.addEventListener('click', (event) => {
                event.preventDefault();

                document.querySelector('#modal-delete-button').addEventListener('click', () => {
                    const deleteFormAction = actionElement.getAttribute('formaction');
                    const deleteForm = document.querySelector('#delete-form');
                    deleteForm.setAttribute('action', deleteFormAction);
                    deleteForm.submit();
                });
            });
        });
    }

    const createPopovers = () => {
        document.querySelectorAll('[data-bs-toggle="popover"]').forEach((popoverElement) => {
            new bootstrap.Popover(popoverElement);
        });
    };

    const createTooltips = () => {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((tooltipElement) => {
            new bootstrap.Tooltip(tooltipElement);
        });
    };

    const createUnsavedFormChangesWarning = () => {
        ['.ea-new-form', '.ea-edit-form'].forEach((formSelector) => {
            const form = document.querySelector(formSelector);
            if (null === form) {
                return;
            }

            // although DirtyForm supports passing a custom message to display,
            // modern browsers don't allow to display custom messages to protect users
            new DirtyForm(form);
        });
    };

    const createNullableFields = () => {
        const updateNullableControlStatus = (checkbox) => {
            const formFieldIsNull = checkbox.checked;
            checkbox.closest('.form-group').querySelectorAll('select, input[type="date"], input[type="time"], input[type="datetime-local"]').forEach((dateTimeHtmlElement) => {
                dateTimeHtmlElement.disabled = formFieldIsNull;
                const dateTimeWidget = dateTimeHtmlElement.closest('.datetime-widget');
                if (null !== dateTimeWidget) {
                    dateTimeWidget.style.display = formFieldIsNull ? 'none' : 'block';
                }
            });
        };

        document.querySelectorAll('.nullable-control input[type="checkbox"]').forEach((checkbox) => {
            updateNullableControlStatus(checkbox);

            checkbox.addEventListener('change', () => {
                updateNullableControlStatus(checkbox);
            });
        });
    };

    const createImageFields = () => {
        document.querySelectorAll('.ea-lightbox-thumbnail').forEach((image) => {
            image.addEventListener('click', (event) => {
                event.preventDefault();
                const lightboxContent = document.querySelector(image.getAttribute('data-ea-lightbox-content-selector')).innerHTML;
                const lightbox = basicLightbox.create(lightboxContent);
                lightbox.show();
            });
        });
    };

    const createFileUploadFields = () => {
        const humanizeFileSize = (bytes) => {
            const unit = ['B', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y'];
            const factor = Math.trunc(Math.floor(Math.log(bytes) / Math.log(1024)));

            return Math.trunc(bytes / (1024 ** factor)) + unit[factor];
        };

        document.querySelectorAll('.ea-fileupload input[type="file"]').forEach((fileUploadElement) => {
            fileUploadElement.addEventListener('change', () => {
                if (0 === fileUploadElement.files.length) {
                    return;
                }

                let filename = '';
                if (1 === fileUploadElement.files.length) {
                    filename = fileUploadElement.files[0].name;
                } else {
                    filename = fileUploadElement.files.length + ' ' + fileUploadElement.getAttribute('data-files-label');
                }

                let bytes = 0;
                for (let i = 0; i < fileUploadElement.files.length; i++) {
                    bytes += fileUploadElement.files[i].size;
                }

                const fileUploadContainer = fileUploadElement.closest('.ea-fileupload');
                const fileUploadCustomInput = fileUploadContainer.querySelector('.custom-file-label');
                const fileUploadFileSizeLabel = fileUploadContainer.querySelector('.input-group-text');
                const fileUploadDeleteButton = fileUploadContainer.querySelector('.ea-fileupload-delete-btn');

                fileUploadFileSizeLabel.childNodes.forEach((fileUploadFileSizeLabelChild) => {
                    if (fileUploadFileSizeLabelChild.nodeType === Node.TEXT_NODE) {
                        fileUploadFileSizeLabel.removeChild(fileUploadFileSizeLabelChild);
                    }
                });

                fileUploadCustomInput.innerHTML = filename;
                fileUploadFileSizeLabel.prepend(humanizeFileSize(bytes));
                fileUploadDeleteButton.style.display = 'block';
            });
        });

        document.querySelectorAll('.ea-fileupload .ea-fileupload-delete-btn').forEach((fileUploadDeleteButton) => {
            fileUploadDeleteButton.addEventListener('click', () => {
                const fileUploadContainer = fileUploadDeleteButton.closest('.ea-fileupload');
                const fileUploadInput = fileUploadContainer.querySelector('input');
                const fileUploadCustomInput = fileUploadContainer.querySelector('.custom-file-label');
                const fileUploadFileSizeLabel = fileUploadContainer.querySelector('.input-group-text');
                const fileUploadListOfFiles = fileUploadContainer.querySelector('.fileupload-list');
                const fileUploadDeleteCheckbox = fileUploadContainer.querySelector('input[type=checkbox].form-check-input');

                if (fileUploadDeleteCheckbox) {
                    fileUploadDeleteCheckbox.checked = true;
                }
                fileUploadInput.value = '';
                fileUploadCustomInput.innerHTML = '';
                fileUploadDeleteButton.style.display = 'none';

                fileUploadFileSizeLabel.childNodes.forEach((fileUploadFileSizeLabelChild) => {
                    if (fileUploadFileSizeLabelChild.nodeType === Node.TEXT_NODE) {
                        fileUploadFileSizeLabel.removeChild(fileUploadFileSizeLabelChild);
                    }
                });

                if (null !== fileUploadListOfFiles) {
                    fileUploadListOfFiles.style.display = 'none';
                }
            });
        });
    };

    const createFieldsWithErrors = () => {
        const handleFieldsWithErrors = (form, pageName) => {
            // Adding visual feedback for invalid fields: any ".form-group" with invalid fields
            // receives "has-error" class. The class is removed on click on the ".form-group"
            // itself to support custom/complex fields.
            form.addEventListener('submit', (submitEvent) => {
                form.querySelectorAll('input,select,textarea').forEach( (input) => {
                    if (!input.validity.valid) {
                        const formGroup = input.closest('div.form-group');
                        formGroup.classList.add('has-error');

                        formGroup.addEventListener('click', function onFormGroupClick() {
                            formGroup.classList.remove('has-error');
                            formGroup.removeEventListener('click', onFormGroupClick);
                        });
                    }
                });

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
    };

    const preventMultipleFormSubmission = () => {
        ['.ea-new-form', '.ea-edit-form'].forEach((formSelector) => {
            const form = document.querySelector(formSelector);
            if (null === form) {
                return;
            }

            form.addEventListener('submit', () => {
                // this timeout is needed to include the disabled button into the submitted form
                setTimeout(() => {
                    const submitButtons = form.querySelectorAll('[type="submit"]');
                    submitButtons.forEach((button) => {
                        button.setAttribute('disabled', 'disabled');
                    });
                }, 1);
            }, false);
        });
    };

    const setInnerHTMLAndRunScripts = (element, htmlContent) => {
        // HTML5 specifies that a <script> tag inserted with innerHTML should not execute
        // https://developer.mozilla.org/en-US/docs/Web/API/Element/innerHTML#Security_considerations
        // That's why we can't use just 'innerHTML'. See https://stackoverflow.com/a/47614491/2804294
        element.innerHTML = htmlContent;
        Array.from(element.querySelectorAll('script')).forEach(oldScript => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    };

    return {
        createMainMenu: createMainMenu,
        createLayoutResizeControls: createLayoutResizeControls,
        createNavigationToggler: createNavigationToggler,
        createSearchHighlight: createSearchHighlight,
        createFilters: createFilters,
        createToggleFields: createToggleFields,
        createBatchActions: createBatchActions,
        createAutoCompleteFields: createAutoCompleteFields,
        createModalWindowsForDeleteActions: createModalWindowsForDeleteActions,
        createPopovers: createPopovers,
        createTooltips: createTooltips,
        createUnsavedFormChangesWarning: createUnsavedFormChangesWarning,
        createNullableFields: createNullableFields,
        createImageFields: createImageFields,
        createFileUploadFields: createFileUploadFields,
        createFieldsWithErrors: createFieldsWithErrors,
        preventMultipleFormSubmission: preventMultipleFormSubmission,
    };
})();
