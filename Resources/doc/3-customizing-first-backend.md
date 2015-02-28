Chapter 3. Customizing your First Backend
=========================================

EasyAdmin proposes an hybrid approach to customize the backends:

  * Use basic YAML-based configuration when it's simple to do so;
  * Use PHP classes and methods for more complex features.

This approach ensures a smooth developer experience and balances simplicity
and extensibility.

Customize the URL Prefix Used to Access the Backend
---------------------------------------------------

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

Companies and organizations needs can be so different, that the contents of
this option are not restricted. In fact, the contents are displayed with
the `raw` Twig filter. This means that you can use any HTML markup to display
the name exactly as you are required:

```yaml
# app/config/config.yml
easy_admin:
    site_name: 'ACME <em style="font-size: 80%; color: yellow">Megacorp.</em>'
    # ...
```

If you want to display your logo, use an `<img>` HTML element as the site
name. When using an image, EasyAdmin automatically resizes it to fit the
backend width. The following example would show the beautiful Symfony logo as
the name of your backend:

```yaml
# app/config/config.yml
easy_admin:
    site_name: '<img src="http://symfony.com/logos/symfony_white_01.png" />'
    # ...
```

Customize the Order of the Main Menu Items
------------------------------------------

Main menu items follow the same order of the entities defined in the admin
configuration file. So you just have to reorder the list of entities to
reorder the main menu elements.

Customize the Label of the Main Menu Items
------------------------------------------

By default, main menu items are called after the entities that they represent.
Use this alternative configuration format to define a custom label for each
menu item:

```yaml
# app/config/config.yml
easy_admin:
    entities:
        Clients: AppBundle\Entity\Customer
        Orders: AppBundle\Entity\Order
        Inventory: AppBundle\Entity\Product
```

The keys defined under the `entities` key (in this case, `Clients`, `Orders`
and `Inventory`) will be used as the labels of the main menu items. If the
keys include white spaces or any reserved YAML character, enclose them with
quotes:

```yaml
# app/config/config.yml
easy_admin:
    entities:
        'Active Clients': AppBundle\Entity\Customer
        'Pending Orders': AppBundle\Entity\Order
        'Inventory (2015)': AppBundle\Entity\Product
```

Customize the Translation of the Backend Interface
--------------------------------------------------

The interface of the backend is pretty minimal, but it includes several text
contents in the form of buttons, titles and links. These contents are displayed
in the same language as the default locale configured in the Symfony
application. The `locale` option value is usually configured in the
`app/config/parameters.yml` file.

The current version of EasyAdmin is translated into tens of languages and
we're actively looking for more translations contributed by the community.

Customize the Security of the Backend Interface
-----------------------------------------------

EasyAdmin relies on the built-in Symfony security features to restrict the
access to the backend. In case you need it, checkout the
[Security Chapter](http://symfony.com/doc/current/book/security.html) of the
official Symfony documentation to learn how to restrict the access to the
backend section of your application.

In addition, when accessing a protected backend, EasyAdmin will display the
name of user who is logged in the application.
