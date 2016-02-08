Chapter 1. Installation
=======================

Installing EasyAdmin requires you to edit two files and execute two console
commands:

Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require javiereguiluz/easyadmin-bundle
```

This command requires you to have Composer installed globally, as explained
in the [Composer documentation](https://getcomposer.org/doc/00-intro.md).

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding it to the list of registered bundles in the
`app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new JavierEguiluz\Bundle\EasyAdminBundle\EasyAdminBundle(),
        );
    }

    // ...
}
```

Step 3: Load the Routes of the Bundle
-------------------------------------

Load the routes of the bundle by adding this configuration at the very beginning
of the `app/config/routing.yml` file:

```yaml
# app/config/routing.yml
easy_admin_bundle:
    resource: "@EasyAdminBundle/Controller/"
    type:     annotation
    prefix:   /admin

# ...
```

Step 4: Prepare the Web Assets of the Bundle
--------------------------------------------

This bundle uses several CSS, JavaScript and font files to create the backend
interfaces. Execute the following command to make those assets available in your
Symfony application:

```cli
php app/console assets:install --symlink
```

** If you are using Symfony 3.x you should use this command:
```cli
php app/bin assets:install --symlink
```


That's it! Now everything is ready to create your first admin backend.

-------------------------------------------------------------------------------

[Chapter 2. Your First Backend](2-first-backend.md) &rarr;
