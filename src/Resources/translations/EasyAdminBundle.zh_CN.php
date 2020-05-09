<?php

return [
    'page_title' => [
        // 'dashboard' => '',
        'detail' => '%entity_label_singular% <small>(#%entity_short_id%)</small>',
        'edit' => '编辑 %entity_label_singular% <small>(#%entity_short_id%)</small>',
        'index' => '%entity_label_plural%',
        'new' => '新增 %entity_label_singular%',
        'exception' => '错误|错误',
    ],

    'datagrid' => [
        // 'hidden_results' => '',
        'no_results' => '没有找到结果.',
    ],

    'paginator' => [
        'first' => '首页',
        'previous' => '上一页',
        'next' => '下一页',
        'last' => '尾页',
        'counter' => '<strong>%start%</strong> - <strong>%end%</strong> of <strong>%results%</strong>',
        // 'results' => '',
    ],

    'label' => [
        'true' => '可用',
        'false' => '不可用',
        'empty' => '空',
        'null' => '未赋值',
        'nullable_field' => '置空',
        'object' => 'PHP对象',
        'inaccessible' => '无法获取',
        'inaccessible.explanation' => '该字段的Getter方法缺失或该字段不是公共属性',
        'form.empty_value' => '空',
    ],

    'field' => [
        // 'code_editor.view_code' => '',
        // 'text_editor.view_content' => '',
    ],

    'action' => [
        'entity_actions' => '操作',
        'new' => '新增 %entity_label_singular%',
        'search' => '搜索',
        'detail' => '展示',
        'edit' => '编辑',
        'delete' => '删除',
        'cancel' => '取消',
        'index' => '返回列表',
        // 'deselect' => '',
        'add_new_item' => '添加一项',
        'remove_item' => '删除一项',
        'choose_file' => '选择文件',
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
        'title' => '是否删除',
        'content' => '是否删除，该操作不可恢复',
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
        'are_you_sure' => '该表单的更改还未保存',
        // 'tab.error_badge_title' => '',
    ],

    'user' => [
        'logged_in_as' => '当前登录用户',
        'unnamed' => '未命名用户',
        'anonymous' => '匿名用户',
        'sign_out' => '退出',
        // 'exit_impersonation' => '',
    ],

    'login_page' => [
        'username' => '用户名',
        'password' => '密码',
        'sign_in' => '登录',
    ],

    'exception' => [
        'entity_not_found' => '当前记录不可用',
        'entity_remove' => '该条记录不可删除，因为有其他记录依赖该条记录。',
        'forbidden_action' => '无权执行该操作',
        // 'insufficient_entity_permission' => 'You don't have permission to access this item.',
    ],
];
