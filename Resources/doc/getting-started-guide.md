

Expanded Configuration Format
-----------------------------

The simple backend created in the previous chapter used the following compact
configuration syntax:

```yaml
# app/config/config.yml
easy_admin:
    entities:
        - AppBundle\Entity\Customer
        - AppBundle\Entity\Order
        - AppBundle\Entity\Product
```

In order to customize the backend, you must use the extended configuration
syntax instead:

```yaml
# app/config/config.yml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
        Order:
            class: AppBundle\Entity\Order
        Product:
            class: AppBundle\Entity\Product
```

The extended configuration syntax allows to configure lots of options for each
entity. Entities are configured as elements under the `entities` key. The name
of the entities are used as the YAML keys. These names must be unique in the
backend and it's recommended to use the CamelCase syntax (e.g. `BlogPost` and
not `blog_post` or `blogPost`).

Refer to the [Configuration Reference] [config-reference] for the full details
of the configuration syntax.
