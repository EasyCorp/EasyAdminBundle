Chapter 11. Configuration Reference
===================================

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

Simple Configuration with Custom Menu Labels
--------------------------------------------

This configuration format allows to set the labels displayed in the main menu
of the backend. Just list the entities but use a text-based key for each
entity:

```yaml
easy_admin:
    entities:
        Customer:  AppBundle\Entity\Customer
        Inventory: AppBundle\Entity\Product
```

Advanced Configuration with no Field Configuration
--------------------------------------------------

This configuration format allows to control which fields, and in which order,
are shown in the listings and in the forms. Just use the `list`, `edit` and
`new` options and define the fields to display in the `fields` option:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            list:
                fields: ['id', 'name', 'email']
        Inventory:
            class: AppBundle\Entity\Product
            list:
                fields: ['id', 'code', 'description', 'price']
            edit:
                fields: ['code', 'description', 'price', 'category']
            new:
                fields: ['code', 'description', 'price', 'category']
```

If the `edit` and `new` configuration is the same, use instead the special
`form` option, which will be applied to both of them:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            list:
                fields: ['id', 'name', 'email']
        Inventory:
            class: AppBundle\Entity\Product
            list:
                fields: ['id', 'code', 'description', 'price']
            form:
                fields: ['code', 'description', 'price', 'category']
```

Advanced Configuration with Custom Field Configuration
------------------------------------------------------

This is the most advanced configuration format and it allows you to control the
type, style, help message and label displayed for each field. Customize any
field just by replacing its name with a hash with its properties:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            list:
                fields: ['id', 'name', { property: 'email', label: 'Contact Info' }]
        Inventory:
            class: AppBundle\Entity\Product
            list:
                fields: ['id', 'code', 'description', 'price']
            form:
                fields:
                    - { property: 'code', help: 'Alphanumeric characters only' }
                    - { property: 'description', type: 'textarea' }
                    - { property: 'price', type: 'number', class: 'input-lg' }
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
        Inventory:
            class: AppBundle\Entity\Product
            list:
                fields: ['id', 'code', 'description', 'price']
            form:
                fields:
                    - { property: 'code', help: 'Alphanumeric characters only' }
                    - { property: 'description', type: 'textarea' }
                    - { property: 'price', type: 'number', class: 'input-lg' }
                    - { property: 'category', label: 'Commercial Category' }
```
