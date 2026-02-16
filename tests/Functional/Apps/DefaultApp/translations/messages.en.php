<?php

return [
    'entities' => [
        EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category::class => [
            'singular' => 'Category (singular from file)',
            'plural' => 'Categories (plural from file)',
            'properties' => [
                'id' => 'ID (from file)',
                'name' => 'Name (from file)',
                'slug' => 'Slug (from file)',
                'active' => 'Active (from file)',
                'activeWithNoPermission' => 'Active No Permission (from file)',
                'activeDisabled' => 'Active Disabled (from file)',
            ],
        ],
    ],
];
