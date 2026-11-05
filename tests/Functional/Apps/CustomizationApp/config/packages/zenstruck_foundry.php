<?php

$container->loadFromExtension('zenstruck_foundry', [
    // tests don't rely on auto-refresh because it's only available on PHP 8.4+
    'enable_auto_refresh_with_lazy_objects' => false,
    'orm' => [
        // the default of zenstruck/foundry 2.x, which 3.0 changes to 'auto'
        'reset' => [
            'mode' => 'schema',
        ],
    ],
    'persistence' => [
        'flush_once' => true,
    ],
]);
