<?php

return [
    'page_title' => [
        // 'dashboard' => '',
        'detail' => '%entity_label_singular% <small>(#%entity_short_id%)</small>',
        'edit' => 'Ret %entity_label_singular% <small>(#%entity_short_id%)</small>',
        'index' => '%entity_label_plural%',
        'new' => 'Opret %entity_label_singular%',
        'exception' => 'Fejl|Fejl',
    ],

    'datagrid' => [
        'hidden_results' => 'Nogle resultater kan ikke vises fordi du ikke har nok rettigheder',
        'no_results' => 'Intet resultat.',
    ],

    'paginator' => [
        'first' => 'Første',
        'previous' => 'Forrige',
        'next' => 'Næste',
        'last' => 'Sidste',
        'counter' => '<strong>%start%</strong> - <strong>%end%</strong> af <strong>%results%</strong>',
        'results' => '{0} Ingen resultater|{1} <strong>1</strong> resultat|]1,Inf] <strong>%count%</strong> resultater',
    ],

    'label' => [
        'true' => 'Ja',
        'false' => 'Nej',
        'empty' => 'Tom',
        'null' => 'Null',
        'nullable_field' => 'Efterlad tom',
        'object' => 'PHP Objekt',
        'inaccessible' => 'Utilgængelig',
        'inaccessible.explanation' => 'Der findes ingen getter metode for dette felt eller også er det ikke et tilgængeligt felt',
        'form.empty_value' => 'Ingen',
    ],

    'field' => [
        // 'code_editor.view_code' => '',
        // 'text_editor.view_content' => '',
    ],

    'action' => [
        'entity_actions' => 'Handlinger',
        'new' => 'Tilføj %entity_label_singular%',
        'search' => 'Søg',
        'detail' => 'Vis',
        'edit' => 'Ret',
        'delete' => 'Slet',
        'cancel' => 'Afbryd',
        'index' => 'Tilbage til listen',
        // 'deselect' => '',
        'add_new_item' => 'Tilføj nyt emne',
        'remove_item' => 'Slet emnet',
        'choose_file' => 'Vælg fil',
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
        'title' => 'Er du sikker på du vil slette dette emne?',
        'content' => 'Denne operation kan ikke fortrydes.',
    ],

    'filter' => [
        'title' => 'Filtre',
        'button.clear' => 'Ryd',
        'button.apply' => 'Aktiver',
        'label.is_equal_to' => 'er lig med',
        'label.is_not_equal_to' => 'er ikke lig med',
        'label.is_greater_than' => 'er større end',
        'label.is_greater_than_or_equal_to' => 'er større end eller lig med',
        'label.is_less_than' => 'er mindre end',
        'label.is_less_than_or_equal_to' => 'er mindre end eller lig med',
        'label.is_between' => 'er i mellem',
        'label.contains' => 'indeholder',
        'label.not_contains' => 'indeholder ikke',
        'label.starts_with' => 'starter med',
        'label.ends_with' => 'slutter med',
        'label.exactly' => 'præcis',
        'label.not_exactly' => 'ikke præcis',
        'label.is_same' => 'er samme som',
        'label.is_not_same' => 'er ikke samme som',
        'label.is_after' => 'er efter',
        'label.is_after_or_same' => 'er efter eller samme som',
        'label.is_before' => 'er før',
        'label.is_before_or_same' => 'er før eller samme som',
    ],

    'form' => [
        'are_you_sure' => 'Du har ikke gemt ændringerne til denne form.',
        'tab.error_badge_title' => 'En ugyldig indtastning|%count% ugyldige indtastninger',
    ],

    'user' => [
        'logged_in_as' => 'Logget ind som',
        'unnamed' => 'Unavngiven bruger',
        'anonymous' => 'Anonym bruger',
        'sign_out' => 'Log ud',
        'exit_impersonation' => 'Stop brugerovertagelse',
    ],

    'login_page' => [
        'username' => 'Username',
        'password' => 'Password',
        'sign_in' => 'Sign in',
    ],

    'exception' => [
        'entity_not_found' => 'Emnet er ikke længerer tilgængeligt.',
        'entity_remove' => 'Dette emne kan ikke slettes, da der er andre emner der er afhængige af det.',
        'forbidden_action' => 'Denne handling kan ikke udføres på dette emne.',
        // 'insufficient_entity_permission' => 'You don't have permission to access this item.',
    ],
];
