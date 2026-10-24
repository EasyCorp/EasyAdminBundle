<?php

$container->loadFromExtension('zenstruck_foundry', [
    // tests don't rely on auto-refresh because it's only available on PHP 8.4+
    'enable_auto_refresh_with_lazy_objects' => false,
    'persistence' => [
        'flush_once' => true,
    ],
]);
