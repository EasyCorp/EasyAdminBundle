Chapter 9. Customizing the Visual Design of the Backend
=======================================================

The current version of EasyAdmin doesn't support the concept of themes, but you
can fully customize its design using CSS and JavaScript files. Define the
`assets` option to load your own web assets:

```yaml
easy_admin:
    assets:
        css:
            - 'bundles/app/css/admin1.css'
            - 'bundles/acmedemo/css/admin2.css'
        js:
            - 'bundles/app/js/admin1.js'
            - 'bundles/acmedemo/js/admin2.js'
    # ...
```

EasyAdmin supports any kind of web asset (internal, external, relative and
absolute) and links to them accordingly:

```yaml
easy_admin:
    assets:
        css:
            # HTTP protocol-relative URL
            - '//example.org/css/admin1.css'
            # absolute non-secure URL
            - 'http://example.org/css/admin2.css'
            # absolute secure URL
            - 'https://example.org/css/admin3.css'
            # absolute internal bundle URL
            - '/bundles/acmedemo/css/admin4.css'
            # relative internal bundle URL
            - 'bundles/app/css/admin5.css'
        js:
            # this option works exactly the same as the 'css' option
            - '//example.org/js/admin1.js'
            - 'http://example.org/js/admin2.js'
            - 'https://example.org/js/admin3.js'
            - '/bundles/acmedemo/js/admin4.js'
            - 'bundles/app/js/admin5.js'
    # ...
```

Unloading the Default JavaScript and Stylesheets
------------------------------------------------

Backend templates use Bootstrap CSS and jQuery frameworks to display their
contents. In case you want to unload these files in addition to loading your
own assets, override the value of the `head_stylesheets` and `body_javascripts`
template blocks.

To do so, you'll have to create your own templates and override default ones,
as explained in the next section.

Customize the Templates of the Backend
--------------------------------------

In addition to loading your own stylesheets and scripts, you can also override
the templates used to build the backend interface. To do so, follow the well-
known Symfony bundle [inheritance mechanism](http://symfony.com/doc/current/book/templating.html#overriding-bundle-templates).

The most important templates used by EasyAdmin are the following:

  * `layout.html.twig`, the common layout that warps all backend pages;
  * `new.html.twig`, the template used for the `new` and `search` views;
  * `show.html.twig`, the template used for the `show` view;
  * `edit.html.twig`, the template used for the `edit` view;
  * `list.html.twig`, the template used for the `list` view;
  * `_list_paginator.html.twig`, the template fragment used to display the
    paginator of the `new` and `search` views;
  * `_flashes.html.twig`, the template fragment used to display flash messages
    for any view.

Suppose you want to modify the paginator displayed at the bottom of each
listing. This element is built with the `_list_paginator.html.twig` template,
so you have to create the following new template to override it:

```
your-project/
├─ app/
│  ├─ ...
│  └─ Resources/
│     └─ EasyAdminBundle/
│        └─ views/
│           └─ _list_paginator.html.twig
├─ src/
├─ vendor/
└─ web/
```

Be careful to use those exact folder and file names. If you do, the backend
will use your template instead of the default one. Please note that when
adding a template in a new location, **you may need to clear your cache** (with
the command `php app/console cache:clear`), **even if you are in debug mode**.
