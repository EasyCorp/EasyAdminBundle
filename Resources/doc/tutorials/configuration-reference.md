Configuration Reference
=======================

Depending on the complexity and the customization of your backend, you can use
different configuration formats.

Simple Configuration with No Custom Menu Labels
-----------------------------------------------

This is the simplest configuration and is best used to create a prototype in a
few seconds. Just list the classes of the entities to manage in the backend:

```yaml
easy_admin:
    entities:
        - AppBundle\Entity\Customer
        - AppBundle\Entity\Product
```

Simple Configuration with Custom Entity Names
---------------------------------------------

This configuration format allows to set explicitly the entity names as the
keys of the YAML configuration file. These entity names are used in buttons,
page titles and the main menu labels:

```yaml
easy_admin:
    entities:
        Customer:  AppBundle\Entity\Customer
        Inventory: AppBundle\Entity\Product
```

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

Full configuration reference
----------------------------

```yaml
# app/config/config.yml
easy_admin:

    # The name displayed as the title of the administration zone (e.g. company name, project name).
    site_name:            'Easy Admin'
    formats:

        # The PHP date format applied to "date" field types.
        date:                 Y-m-d # Example: d/m/Y (see http://php.net/manual/en/function.date.php)

        # The PHP time format applied to "time" field types.
        time:                 'H:i:s' # Example: h:i a (see http://php.net/date)

        # The PHP date/time format applied to "datetime" field types.
        datetime:             'F j, Y H:i' # Example: l, F jS Y / h:i (see http://php.net/date)

        # The sprintf-compatible format applied to numeric values.
        number:               ~ # Example: %.2d (see http://php.net/sprintf)

    # The names of the actions disabled for all backend entities.
    disabled_actions:     []
    design:

        # The theme used to render the backend pages. For now this value can only be "default".
        theme:                default

        # The color scheme applied to the backend design (values: "dark" or "light").
        color_scheme:         ~ # One of "dark"; "light"

        # The color used in the backend design to highlight important elements.
        brand_color:          '#E67E22'

        # The form theme applied to backend forms. Allowed values: "horizontal", "vertical" and a custom theme path or array of custom theme paths.
        form_theme:

            # Default:
            - @EasyAdmin/form/bootstrap_3_horizontal_layout.html.twig
        assets:

            # The array of CSS assets to load in all backend pages.
            css:                  []

            # The array of JavaScript assets to load in all backend pages.
            js:                   []

        # The custom templates used to render each backend element.
        templates:

            # Used to decorate the main templates (list, edit, new and show)
            layout:               ~

            # Used to render the page where entities are edited
            edit:                 ~

            # Used to render the listing page and the search results page
            list:                 ~

            # Used to render the page where new entities are created
            new:                  ~

            # Used to render the contents stored by a given entity
            show:                 ~

            # Used to render the form displayed in the new and edit pages
            form:                 ~

            # Used to render the notification area were flash messages are displayed
            flash_messages:       ~

            # Used to render the paginator in the list page
            paginator:            ~

            # Used to render array field types
            field_array:          ~

            # Used to render fields that store Doctrine associations
            field_association:    ~

            # Used to render bigint field types
            field_bigint:         ~

            # Used to render boolean field types
            field_boolean:        ~

            # Used to render date field types
            field_date:           ~

            # Used to render datetime field types
            field_datetime:       ~

            # Used to render datetimetz field types
            field_datetimetz:     ~

            # Used to render decimal field types
            field_decimal:        ~

            # Used to render float field types
            field_float:          ~

            # Used to render the field called "id". This avoids formatting its value as any other regular number (with decimals and thousand separators)
            field_id:             ~

            # Used to render image field types (a special type that displays the image contents)
            field_image:          ~

            # Used to render integer field types
            field_integer:        ~

            # Used to render unescaped values
            field_raw:            ~

            # Used to render simple array field types
            field_simple_array:   ~

            # Used to render smallint field types
            field_smallint:       ~

            # Used to render string field types
            field_string:         ~

            # Used to render text field types
            field_text:           ~

            # Used to render time field types
            field_time:           ~

            # Used to render toggle field types (a special type that display booleans as flip switches)
            field_toggle:         ~

            # Used when the field to render is an empty collection
            label_empty:          ~

            # Used when is not possible to access the value of the field to render (there is no getter or public property)
            label_inaccessible:   ~

            # Used when the value of the field to render is null
            label_null:           ~

            # Used when any kind of error or exception happens when trying to access the value of the field to render
            label_undefined:      ~
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
