<?php

return [
    'page_title' => [
        // 'dashboard' => '',
        'detail' => '%entity_label_singular% <small>(#%entity_short_id%)</small>',
        'edit' => '%entity_label_singular% <small>(#%entity_short_id%)</small>',
        'index' => '%entity_label_plural%',
        'new' => 'Dodaj nowy %entity_label_singular%',
        'exception' => 'Błąd|Błędy',
    ],

    'datagrid' => [
        // 'hidden_results' => '',
        'no_results' => 'Brak wyników.',
    ],

    'paginator' => [
        'first' => 'Pierwsza',
        'previous' => 'Poprzednia',
        'next' => 'Następna',
        'last' => 'Ostatnia',
        'counter' => '<strong>%start%</strong> - <strong>%end%</strong> z <strong>%results%</strong>',
        'results' => '<strong>%count%</strong> wynik|<strong>%count%</strong> wyniki|<strong>%count%</strong> wyników|{0} Brak wyników',
    ],

    'label' => [
        'true' => 'Tak',
        'false' => 'Nie',
        'empty' => 'Pusty',
        'null' => 'Brak',
        'nullable_field' => 'Zostaw niewypełnione',
        'object' => 'Obiekt PHP',
        'inaccessible' => 'Niedostępny',
        'inaccessible.explanation' => 'Metoda pobierająca (<i>ang. getter</i>) nie istnieje  dla tego pola lub właściwość (<i>ang. field</i>) nie jest publiczna',
        'form.empty_value' => 'Pusta wartość',
    ],

    'field' => [
        // 'code_editor.view_code' => '',
        // 'text_editor.view_content' => '',
    ],

    'action' => [
        'entity_actions' => 'Akcje',
        'new' => 'Dodaj %entity_label_singular%',
        'search' => 'Szukaj',
        'detail' => 'Pokaż',
        'edit' => 'Edytuj',
        'delete' => 'Usuń',
        'cancel' => 'Anuluj',
        'index' => 'Wróć do listy',
        'deselect' => 'Odznacz',
        'add_new_item' => 'Dodaj nową pozycję',
        'remove_item' => 'Usuń pozycję',
        'choose_file' => 'Wybierz plik',
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
        'title' => 'Czy na pewno chcesz usunąć ten element?',
        'content' => 'Tej operacji nie można cofnąć.',
    ],

    'filter' => [
        'title' => 'Filtry',
        'button.clear' => 'Wyczyść',
        'button.apply' => 'Zatwierdź',
        'label.is_equal_to' => 'równe',
        'label.is_not_equal_to' => 'różne od',
        'label.is_greater_than' => 'większy niż',
        'label.is_greater_than_or_equal_to' => 'większy lub równy',
        'label.is_less_than' => 'mniejszy niż',
        'label.is_less_than_or_equal_to' => 'mniejszy lub równy',
        // 'label.is_between' => '',
        'label.contains' => 'zawiera',
        'label.not_contains' => 'nie zawiera',
        'label.starts_with' => 'zaczyna się od',
        'label.ends_with' => 'kończy się na',
        'label.exactly' => 'dokładnie jak',
        'label.not_exactly' => 'nie dokładnie jak',
        'label.is_same' => 'takie samo jak',
        'label.is_not_same' => 'inne niż',
        'label.is_after' => 'późniejsza niż',
        'label.is_after_or_same' => 'taka sama lub późniejsza niż',
        'label.is_before' => 'wcześniejsza niż',
        'label.is_before_or_same' => 'taka sama lub wcześniejsza niż ',
    ],

    'form' => [
        'are_you_sure' => 'Nie zapisano zmian wprowadzonych w tym formularzu.',
        'tab.error_badge_title' => 'Wystąpił jeden błąd|Ilość błędów: %count%',
    ],

    'user' => [
        'logged_in_as' => 'Zalogowany jako',
        'unnamed' => 'Użytkownik bez nazwy',
        'anonymous' => 'Anonimowy użytkownik',
        'sign_out' => 'Wyloguj',
        'exit_impersonation' => 'Opuść tryb maskowania',
    ],

    'login_page' => [
        'username' => 'Użytkownik',
        'password' => 'Hasło',
        'sign_in' => 'Zaloguj się',
    ],

    'exception' => [
        'entity_not_found' => 'Ten obiekt nie jest już dostępny.',
        'entity_remove' => 'Ten obiekt nie może być usunięty ponieważ istnieją inne, które są z nim powiązane.',
        'forbidden_action' => 'Na tej pozycji nie można wykonać wybranej akcji.',
        // 'insufficient_entity_permission' => 'You don't have permission to access this item.',
    ],
];
