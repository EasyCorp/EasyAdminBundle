<?php

return [
    'page_title' => [
        // 'dashboard' => '',
        'detail' => '%entity_label_singular% <small>(#%entity_short_id%)</small>',
        'edit' => 'Redaguoti %entity_label_singular% <small>(#%entity_short_id%)</small>',
        'index' => '%entity_label_plural%',
        'new' => 'Sukurti %entity_label_singular%',
        'exception' => 'Klaida|Klaidos',
    ],

    'datagrid' => [
        // 'hidden_results' => '',
        'no_results' => 'Rezultatų nerasta.',
    ],

    'paginator' => [
        'first' => 'Pirmas',
        'previous' => 'Ankstesnis',
        'next' => 'Sekantis',
        'last' => 'Paskutinis',
        'counter' => '<strong>%start%</strong> - <strong>%end%</strong> iš <strong>%results%</strong>',
        'results' => '{0} Nėra rezultatų|{1} <strong>1</strong> rezultatas|]1,Inf] <strong>%count%</strong> rezultatai',
    ],

    'label' => [
        'true' => 'Taip',
        'false' => 'Ne',
        'empty' => 'Tuščia',
        'null' => 'Null',
        'nullable_field' => 'Palikti tuščią',
        'object' => 'PHP Objektas',
        'inaccessible' => 'Nepasiekiama',
        'inaccessible.explanation' => 'Getter metodas neegzistuoja šiame lauke arba nuosavybė nėra vieša',
        'form.empty_value' => 'Nenurodyta',
    ],

    'field' => [
        // 'code_editor.view_code' => '',
        // 'text_editor.view_content' => '',
    ],

    'action' => [
        'entity_actions' => 'Veiksmai',
        'new' => 'Sukurti %entity_label_singular%',
        'search' => 'Ieškoti',
        'detail' => 'Rodyti',
        'edit' => 'Redaguoti',
        'delete' => 'Pašalinti',
        'cancel' => 'Atšaukti',
        'index' => 'Grįžti į sąrašą',
        // 'deselect' => '',
        'add_new_item' => 'Pridėti naują elementą',
        'remove_item' => 'Pašalinti elementą',
        'choose_file' => 'Pasirinkti failą',
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
        'title' => 'Ar tikrai norite ištrinti šį elementą?',
        'content' => 'Šios operacijos atkurti nebegalėsite.',
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
        'are_you_sure' => 'Jūs neįrašėte šios formos pakeitimų.',
        // 'tab.error_badge_title' => '',
    ],

    'user' => [
        'logged_in_as' => 'Prisijungta kaip',
        'unnamed' => 'Neįvardintas vartotojas',
        'anonymous' => 'Anonimiškas vartotojas',
        'sign_out' => 'Atsijungti',
        // 'exit_impersonation' => '',
    ],

    'login_page' => [
        'username' => 'Vartotojo vardas',
        'password' => 'Slaptažodis',
        'sign_in' => 'Prisijungti',
    ],

    'exception' => [
        'entity_not_found' => 'Šis elementas nebepasiekiamas.',
        'entity_remove' => 'Šis elementas negali būti ištrintas, nes nuo jo priklauso kiti elementai.',
        'forbidden_action' => 'Norimas atlikti veiksmas šiam elementui negalimas.',
        // 'insufficient_entity_permission' => 'You don't have permission to access this item.',
    ],
];
