Chapter 10. Customizing the Visual Design of the Backend
========================================================

The current version of EasyAdmin doesn't support the concept of themes.
However, you can customize lots of options related to the visual design of your
backend. All these options are defined under the global `design` option:

```yaml
easy_admin:
    design:
        # ...
```

Changing the Backend Theme
--------------------------

The current version of EasyAdmin doesn't allow to select the theme used to
render the backend pages. In future versions of the bundle, you'll be able to
change the default theme using the `theme` option. For now, the value of this
option can only be `default`:

```yaml
easy_admin:
    design:
        theme: 'default'
        # ...
```

Customizing the Main Backend Color
----------------------------------

The default backend visual design uses a dark orange shade as the main color.
Use the `brand_color` design option to change it:

```yaml
easy_admin:
    design:
        brand_color: '#1ABC9C'
    # ...
```

Applying the above configuration, the backend will now display a beautiful
turquoise shade:

![Using a custom brand color in the backend](images/easyadmin-design-brand-color.png)

The value of the `brand_color` option is directly passed to the `color` and
`background-color` CSS properties, so you can define the color using any valid
CSS format:

```yaml
easy_admin:
    design:
        brand_color: 'rgb(26, 188, 156)'
    # ...
```

This flexibility allows you to use semi-transparent colors:

```yaml
easy_admin:
    design:
        brand_color: 'rgba(26, 188, 156, 0.85)'
    # ...
```

Selecting the Color Scheme
--------------------------

The default theme provides two different color schemes: `dark` and `light`. The
`dark` scheme is the default one because it's the most common alternative when
designing backends. The `light` scheme is a cleaner and much more white design
alternative:

```yaml
easy_admin:
    design:
        color_scheme: 'light'
    # ...
```

![The default backend homepage using the light color scheme](images/easyadmin-design-color-scheme-light.png)

Customizing Form Design
-----------------------

By default, forms are displayed using the **horizontal style** defined by the
Bootstrap 3 CSS framework:

![Default horizontal form style](images/easyadmin-form-horizontal.png)

The style of the forms can be changed application-wide using the `form_theme`
option inside the `design` configuration section. In fact, the default form
style is equivalent to using this configuration:

```yaml
easy_admin:
    design:
        form_theme: 'horizontal'
    # ...
```

If you prefer to display your forms using the **vertical style** defined by
Bootstrap, change the value of this option to `vertical`:

```yaml
easy_admin:
    design:
        form_theme: 'vertical'
    # ...
```

The same form shown previously will now be rendered as follows:

![Vertical form style](images/easyadmin-form-vertical.png)

The `horizontal` and `vertical` values are just nice shortcuts to use any of
the two built-in form themes. But you can also use your own form themes. Just
set the full theme path as the value of the `form_theme` option:

```yaml
easy_admin:
    design:
        form_theme: '@AppBundle/form/custom_layout.html.twig'
    # ...
```

You can even pass several form themes paths in an array to use all of them when
rendering the backend forms:

```yaml
easy_admin:
    design:
        form_theme:
            - '@AppBundle/form/custom_layout.html.twig'
            - 'form_div_layout.html.twig'
    # ...
```

### Multiple-Column Forms

EasyAdmin doesn't provide any mechanism to create multi-column form layouts.
However, you can use the `class` form field to create these advanced layouts.
The `class` value is applied to the parent `<div>` element which contains the
field label, the field widget, the field help and the optional field errors:

![Multi-column form](images/easyadmin-form-multi-column.png)

The configuration used to display this form is the following:

 ```yaml
easy_admin:
    design:
        form_theme: 'vertical'
    entities:
        Product:
            # ...
            form:
                fields:
                    - { property: name, class: 'col-sm-12' }
                    - { property: price, type: 'number', help: 'Prices are always in euros', class: 'col-sm-6' }
                    - { property: 'ean', label: 'EAN', help: 'EAN 13 valid code. Leave empty if unknown.', class: 'col-sm-6' }
                    - { property: 'enabled', class: 'col-sm-12' }
                    - { property: 'description', class: 'col-sm-12' }
    # ...
```

Adding Custom Web Assets
------------------------

Use the `assets` option to define the web assets (CSS and JavaScript files)
that should be loaded in the backend layout:

```yaml
easy_admin:
    design:
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
    design:
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
