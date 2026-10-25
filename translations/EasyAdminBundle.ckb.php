<?php

return [
    'page_title' => [
        'dashboard' => 'داشبۆرد',
        'detail' => '%entity_label_singular% <small>(#%entity_short_id%)</small>',
        'edit' => 'دەستکاریکردنی %entity_label_singular%',
        'index' => '%entity_label_plural%',
        'new' => 'دروستکردنی %entity_label_singular%',
        'exception' => 'هەڵە|هەڵەکان',
    ],

    'datagrid' => [
        'hidden_results' => 'هەندێک ئەنجام پیشان نادرێن چونکە مۆڵەتی پێویستت نییە',
        'no_results' => 'هیچ ئەنجامێک نەدۆزرایەوە.',
        'sort' => 'ڕیزکردن',
        'sort_by' => 'ڕیزکردن بەپێی',
    ],

    'paginator' => [
        'first' => 'یەکەم',
        'previous' => 'پێشوو',
        'next' => 'دواتر',
        'last' => 'کۆتایی',
        'counter' => '<strong>%start%</strong> - <strong>%end%</strong> لە <strong>%results%</strong>',
        'results' => '{0} هیچ ئەنجامێک نییە|{1} <strong>1</strong> ئەنجام|]1,Inf] <strong>%count%</strong> ئەنجام',
        'label' => 'لاپەڕەبەندی',
        'more_pages' => 'لاپەڕەی زیاتر',
    ],

    'label' => [
        'true' => 'بەڵێ',
        'false' => 'نەخێر',
        'empty' => 'بەتاڵ',
        'null' => 'Null',
        'object' => 'ئۆبجێکتی PHP',
        'inaccessible' => 'دەستپێنەگەیشتوو',
        'inaccessible.explanation' => 'میتۆدی Getter بۆ ئەم خانەیە بوونی نییە یان خانەکە گشتی (public) نییە',
        'form.empty_value' => 'هیچ',
    ],

    'field' => [
        'code_editor.view_code' => 'پیشاندانی کۆد',
        'text_editor.view_content' => 'پیشاندانی ناوەڕۆک',
    ],

    'action' => [
        'entity_actions' => 'کردارەکان',
        'new' => 'زیادکردنی %entity_label_singular%',
        'search' => 'گەڕان',
        'detail' => 'پیشاندان',
        'edit' => 'دەستکاری',
        'delete' => 'سڕینەوە',
        'cancel' => 'هەڵوەشاندنەوە',
        'index' => 'گەڕانەوە بۆ لیست',
        'deselect' => 'لابردنی دیاریکردن',
        'add_new_item' => 'زیادکردنی بابەتێکی نوێ',
        'remove_item' => 'لابردنی بابەتەکە',
        'choose_file' => 'هەڵبژاردنی فایل',
        'close' => 'داخستن',
        'download' => 'داگرتن',
        'create' => 'دروستکردن',
        'create_and_add_another' => 'دروستکردن و زیادکردنی یەکێکی تر',
        'create_and_continue' => 'دروستکردن و بەردەوامبوون لە دەستکاری',
        'save' => 'پاشەکەوتکردنی گۆڕانکارییەکان',
        'save_and_continue' => 'پاشەکەوتکردن و بەردەوامبوون لە دەستکاری',
        'toggle_dropdown' => 'کردنەوە/داخستنی لیست',
    ],

    'batch_action_modal' => [
        'title' => 'خەریکە کرداری "%action_name%" لەسەر %num_items% بابەت جێبەجێ دەکەیت.',
        'content' => 'ناتوانیت ئەم کردارە پاشگەز بکەیتەوە.',
        'action' => 'بەردەوامبوون',
    ],

    'delete_modal' => [
        'title' => 'دڵنیایت دەتەوێت ئەم بابەتە بسڕیتەوە؟',
        'content' => 'ناتوانیت ئەم کردارە پاشگەز بکەیتەوە.',
    ],

    'action_confirmation_modal' => [
        'title' => 'دڵنیایت دەتەوێت %action_name%؟',
        'action' => 'پشتڕاستکردنەوە',
    ],

    'filter' => [
        'title' => 'فلتەرەکان',
        'button.clear' => 'پاککردنەوە',
        'button.apply' => 'جێبەجێکردن',
        'label.is_equal_to' => 'یەکسانە بە',
        'label.is_not_equal_to' => 'یەکسان نییە بە',
        'label.is_greater_than' => 'گەورەترە لە',
        'label.is_greater_than_or_equal_to' => 'گەورەترە یان یەکسانە بە',
        'label.is_less_than' => 'بچووکترە لە',
        'label.is_less_than_or_equal_to' => 'بچووکترە یان یەکسانە بە',
        'label.is_between' => 'لە نێوانی',
        'label.contains' => 'لەخۆدەگرێت',
        'label.contains_all' => 'هەموویان لەخۆدەگرێت',
        'label.not_contains' => 'لەخۆناگرێت',
        'label.starts_with' => 'دەستپێدەکات بە',
        'label.ends_with' => 'کۆتایی دێت بە',
        'label.exactly' => 'ڕێک وەک',
        'label.not_exactly' => 'ڕێک وەک نییە',
        'label.is_same' => 'یەکسانە بە',
        'label.is_not_same' => 'یەکسان نییە بە',
        'label.is_after' => 'دواترە لە',
        'label.is_after_or_same' => 'دواترە یان یەکسانە بە',
        'label.is_before' => 'پێشترە لە',
        'label.is_before_or_same' => 'پێشترە یان یەکسانە بە',
    ],

    'form' => [
        'are_you_sure' => 'گۆڕانکارییەکانی ئەم فۆرمەت پاشەکەوت نەکردووە.',
        'tab.error_badge_title' => '{1} یەک خانەی نادروست|[0,Inf[ %count% خانەی نادروست',
        'slug.confirm_text' => 'ئەگەر ناونیشانی کورت (slug) بگۆڕیت، لەوانەیە بەستەرەکانی لاپەڕەکانی تر تێک بشکێنیت.',
    ],

    'user' => [
        'logged_in_as' => 'چوویتە ژوورەوە وەک',
        'unnamed' => 'بەکارهێنەری بێناو',
        'anonymous' => 'بەکارهێنەری نەناسراو',
        'sign_out' => 'چوونەدەرەوە',
        'exit_impersonation' => 'دەرچوون لە دۆخی نوێنەرایەتی',
    ],

    'settings' => [
        'appearance' => [
            'label' => 'ڕووکار',
            'light' => 'ڕووناک',
            'dark' => 'تاریک',
            'auto' => 'خۆکار',
        ],
        'locale' => 'زمان',
    ],

    'login_page' => [
        'username' => 'ناوی بەکارهێنەر',
        'password' => 'وشەی نهێنی',
        'sign_in' => 'چوونەژوورەوە',
        'forgot_password' => 'وشەی نهێنیت لەبیرکردووە؟',
        'remember_me' => 'لەبیرم بگرە',
    ],

    'exception' => [
        'entity_not_found' => 'ئەم بابەتە چیتر بەردەست نییە.',
        'entity_remove' => 'ئەم بابەتە ناسڕدرێتەوە چونکە بابەتی تر پشتی پێ دەبەستن.',
        'forbidden_action' => 'ناتوانرێت کرداری داواکراو لەسەر ئەم بابەتە ئەنجام بدرێت.',
        'insufficient_entity_permission' => 'مۆڵەتی دەستگەیشتنت بەم بابەتە نییە.',
        'general' => 'هەڵەیەک ڕوویدا لە کاتی جێبەجێکردنی داواکارییەکەت.',
        'general_403' => 'مۆڵەتی ئەنجامدانی ئەم کردارەت نییە.',
        'general_404' => 'لاپەڕەی داواکراو نەدۆزرایەوە.',
        'general_500' => 'هەڵەیەکی ناوخۆیی ڕوویدا لە کاتی جێبەجێکردنی داواکارییەکەت.',
    ],

    'file_upload' => [
        'add_file' => 'زیادکردنی فایل',
        'add_files' => 'زیادکردنی فایلەکان',
        'clear_all' => 'پاککردنەوەی هەموو',
    ],

    'autocomplete' => [
        'no-results-found' => 'هیچ ئەنجامێک نەدۆزرایەوە',
        'no-more-results' => 'ئەنجامی زیاتر نییە',
        'loading-more-results' => 'بارکردنی ئەنجامی زیاتر…',
    ],
];
