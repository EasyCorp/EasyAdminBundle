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

EasyAdmin doesn't support multi-column form layouts. However, you can use the
`css_class` form field to create these advanced layouts. The `css_class` value
is applied to the parent `<div>` element which contains the field label, the
field widget, the field help and the optional field errors:

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
                    - { property: name, css_class: 'col-sm-12' }
                    - { property: price, type: 'number', help: 'Prices are always in euros', css_class: 'col-sm-6' }
                    - { property: 'ean', label: 'EAN', help: 'EAN 13 valid code. Leave empty if unknown.', css_class: 'col-sm-6' }
                    - { property: 'enabled', css_class: 'col-sm-12' }
                    - { property: 'description', css_class: 'col-sm-12' }
    # ...
```
