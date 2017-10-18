EasyAdmin
=========

[![Tests][1]][2] [![Code Quality][3]][4] [![Code Coverage][5]][6] [![Symfony 2.x and 3.x][7]][8]

EasyAdmin creates administration backends for your Symfony applications with
unprecedented simplicity.

<img src="https://raw.githubusercontent.com/javiereguiluz/EasyAdminBundle/master/doc/images/easyadmin-promo.png" alt="Symfony Backends created with EasyAdmin" align="right" />

* [Installation](#installation)
* [Creating Your First Backend](#your-first-backend)
* [Documentation][9]
* [Demo application](#demo-application)

**Features**

  * **CRUD** operations on Doctrine entities (create, edit, list, delete).
  * Full-text **search**, **pagination** and column **sorting**.
  * Fully **responsive** design (smartphones, tablets and desktops).
  * Supports Symfony 2.x and 3.x.
  * Translated into tens of languages.
  * **Fast**, **simple** and **smart** where appropriate.

**Requirements**

  * Symfony 2.3+ or 3.x applications (Silex not supported).
  * Doctrine ORM entities (Doctrine ODM and Propel not supported).
  * Entities with composite keys or using inheritance are not supported.

Documentation
-------------

[Read the EasyAdminBundle documentation at symfony.com][9].

Demo Application
----------------

[easy-admin-demo](https://github.com/javiereguiluz/easy-admin-demo) is a complete
Symfony application created to showcase EasyAdmin features.

Installation
------------

### Step 1: Download the Bundle

```bash
$ composer require javiereguiluz/easyadmin-bundle
```

This command requires you to have Composer installed globally, as explained
in the [Composer documentation](https://getcomposer.org/doc/00-intro.md).

### Step 2: Enable the Bundle

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
            new EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle(),
        );
    }

    // ...
}
```

### Step 3: Load the Routes of the Bundle

```yaml
# app/config/routing.yml
easy_admin_bundle:
    resource: "@EasyAdminBundle/Controller/"
    type:     annotation
    prefix:   /admin

# ...
```

### Step 4: Prepare the Web Assets of the Bundle

```cli
# Symfony 2
php app/console assets:install --symlink

# Symfony 3
php bin/console assets:install --symlink
```

That's it! Now everything is ready to create your first admin backend.

Your First Backend
------------------

Creating your first backend will take you less than 30 seconds. Let's suppose
that your Symfony application defines three Doctrine ORM entities called
`Product`, `Category` and `User`.

Open the `app/config/config.yml` file and add the following configuration:

```yaml
# app/config/config.yml
easy_admin:
    entities:
        - AppBundle\Entity\Product
        - AppBundle\Entity\Category
        - AppBundle\Entity\User
```

**Congratulations! You've just created your first fully-featured backend!**
Browse the `/admin` URL in your Symfony application and you'll get access to
the admin backend:

![Default EasyAdmin Backend interface](https://raw.githubusercontent.com/javiereguiluz/EasyAdminBundle/master/doc/images/easyadmin-default-backend.png)

Keep reading [the rest of the documentation][9] to learn how to create complex backends.

License
-------

This software is published under the [MIT License](LICENSE.md)

[1]: https://travis-ci.org/javiereguiluz/EasyAdminBundle.svg?branch=master
[2]: https://travis-ci.org/javiereguiluz/EasyAdminBundle
[3]: https://insight.sensiolabs.com/projects/a3bfb8d9-7b2d-47ab-a95f-382af395bd51/mini.png
[4]: https://insight.sensiolabs.com/projects/a3bfb8d9-7b2d-47ab-a95f-382af395bd51
[5]: https://coveralls.io/repos/javiereguiluz/EasyAdminBundle/badge.svg?branch=master
[6]: https://coveralls.io/r/javiereguiluz/EasyAdminBundle?branch=master
[7]: https://img.shields.io/badge/Symfony-%202.x%20and%203.x-green.svg
[8]: https://symfony.com/
[9]: http://symfony.com/doc/current/bundles/EasyAdminBundle
