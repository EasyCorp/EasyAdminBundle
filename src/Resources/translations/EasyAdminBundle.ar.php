<?php

return [
    'page_title' => [
        // 'dashboard' => '',
        'detail' => '<small>(#%entity_short_id%)</small> %entity_label_singular%',
        'edit' => '<small>(#%entity_short_id%)</small> %entity_label_singular% تعديل',
        'index' => '%entity_label_plural%',
        'new' => '"%entity_label_singular%" جديد',
        'exception' => '{1} خطأ|{2} خطأن|]2,Inf] أخطاء ',
    ],

    'datagrid' => [
        'hidden_results' => 'لا يمكنك عرض بعض النتائج لأنك لا تملك أذونات كافية',
        'no_results' => 'لا توجد أيّ نتائج',
    ],

    'paginator' => [
        'first' => 'الأول',
        'previous' => 'السابق',
        'next' => 'التالي',
        'last' => 'الأخير',
        'counter' => '<strong>%results%</strong> / <strong>%end%</strong> - <strong>%start%</strong>',
        'results' => '{0} لا توجد أيّ نتائج |{1} <strong>1</strong> نتيجة|]1,Inf] <strong>%count%</strong> نتائج',
    ],

    'label' => [
        'true' => 'نعم',
        'false' => 'لا',
        'empty' => 'فارغ',
        'null' => 'لا شيء',
        'nullable_field' => 'اتركه فارغ',
        'object' => 'Objet PHP',
        'inaccessible' => 'لا يمكن الوصول إليها',
        'inaccessible.explanation' => 'لا يوجد وصف الوصول لهذه الخاصية أو أنها ليست عامة.',
        'form.empty_value' => 'لا شيء',
    ],

    'field' => [
        // 'code_editor.view_code' => '',
        // 'text_editor.view_content' => '',
    ],

    'action' => [
        'entity_actions' => 'إجراءات',
        'new' => '%entity_label_singular% جديد',
        'search' => 'بحث',
        'detail' => 'إطلاع',
        'edit' => 'تعديل',
        'delete' => 'حذف',
        'cancel' => 'الغاء',
        'index' => 'رجوع إلى القائمة',
        'deselect' => 'الغاء تحديد',
        'add_new_item' => 'إضافة عنصر جديد',
        'remove_item' => 'حذف العنصر',
        'choose_file' => 'اختيار ملفّ',
        // 'close' => '',
        // 'create' => '',
        // 'create_and_add_another' => '',
        // 'create_and_continue' => '',
        // 'save' => '',
        // 'save_and_continue' => '',
    ],

    'batch_action_modal' => [
        'title' => 'سوف تقوم بتطبيق الأجراء "%action_name%" على %num_items% عنصر',
        'content' => 'لا يمكنك التراجع عن هذا الإجراء.',
        // 'action' => '',
    ],

    'delete_modal' => [
        'title' => 'هل تريد حذف هذا العنصر؟',
        'content' => 'هذا الإجراء غير قابل للإلغاء.',
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
        'are_you_sure' => 'لم يتّم حفظ التغييرات.',
        'tab.error_badge_title' => 'حقل واحد غير صالح|%count% حقول غير صالحة',
    ],

    'user' => [
        'logged_in_as' => 'تسجيل الدخول بإسم',
        'unnamed' => 'مستخدم بدون إسم',
        'anonymous' => 'مستخدم مجهول',
        'sign_out' => 'تسجيل الخروج',
        'exit_impersonation' => 'خروج وهمي',
    ],

    'login_page' => [
        'username' => 'إسم المستخدم',
        'password' => 'كلمة السّر',
        'sign_in' => 'تسجيل الدخول',
    ],

    'exception' => [
        'entity_not_found' => 'هذا العنصر لم يعد متوفر',
        'entity_remove' => 'لا يمكنك حذف هذا العنصر لأن العناصر الأخرى تعتمد عليه.',
        'forbidden_action' => 'لا يمكنك تنفيذ الإجراء المطلوب على هذا العنصر.',
        // 'insufficient_entity_permission' => 'You don't have permission to access this item.',
    ],
];
