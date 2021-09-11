<?php

return [
    'page_title' => [
        // 'dashboard' => '',
        'detail' => '%entity_as_string%',
        'edit' => 'ویرایش %entity_label_singular%',
        'index' => '%entity_label_plural%',
        'new' => 'ایجاد %entity_label_singular%',
        'exception' => 'اخطار|اخطارها',
    ],

    'datagrid' => [
        // 'hidden_results' => '',
        'no_results' => 'نتیجه‌ای یافت نشد',
    ],

    'paginator' => [
        'first' => 'اول',
        'previous' => 'قبلی',
        'next' => 'بعدی',
        'last' => 'آخر',
        'counter' => '<strong>%start%</strong> - <strong>%end%</strong> of <strong>%results%</strong>',
        // 'results' => '',
    ],

    'label' => [
        'true' => 'بله',
        'false' => 'خیر',
        'empty' => 'خالی',
        'null' => 'تهی',
        'nullable_field' => 'Leave empty',
        'object' => 'شی PHP',
        'inaccessible' => 'غیرقابل دسترس',
        'inaccessible.explanation' => 'متد Getter برای این فیلد موجود نیست و یا Property عمومی تعریف نشده است',
        'form.empty_value' => 'هیچ',
    ],

    'field' => [
        // 'code_editor.view_code' => '',
        // 'text_editor.view_content' => '',
    ],

    'action' => [
        'entity_actions' => 'عملیات',
        'new' => 'افزودن %entity_label_singular%',
        'search' => 'جستجو',
        'detail' => 'نمایش',
        'edit' => 'ویرایش',
        'delete' => 'حذف',
        'cancel' => 'انصراف',
        'index' => 'بازگشت به لیست',
        // 'deselect' => '',
        'add_new_item' => 'افزودن آیتم جدید',
        'remove_item' => 'حذف آیتم',
        'choose_file' => 'انتخاب فایل',
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
        'title' => 'واقعا می‌خواهید این آیتم را حذف کنید؟',
        'content' => 'این عملیات غیرقابل بازگشت است.',
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
        'are_you_sure' => 'شما تغییرات ایجاد شده در این فرم را ذخیره نکرده‌اید.',
        'tab.error_badge_title' => 'یک ورودی نامعتبر است|%count% ورودی نا معتبر است',
    ],

    'user' => [
        'logged_in_as' => 'ورود به عنوان',
        'unnamed' => 'کاربر بدون نام',
        'anonymous' => 'کاربر ناشناس',
        'sign_out' => 'خروج',
        'exit_impersonation' => 'خروج از impersonation',
    ],

    'login_page' => [
        'username' => 'Username',
        'password' => 'Password',
        'sign_in' => 'Sign in',
        'forgot_password' => 'کلمه عبور خود را فراموش کرده اید؟',
        'remember_me' => 'مرا به خاطر بسپار',
    ],

    'exception' => [
        'entity_not_found' => 'این آیتم دیگر در دسترس نیست',
        'entity_remove' => 'این آیتم نمی‌تواند حذف شود، زیرا آیتم های وابسته‌ای دارد.',
        'forbidden_action' => 'عملیات درخواستی در مورد این آیتم قابل انجام نیست.',
        // 'insufficient_entity_permission' => 'You don't have permission to access this item.',
    ],

    'autocomplete' => [
        'no-results-found' => 'هیچ نتیجه‌ای یافت نشد',
        // 'no-more-results' => 'No more results',
        'loading-more-results' => 'در حال بارگذاری نتایج بیشتر…',
    ],
];
