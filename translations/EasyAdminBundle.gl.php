<?php

return [
    'page_title' => [
        'dashboard' => 'Panel de control',
        'detail' => '%entity_label_singular% <small>(#%entity_short_id%)</small>',
        'edit' => 'Modificar %entity_label_singular%',
        'index' => '%entity_label_plural%',
        'new' => 'Crear %entity_label_singular%',
        'exception' => 'Error|Errores',
    ],

    'datagrid' => [
        'hidden_results' => 'Algúns resultados non poden mostrarse porque non tes permisos suficientes',
        'no_results' => 'Non se atoparon resultados.',
    ],

    'paginator' => [
        'first' => 'Primeira',
        'previous' => 'Anterior',
        'next' => 'Seguinte',
        'last' => 'Última',
        'counter' => '<strong>%start%</strong> - <strong>%end%</strong> de <strong>%results%</strong>',
        'results' => '{0} Non se atoparon resultados|{1} <strong>1</strong> resultado|]1,Inf] <strong>%count%</strong> resultados',
    ],

    'label' => [
        'true' => 'Si',
        'false' => 'Non',
        'empty' => 'Baleiro',
        'null' => 'Nulo',
        'object' => 'Obxecto PHP',
        'inaccessible' => 'Inaccesible',
        'inaccessible.explanation' => 'Este campo non ten ningún método getter ou a propiedade asociada non é publica',
        'form.empty_value' => 'Ningún',
    ],

    'field' => [
        'code_editor.view_code' => 'Ver código',
        'text_editor.view_content' => 'Ver contido',
    ],

    'action' => [
        'entity_actions' => 'Accións',
        'new' => 'Crear %entity_label_singular%',
        'search' => 'Buscar',
        'detail' => 'Ver',
        'edit' => 'Modificar',
        'delete' => 'Borrar',
        'cancel' => 'Cancelar',
        'index' => 'Volver o listado',
        'deselect' => 'Deseleccionar',
        'add_new_item' => 'Engadir un elemento',
        'remove_item' => 'Eliminar este elemento',
        'choose_file' => 'Seleccionar arquivo',
        'close' => 'Pechar',
        'download' => 'Descargar',
        'create' => 'Crear',
        'create_and_add_another' => 'Crear e engadir outro',
        'create_and_continue' => 'Crear e continuar editando',
        'save' => 'Gardar cambios',
        'save_and_continue' => 'Gardar e continuar editando',
        'toggle_dropdown' => 'Alternar menú despregable',
    ],

    'batch_action_modal' => [
        'title' => 'Vas aplicar a acción "%action_name%" a %num_items% elemento(s).',
        'content' => 'Esta operación non se pode desfacer.',
        'action' => 'Continuar',
    ],

    'delete_modal' => [
        'title' => '¿Queres realmente borrar este elemento?',
        'content' => 'Esta acción non se pode desfacer.',
    ],

    'action_confirmation_modal' => [
        'title' => 'Estás seguro de que queres %action_name%?',
        'action' => 'Confirmar',
    ],

    'filter' => [
        'title' => 'Filtros',
        'button.clear' => 'Limpar',
        'button.apply' => 'Aplicar',
        'label.is_equal_to' => 'é igual a',
        'label.is_not_equal_to' => 'non é igual a',
        'label.is_greater_than' => 'é maior que',
        'label.is_greater_than_or_equal_to' => 'é maior ou igual que',
        'label.is_less_than' => 'é menor que',
        'label.is_less_than_or_equal_to' => 'é menor ou igual que',
        'label.is_between' => 'está entre',
        'label.contains' => 'contén',
        'label.contains_all' => 'contén todos',
        'label.not_contains' => 'non contén',
        'label.starts_with' => 'comeza por',
        'label.ends_with' => 'remata por',
        'label.exactly' => 'exactamente',
        'label.not_exactly' => 'non exactamente',
        'label.is_same' => 'é o mesmo',
        'label.is_not_same' => 'non é o mesmo',
        'label.is_after' => 'é posterior a',
        'label.is_after_or_same' => 'é posterior ou igual a',
        'label.is_before' => 'é anterior a',
        'label.is_before_or_same' => 'é anterior ou igual a',
    ],

    'form' => [
        'are_you_sure' => 'Non se gardaron os cambios feitos neste formulario.',
        'tab.error_badge_title' => 'Unha entrada non válida|%count% entradas non válidas',
        'slug.confirm_text' => 'Se cambias o slug, podes romper ligazóns noutras páxinas.',
    ],

    'user' => [
        'logged_in_as' => 'Conectado/a como',
        'unnamed' => 'Usuario sen nome',
        'anonymous' => 'Usuario anónimo',
        'sign_out' => 'Pechar sesión',
        'exit_impersonation' => 'Saír da suplantación',
    ],

    'settings' => [
        'appearance' => [
            'label' => 'Aparencia',
            'light' => 'Claro',
            'dark' => 'Escuro',
            'auto' => 'Automático',
        ],
        'locale' => 'Idioma',
    ],

    'login_page' => [
        'username' => 'Nome de usuario',
        'password' => 'Contrasinal',
        'sign_in' => 'Iniciar sesión',
        'forgot_password' => 'Esqueciches o contrasinal?',
        'remember_me' => 'Lembrarme',
    ],

    'exception' => [
        'entity_not_found' => 'Este elemento xa no está dispoñible.',
        'entity_remove' => 'Este elemento non se pode eliminar porque outros elementos dependen del.',
        'forbidden_action' => 'Non se pode realizar a acción solicitada neste elemento.',
        'insufficient_entity_permission' => 'Non tes permiso para acceder a este elemento.',
        'general' => 'Produciuse un erro ao procesar a solicitude.',
        'general_403' => 'Non tes permiso para realizar esta acción.',
        'general_404' => 'Non se atopou a páxina solicitada.',
        'general_500' => 'Produciuse un erro interno ao procesar a solicitude.',
    ],

    'file_upload' => [
        'add_file' => 'Engadir ficheiro',
        'add_files' => 'Engadir ficheiros',
        'clear_all' => 'Limpar todo',
    ],

    'autocomplete' => [
        'no-results-found' => 'Non se atoparon resultados',
        'no-more-results' => 'Non hai máis resultados',
        'loading-more-results' => 'Cargando máis resultados…',
    ],
];
