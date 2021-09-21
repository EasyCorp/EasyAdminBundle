<?php

return [
    'page_title' => [
        // 'dashboard' => '',
        'detail' => '%entity_as_string%',
        'edit' => 'Επεξεργασία %entity_label_singular%',
        'index' => '%entity_label_plural%',
        'new' => 'Δημιουργία %entity_label_singular%',
        'exception' => 'Λάθος|Λάθοι',
    ],

    'datagrid' => [
        // 'hidden_results' => '',
        'no_results' => 'Δεν βρέθηκαν αποτελέσματα.',
    ],

    'paginator' => [
        'first' => 'Πρώτη',
        'previous' => 'Προηγούμενη',
        'next' => 'Επόμενη',
        'last' => 'Τελευταία',
        'counter' => '<strong>%start%</strong> - <strong>%end%</strong> από <strong>%results%</strong>',
        // 'results' => '',
    ],

    'label' => [
        'true' => 'Ναι',
        'false' => 'Όχι',
        'empty' => 'Άδειο',
        'null' => 'Κενό',
        'nullable_field' => 'Χωρίς τιμή',
        'object' => 'Αντικείμενο PHP',
        'inaccessible' => 'Μη προσβάσιμο',
        'inaccessible.explanation' => 'Δεν υπάρχει μέθοδος ανάγνωσης (getter) για αυτό το πεδίο ή η ιδιότητα αυτή δεν είναι προσβάσιμη',
        'form.empty_value' => 'Καμία',
    ],

    'field' => [
        // 'code_editor.view_code' => '',
        // 'text_editor.view_content' => '',
    ],

    'action' => [
        'entity_actions' => 'Ενέργειες',
        'new' => 'Δημιουργία %entity_label_singular%',
        'search' => 'Αναζήτηση',
        'detail' => 'Εμφάνιση',
        'edit' => 'Επεξεργασία',
        'delete' => 'Διαγραφή',
        'cancel' => 'Άκυρο',
        'index' => 'Επιστροφή στην λίστα',
        // 'deselect' => '',
        'add_new_item' => 'Δημιουργία νέου αντικειμένου',
        'remove_item' => 'Αφαίρεση αντικειμένου',
        'choose_file' => 'Επιλογή αρχείου',
        // 'close' => '',
        // 'create' => '',
        // 'create_and_add_another' => '',
        // 'create_and_continue' => '',
        // 'save' => '',
        // 'save_and_continue' => '',
    ],

    'batch_action_modal' => [
        // 'title' => '',
        // 'content' => '',
        // 'action' => '',
    ],

    'delete_modal' => [
        'title' => 'Θέλετε σίγουρα να διαγράψετε αυτό το αντικείμενο;',
        'content' => 'Αυτή η ενέργεια δεν αναιρείται.',
    ],

    'filter' => [
        // 'title' => '',
        // 'button.clear' => '',
        // 'button.apply' => '',
        // 'label.is_equal_to' => '',
        // 'label.is_not_equal_to' => '',
        // 'label.is_greater_than' => '',
        // 'label.is_greater_than_or_equal_to' => '',
        // 'label.is_less_than' => '',
        // 'label.is_less_than_or_equal_to' => '',
        // 'label.is_between' => '',
        // 'label.contains' => '',
        // 'label.not_contains' => '',
        // 'label.starts_with' => '',
        // 'label.ends_with' => '',
        // 'label.exactly' => '',
        // 'label.not_exactly' => '',
        // 'label.is_same' => '',
        // 'label.is_not_same' => '',
        // 'label.is_after' => '',
        // 'label.is_after_or_same' => '',
        // 'label.is_before' => '',
        // 'label.is_before_or_same' => '',
    ],

    'form' => [
        'are_you_sure' => 'Δεν έχετε αποθηκεύσει τις αλλαγές που έχετε κάνει στην φόρμα.',
        'tab.error_badge_title' => 'Μη αποδεκτή τιμή|%count% μη αποδεκτές τιμές',
    ],

    'user' => [
        'logged_in_as' => 'Συνδεδεμένος ως',
        'unnamed' => 'Χρήστης δίχως όνομα',
        'anonymous' => 'Ανώνυμος Χρήστης',
        'sign_out' => 'Αποσύνδεση',
        'exit_impersonation' => 'Διακοπή προσωποποίησης',
    ],

    'login_page' => [
        'username' => 'Username',
        'password' => 'Password',
        'sign_in' => 'Sign in',
        // 'forgot_password' => '',
        'remember_me' => 'Να με θυμάσαι',
    ],

    'exception' => [
        'entity_not_found' => 'Αυτό το αντικείμενο δεν είναι πλέον διαθέσιμο.',
        'entity_remove' => 'Το αντικείμενο δεν είναι δυνατόν να διαγραφεί διότη υπάρχουν αντικείμενο που βασίζονται σε αυτό.',
        'forbidden_action' => 'Η ενέργεια αυτή είναι αδύνατον να εφαρμοστεί σε αυτό το αντικείμενο.',
        // 'insufficient_entity_permission' => 'You don't have permission to access this item.',
    ],

    'autocomplete' => [
        'no-results-found' => 'Δεν βρέθηκαν αποτελέσματα',
        // 'no-more-results' => 'No more results',
        'loading-more-results' => 'Φόρτωση περισσότερων αποτελεσμάτων…',
    ],
];
