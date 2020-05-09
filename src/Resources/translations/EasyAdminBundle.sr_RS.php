<?php

return [
    'page_title' => [
        // 'dashboard' => '',
        'detail' => '%entity_label_singular% <small>(#%entity_short_id%)</small>',
        'edit' => 'Izmena %entity_label_singular% <small>(#%entity_short_id%)</small>',
        'index' => '%entity_label_plural%',
        'new' => 'Novi %entity_label_singular%',
        'exception' => 'Greška|Greške',
    ],

    'datagrid' => [
        // 'hidden_results' => '',
        'no_results' => 'Nema pronađenin rezultata.',
    ],

    'paginator' => [
        'first' => 'Prva',
        'previous' => 'Prethodna',
        'next' => 'Sledeća',
        'last' => 'Poslednja',
        'counter' => '<strong>%start%</strong> - <strong>%end%</strong> od <strong>%results%</strong>',
        // 'results' => '',
    ],

    'label' => [
        'true' => 'Da',
        'false' => 'Ne',
        'empty' => 'Prazno',
        'null' => 'Ništa',
        'nullable_field' => 'Ostavi prazno',
        'object' => 'PHP Objekat',
        'inaccessible' => 'Nedostupno',
        'inaccessible.explanation' => 'Getter metoda ne postoji za ovo polje ili je nedostupna',
        'form.empty_value' => 'Prazno',
    ],

    'field' => [
        // 'code_editor.view_code' => '',
        // 'text_editor.view_content' => '',
    ],

    'action' => [
        'entity_actions' => 'Akcije',
        'new' => 'Dodaj %entity_label_singular%',
        'search' => 'Pretraži',
        'detail' => 'Prikaži',
        'edit' => 'Izmeni',
        'delete' => 'Izbriši',
        'cancel' => 'Otkaži',
        'index' => 'Nazad na listu',
        // 'deselect' => '',
        'add_new_item' => 'Dodaj novi zapis',
        'remove_item' => 'Ukloni zapis',
        'choose_file' => 'Одабери датотеку',
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
        'title' => 'Da li sigurno želite da obrišete ovaj zapis?',
        'content' => 'Ova operacija je nepovratna.',
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
        'are_you_sure' => 'Niste sačuvali izmene na ovoj formi.',
        'tab.error_badge_title' => 'Jedan pogrešan unos|%count% pogrešnih unosa',
    ],

    'user' => [
        'logged_in_as' => 'Ulogovan kao',
        'unnamed' => 'Korisnik bez imena',
        'anonymous' => 'Anonimni korisnik',
        'sign_out' => 'Izloguj se',
        'exit_impersonation' => 'Izađi iz oponašanja',
    ],

    'login_page' => [
        'username' => 'Username',
        'password' => 'Password',
        'sign_in' => 'Sign in',
    ],

    'exception' => [
        'entity_not_found' => 'Ovaj zapis više nije dostupan.',
        'entity_remove' => 'Ovaj zapis ne može biti izbrisan zato što drugi zapisi su vezani za njega.',
        'forbidden_action' => 'Data akcija ne može biti primenjena na ovaj zapis.',
        // 'insufficient_entity_permission' => 'You don't have permission to access this item.',
    ],
];
