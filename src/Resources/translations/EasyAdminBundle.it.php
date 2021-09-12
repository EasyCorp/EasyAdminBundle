<?php

return [
    'page_title' => [
        // 'dashboard' => '',
        'detail' => '%entity_as_string%',
        'edit' => 'Modifica %entity_label_singular%',
        'index' => '%entity_label_plural%',
        'new' => 'Crea %entity_label_singular%',
        'exception' => 'Errore|Errori',
    ],

    'datagrid' => [
        // 'hidden_results' => '',
        'no_results' => 'Nessun risultato trovato.',
    ],

    'paginator' => [
        'first' => 'Prima',
        'previous' => 'Precedente',
        'next' => 'Successiva',
        'last' => 'Ultima',
        'counter' => '<strong>%start%</strong> - <strong>%end%</strong> di <strong>%results%</strong>',
        'results' => '{0} Nessun risultato|{1} <strong>1</strong> risultato|]1,Inf] <strong>%count%</strong> risultati',
    ],

    'label' => [
        'true' => 'Si',
        'false' => 'No',
        'empty' => 'Vuoto',
        'null' => 'Null',
        'nullable_field' => 'Lascia vuoto',
        'object' => 'Oggetto PHP',
        'inaccessible' => 'Inaccessibile',
        'inaccessible.explanation' => 'Il metodo getter non esiste per questo campo o la proprietà non è pubblica',
        'form.empty_value' => 'Nessun valore',
    ],

    'field' => [
        // 'code_editor.view_code' => '',
        // 'text_editor.view_content' => '',
    ],

    'action' => [
        'entity_actions' => 'Azioni',
        'new' => 'Crea %entity_label_singular%',
        'search' => 'Cerca',
        'detail' => 'Vedi',
        'edit' => 'Modifica',
        'delete' => 'Elimina',
        'cancel' => 'Annulla',
        'index' => 'Torna alla lista',
        'deselect' => 'Deseleziona',
        'add_new_item' => 'Aggiungi un nuovo elemento',
        'remove_item' => 'Rimuovi l\'elemento',
        'choose_file' => 'Scegli file',
        'close' => 'Chiudi',
        'create' => 'Crea',
        'create_and_add_another' => 'Crea ed aggiungi',
        'create_and_continue' => 'Crea e continua',
        'save' => 'Salva',
        'save_and_continue' => 'Salva e continua a modificare',
    ],

    'batch_action_modal' => [
        // 'title' => '',
        // 'content' => '',
        // 'action' => '',
    ],

    'delete_modal' => [
        'title' => 'Vuoi eliminare questo elemento?',
        'content' => 'Questa azione è irreversibile.',
    ],

    'filter' => [
        'title' => 'Filtri',
        'button.clear' => 'Cancella',
        'button.apply' => 'Applica',
        'label.is_equal_to' => 'è uguale a',
        'label.is_not_equal_to' => 'non è uguale a',
        'label.is_greater_than' => 'è maggiore di',
        'label.is_greater_than_or_equal_to' => 'è maggiore o uguale di',
        'label.is_less_than' => 'è minore di',
        'label.is_less_than_or_equal_to' => 'è minore o uguale di',
        // 'label.is_between' => '',
        'label.contains' => 'contiene',
        'label.not_contains' => 'non contiene',
        'label.starts_with' => 'inizia con',
        'label.ends_with' => 'finisce con',
        'label.exactly' => 'esattamente',
        'label.not_exactly' => 'non esattamente',
        'label.is_same' => 'è uguale',
        'label.is_not_same' => 'non è uguale',
        'label.is_after' => 'è successivo',
        'label.is_after_or_same' => 'è successivo o uguale',
        'label.is_before' => 'è precedente',
        'label.is_before_or_same' => 'è precedente o uguale',
    ],

    'form' => [
        'are_you_sure' => 'Non hai salvato le modifiche apportate su questo modulo.',
        'tab.error_badge_title' => 'Un campo non è valido|%count% campi non sono validi',
    ],

    'user' => [
        'logged_in_as' => 'Connesso come',
        'unnamed' => 'Utente senza nome',
        'anonymous' => 'Utente anonimo',
        'sign_out' => 'Esci',
        'exit_impersonation' => 'Esci dall\'impersonazione',
    ],

    'login_page' => [
        'username' => 'Nome utente',
        'password' => 'Password',
        'sign_in' => 'Accedi',
        'forgot_password' => 'Password dimenticata?',
        'remember_me' => 'Ricordami',
    ],

    'exception' => [
        'entity_not_found' => 'Questo elemento non è più disponibile.',
        'entity_remove' => 'L\'elemento selezionato non può essere cancellato perché altri elementi dipendono da questo.',
        'forbidden_action' => 'L\'azione richiesta non può essere eseguita su questo elemento.',
        // 'insufficient_entity_permission' => 'You don't have permission to access this item.',
    ],
];
