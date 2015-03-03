Chapter 5. Customizing the Show Action
======================================

Customize which Fields are Displayed
------------------------------------

By default, the `show` action displays all the entity fields and their
values. Use the `fields` option under the `show` key to restrict the fields to
display:

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

Customize the Order of the Fields
---------------------------------

By default, the `show` action displays the entity properties in the same order
as they were defined in the associated entity class. You could customize the
`show` action contents just by reordering the entity properties, but it's more
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
[Customizing the List Action](4-customizing-list-action.md) chapter to know how
to define the base path for images stored as relative URLs.
