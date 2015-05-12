Chapter 3. Backend Configuration
================================

EasyAdmin proposes an hybrid approach to customize the backends:

  * Use YAML-based configuration when it's simple to do so;
  * Use PHP classes/methods and Twig templates for more advanced customization.

This chapter focus on the basic configuration options that you can define for
the backend using the YAML configuration file.

Expanded Configuration Format
-----------------------------

The simple backend created in the previous chapter used a compact configuration
like the following:

```yaml
# app/config/config.yml
easy_admin:
    entities:
        - AppBundle\Entity\Customer
        - AppBundle\Entity\Order
        - AppBundle\Entity\Product
```

When you start customizing the backend, instead of the compact configuration
format, you must use the expanded format:

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

This expanded configuration format allows to define lots of attributes for each
entity, as explained the following chapters. Refer to the
[Configuration Reference] [config-reference] tutorial to check out all the
available configuration formats.

Customize the URL Used to Access the Backend
--------------------------------------------

By default, your backend will be accessible at the `/admin` URI of your Symfony
application. This value is defined in the `prefix` option used when loading
the routes of the bundle. You are free to change its value to meet your own
backend requirements:

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

The value of this option is displayed with the `raw` Twig filter. This means
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

By default, main menu items are called after the entities that they represent.
If you want to customize any menu item, define the `label` option of its
associated entity. To do so, use the following alternative configuration
format:

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

Translate the Backend Interface
-------------------------------

The backend uses the same language as the underlying Symfony application, which
is usually configured in the `locale` option of the `app/config/parameters.yml`
file. The current version of EasyAdmin supports tens of languages and we're
actively looking for more translations contributed by the community.

Customize the Translation of the Main Menu Items
------------------------------------------------

In addition to the built-in backend elements, you may need to translate the
names/labels of your entities, because they are displayed in the main menu.
To do so, use translation keys instead of contents in the configuration file:

```yaml
# app/config/config.yml
easy_admin:
    entities:
        Customers: 
            label: app.customers
            class: AppBundle\Entity\Customer
        Orders: 
            label: app.orders
            class: AppBundle\Entity\Order
```

The `app.customers` and `app.orders` values are not the real entity names but
the translation keys. If your application includes a translation file which
defines the value of those keys for the active language, you'll see the main
menu items translated.

Restrict the Access to the Backend
----------------------------------

EasyAdmin doesn't provide any security related feature because it relies on 
the underlying Symfony security features. In case you need it, checkout the
[Security Chapter](http://symfony.com/doc/current/book/security.html) of the
official Symfony documentation to learn how to restrict the access to the
backend section of your application.

When accessing a protected backend, EasyAdmin will display the name of user
who is logged in the application.

[config-reference]: ../tutorials/configuration-reference.md
