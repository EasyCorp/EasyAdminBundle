<?php

return [
    'page_title' => [
        // 'dashboard' => '',
        'detail' => '%entity_label_singular% megtekintése - <small>(#%entity_short_id%)</small>',
        'edit' => '%entity_label_singular% szerkesztése - <small>(#%entity_short_id%)</small>',
        'index' => '%entity_label_plural%',
        'new' => 'Új %entity_label_singular% létrehozása',
        'exception' => 'Hiba|Hibák',
    ],

    'datagrid' => [
        // 'hidden_results' => '',
        'no_results' => 'Nincs találat.',
    ],

    'paginator' => [
        'first' => 'Első',
        'previous' => 'Előző',
        'next' => 'Következő',
        'last' => 'Utolsó',
        'counter' => '<strong>%start%</strong> - <strong>%end%</strong> / <strong>%results%</strong>',
        // 'results' => '',
    ],

    'label' => [
        'true' => 'Igen',
        'false' => 'Nem',
        'empty' => 'Üresen hagy',
        'null' => 'Semmi',
        'nullable_field' => 'Üresen hagy',
        'object' => 'PHP objektum',
        'inaccessible' => 'Elérhetetlen',
        'inaccessible.explanation' => 'A getter metódus nem létezik ehhez a mezőhöz vagy a tulajdonság nem publikus.',
        'form.empty_value' => 'Nincs',
    ],

    'field' => [
        // 'code_editor.view_code' => '',
        // 'text_editor.view_content' => '',
    ],

    'action' => [
        'entity_actions' => 'Műveletek',
        'new' => 'Új %entity_label_singular% létrehozása',
        'search' => 'Keresés',
        'detail' => 'Megtekintés',
        'edit' => 'Szerkesztés',
        'delete' => 'Törlés',
        'cancel' => 'Mégsem',
        'index' => 'Vissza a listához',
        // 'deselect' => '',
        'add_new_item' => 'Új elem létrehozása',
        'remove_item' => 'Elem eltávolítása',
        'choose_file' => 'Fájl kiválasztása',
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
        'title' => 'Biztos benne, hogy törli ezt az elemet?',
        'content' => 'Ez a művelet visszavonhatatlan.',
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
        'are_you_sure' => 'A formon végzett módosítások nem lettek elmentve.',
        // 'tab.error_badge_title' => '',
    ],

    'user' => [
        'logged_in_as' => 'Belépve mint',
        'unnamed' => 'Névtelen felhasználó',
        'anonymous' => 'Anonim felhasználó',
        'sign_out' => 'Kilépés',
        // 'exit_impersonation' => '',
    ],

    'login_page' => [
        'username' => 'Username',
        'password' => 'Password',
        'sign_in' => 'Sign in',
    ],

    'exception' => [
        'entity_not_found' => 'Ez az elem már nem elérhető.',
        'entity_remove' => 'Ez az elem nem törölhető más kapcsolódó adatok miatt.',
        'forbidden_action' => 'A kért művelet nem hajtható végre ezen az elemen.',
        // 'insufficient_entity_permission' => 'You don't have permission to access this item.',
    ],
];
