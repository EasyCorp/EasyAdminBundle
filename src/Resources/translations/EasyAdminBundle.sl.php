<?php

return [
    'page_title' => [
        // 'dashboard' => '',
        'detail' => '%entity_label_singular% <small>(#%entity_short_id%)</small>',
        'edit' => '%entity_label_singular% <small>(#%entity_short_id%)</small>',
        'index' => '%entity_label_plural%',
        'new' => 'Dodaj %entity_label_singular%',
        'exception' => '%count% napaka|%count% napaki|%count% napake|%count% napak',
    ],

    'datagrid' => [
        // 'hidden_results' => '',
        'no_results' => 'Nobenih rezultatov ni najdenih.',
    ],

    'paginator' => [
        'first' => 'Prva',
        'previous' => 'Prejšnja',
        'next' => 'Naslednja',
        'last' => 'Zadnja',
        'counter' => '<strong>%start%</strong> - <strong>%end%</strong> od <strong>%results%</strong>',
        // 'results' => '',
    ],

    'label' => [
        'true' => 'Da',
        'false' => 'Ne',
        'empty' => 'Prazno',
        'null' => 'Null',
        'nullable_field' => 'Pusti prazno',
        'object' => 'PHP objekt',
        'inaccessible' => 'Nedostopno',
        'inaccessible.explanation' => 'Getter metoda ne obstaja za to polje ali pa lastnost ni javna',
        'form.empty_value' => 'Noben',
    ],

    'field' => [
        // 'code_editor.view_code' => '',
        // 'text_editor.view_content' => '',
    ],

    'action' => [
        'entity_actions' => 'Dejanja',
        'new' => 'Dodaj %entity_label_singular%',
        'search' => 'Iskanje',
        'detail' => 'Prikaži',
        'edit' => 'Uredi',
        'delete' => 'Izbriši',
        'cancel' => 'Prekliči',
        'index' => 'Nazaj na seznam',
        // 'deselect' => '',
        'add_new_item' => 'Dodaj nov element',
        'remove_item' => 'Odstrani element',
        'choose_file' => 'Izberite datoteko',
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
        'title' => 'Ali res želite izbrisati ta element?',
        'content' => 'Razveljavitev za to operacijo ne obstaja.',
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
        'are_you_sure' => 'Sprememb, ki ste jih naredili na tem obrazcu, niste shranili.',
        // 'tab.error_badge_title' => '',
    ],

    'user' => [
        'logged_in_as' => 'Prijavljeni kot',
        'unnamed' => 'Neimenovani uporabnik',
        'anonymous' => 'Anonimni uporabnik',
        'sign_out' => 'Odjava',
        // 'exit_impersonation' => '',
    ],

    'login_page' => [
        'username' => 'uporabniško ime',
        'password' => 'Geslo',
        'sign_in' => 'Prijava',
    ],

    'exception' => [
        'entity_not_found' => 'Ta element ni več na voljo.',
        'entity_remove' => 'Tega elementac ni mogoče izbrisati, ker so ostali elementi odvisni od njega.',
        'forbidden_action' => 'Zahtevanega dejanja ni mogoče izvršiti na tem elementu.',
        // 'insufficient_entity_permission' => 'You don't have permission to access this item.',
    ],
];
