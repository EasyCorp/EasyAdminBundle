Chapter 1. Installation
=======================

EasyAdmin installation requires you to edit two files and execute two console
commands, as explained in the following steps.

Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require javiereguiluz/easyadmin-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding the following line in the `app/AppKernel.php`
file of your Symfony application:

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

Open your main routing configuration file (usually `app/config/routing.yml`)
and copy the following four lines at the very beginning of it:

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

This bundles includes several CSS, JavaScript and font files which are used in
the backend interface, Execute the following command to make those assets
available in your Symfony application:

```cli
php app/console assets:install --symlink
```

That's it! Now everything is ready to create your first admin backend.
