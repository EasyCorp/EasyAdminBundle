// any CSS you require will output into a single css file (app.css in this case)
require('../css/app.css');

import bootstrap from 'bootstrap/dist/js/bootstrap.bundle';
import Mark from 'mark.js/src/vanilla';
import Autocomplete from './autocomplete';
import { sanitizeUrl, toggleVisibilityClasses } from './helpers';
import { initTabs } from './tabs';

// Provide Bootstrap variable globally to allow custom backend pages to use it
window.bootstrap = bootstrap;

// maps an action variant to its button CSS class; shared by the action and
// batch action confirmation modals so their confirm button mirrors the
// variant/color of the action that opened the modal
const variantToClass = {
    default: 'btn-secondary',
    primary: 'btn-primary',
    success: 'btn-success',
    warning: 'btn-warning',
    danger: 'btn-danger',
};
const allVariantClasses = Object.values(variantToClass);

document.addEventListener('DOMContentLoaded', () => {
    window.EasyAdminApp = new App();
});

class App {
    #sidebarWidthLocalStorageKey;
    #contentWidthLocalStorageKey;

    constructor() {
        this.#sidebarWidthLocalStorageKey = 'ea/sidebar/width';
        this.#contentWidthLocalStorageKey = 'ea/content/width';

        this.#removeHashFormUrl();
        initTabs();
        this.#createMainMenu();
        this.#createLayoutResizeControls();
        this.#createNavigationToggler();
        this.#createSearchHighlight();
        this.#createSearchInputAutoSizing();
        this.#createFilters();
        this.#createAutoCompleteFields();
        this.#createBatchActions();
        this.#createActionConfirmationModals();
        this.#createDefaultRowAction();
        this.#createPopovers();
        this.#createTooltips();
        this.#createActionHandlers();

        document.addEventListener('ea.collection.item-added', () => this.#createAutoCompleteFields());
    }

    // When using tabs in forms, the selected tab is persisted (in the URL hash) so you
    // can see the same tab when reloading the page (e.g. '#tab-contact-information').
    // This method removes the hash from URL in the index page to not show form-related
    // information in the index page
    #removeHashFormUrl() {
        if (!window.location.href.includes('#')) {
            return;
        }

        // remove the hash only in the index page
        if (!document.querySelector('body').classList.contains('ea-index')) {
            return;
        }

        // don't set the hash to '' because that also removes the query parameters
        const urlParts = window.location.href.split('#');
        const urlWithoutHash = urlParts[0];
        window.history.replaceState({}, '', urlWithoutHash);
    }

    #createMainMenu() {
        // the expand/collapse animation is fully CSS-based (see sidebar.css); this only
        // toggles the 'is-expanded' class, keeping a single submenu open at a time
        // '.is-kept-open' submenus are always expanded: they don't toggle and the
        // accordion never closes them
        const menuItemsWithSubmenus = document.querySelectorAll(
            '#main-menu .ea-sidebar-item.has-submenu:not(.is-kept-open)'
        );
        menuItemsWithSubmenus.forEach((menuItem) => {
            const submenuToggle = menuItem.querySelector(':scope > .ea-sidebar-item-link');
            if (null === submenuToggle) {
                return;
            }

            submenuToggle.addEventListener('click', () => {
                const willExpand = !menuItem.classList.contains('is-expanded');

                menuItemsWithSubmenus.forEach((otherMenuItem) => {
                    otherMenuItem.classList.remove('is-expanded');
                    otherMenuItem
                        .querySelector(':scope > .ea-sidebar-item-link')
                        ?.setAttribute('aria-expanded', 'false');
                });

                menuItem.classList.toggle('is-expanded', willExpand);
                submenuToggle.setAttribute('aria-expanded', String(willExpand));
            });
        });
    }

    #createLayoutResizeControls() {
        const sidebarResizerHandler = document.querySelector('#sidebar-resizer-handler');
        if (null !== sidebarResizerHandler) {
            sidebarResizerHandler.addEventListener('click', () => {
                const oldValue = localStorage.getItem(this.#sidebarWidthLocalStorageKey) || 'normal';
                const newValue = 'normal' === oldValue ? 'compact' : 'normal';

                document.querySelector('body').classList.remove(`ea-sidebar-width-${oldValue}`);
                document.querySelector('body').classList.add(`ea-sidebar-width-${newValue}`);
                localStorage.setItem(this.#sidebarWidthLocalStorageKey, newValue);
            });
        }

        const contentResizerHandler = document.querySelector('#content-resizer-handler');
        if (null !== contentResizerHandler) {
            contentResizerHandler.addEventListener('click', () => {
                const oldValue = localStorage.getItem(this.#contentWidthLocalStorageKey) || 'normal';
                const newValue = 'normal' === oldValue ? 'full' : 'normal';

                document.querySelector('body').classList.remove(`ea-content-width-${oldValue}`);
                document.querySelector('body').classList.add(`ea-content-width-${newValue}`);
                localStorage.setItem(this.#contentWidthLocalStorageKey, newValue);
            });
        }
    }

    #createNavigationToggler() {
        const toggler = document.querySelector('#navigation-toggler');
        const cssClassName = 'ea-mobile-sidebar-visible';
        let modalBackdrop;

        if (null === toggler) {
            return;
        }

        // listen on window in the capture phase so this runs before Bootstrap closes
        // open dropdowns and modals: its delegated dropdown handlers also listen in
        // the capture phase, but on document, so they would run first otherwise
        const onKeyDown = (event) => {
            if ('Escape' !== event.key) {
                return;
            }

            if (null !== document.querySelector('.dropdown-menu.show, .modal.show')) {
                return;
            }

            closeSidebar();
            toggler.focus();
        };

        const openSidebar = () => {
            document.body.classList.add(cssClassName);
            toggler.setAttribute('aria-expanded', 'true');

            modalBackdrop = document.createElement('div');
            modalBackdrop.classList.add('modal-backdrop', 'fade', 'show');
            modalBackdrop.onclick = closeSidebar;
            document.body.appendChild(modalBackdrop);

            window.addEventListener('keydown', onKeyDown, true);
        };

        const closeSidebar = () => {
            document.body.classList.remove(cssClassName);
            toggler.setAttribute('aria-expanded', 'false');

            if (modalBackdrop) {
                modalBackdrop.remove();
                modalBackdrop = null;
            }

            window.removeEventListener('keydown', onKeyDown, true);
        };

        toggler.addEventListener('click', () => {
            document.body.classList.contains(cssClassName) ? closeSidebar() : openSidebar();
        });
    }

    #createSearchHighlight() {
        const searchElement = document.querySelector('.form-action-search [name="query"]');
        if (null === searchElement) {
            return;
        }

        const searchQuery = searchElement.value;
        if ('' === searchQuery.trim()) {
            return;
        }

        // splits a string into tokens, taking into account quoted strings
        // Example: 'foo "bar baz" qux' => ['foo', 'bar baz', 'qux']
        const tokenizeString = (string) => {
            const regex = /"([^"\\]*(\\.[^"\\]*)*)"|\S+/g;
            const tokens = [];

            let match = regex.exec(string);
            while (null !== match) {
                tokens.push(match[0].replaceAll('"', '').trim());
                match = regex.exec(string);
            }

            return tokens;
        };

        const searchQueryTerms = tokenizeString(searchElement.value);

        const elementsToHighlight = document.querySelectorAll('.datagrid [data-id] .searchable');
        const highlighter = new Mark(elementsToHighlight);
        highlighter.mark(searchQueryTerms, { separateWordSearch: false });
    }

    #createSearchInputAutoSizing() {
        const searchElement = document.querySelector('.content-search-label input[type="search"]');
        if (null === searchElement) {
            return;
        }

        // keep the parent label's data-value in sync with the typed text, so the
        // ::after pseudo-element used to auto-size the search input grows while typing
        searchElement.addEventListener('input', () => {
            searchElement.parentNode.dataset.value = searchElement.value;
        });
    }

    #createFilters() {
        const filterButton = document.querySelector('.datagrid-filters .action-filters-button');
        if (null === filterButton) {
            return;
        }

        const filterModal = document.querySelector(filterButton.getAttribute('data-bs-target'));

        // the filter URL is fetched and its response is injected into the page (see below),
        // so it must be same-origin to prevent loading attacker-controlled remote HTML
        const filtersUrl = sanitizeUrl(filterButton.getAttribute('data-href'), true);
        if (null === filtersUrl) {
            return;
        }

        // this is needed to avoid errors when connection is slow
        filterButton.setAttribute('href', filtersUrl);
        filterButton.removeAttribute('data-href');
        filterButton.classList.remove('disabled');

        filterButton.addEventListener('click', (event) => {
            const filterModalBody = filterModal.querySelector('.modal-body');
            filterModalBody.innerHTML =
                '<div class="fa-3x px-3 py-3 text-muted text-center"><i class="fas fa-circle-notch fa-spin"></i></div>';

            fetch(filterButton.getAttribute('href'))
                .then((response) => {
                    return response.text();
                })
                .then((text) => {
                    filterModalBody.innerHTML = text;
                    this.#createAutoCompleteFields();
                    this.#createFilterToggles();
                })
                .catch((error) => {
                    console.error(error);
                });

            event.preventDefault();
        });

        const removeFilter = (filterField) => {
            filterField
                .closest('form')
                .querySelectorAll(`input[name^="filters[${filterField.dataset.filterProperty}]"]`)
                .forEach((filterFieldInput) => {
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
    }

    #createBatchActions() {
        let lastUpdatedRowCheckbox = null;
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

        rowCheckboxes.forEach((rowCheckbox, rowCheckboxIndex) => {
            rowCheckbox.dataset.rowIndex = rowCheckboxIndex;

            rowCheckbox.addEventListener('click', (e) => {
                if (lastUpdatedRowCheckbox && e.shiftKey) {
                    const lastIndex = Number.parseInt(lastUpdatedRowCheckbox.dataset.rowIndex);
                    const currentIndex = Number.parseInt(e.target.dataset.rowIndex);
                    const valueToApply = e.target.checked;
                    const lowest = Math.min(lastIndex, currentIndex);
                    const highest = Math.max(lastIndex, currentIndex);

                    rowCheckboxes.forEach((rowCheckbox2, rowCheckboxIndex2) => {
                        if (lowest <= rowCheckboxIndex2 && rowCheckboxIndex2 <= highest) {
                            rowCheckbox2.checked = valueToApply;
                            rowCheckbox2.dispatchEvent(new Event('change'));
                        }
                    });
                }
                lastUpdatedRowCheckbox = e.target;
            });

            rowCheckbox.addEventListener('change', () => {
                const selectedRowCheckboxes = document.querySelectorAll(
                    'input[type="checkbox"].form-batch-checkbox:checked'
                );
                const row = rowCheckbox.closest('[data-id]');
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
                    toggleVisibilityClasses(contentTitle, rowsAreSelected);
                }
                if (null !== filters) {
                    toggleVisibilityClasses(filters, rowsAreSelected);
                }
                if (null !== globalActions) {
                    toggleVisibilityClasses(globalActions, rowsAreSelected);
                }
                if (null !== batchActions) {
                    toggleVisibilityClasses(batchActions, !rowsAreSelected);
                }
            });
        });

        const modalTitle = document.querySelector('#modal-batch-action .modal-body-content h4');
        const titleContentWithPlaceholders = modalTitle?.textContent;

        document.querySelectorAll('[data-action-batch]').forEach((dataActionBatch) => {
            dataActionBatch.addEventListener('click', (event) => {
                event.preventDefault();

                const actionElement = event.currentTarget;
                const selectedItems = document.querySelectorAll('input[type="checkbox"].form-batch-checkbox:checked');

                const submitBatchAction = () => {
                    // prevent double submission of the batch action form
                    actionElement.setAttribute('disabled', 'disabled');

                    const batchFormFields = {
                        batchActionName: actionElement.getAttribute('data-action-name'),
                        entityFqcn: actionElement.getAttribute('data-entity-fqcn'),
                        batchActionUrl: actionElement.getAttribute('data-action-url'),
                        batchActionCsrfToken: actionElement.getAttribute('data-action-csrf-token'),
                    };
                    selectedItems.forEach((item, i) => {
                        batchFormFields[`batchActionEntityIds[${i}]`] = item.value;
                    });

                    const batchForm = document.createElement('form');
                    batchForm.setAttribute('method', 'POST');
                    batchForm.setAttribute('action', actionElement.getAttribute('data-action-url'));
                    for (const fieldName in batchFormFields) {
                        const formField = document.createElement('input');
                        formField.setAttribute('type', 'hidden');
                        formField.setAttribute('name', fieldName);
                        formField.setAttribute('value', batchFormFields[fieldName]);
                        batchForm.appendChild(formField);
                    }

                    document.body.appendChild(batchForm);
                    batchForm.submit();
                };

                // check if this batch action should skip confirmation
                if (actionElement.hasAttribute('data-action-batch-no-confirm')) {
                    submitBatchAction();
                } else {
                    // show confirmation modal
                    const actionName = actionElement.textContent.trim() || actionElement.getAttribute('title');

                    // use custom message if provided, otherwise use default modal title
                    const customMessage = actionElement.getAttribute('data-batch-action-confirm-message');
                    const messageTemplate = customMessage ?? titleContentWithPlaceholders;

                    modalTitle.textContent = messageTemplate
                        .replace('%action_name%', actionName)
                        .replace('%num_items%', selectedItems.length.toString());

                    // apply to the modal button the same variant as the action that opened the modal
                    const modalButton = document.querySelector('#modal-batch-action-button');
                    const variant = actionElement.getAttribute('data-action-variant') || 'danger';
                    const variantClass = variantToClass[variant] || 'btn-danger';
                    modalButton.classList.remove(...allVariantClasses);
                    modalButton.classList.add(variantClass);

                    modalButton.addEventListener('click', submitBatchAction, { once: true });
                }
            });
        });
    }

    #createAutoCompleteFields() {
        const autocomplete = new Autocomplete();
        document.querySelectorAll('[data-ea-widget="ea-autocomplete"]').forEach((autocompleteElement) => {
            autocomplete.create(autocompleteElement);
        });
    }

    #createActionConfirmationModals() {
        const modalTitle = document.querySelector('#action-confirmation-title');
        const modalContent = document.querySelector('#action-confirmation-content');
        const modalButton = document.querySelector('#modal-action-confirmation-button');
        const defaultTitleTemplate = modalTitle?.textContent;
        const defaultButtonLabel = modalButton?.textContent;

        document.querySelectorAll('[data-action-confirmation="true"]').forEach((actionElement) => {
            actionElement.addEventListener('click', (event) => {
                event.preventDefault();

                const actionName = actionElement.textContent.trim() || actionElement.getAttribute('title');
                const entityName = actionElement.getAttribute('data-action-entity-name') || '';
                const entityId = actionElement.getAttribute('data-action-entity-id') || '';

                // use custom message if provided, otherwise use default modal title
                const customMessage = actionElement.getAttribute('data-action-confirmation-message');
                const messageTemplate = customMessage ?? defaultTitleTemplate;

                modalTitle.textContent = messageTemplate
                    .replace('%action_name%', actionName)
                    .replace('%entity_name%', entityName)
                    .replace('%entity_id%', entityId);

                // optional description below the title (e.g. the DELETE action); hidden when absent
                const contentMessage = actionElement.getAttribute('data-action-confirmation-content');
                if (modalContent) {
                    if (contentMessage) {
                        modalContent.textContent = contentMessage
                            .replace('%action_name%', actionName)
                            .replace('%entity_name%', entityName)
                            .replace('%entity_id%', entityId);
                        modalContent.classList.remove('d-none');
                    } else {
                        modalContent.textContent = '';
                        modalContent.classList.add('d-none');
                    }
                }

                // use custom button label if provided, otherwise use default
                const customButtonLabel = actionElement.getAttribute('data-action-confirmation-button');
                modalButton.textContent = customButtonLabel ?? defaultButtonLabel;

                // apply to the modal button the same variant as the action that opened the modal
                const variant = actionElement.getAttribute('data-action-variant') || 'danger';
                const variantClass = variantToClass[variant] || 'btn-danger';
                modalButton.classList.remove(...allVariantClasses);
                modalButton.classList.add(variantClass);

                modalButton.addEventListener(
                    'click',
                    () => {
                        // Case 1: POST action with formaction (like DELETE with CSRF token)
                        const formAction = actionElement.getAttribute('formaction');
                        if (formAction) {
                            const form = document.querySelector('#action-confirmation-form');
                            form.setAttribute('action', formAction);
                            form.submit();
                            return;
                        }

                        // Case 2: dropdown action rendered as form (data-ea-action-form-id)
                        const actionFormId = actionElement.getAttribute('data-ea-action-form-id');
                        if (actionFormId) {
                            document.getElementById(actionFormId).submit();
                            return;
                        }

                        // Case 3: standalone button inside a <form> (renderAsForm)
                        const parentForm = actionElement.closest('form');
                        if (parentForm?.hasAttribute('action')) {
                            parentForm.submit();
                            return;
                        }

                        // Case 4: GET action with href
                        const href = actionElement.getAttribute('href');
                        if (href) {
                            window.location.href = href;
                        }
                    },
                    { once: true }
                );
            });
        });
    }

    #createDefaultRowAction() {
        const clickableRows = document.querySelectorAll('[data-default-action-url]');
        if (0 === clickableRows.length) {
            return;
        }

        clickableRows.forEach((row) => row.classList.add('ea-clickable-row'));

        const clickTrigger =
            clickableRows[0].closest('[data-default-action-trigger]')?.getAttribute('data-default-action-trigger') ||
            'single';

        const interactiveSelectors = [
            'a',
            'button',
            'input',
            'select',
            'textarea',
            '.form-check',
            '.dropdown',
            '.actions',
            '[data-bs-toggle]',
            '.btn',
            '.modal',
        ];

        const isInteractiveElement = (element) => {
            // walk up the DOM tree to check if any ancestor is interactive
            // this also handles elements with pointer-events: none whose clicks bubble to parents
            let current = element;
            while (current && current !== document.body) {
                if (interactiveSelectors.some((selector) => current.matches(selector))) {
                    return true;
                }
                current = current.parentElement;
            }

            return false;
        };

        const navigateToUrl = (url, event) => {
            // create a temporary link and click it to let Turbo (or other libraries) intercept the navigation
            const link = document.createElement('a');
            link.href = url;

            // middle-click or ctrl/cmd-click opens the row in a new tab, like a real link.
            // Setting target="_blank" on the link (instead of using window.open()) opens the
            // new tab as a regular link navigation, which isn't stopped by the popup blocker;
            // Firefox blocks window.open() when it's called from a middle-click handler.
            if (event && (event.metaKey || event.ctrlKey || 1 === event.button)) {
                link.target = '_blank';
                link.rel = 'noopener';

                // clear the cell/text selection that Firefox creates on ctrl/cmd-click so it
                // doesn't linger highlighted after the new tab opens
                const selection = window.getSelection();
                if (null !== selection) {
                    selection.removeAllRanges();
                }
            }

            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        };

        const handleRowActivation = (row, event) => {
            // don't navigate if rows are selected (batch mode)
            if (row.classList.contains('selected-row')) {
                return;
            }

            const url = row.dataset.defaultActionUrl;
            if (url) {
                navigateToUrl(url, event);
            }
        };

        // when the single-click trigger is active, a drag-to-select gesture ends with a `click`
        // event at the release point. Skip navigation in that case so users can highlight and
        // copy text from a cell without being navigated away.
        const userIsSelectingTextInRow = (row) => {
            if ('double' === clickTrigger) {
                return false;
            }

            const selection = window.getSelection();
            if (null === selection || 0 === selection.toString().length || 0 === selection.rangeCount) {
                return false;
            }

            return row.contains(selection.getRangeAt(0).commonAncestorContainer);
        };

        clickableRows.forEach((row) => {
            // handle mouse clicks
            row.addEventListener(clickTrigger === 'double' ? 'dblclick' : 'click', (event) => {
                if (isInteractiveElement(event.target)) {
                    return;
                }

                // a middle-click or ctrl/cmd-click opens a new tab; it's not a text-selection
                // gesture, so skip the selection guard. Firefox selects the clicked table cell
                // on ctrl/cmd-click, which would otherwise be mistaken for a text selection and
                // cancel the navigation.
                const opensNewTab = event.metaKey || event.ctrlKey || 1 === event.button;
                if (!opensNewTab && userIsSelectingTextInRow(row)) {
                    return;
                }

                handleRowActivation(row, event);
            });

            // handle middle-click (auxclick) to open the row in a new tab
            row.addEventListener('auxclick', (event) => {
                if (1 !== event.button) {
                    return;
                }

                if (isInteractiveElement(event.target)) {
                    return;
                }

                event.preventDefault();
                handleRowActivation(row, event);
            });

            // handle keyboard navigation (Enter and Space)
            row.addEventListener('keydown', (event) => {
                if ('Enter' !== event.key && ' ' !== event.key) {
                    return;
                }

                // don't activate if focus is on an interactive child element
                if (isInteractiveElement(event.target) && event.target !== row) {
                    return;
                }

                event.preventDefault();
                handleRowActivation(row, event);
            });
        });
    }

    #createPopovers() {
        document.querySelectorAll('[data-bs-toggle="popover"]').forEach((popoverElement) => {
            new bootstrap.Popover(popoverElement);
        });
    }

    #createTooltips() {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((tooltipElement) => {
            new bootstrap.Tooltip(tooltipElement);
        });
    }

    #createFilterToggles() {
        document.querySelectorAll('.filter-checkbox').forEach((filterCheckbox) => {
            filterCheckbox.addEventListener('change', () => {
                const filterToggleLink = filterCheckbox.nextElementSibling;
                const filterExpandedAttribute = filterCheckbox.nextElementSibling.getAttribute('aria-expanded');

                if (
                    (filterCheckbox.checked && 'false' === filterExpandedAttribute) ||
                    (!filterCheckbox.checked && 'true' === filterExpandedAttribute)
                ) {
                    filterToggleLink.click();
                }
            });
        });

        document.querySelectorAll('form[data-ea-filters-form-id]').forEach((form) => {
            // TODO: when using the native datepicker, 'change' isn't fired unless you input the entire date + time information
            form.addEventListener('change', (event) => {
                if (event.target.classList.contains('filter-checkbox')) {
                    return;
                }

                const filterCheckbox = event.target.closest('.filter-field').querySelector('.filter-checkbox');
                if (!filterCheckbox.checked) {
                    filterCheckbox.checked = true;
                }
            });
        });

        document.querySelectorAll('[data-ea-comparison-id]').forEach((comparisonWidget) => {
            comparisonWidget.addEventListener('change', (event) => {
                const comparisonWidget = event.currentTarget;
                const comparisonId = comparisonWidget.dataset.eaComparisonId;

                if (comparisonId === undefined) {
                    return;
                }

                const secondValue = document.querySelector(`[data-ea-value2-of-comparison-id="${comparisonId}"]`);

                if (secondValue === null) {
                    return;
                }

                toggleVisibilityClasses(secondValue, comparisonWidget.value !== 'between');
            });
        });
    }

    #createActionHandlers() {
        // handle form submissions via data attribute (replaces inline onclick handlers)
        // skip elements with confirmation modals (handled by #createActionConfirmationModals)
        document.querySelectorAll('[data-ea-action-form-id]').forEach((element) => {
            element.addEventListener('click', (event) => {
                if (element.hasAttribute('data-action-confirmation')) {
                    return;
                }
                event.preventDefault();
                const formId = element.getAttribute('data-ea-action-form-id');
                document.getElementById(formId).submit();
            });
        });

        // handle navigation via data attribute (replaces inline onclick handlers)
        // skip elements with confirmation modals (handled by #createActionConfirmationModals)
        document.querySelectorAll('[data-ea-action-url]').forEach((element) => {
            element.addEventListener('click', (event) => {
                if (element.hasAttribute('data-action-confirmation')) {
                    return;
                }
                event.preventDefault();
                const actionUrl = sanitizeUrl(element.getAttribute('data-ea-action-url'));
                if (null !== actionUrl) {
                    window.location = actionUrl;
                }
            });
        });
    }
}
