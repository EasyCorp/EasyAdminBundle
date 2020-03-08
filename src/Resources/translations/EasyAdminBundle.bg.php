<?php

return [
    'page_title' => [
        // 'dashboard' => '',
        'detail' => '%entity_label_singular% (#%entity_id%)',
        'edit' => 'Редактиране на %entity_label_singular% (#%entity_id%)',
        'index' => '%entity_label_plural%',
        'new' => 'Създаване на %entity_label_singular%',
        'exception' => 'Грешка|Грешки',
    ],

    'datagrid' => [
        // 'hidden_results' => '',
        'no_results' => 'Няма резултати.',
    ],

    'paginator' => [
        'first' => 'Първа',
        'previous' => 'Предишна',
        'next' => 'Следваща',
        'last' => 'Последна',
        'counter' => '<strong>%start%</strong>–<strong>%end%</strong> от <strong>%results%</strong>',
        'results' => '{0} Няма резултати|{1} <strong>1</strong> резултат|]1,Inf] <strong>%count%</strong> резултата',
    ],

    'label' => [
        'true' => 'Да',
        'false' => 'Не',
        'empty' => 'Празно',
        'null' => '',
        'nullable_field' => 'Да се остави празно',
        'object' => 'Обект от PHP',
        'inaccessible' => 'Недостъпно',
        'inaccessible.explanation' => 'За това поле не съществува обектов метод за достъп (getter), нито пък съответната обектова променлива е публично достъпна (public).',
        // 'form.empty_value' => '',
    ],

    'property' => [
        // 'code_editor.view_code' => '',
        // 'text_editor.view_content' => '',
    ],

    'action' => [
        'entity_actions' => 'Действия',
        'new' => 'Добавяне на %entity_label_singular%',
        'search' => 'Търсене',
        'detail' => 'Преглед',
        'edit' => 'Редактиране',
        'delete' => 'Изтриване',
        'cancel' => 'Отказ',
        'index' => 'Обратно към списъка',
        // 'deselect' => '',
        'add_new_item' => 'Добавяне на нов елемент',
        'remove_item' => 'Изтриване на елемента',
        'choose_file' => 'Избор на файл',
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
        'title' => 'Наистина ли желаете да изтриете записа?',
        'content' => 'Това действие е необратимо.',
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
        'are_you_sure' => 'Не сте записали направените във формуляра промени.',
        'tab.error_badge_title' => 'Едно невалидно поле|%count% невалидни полета',
    ],

    'user' => [
        'logged_in_as' => 'Влезли сте като',
        'unnamed' => 'Безименен потребител',
        'anonymous' => 'Анонимен потребител',
        'sign_out' => 'Изход',
        'exit_impersonation' => 'Изход от представянето',
    ],

    'login_page' => [
        'username' => 'Потребителско има',
        'password' => 'Парола',
        'sign_in' => 'Вход',
    ],

    'exception' => [
        'entity_not_found' => 'Този елемент вече не е налице.',
        'entity_remove' => 'Този елемент не може да бъде изтрит, защото други елементи зависят от него.',
        'forbidden_action' => 'Заявеното действие не може да се изпълни за този елемент.',
    ],
];
