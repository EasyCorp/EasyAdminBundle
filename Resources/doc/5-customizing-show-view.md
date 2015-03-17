Chapter 5. Customizing the Show View
====================================

Customize which Fields are Displayed
------------------------------------

By default, the `show` view displays all the entity fields and their values.
Use the `fields` option under the `show` key to restrict the fields to display:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            show:
                fields: ['id', 'firstName', 'secondName', 'phone', 'email']
    # ...
```

Customize the Title of the Page
-------------------------------

By default, the title of the `show` page displays the name of the entity and
the value of the primary key field. Define the `title` option to set a custom
page title:

```yaml
# app/config/config.yml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            show:
                title: 'Customer details'
        # ...
```

The `title` option can include any of the following two variables:

  * `%entity_name%`, resolves to the class name of the current entity (e.g.
    `Customer`, `Product`, `User`, etc.)
  * `%entity_id%`, resolves to the value of the primary key of the entity being
    displayed. Even if the option is called `entity_id`, it also works for
    primary keys with names different from `id`.

Beware that, in Symfony applications, YAML values enclosed with `%` and `%`
have a special meaning. Use two consecutive `%` characters to avoid any issue:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            show:
                title: 'Customer %%entity_id%% details'
        # ...
```

Customize the Order of the Fields
---------------------------------

By default, the `show` view displays the entity properties in the same order
as they were defined in the associated entity class. You could customize the
`show` view contents just by reordering the entity properties, but it's more
convenient to just define the order using the `fields` option of the `show`
option:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            show:
                fields: ['id', 'phone', 'email', 'firstName', 'secondName']
    # ...
```

Customize the Labels of the Values
----------------------------------

By default, column labels are a "humanized" version of the related Doctrine
entity property name. If your property is called `published`, the column label
will be `Published` and if your property is called `dateOfBirth`, the column
label will be `Date of birth`.

In case you want to define a custom label for one or all properties, just use
the following expanded field configuration:

```yaml
# app/config/config.yml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            show:
                fields: ['id', 'name', { property: 'email', label: 'Contact' }]
    # ...
```

Instead of using a string to define the name of the property (e.g. `email`) you
have to define a hash with the name of the property (`property: 'email'`) and
the custom label you want to display (`label: 'Contact'`).

### Translate Labels

Read *"Translate Column Labels"* section of the [chapter 4](4-customizing-list-view.md).

Display Images Field Types
--------------------------

If some field stores the URL of an image, you can show the actual image
instead of its URL. Just set the type of the field to `image`:

```yaml
easy_admin:
    entities:
        Product:
            class: AppBundle\Entity\Product
            show:
                fields:
                    - { property: 'photo', format: 'image' }
                    # ...
    # ...
```

Refer to the *Display Images Field Types* section of the
[Customizing the List View](4-customizing-list-view.md) chapter to know how
to define the base path for images stored as relative URLs.

Customize Fields Appearance
---------------------------

By default, all fields are displayed using the most appropriate format
according to their Doctrine type. Use the `type` option to explicitly set how
the field should be displayed:

```yaml
easy_admin:
    entities:
        Product:
            class: AppBundle\Entity\Product
            show:
                fields:
                    - { property: '...', type: '...' }
                    # ...
    # ...
```

These are the supported types:

  * All the Doctrine data types:
    * Dates: `date`, `datetime`, `datetimetz`, `time`
    * Logical: `boolean`
    * Arrays: `array`, `simple_array`
    * Text: `string`, `text`
    * Numeric: `bigint`, `integer`, `smallint`, `decimal`, `float`
  * `image`, custom type defined by EasyAdmin which displays images inlined in
    the entity show page. Read the previous sections for more details.
