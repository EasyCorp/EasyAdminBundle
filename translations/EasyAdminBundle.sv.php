<?php

return [
    'page_title' => [
        'dashboard' => 'Instrumentpanel',
        'detail' => '%entity_label_singular% <small>(#%entity_short_id%)</small>',
        'edit' => '%entity_label_singular%',
        'index' => '%entity_label_plural%',
        'new' => 'Create new %entity_label_singular%',
        'exception' => 'Fel|Fel',
    ],

    'datagrid' => [
        'hidden_results' => 'Vissa resultat kan inte visas eftersom du inte har tillräckliga behörigheter',
        'no_results' => 'Inga resultat.',
    ],

    'paginator' => [
        'first' => 'Första',
        'previous' => 'Förra',
        'next' => 'Nästa',
        'last' => 'Sista',
        'counter' => '<strong>%start%</strong> - <strong>%end%</strong> av <strong>%results%</strong>',
        'results' => '{0} Inga resultat|{1} <strong>1</strong> resultat|]1,Inf] <strong>%count%</strong> resultat',
    ],

    'label' => [
        'true' => 'Ja',
        'false' => 'Nej',
        'empty' => 'Tom',
        'null' => 'Null',
        'object' => 'PHP objekt',
        'inaccessible' => 'Otillgänglig',
        'inaccessible.explanation' => 'Det finns ingen Getter-funktion för detta fält eller så är inte egenskapen publik',
        'form.empty_value' => 'Ingen',
    ],

    'field' => [
        'code_editor.view_code' => 'Visa kod',
        'text_editor.view_content' => 'Visa innehåll',
    ],

    'action' => [
        'entity_actions' => 'Åtgärder',
        'new' => 'Skapa %entity_label_singular%',
        'search' => 'Sök',
        'detail' => 'Visa',
        'edit' => 'Redigera',
        'delete' => 'Ta bort',
        'cancel' => 'Avbryt',
        'index' => 'Åter till lista',
        'deselect' => 'Avmarkera',
        'add_new_item' => 'Lägg till nytt objekt',
        'remove_item' => 'Ta bort objekt',
        'choose_file' => 'Välj fil',
        'close' => 'Stäng',
        'download' => 'Ladda ner',
        'create' => 'Skapa',
        'create_and_add_another' => 'Skapa och lägg till en till',
        'create_and_continue' => 'Skapa och fortsätt redigera',
        'save' => 'Spara ändringar',
        'save_and_continue' => 'Spara och fortsätt redigera',
        'toggle_dropdown' => 'Växla rullgardinsmeny',
    ],

    'batch_action_modal' => [
        'title' => 'Du kommer att tillämpa åtgärden "%action_name%" på %num_items% objekt.',
        'content' => 'Det går inte att ångra den här åtgärden.',
        'action' => 'Fortsätt',
    ],

    'delete_modal' => [
        'title' => 'Vill du verkligen ta bort detta?',
        'content' => 'Du kan inte ångra det här.',
    ],

    'action_confirmation_modal' => [
        'title' => 'Är du säker på att du vill %action_name%?',
        'action' => 'Bekräfta',
    ],

    'filter' => [
        'title' => 'Filter',
        'button.clear' => 'Rensa',
        'button.apply' => 'Tillämpa',
        'label.is_equal_to' => 'är lika med',
        'label.is_not_equal_to' => 'är inte lika med',
        'label.is_greater_than' => 'är större än',
        'label.is_greater_than_or_equal_to' => 'är större än eller lika med',
        'label.is_less_than' => 'är mindre än',
        'label.is_less_than_or_equal_to' => 'är mindre än eller lika med',
        'label.is_between' => 'är mellan',
        'label.contains' => 'innehåller',
        'label.contains_all' => 'innehåller alla',
        'label.not_contains' => 'innehåller inte',
        'label.starts_with' => 'börjar med',
        'label.ends_with' => 'slutar med',
        'label.exactly' => 'exakt',
        'label.not_exactly' => 'inte exakt',
        'label.is_same' => 'är samma',
        'label.is_not_same' => 'är inte samma',
        'label.is_after' => 'är efter',
        'label.is_after_or_same' => 'är efter eller samma',
        'label.is_before' => 'är före',
        'label.is_before_or_same' => 'är före eller samma',
    ],

    'form' => [
        'are_you_sure' => 'Du har inte sparat ändringarna i formuläret.',
        'tab.error_badge_title' => 'Ett fält är fel|%count% fält är fel',
        'slug.confirm_text' => 'Om du ändrar sluggen kan du bryta länkar på andra sidor.',
    ],

    'user' => [
        'logged_in_as' => 'Inloggad som',
        'unnamed' => 'Namnlös användare',
        'anonymous' => 'Anonym användare',
        'sign_out' => 'Logga ut',
        'exit_impersonation' => 'Avsluta imitation',
    ],

    'settings' => [
        'appearance' => [
            'label' => 'Utseende',
            'light' => 'Ljust',
            'dark' => 'Mörkt',
            'auto' => 'Automatiskt',
        ],
        'locale' => 'Språk',
    ],

    'login_page' => [
        'username' => 'Username',
        'password' => 'Password',
        'sign_in' => 'Sign in',
        'forgot_password' => 'Glömt ditt lösenord?',
        'remember_me' => 'Kom ihåg mig',
    ],

    'exception' => [
        'entity_not_found' => 'Detta objekt är inte tillgängligt längre.',
        'entity_remove' => 'Detta object kan inte tas bort för att andra objekt har ett beroende på det.',
        'forbidden_action' => 'Den åtgärd du försökte göra kan inte utföras på detta objekt.',
        'insufficient_entity_permission' => 'Du har inte behörighet att komma åt detta objekt.',
        'general' => 'Ett fel uppstod vid behandlingen av din begäran.',
        'general_403' => 'Du har inte behörighet att utföra denna åtgärd.',
        'general_404' => 'Den begärda sidan kunde inte hittas.',
        'general_500' => 'Ett internt fel uppstod vid behandlingen av din begäran.',
    ],

    'file_upload' => [
        'add_file' => 'Lägg till fil',
        'add_files' => 'Lägg till filer',
        'clear_all' => 'Rensa alla',
    ],

    'autocomplete' => [
        'no-results-found' => 'Inga träffar',
        'no-more-results' => 'Inga fler resultat',
        'loading-more-results' => 'Laddar fler resultat…',
    ],
];
