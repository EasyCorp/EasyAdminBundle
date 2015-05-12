Chapter 5. Design Customization
===============================

The design of the backend can be customized in two ways:

  1. Using some simple configuration options, as explained in this chapter.
  2. Overriding the default templates and fragments used to render the backend,
    which is useful for very complex backends. This is explained in the
    [Advanced Design Customization] [advanced-design-customization] tutorial.

Customizing the Main Backend Color
----------------------------------

By default, backends use a dark orange shade as the main color. Use the
`brand_color` design option to change it:

```yaml
easy_admin:
    design:
        # this backend uses turquoise as its main color
        brand_color: '#1ABC9C'
    # ...
```

This simple configuration option allows you to easily match the backend design
to your project identity:

![Using a custom brand color in the backend](../images/easyadmin-design-brand-color.png)

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

EasyAdmin provides two different color schemes: `dark` and `light`. The `dark`
scheme is the default one because it's the most common choice when designing
backends. The `light` scheme is a cleaner and more minimalist alternative:

```yaml
easy_admin:
    design:
        color_scheme: 'light'
    # ...
```

![The default backend homepage using the light color scheme](../images/easyadmin-design-color-scheme-light.png)

Combine the `light` color scheme with the `brand_color` option to get a myriad
of new backend designs:

![Combining the light color scheme with a colorful palette](../images/easyadmin-design-brand-color-light-theme.png)

Customizing Form Design
-----------------------

By default, forms are displayed using the **horizontal style** defined by the
Bootstrap 3 CSS framework:

![Default horizontal form style](../images/easyadmin-form-horizontal.png)

The style of the forms can be changed application-wide using the `form_theme`
option inside the `design` configuration section. In fact, the default form
style is equivalent to using this configuration:

```yaml
easy_admin:
    design:
        form_theme: 'horizontal'
    # ...
```

If you prefer to display your forms using the **vertical Bootstrap style**,
change the value of this option to `vertical`:

```yaml
easy_admin:
    design:
        form_theme: 'vertical'
    # ...
```

The same form shown previously will now be rendered as follows:

![Vertical form style](../images/easyadmin-form-vertical.png)

The `horizontal` and `vertical` values are just nice shortcuts for the two
built-in form themes. But you can also use your own form themes. Just set the
full theme path as the value of the `form_theme` option:

```yaml
easy_admin:
    design:
        form_theme: '@AppBundle/form/custom_layout.html.twig'
    # ...
```

You can even pass several form themes in an array to use all of them when
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

![Multi-column form](../images/easyadmin-form-multi-column.png)

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

[advanced-design-customization]: ../tutorials/advanced-design-customization.md
