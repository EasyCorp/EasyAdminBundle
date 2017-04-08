EasyAdmin
=========

[![Tests][1]][2] [![Code Quality][3]][4] [![Code Coverage][5]][6] [![Symfony 2.x and 3.x][7]][8]

EasyAdmin creates administration backends for your Symfony applications with
unprecedented simplicity.

<img src="https://raw.githubusercontent.com/javiereguiluz/EasyAdminBundle/master/Resources/doc/images/easyadmin-promo.png" alt="Symfony Backends created with EasyAdmin" align="right" />

* [Installation](#installation)
* [Creating Your First Backend](#your-first-backend)
* [Documentation](#documentation)
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

#### The Book

  * [Chapter 0 - Installation and your first backend](Resources/doc/book/0-installation-and-first-backend.md)
  * [Chapter 1 - Basic configuration](Resources/doc/book/1-basic-configuration.md)
  * [Chapter 2 - Design configuration](Resources/doc/book/2-design-configuration.md)
  * [Chapter 3 - `list`, `search` and `show` views configuration](Resources/doc/book/3-list-search-show-configuration.md)
  * [Chapter 4 - `edit` and `new` views configuration](Resources/doc/book/4-edit-new-configuration.md)
  * [Chapter 5 - Actions configuration](Resources/doc/book/5-actions-configuration.md)
  * [Chapter 6 - Menu configuration](Resources/doc/book/6-menu-configuration.md)
  * [Chapter 7 - Creating complex and dynamic backends](Resources/doc/book/7-complex-dynamic-backends.md)
  * [Chapter 8 - About this project](Resources/doc/book/8-about.md)
  * [Appendix - Full configuration reference](Resources/doc/book/configuration-reference.md)

#### Tutorials

  * [How to translate the backend](Resources/doc/tutorials/i18n.md)
  * [How to define custom actions](Resources/doc/tutorials/custom-actions.md)
  * [How to define custom options for entity properties](Resources/doc/tutorials/custom-property-options.md)
  * [How to manage configuration for complex backends](Resources/doc/tutorials/complex-backend-config.md)
  * [Tips and tricks](Resources/doc/tutorials/tips-and-tricks.md)

#### Integrations with third-party bundles/services

  * [How to upload files and images with VichUploaderBundle](Resources/doc/tutorials/upload-files-and-images.md)
  * [How to integrate FOSUserBundle to manage users](Resources/doc/tutorials/fosuserbundle-integration.md)
  * [How to use a WYSIWYG editor with IvoryCKEditorBundle](Resources/doc/tutorials/wysiwyg-editor.md)
  * [How To integrate FOSRestBundle and EasyAdmin](Resources/doc/tutorials/fosrestbundle-integration.md)

> **❮ NOTE ❯** you are reading the documentation of the bundle's **development**
> version. You can also [read the documentation of the latest stable version ➜](https://github.com/javiereguiluz/EasyAdminBundle/tree/v1.16.8/).

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
            new JavierEguiluz\Bundle\EasyAdminBundle\EasyAdminBundle(),
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

![Default EasyAdmin Backend interface](https://raw.githubusercontent.com/javiereguiluz/EasyAdminBundle/master/Resources/doc/images/easyadmin-default-backend.png)

Keep reading the rest of the documentation to learn how to create complex backends.

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
