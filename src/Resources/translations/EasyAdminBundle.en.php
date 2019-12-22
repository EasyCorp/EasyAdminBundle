<?php

return [
    // page titles
    'page.new.title' => 'Create %entity_label_singular%',
    'page.detail.title' => '%entity_label_singular% <span class="text-small text-muted">(#%entity_id%)</span>',
    'page.edit.title' => 'Edit %entity_label_singular% (#%entity_id%)',
    'page.index.title' => '%entity_label_plural%',

    // paginator
    'paginator.first' => 'First',
    'paginator.previous' => 'Previous',
    'paginator.next' => 'Next',
    'paginator.last' => 'Last',
    'paginator.counter' => '<strong>%start%</strong> - <strong>%end%</strong> of <strong>%results%</strong>',
    'paginator.results' => '{0} No results|{1} <strong>1</strong> result|]1,Inf] <strong>%count%</strong> results',

    // labels
    'label.true' => 'Yes',
    'label.false' => 'No',
    'label.empty' => 'Empty',
    'label.null' => 'Null',
    'label.nullable_field' => 'Leave empty',
    'label.object' => 'PHP Object',
    'label.inaccessible' => 'Inaccessible',
    'label.inaccessible.explanation' => 'Getter method does not exist for this field or the property is not public',
    'label.form.empty_value' => 'None',

    // user
    'user.logged_in_as' => 'Logged in as',
    'user.unnamed' => 'Unnamed User',
    'user.anonymous' => 'Anonymous User',
    'user.signout' => 'Sign out',
    'user.exit_impersonation' => 'Exit impersonation',

    // actions
    'list.row_actions' => 'Actions',
    'action.new' => 'Add %entity_label%',
    'action.show' => 'Show',
    'action.edit' => 'Edit',
    'action.save' => 'Save changes',
    'action.cancel' => 'Cancel',
    'action.list' => 'Back to listing',
    'action.deselect' => 'Deselect',
    'action.add_new_item' => 'Add a new item',
    'action.remove_item' => 'Remove the item',
    'action.choose_file' => 'Choose file',
    'action.close' => 'Close',

    // batch actions
    'batch_action_modal.title' => 'Do you really want to alter the selected items?',
    'batch_action_modal.content' => 'There is no undo for this operation.',
    'batch_action_modal.action' => 'Proceed',

    // filters
    'filter.title' => 'Filters',
    'filter.button.clear' => 'Clear',
    'filter.button.apply' => 'Apply',
    'filter.label.is_equal_to' => 'is equal to',
    'filter.label.is_not_equal_to' => 'is not equal to',
    'filter.label.is_greater_than' => 'is greater than',
    'filter.label.is_greater_than_or_equal_to' => 'is greater than or equal to',
    'filter.label.is_less_than' => 'is less than',
    'filter.label.is_less_than_or_equal_to' => 'is less than or equal to',
    'filter.label.is_between' => 'is between',
    'filter.label.contains' => 'contains',
    'filter.label.not_contains' => 'doesn\'t contain',
    'filter.label.starts_with' => 'starts with',
    'filter.label.ends_with' => 'ends with',
    'filter.label.exactly' => 'exactly',
    'filter.label.not_exactly' => 'not exactly',
    'filter.label.is_same' => 'is same',
    'filter.label.is_not_same' => 'is not same',
    'filter.label.is_after' => 'is after',
    'filter.label.is_after_or_same' => 'is after or same',
    'filter.label.is_before' => 'is before',
    'filter.label.is_before_or_same' => 'is before or same',

    // search
    'action.search' => 'Search',
    'search.no_results' => 'No results found.',

    // delete
    'action.delete' => 'Delete',
    'delete_modal.title' => 'Do you really want to delete this item?',
    'delete_modal.content' => 'There is no undo for this operation.',
    'delete_modal.action' => 'Delete',

    // properties
    'property.code_editor.view_code' => 'View code',

    // login page
    'login.username' => 'Username',
    'login.password' => 'Password',
    'login.sign_in' => 'Sign in',
    'security.list.hidden_results' => 'Some results can\'t be displayed because you don\'t have enough permissions',

    // exceptions
    'exception.entity_not_found' => 'This item is no longer available.',
    'exception.entity_remove' => 'This item can\'t be deleted because other items depend on it.',
    'exception.forbidden_action' => 'The requested action can\'t be performed on this item.',
    'exception.no_entities_configured' => 'The application is not properly configured.',
    'exception.undefined_entity' => 'The application is not properly configured for this kind of items.',
    'exception.no_permission' => 'You don\'t have enough permissions to do this.',

    // misc.
    'errors' => 'Error|Errors',
    'form.are_you_sure' => 'You haven\'t saved the changes made on this form.',
    'form.tab.error_badge_title' => 'One invalid input|%count% invalid inputs',
    'show.remaining_items' => '{1} there is another item not displayed in this listing|]1,Inf] %count% other items are not displayed in this listing',
];
