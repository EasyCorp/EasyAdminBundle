Chapter 1. General Configuration
================================

EasyAdmin proposes a hybrid approach to customize the backends:

  * Use YAML-based configuration when it's simple to do so;
  * Use PHP methods and Twig templates for more advanced customization.

This chapter explains all the general YAML-based configuration options. The rest
of chapters explain how to do extreme backend customizations using PHP methods
and Twig templates.

Changing the URL Used to Access the Backend
-------------------------------------------

By default, the backend is accessible at the `/admin` URL of your Symfony
application. This value is defined in the `prefix` option when loading the
routes of the bundle. Change its value to meet your own requirements:

```yaml
# app/config/routing.yml
easy_admin_bundle:
    resource: "@EasyAdminBundle/Controller/"
    type:     annotation
    prefix:   /_secret_backend  # <-- change this value

# ...
```

Changing the Name of the Backend
--------------------------------

By default, the backend displays `Easy Admin` as its name. Use the `site_name`
option to change this value:

```yaml
# app/config/config.yml
easy_admin:
    site_name: 'ACME Megacorp.'
    # ...
```

The contents of this option are not escaped before displaying them, so you can
use HTML tags if needed:

```yaml
# app/config/config.yml
easy_admin:
    site_name: 'ACME <em style="font-size: 80%;">Megacorp.</em>'
    # ...
```

This flexibility allows to use an `<img>` HTML tag to display an image-based
logo instead of a text-based logo:

```yaml
# app/config/config.yml
easy_admin:
    site_name: '<img src="http://symfony.com/logos/symfony_white_01.png" />'
    # ...
```

Restricting the Access to the Backend
-------------------------------------

EasyAdmin doesn't provide any security related feature because it relies on the
underlying Symfony security mechanism. Read the [Security Chapter][1] of the
official Symfony documentation to learn how to restrict the access to the
backend section of your application.

When accessing a protected backend, EasyAdmin displays the name of user who is
logged in the application. Otherwise it displays: "Anonymous User".

-------------------------------------------------------------------------------

&larr; [Chapter 2. Your First Backend](2-first-backend.md)  |  [Chapter 4. Views and Actions](4-views-and-actions.md) &rarr;

[1]: http://symfony.com/doc/current/book/security.html)



TODO: ADD THIS

Changing the Backend Index Page -> link to the other chapter


