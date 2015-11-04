Configuration Reference
=======================

Simplest Backend Configuration
------------------------------

Useful only for creating backend prototypes in a few seconds:

```yaml
easy_admin:
    entities:
        - AppBundle\Entity\Customer
        - AppBundle\Entity\Product
        # ...
```

Full Backend Configuration
--------------------------

* [easy_admin](#easy_admin)
  * [site_name](#site_name)
  * [formats](#formats)
    * [date](#date)
    * [time](#time)
    * [datetime](#datetime)
    * [number](#number)
  * [disabled_actions](#disabled_actions)
  * [design](#design)
    * [theme](#theme)
    * [color_scheme](#color_scheme)
    * [brand_color](#brand_color)
    * [form_theme](#form_theme)
    * [assets](#assets)
      * [css](#css)
      * [js](#js)
    * [templates](#templates)

### easy_admin

This is the root key for the entire backend configuration. All the other options
are defined under this key.

### site_name

(**default value**: `'Easy Admin'`, **type**: string)

The name displayed as the title of the administration zone (e.g. your company
name, the project name, etc.) This value is displayed in the backend "as is", so
you can include HTML tags and they will be rendered as HTML content (e.g.
`<strong>ACME</strong>`).

### formats

This is the parent key of the four options that configure the formats used to
display dates and numbers.

#### date

(**default value**: `'Y-m-d'`, **type**: string)

The format applied in the `list` and `show` views to display the properties of
type `date`. This format doesn't affect to `time` and `datetime` properties.
The value must be a valid PHP date format according to the syntax options defined
in http://php.net/date

#### time

(**default value**: `'H:i:s'`, **type**: string)

The format applied in the `list` and `show` views to display the properties of
type `time`. This format doesn't affect to `date` and `datetime` properties.
The value must be a valid PHP time format according to the syntax options defined
in http://php.net/date

#### datetime

(**default value**: `'F j, Y H:i'`, **type**: string)

The format applied in the `list` and `show` views to display the properties of
type `datetime`. This format doesn't affect to `date` and `time` properties.
The value must be a valid PHP time format according to the syntax options defined
in http://php.net/date

#### number

(**default value**: none, **type**: string)

The format applied in the `list` and `show` views to display the numeric
properties. The value must be a format according to the syntax options defined
in http://php.net/sprintf

#### disabled_actions

(**default value**: empty array, **type**: array)

The names of the actions disabled for all backend entities. This value can be
overridden in a entity-by-entity basis, so you can disable some actions globally
and then reenable some of them for some entities. Example:

```yaml
easy_admin:
    disabled_actions: ['new', 'edit']
    # ...
```

### design

This is the parent key of the options that configure the options related to the
visual design of the backend.

#### theme

(**default value**: `'default'`, **type**: string)

The name of the theme used to create the backend. The only theme available is
called `default`. This option is in fact a placeholder for future use. You can
safely ignore it.

#### color_scheme

(**default value**: `'dark'`, **type**: string, **values**: `'dark'` or `'light'`)

It defines the colors used in the backend design. If you find the default `dark`
color scheme too dark, try the `light` color scheme.

#### brand_color

(**default value**: `'#E67E22'`, **type**: string, **values**: any valid CSS
expression to define a color)

This is the color used to highlight important elements of the backend, such as
the site name, links and buttons. Use the main color of your company or project
to create a backend that matches your branding perfectly.

This value can be define using any valid CSS expression to define a color, so
you can even use semi-transparent colors.

#### form_theme

(**default value**: `'horizontal'`, **type**: string or array of strings,
**values**: `'horizontal'`, `'vertical'`, any valid form theme template path)

The form theme used to render the form fields in the `edit` and `new` views.
The default `'horizontal'` value is a shortcut of `@EasyAdmin/form/bootstrap_3_horizontal_layout.html.twig`
which displays the form fields using the default horizontal Bootstrap 3 design.

The `'vertical'` value is a shortcut of `@EasyAdmin/form/bootstrap_3_layout.html.twig`
which displays the form fields using the more common vertical Bootstrap 3 design.
This style is better than `'horizontal'` when you want to increase the space
available to edit the property values.

Moreover, you can use your own form theme (or themes) just by defining the path
to those templates.

Example of using one form theme:

```yaml
easy_admin:
    design:
        form_theme: '@App/custom_form_theme.html.twig'
    # ...
```

Example of using several form themes:

```yaml
easy_admin:
    design:
        form_theme: ['@App/custom_form_theme.html.twig', '@Acme/form/global_theme.html.twig']
    # ...
```

#### assets

This is the parent key of the `css` and `js` keys that allow to include any
number of CSS and JavaScript assets in the backend layout.

##### css

(**default value**: empty array, **type**: array, **values**: any valid link
to CSS files)

This option defines the custom CSS file (or files) that are included in the
backend layout after loading the default CSS files. It's useful to link to the
CSS files that customize the design of your backends. The values of this option
are output directly in a `<link>` HTML element, so you can use relative or
absolute links. Example:

```yaml
easy_admin:
    design:
        css: ['/bundles/app/custom_backend.css', 'https://example.com/css/theme.css']
    # ...
```

CSS files are included in the same order as defined. This option cannot be used
to remove the default CSS files loaded by EasyAdmin. To do so, you must override
the `<head>` part of the layout template using a custom template.

##### js

(**default value**: empty array, **type**: array, **values**: any valid link
to JavaScript files)

This option defines the custom JavaScript file (or files) that are included in
the backend layout after loading the default JavaScript files. It's useful to
link to the JavaScript files that customize the behavior of your backends. The
values of this option are output directly in a `<script>` HTML element, so you
can use relative or absolute links. Example:

```yaml
easy_admin:
    design:
        js: ['/bundles/app/custom_widgets.js', 'https://example.com/js/animations.js']
    # ...
```

JavaScript files are included in the same order as defined. This option cannot
be used to remove the default JavaScript files loaded by EasyAdmin. To do so,
you must override the `<head>` part of the layout template using a custom template.

#### templates

(**default value**: none, **type**: strings, **values**: any valid Twig template path)

This option allows to redefine the template used to render each backend element,
from the global layout to the micro-templates used to render each form field type.
For example, to use your own template to display the properties of type `boolean`
redefine the `field_boolean` template:

```yaml
easy_admin:
    design:
        templates:
            field_boolean: '@MyBundle/backend/boolean.html.twig'
    # ...
```

Similarly, to customize the entire backend layout (used to render all pages) just
redefine the `layout` template:

```yaml
easy_admin:
    design:
        templates:
            layout: '@MyBundle/backend/base.html.twig'
    # ...
```

This is the full list of templates that can be redefined:

```yaml
easy_admin:
    design:
        templates:
            # Used to decorate the main templates (list, edit, new and show)
            layout: '...'
            # Used to render the page where entities are edited
            edit: '...'
            # Used to render the listing page and the search results page
            list: '...'
            # Used to render the page where new entities are created
            new: '...'
            # Used to render the contents stored by a given entity
            show: '...'
            # Used to render the form displayed in the new and edit pages
            form: '...'
            # Used to render the notification area were flash messages are displayed
            flash_messages: '...'
            # Used to render the paginator in the list page
            paginator: '...'
            # Used to render array field types
            field_array: '...'
            # Used to render fields that store Doctrine associations
            field_association: '...'
            # Used to render bigint field types
            field_bigint: '...'
            # Used to render boolean field types
            field_boolean: '...'
            # Used to render date field types
            field_date: '...'
            # Used to render datetime field types
            field_datetime: '...'
            # Used to render datetimetz field types
            field_datetimetz: '...'
            # Used to render decimal field types
            field_decimal: '...'
            # Used to render float field types
            field_float: '...'
            # Used to render the field called "id". This avoids formatting its
            # value as any other regular number (with decimals and thousand separators)
            field_id: '...'
            # Used to render image field types (a special type that displays the image contents)
            field_image: '...'
            # Used to render integer field types
            field_integer: '...'
            # Used to render unescaped values
            field_raw: '...'
            # Used to render simple array field types
            field_simple_array: '...'
            # Used to render smallint field types
            field_smallint: '...'
            # Used to render string field types
            field_string: '...'
            # Used to render text field types
            field_text: '...'
            # Used to render time field types
            field_time: '...'
            # Used to render toggle field types (a special type that display
            # booleans as flip switches)
            field_toggle: '...'
            # Used when the field to render is an empty collection
            label_empty: '...'
            # Used when is not possible to access the value of the field
            # to render (there is no getter or public property)
            label_inaccessible: '...'
            # Used when the value of the field to render is null
            label_null: '...'
            # Used when any kind of error or exception happens when trying to
            # access the value of the field to render
            label_undefined: '...'
    # ...
```

The `label_*` and `field_*` templates are only applied in the `list` and `show`
templates. In order to customize the fields of the forms displayed in the `new`
and `edit` views, use the `easy_admin.design.form_theme` option.





```
    list:

        # The list of actions enabled in the "list" view.
        actions:              []

        # The maximum number of items to show on listing and search pages.
        max_results:          15
    edit:

        # The list of actions enabled in the "edit" view.
        actions:              []
    new:

        # The list of actions enabled in the "new" view.
        actions:              []
    show:

        # The list of actions enabled in the "show" view.
        actions:              []

    # The list of entities to manage in the administration zone.
    entities:             []

    # DEPRECATED: use the "actions" option of the "list" view.
    list_actions:         ~

    # DEPRECATED: use "max_results" option under the "list" global key.
    list_max_results:     ~
    assets:

        # DEPRECATED: use the "design -> assets -> css" option.
        css:                  []

        # DEPRECATED: use the "design -> assets -> js" option.
        js:                   []


```

Deprecated Configuration Options
--------------------------------



Advanced Configuration with no Property Configuration
-----------------------------------------------------

This configuration format allows to control which properties, and in which
order, are shown in the views. Just use the `fields` option in the `edit`,
`list`, `new` and `show` views:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            list:
                fields: ['id', 'name', 'email']
        Product:
            label: Inventory
            class: AppBundle\Entity\Product
            list:
                fields: ['id', 'code', 'description', 'price']
            edit:
                fields: ['code', 'description', 'price', 'category']
            new:
                fields: ['code', 'description', 'price', 'category']
```

If the `edit` and `new` configuration is the same, use instead the special
`form` view, which will be applied to both of them:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            list:
                fields: ['id', 'name', 'email']
        Product:
            label: Inventory
            class: AppBundle\Entity\Product
            list:
                fields: ['id', 'code', 'description', 'price']
            form:
                fields: ['code', 'description', 'price', 'category']
```

Advanced Configuration with Custom Property Configuration
---------------------------------------------------------

This is the most advanced configuration format and it allows you to control the
type, style, help message and label displayed for each property:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            list:
                fields: ['id', 'name', { property: 'email', label: 'Contact Info' }]
        Product:
            label: Inventory
            class: AppBundle\Entity\Product
            list:
                fields: ['id', 'code', 'description', 'price']
            form:
                fields:
                    - { property: 'code', help: 'Alphanumeric characters only' }
                    - { property: 'description', type: 'textarea' }
                    - { property: 'price', type: 'number', css_class: 'input-lg' }
                    - { property: 'category', label: 'Commercial Category' }
```

Combining Different Configuration Formats
-----------------------------------------

The previous configuration formats can also be combined. This is useful to use
the default configuration when it's convenient and to customize it when needed:

```yaml
easy_admin:
    entities:
        Customer:  AppBundle\Entity\Customer
        Product:
            label: Inventory
            class: AppBundle\Entity\Product
            list:
                fields: ['id', 'code', 'description', 'price']
            form:
                fields:
                    - { property: 'code', help: 'Alphanumeric characters only' }
                    - { property: 'description', type: 'textarea' }
                    - { property: 'price', type: 'number', css_class: 'input-lg' }
                    - { property: 'category', label: 'Commercial Category' }
```

