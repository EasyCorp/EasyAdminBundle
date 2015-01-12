EasyAdmin
=========

<img src="https://cloud.githubusercontent.com/assets/73419/5711880/23b3700c-9aae-11e4-997b-cf88b257132e.png"
 alt="EasyAdmin for Symfony Backends" title="EasyAdmin" align="right" />

EasyAdmin lets you create administration backends for Symfony applications
with unprecedented simplicity.

**Features**

  * **CRUD** operations on Doctrine entities (create, edit, list, delete).
  * Full-text **search**, **pagination** and column **sorting**.
  * Fully **responsive** design with four break points.
  * No need to pre-generate code.
  * **Fast**, **simple** and **smart** where appropriate.

**Requirements**

The current version of EasyAdmin is compatible with Symfony 2.3+ applications 
that define their model using Doctrine ORM entities. These entities must 
define a simple primary key called `id` and `many-to-many` associations are 
not supported.

Installation
------------

In order to install EasyAdmin you have to edit two files and execute two 
console commands, as explained in the following steps.

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

-----

**NOTE**: don't execute the following command on your computer because the 
bundle hasn't been released yet. It will be published in the coming days.

-----

```bash
$ composer require javiereguiluz/easyadmin
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 2: Enable the Bundle

Then, enable the bundle by adding the following line in the `app/AppKernel.php`
file of your project:

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
            new JavierEguiluz\EasyAdminBundle\JavierEguiluzEasyAdminBundle(),
        );
    }

    // ...
}
```

### Step 3: Load the Routes of the Bundle

Open your main routing configuration file (usually `app/config/routing.yml`)
and add the following three lines at the very beginning of it:

```yaml
# app/config/routing.yml
easy_admin_bundle:
    resource: "@EasyAdminBundle/Controller/"
    type:     annotation
    prefix:   /admin

# ...
```

### Step 4: Prepare the Web Assets of the Bundle

This bundles includes several CSS, JavaScript and font files used to display 
its interface. In order to add those files to your application, you must 
execute the following command:

```cli
php app/console assets:install --symlink
```

That's it! Now everything is ready to create your first admin backend.

Your First Backend
------------------

In order to create your first backend, you have to edit just one file. The 
entire process will take you around 30 seconds.

Assuming that you already have defined three Doctrine ORM entities called
`Customer`, `Order` and `Product`, open your main application configuration
file (usually `app/config/config.yml`) and add the following configuration:

```yaml
# app/config/config.yml
easy_admin:
    entities:
        - AppBundle\Entity\Customer
        - AppBundle\Entity\Order
        - AppBundle\Entity\Product
```

**Congratulations! You've just created your first fully-featured backend!** 
Access  the `/admin` URL in your Symfony application and you'll get access to 
the admin backend:

![Default listing interface](Resources/doc/images/easyadmin-customer-listing.png)

Unlike most of the usual admin generators, EasyAdmin doesn't generate any code.
All resources are served on-the-fly to ensure an exceptional developer
experience and to instantly apply any configuration change.

Without any further configuration, EasyAdmin guesses the best settings for you 
admin backend. This ensures that all interfaces look "good enough", which may 
be acceptable for simple backends and rapid prototypes. However, most of the
times you may need to customize some parts of the backend. Keep reading to know
how to do it.

Advanced Backends
-----------------

// Coming soon...

How to Collaborate in this Project
----------------------------------

// Coming soon...


-----

EasyAdmin, the missing admin generator for Symfony applications.
