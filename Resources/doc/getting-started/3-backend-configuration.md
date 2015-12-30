Chapter 3. Backend Configuration
================================

EasyAdmin proposes a hybrid approach to customize the backends:

  * Use YAML-based configuration when it's simple to do so;
  * Use PHP methods and Twig templates for more advanced customization.

This chapter explains all the YAML-based configuration options. Read the
[Customizing AdminController] [custom-admin-controller] tutorial to learn how
to do extreme backend customizations using PHP methods and Twig templates.

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

Customize the URL Used to Access the Backend
--------------------------------------------

By default, the backend will be accessible at the `/admin` URL of your Symfony
application. This value is defined in the `prefix` option when loading the
routes of the bundle. Change its value to meet your own backend requirements:

```yaml
# app/config/routing.yml
easy_admin_bundle:
    resource: "@EasyAdminBundle/Controller/"
    type:     annotation
    prefix:   /_secret_backend  # <-- change this value

# ...
```

Customize the Name of the Backend
---------------------------------

By default, the backend will display `Easy Admin` as its name. Use the
`site_name` option to change this value:

```yaml
# app/config/config.yml
easy_admin:
    site_name: 'ACME Megacorp.'
    # ...
```

The contents of this option are not escaped before displaying them. This means
that you can use any HTML markup to display the name exactly as you are
required to meet your company or organization needs:

```yaml
# app/config/config.yml
easy_admin:
    site_name: 'ACME <em style="font-size: 80%; color: yellow">Megacorp.</em>'
    # ...
```

If you want to display your logo, use an `<img>` HTML element as the site
name. When using an image, EasyAdmin automatically resizes it to fit the
available width. The following example would show the beautiful Symfony logo as
the name of your backend:

```yaml
# app/config/config.yml
easy_admin:
    site_name: '<img src="http://symfony.com/logos/symfony_white_01.png" />'
    # ...
```

Customize the Order of the Main Menu Items
------------------------------------------

Main menu items are displayed following the same order of the entities defined
in the admin configuration file. So you just have to reorder the list of
entities to reorder the main menu elements.

Customize the Label of the Main Menu Items
------------------------------------------

By default, main menu items display the name of their associated entity. If you
want to customize any menu item, define the `label` option of its related entity:

```yaml
# app/config/config.yml
easy_admin:
    entities:
        Customers:
            label: 'Active Clients'
            class: AppBundle\Entity\Customer
        Orders:
            label: 'Pending Orders'
            class: AppBundle\Entity\Order
```

Restrict the Access to the Backend
----------------------------------

EasyAdmin doesn't provide any security related feature because it relies on
the underlying Symfony security features. In case you need it, checkout the
[Security Chapter](http://symfony.com/doc/current/book/security.html) of the
official Symfony documentation to learn how to restrict the access to the
backend section of your application.

When accessing a protected backend, EasyAdmin will display the name of user
who is logged in the application.

[custom-admin-controller]: ../tutorials/customizing-admin-controller.md
[config-reference]: ../tutorials/configuration-reference.md
