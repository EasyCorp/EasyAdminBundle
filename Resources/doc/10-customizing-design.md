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
        # this backend uses turquoise as its main color
        brand_color: '#1ABC9C'
    # ...
```

This simple configuration option allows you to easily match the backend design
to your project identity:

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

Combine the `light` color scheme with the `brand_color` option to get a myriad
of new backend designs:

![Combining the light color scheme with a colorful palette](images/easyadmin-design-brand-color-light-theme.png)

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
the templates used to build the backend interface. To do so, EasyAdmin defines
an advanced but simple to use overriding mechanism.

First, these are the names of the main templates which can be overridden:

  * `layout`, the common layout that decorates the rest of templates (`list`,
    `edit`, `new` and `show`);
  * `new`, the template used to render the page where new entities are created;
  * `show`, the template used to render the contents stored by a given entity;
  * `edit`, the template used to render the page where entities are edited;
  * `list`, the template used to render the listing page and the search
    results page;
  * `paginator`, the template used to render the paginator of the `list` and
    `search` views;
  * `form`, the template to render the form displayed in the `new` and `edit`
    pages.

EasyAdmin applies the following overriding mechanism to select the template
used to render each element (from highest to lowest priority):

  1. `easy_admin.entities.<entityName>.templates.<templateName>` configuration 
     option.
  2. `easy_admin.design.templates.<template_name>` configuration option.
  3. `app/Resources/views/easy_admin/<entiy_name>/<template_name>.html.twig`
  4. `app/Resources/views/easy_admin/<template_name>.html.twig`
  5. `@EasyAdmin/default/<template_name>` (these are the templates defined by
     EasyAdmin and they are always available).

### Tweaking the default templates for all entities

Most often than not, customizing the design of the backend is a matter of
tweaking some element of the default templates. The easiest way to do that is
to create a new template that extends from the default one and override the
specific template block you want to customize.

Suppose you want to change the search form displayed in the `list` view.
First, create a new template in `app/Resources/views/easy_admin/list.html.twig`
and make it extend from the default `list` template. Then, override the
`search_action` block:

```twig
{# app/Resources/views/easy_admin/list.html.twig #}
{% extends @EasyAdmin/default/list.html.twig %}

{% block search_action %}
    {# ... #}
{% endblock %}
```

If you prefer to use one of your existing templates located elsewhere, add the
``easy_admin.design.templates.list` option and use any of the valid Symfony
formats to define the template path:

```yaml
easy_admin:
    design:
        templates:
            list: 'AppBundle:Backend:list.html.twig'
```

### Tweaking the default templates for some entities

Follow the steps explained in the previous section, but change the location of
the template to a subdirectory called after the entity. Suppose you want to
override the search form just for the `Customer` entity. Then, create this
template:

```
your-project/
├─ app/
│  ├─ ...
│  └─ Resources/
│     └─ views/
│        └─ easy_admin/
│           └─ Customer/
│              └─ list.html.twig
├─ src/
├─ vendor/
└─ web/
```

Again, you can use an existing template located elsewhere via the ``templates``
configuration option. This time you have to define the option just for the
specific entity:

```yaml
easy_admin:
    entities:
        Customer:
            # ...
            templates:
                list: 'AppBundle:Backend:list.html.twig'
```

### Overriding the default templates for all entities

Suppose you want to modify the paginator displayed at the bottom of each
listing page. This element is built with the `paginator` template, so you have
to create the following template to override it for all entities:

```
your-project/
├─ app/
│  ├─ ...
│  └─ Resources/
│     └─ views/
│        └─ easy_admin/
│           └─ paginator.html.twig
├─ src/
├─ vendor/
└─ web/
```

If you prefer to use one of your existing templates to override it, add the
``easy_admin.design.templates.paginator` option and use any of the valid
Symfony formats to define the template path:

```yaml
easy_admin:
    design:
        templates:
            paginator: 'AppBundle:Default:fragments/_paginator.html.twig'
```

### Overriding the default templates for some entities

Follow the steps explained in the previous section, but change the location of
the template to a subdirectory called after the entity. Suppose you want to
override the paginator just for the `Customer` entity. Then, create this
template:

```
your-project/
├─ app/
│  ├─ ...
│  └─ Resources/
│     └─ views/
│        └─ easy_admin/
│           └─ Customer/
│              └─ paginator.html.twig
├─ src/
├─ vendor/
└─ web/
```

Again, you can use an existing template located elsewhere via the ``templates``
configuration option. This time you have to define the option just for the
specific entity:

```yaml
easy_admin:
    entities:
        Customer:
            # ...
            templates:
                paginator: 'AppBundle:Default:fragments/_paginator.html.twig'
```

Customize the Templates Used to Render Each Field
-------------------------------------------------

You can use the same overriding mechanism to customize the template fragments
used to render each field type. These are the available templates (most of
them correspond to the associated Doctrine data type):

  * `field_array`
  * `field_association`, used to render fields that store Doctrine associations
  * `field_bigint`
  * `field_boolean`
  * `field_date`
  * `field_datetime`
  * `field_datetimetz`
  * `field_decimal`
  * `field_float`
  * `field_id`, used to render the field called `id`. This avoids formatting 
    its value as any other regular number with decimals and thousand separators
  * `field_image`, special type used to display the contents of an image
  * `field_integer`
  * `field_simple_array`
  * `field_smallint`
  * `field_string`
  * `field_text`
  * `field_time`
  * `field_toggle`, special type used to display boolean values as flip 
    switches
  * `label_empty`, used when the field to render is an empty collection
  * `label_inaccessible, used when is not possible to access the value of the
    field to render because there is no getter or public property
  * `label_null`, used when the value of the field to render is null
  * `label_undefined`, used when any kind of error or exception happens when
    trying to access the value of the field to render

Suppose that in you backend you don't want to display a `NULL` badge for null
values and prefer to display a more human friendly value, such as a dash (`-`).
The easiest way to override this template, would be to create a new
`label_null` template with your own contents:

```twig
{# app/Resources/views/easy_admin/label_null.html.twig #}
<span class="null">~</span>
```

To override this value just for a specific entity (for example, `Invoice`), 
create this other template:

```twig
{# app/Resources/views/easy_admin/Invoice/label_null.html.twig #}
<span class="null">Unpaid</span>
```

To use your own custom template, define the following configuration option:

```yaml
easy_admin:
    design:
        templates:
            label_null: 'AppBundle:Default:labels/null.html.twig'
```

If you want to customize any of these templates, it's recommended to check out
first the contents of the default `field_*` and `label_*` templates, so you can
learn about their features.

Inside the `field_*` and `label_*` templates you can use any of the following
variables:

  * `view`, the name of the view where the field is being rendered (`show` or
    `list`).
  * `value`, the content of the field being rendered, which can be a variable
    of any time (String, numeric, boolean, array, etc.)
  * `format`, available only for the date and numeric field types. It defines
    the formatting that should be applied to the value before displaying it.
