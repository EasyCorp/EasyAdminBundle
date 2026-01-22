Translations
============

EasyAdmin allows to set labels for menu items, fields, etc. If you omit
the label or set it to ``null`` EasyAdmin will try to auto-generate a
label in various places. For entities and their properties you can add
project-wide translations which will be used by default:

    // translations/EasyAdminBundle.en.php
    return [
        'entities' => [
            App\Entity\Developer::class => [
                'singular' => 'Dev',
                'plural' => 'Devs',
                'properties' => [
                    'name' => 'Name :)'
                    'favouriteProject' => 'Favorite project :)',
                ],
            ],
            App\Entity\DeveloperCategory::class => [
                'singular' => 'Category',
                'plural' => 'Categories',
            ],
        ],
    ];
