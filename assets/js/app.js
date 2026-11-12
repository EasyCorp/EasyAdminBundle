require('../css/app.css');

import bootstrap from 'bootstrap/dist/js/bootstrap.bundle';
import Mark from 'mark.js/src/vanilla';
import Autocomplete from './autocomplete';
import { sanitizeUrl, toggleVisibilityClasses } from './helpers';
import { initTabs } from './tabs';

// Provide Bootstrap variable globally to allow custom backend pages to use it
window.bootstrap = bootstrap;

const buttonColorClasses = ['btn-secondary', 'btn-primary', 'btn-success', 'btn-warning', 'btn-danger', 'btn-info'];
const confirmationButtonColorClass = (actionColor) =>
    'danger' === (actionColor || 'danger') ? 'btn-danger' : 'btn-primary';

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
        this.#createSidebarSubmenus();
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
        this.#createContentHelp();
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

        if (!document.querySelector('body').classList.contains('ea-index')) {
            return;
        }

        // don't set the hash to '' because that also removes the query parameters
        const urlParts = window.location.href.split('#');
        const urlWithoutHash = urlParts[0];
        window.history.replaceState({}, '', urlWithoutHash);
    }

    #createSidebarSubmenus() {
        const submenuItemSelector = '.ea-sidebar-item.has-submenu:not(.is-kept-open)';
        const submenuToggleSelector = ':scope > .ea-sidebar-item-link';

        document.addEventListener('click', (event) => {
            const submenuToggle = event.target.closest('.ea-sidebar-item-link');
            const menuItem = submenuToggle?.parentElement;
            if (!menuItem?.matches(submenuItemSelector)) {
                return;
            }

            const willExpand = !menuItem.classList.contains('is-expanded');

            // the accordion applies to the whole menu that contains the item (all its
            // groups); items rendered outside a menu only close their siblings
            const accordionScope = menuItem.closest('.ea-sidebar-content') ?? menuItem.parentElement;
            accordionScope.querySelectorAll(`${submenuItemSelector}.is-expanded`).forEach((otherMenuItem) => {
                // nested submenus must keep their open ancestors (the components allow
                // more than one level, even though the main menu only uses one)
                if (otherMenuItem.contains(menuItem)) {
                    return;
                }

                otherMenuItem.classList.remove('is-expanded');
                otherMenuItem.querySelector(submenuToggleSelector)?.setAttribute('aria-expanded', 'false');
            });

            menuItem.classList.toggle('is-expanded', willExpand);
            submenuToggle.setAttribute('aria-expanded', String(willExpand));
        });
    }

    #createLayoutResizeControls() {
        this.#createLayoutToggle(
            '.ea-layout-toggle-sidebar',
            this.#sidebarWidthLocalStorageKey,
            'sidebar',
            'compact',
            false
        );
        this.#createLayoutToggle(
            '.ea-layout-toggle-content',
            this.#contentWidthLocalStorageKey,
            'content',
            'full',
            true
        );
    }

    #createLayoutToggle(selector, localStorageKey, layoutPart, alternativeWidth, isAlternativeWidthExpanded) {
        const toggle = document.querySelector(selector);
        if (null === toggle) {
            return;
        }

        const isExpanded = () =>
            document.body.classList.contains(`ea-${layoutPart}-width-${alternativeWidth}`) ===
            isAlternativeWidthExpanded;

        const updateToggle = () => {
            const label = isExpanded() ? toggle.dataset.eaCollapseLabel : toggle.dataset.eaExpandLabel;
            toggle.setAttribute('aria-expanded', String(isExpanded()));
            toggle.setAttribute('aria-label', label);
            toggle.querySelector('.ea-layout-toggle-label').textContent = label;
        };

        // the initial width can come from the browser local storage (see page-layout.js)
        // instead of the backend config used to render the toggle
        updateToggle();

        toggle.addEventListener('click', () => {
            const oldValue = document.body.classList.contains(`ea-${layoutPart}-width-${alternativeWidth}`)
                ? alternativeWidth
                : 'normal';
            const newValue = 'normal' === oldValue ? alternativeWidth : 'normal';

            document.body.classList.add(`ea-${layoutPart}-transitions`);
            document.body.classList.remove(`ea-${layoutPart}-width-${oldValue}`);
            document.body.classList.add(`ea-${layoutPart}-width-${newValue}`);
            if ('true' !== document.body.getAttribute(`data-ea-${layoutPart}-width-configured`)) {
                localStorage.setItem(localStorageKey, newValue);
            }
            updateToggle();
        });
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
            modalBackdrop.classList.add('modal-backdrop', 'fade', 'ea-sidebar-backdrop');
            modalBackdrop.onclick = closeSidebar;
            document.body.appendChild(modalBackdrop);
            // reading the layout makes the browser apply the transparent style first;
            // otherwise 'show' would be applied at once and the backdrop wouldn't fade in
            modalBackdrop.getBoundingClientRect();
            modalBackdrop.classList.add('show');

            window.addEventListener('keydown', onKeyDown, true);
        };

        const closeSidebar = () => {
            document.body.classList.remove(cssClassName);
            toggler.setAttribute('aria-expanded', 'false');

            if (modalBackdrop) {
                const closingBackdrop = modalBackdrop;
                closingBackdrop.classList.remove('show');
                // getAnimations() is empty when there's no transition (e.g. reduced motion),
                // so the backdrop is removed right away instead of waiting for 'transitionend'
                Promise.allSettled(closingBackdrop.getAnimations().map((animation) => animation.finished)).then(() =>
                    closingBackdrop.remove()
                );
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

        // 'foo "bar baz" qux' => ['foo', 'bar baz', 'qux']
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
        highlighter.mark(searchQueryTerms, {
            separateWordSearch: false,
            done: () => this.#keepHighlightedTextTogether(elementsToHighlight),
        });
    }

    // in flex and grid containers (e.g. badges) each piece of a highlighted text would become a
    // separate item, adding the gap of the container between them and removing the spaces around them
    #keepHighlightedTextTogether(highlightedElements) {
        const containers = new Set();
        for (const element of highlightedElements) {
            for (const mark of element.querySelectorAll('mark')) {
                containers.add(mark.parentElement);
            }
        }

        const isTextPiece = (node) => Node.TEXT_NODE === node.nodeType || 'MARK' === node.nodeName;
        for (const container of containers) {
            const { display } = getComputedStyle(container);
            if (!display.includes('flex') && !display.includes('grid')) {
                continue;
            }

            for (const mark of container.querySelectorAll(':scope > mark')) {
                if (mark.parentElement !== container) {
                    continue;
                }

                let first = mark;
                while (first.previousSibling && isTextPiece(first.previousSibling)) {
                    first = first.previousSibling;
                }

                const wrapper = document.createElement('span');
                container.insertBefore(wrapper, first);
                while (wrapper.nextSibling && isTextPiece(wrapper.nextSibling)) {
                    wrapper.append(wrapper.nextSibling);
                }
            }
        }
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

        // templates that override the 'batch_actions' block may still hide it with 'd-none';
        // remove it on page load because elements with 'display: none' can't be animated
        document.querySelectorAll('.page-actions-stack .batch-actions.d-none').forEach((batchActions) => {
            batchActions.classList.remove('d-none');
            batchActions.inert = true;
        });

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
                const pageActionsStack = content.querySelector('.page-actions-stack');
                const filters = content.querySelector('.datagrid-filters');
                const globalActions = content.querySelector('.global-actions');
                const batchActions = content.querySelector('.batch-actions');

                if (null !== pageActionsStack) {
                    pageActionsStack.classList.toggle('rows-are-selected', rowsAreSelected);
                    const defaultActions = pageActionsStack.querySelector('.page-actions-default');
                    if (null !== defaultActions) {
                        defaultActions.inert = rowsAreSelected;
                    }
                    if (null !== batchActions) {
                        batchActions.inert = !rowsAreSelected;
                    }

                    return;
                }

                // templates that override the 'page_actions' block don't define the stack of actions
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

        const modalTitle = document.querySelector('#batch-action-confirmation-title');
        const modalMessage = document.querySelector('#batch-action-confirmation-message');
        const modalButton = document.querySelector('#modal-batch-action-button');
        const titleContentWithPlaceholders = modalTitle?.textContent;

        // all batch actions share the modal; a listener added on each opening would also run
        // the actions whose modal was cancelled before
        let pendingBatchAction = null;
        modalButton?.addEventListener('click', () => {
            const batchAction = pendingBatchAction;
            pendingBatchAction = null;
            batchAction?.();
        });

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

                if (actionElement.hasAttribute('data-action-batch-no-confirm')) {
                    submitBatchAction();
                } else {
                    const actionName = actionElement.textContent.trim() || actionElement.getAttribute('title');

                    const replacePlaceholders = (template) =>
                        template
                            .replace('%action_name%', actionName)
                            .replace('%num_items%', selectedItems.length.toString());

                    // a custom confirmation message replaces the title and is shown as regular text
                    const customMessage = actionElement.getAttribute('data-batch-action-confirm-message');
                    modalTitle.textContent = replacePlaceholders(titleContentWithPlaceholders);
                    modalTitle.classList.toggle('d-none', null !== customMessage);
                    if (modalMessage) {
                        modalMessage.textContent = customMessage ? replacePlaceholders(customMessage) : '';
                        modalMessage.classList.toggle('d-none', null === customMessage);
                    }

                    modalButton.classList.remove(...buttonColorClasses);
                    modalButton.classList.add(
                        confirmationButtonColorClass(actionElement.getAttribute('data-action-variant'))
                    );

                    pendingBatchAction = submitBatchAction;
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
        const modalMessage = document.querySelector('#action-confirmation-message');
        const modalContent = document.querySelector('#action-confirmation-content');
        const modalButton = document.querySelector('#modal-action-confirmation-button');
        const defaultTitleTemplate = modalTitle?.textContent;
        const modalButtonLabel = modalButton?.querySelector('.btn-label') ?? modalButton;
        const defaultButtonLabel = modalButtonLabel?.textContent;

        // all actions share the modal; a listener added on each opening would also run the
        // actions whose modal was cancelled before (e.g. deleting a different entity)
        let pendingAction = null;
        modalButton?.addEventListener('click', () => {
            const action = pendingAction;
            pendingAction = null;
            action?.();
        });

        document.querySelector('#modal-action-confirmation')?.addEventListener('hidden.bs.modal', () => {
            pendingAction = null;
        });

        document.querySelectorAll('[data-action-confirmation="true"]').forEach((actionElement) => {
            actionElement.addEventListener('click', (event) => {
                event.preventDefault();

                const actionName = actionElement.textContent.trim() || actionElement.getAttribute('title');
                const entityName = actionElement.getAttribute('data-action-entity-name') || '';
                const entityId = actionElement.getAttribute('data-action-entity-id') || '';
                const replacePlaceholders = (template) =>
                    template
                        .replace('%action_name%', actionName)
                        .replace('%entity_name%', entityName)
                        .replace('%entity_id%', entityId);
                const showText = (element, template) => {
                    element.textContent = template ? replacePlaceholders(template) : '';
                    element.classList.toggle('d-none', !template);
                };

                // a custom confirmation message replaces the title and is shown as regular text
                const customMessage = actionElement.getAttribute('data-action-confirmation-message');
                const title = actionElement.getAttribute('data-action-confirmation-title') ?? defaultTitleTemplate;
                showText(modalTitle, customMessage ? null : title);
                if (modalMessage) {
                    showText(modalMessage, customMessage);
                }
                if (modalContent) {
                    showText(modalContent, actionElement.getAttribute('data-action-confirmation-content'));
                }

                const customButtonLabel = actionElement.getAttribute('data-action-confirmation-button');
                modalButtonLabel.textContent = customButtonLabel ?? defaultButtonLabel;

                modalButton.classList.remove(...buttonColorClasses);
                modalButton.classList.add(
                    confirmationButtonColorClass(actionElement.getAttribute('data-action-variant'))
                );

                pendingAction = () => {
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

                    // Case 3: submit button bound to a form, possibly outside its DOM tree (e.g. EDIT/NEW pages);
                    // unlike submit(), requestSubmit() sends the name/value of the button (needed by the
                    // built-in save actions) and fires the 'submit' event that form.js and DirtyForm rely on
                    if (
                        actionElement instanceof HTMLButtonElement &&
                        actionElement.type === 'submit' &&
                        actionElement.form
                    ) {
                        actionElement.form.requestSubmit(actionElement);
                        return;
                    }

                    // Case 4: standalone button inside a <form> (renderAsForm)
                    const parentForm = actionElement.closest('form');
                    if (parentForm?.hasAttribute('action')) {
                        parentForm.submit();
                        return;
                    }

                    // Case 5: GET action with href
                    const href = actionElement.getAttribute('href');
                    if (href) {
                        window.location.href = href;
                    }
                };
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
            // ancestors are checked too, because clicks on elements with 'pointer-events: none'
            // target their parents
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

            // middle-click or ctrl/cmd-click opens a new tab, like a real link; target="_blank"
            // is used instead of window.open() because Firefox blocks the latter in middle-click handlers
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

            row.addEventListener('keydown', (event) => {
                if ('Enter' !== event.key && ' ' !== event.key) {
                    return;
                }

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

    #createContentHelp() {
        const help = document.querySelector('[data-ea-content-help]');
        if (null === help) {
            return;
        }

        const message = help.querySelector('.content-help-message');
        const expander = help.querySelector('.content-help-expander');
        const isCollapsed = () => 'collapsed' === help.dataset.state;
        const updateExpander = () => {
            if (isCollapsed()) {
                expander.hidden = message.scrollHeight <= message.clientHeight + 1;
            }
        };

        const expand = () => {
            if (!isCollapsed()) {
                return;
            }

            const initialHeight = message.getBoundingClientRect().height;
            const expanderHadFocus = document.activeElement === expander;
            help.dataset.state = 'expanded';
            expander.setAttribute('aria-expanded', 'true');
            expander.hidden = true;
            if (expanderHadFocus) {
                message.focus({ preventScroll: true });
            }

            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return;
            }

            // 'height: auto' can't be transitioned in all browsers, so animate the measured heights
            const finalHeight = message.getBoundingClientRect().height;
            message.animate(
                { blockSize: [`${initialHeight}px`, `${finalHeight}px`], overflow: ['hidden', 'hidden'] },
                { duration: 200, easing: 'ease-out' }
            );
        };

        expander.addEventListener('click', expand);
        // links hidden after the first line can still be focused with the keyboard
        message.addEventListener('focusin', (event) => {
            if (
                event.target !== message &&
                event.target.getBoundingClientRect().top >= message.getBoundingClientRect().bottom
            ) {
                expand();
            }
        });

        updateExpander();
        new ResizeObserver(updateExpander).observe(help);
        document.fonts.ready.then(updateExpander);
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
