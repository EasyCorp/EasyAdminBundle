**❮ NOTE ❯** This bundle releases new versions on a regular basis. Make sure
to update your dependencies frequently to get the latest version.
[Check out the changelog](https://github.com/javiereguiluz/EasyAdminBundle/releases)
to learn about the new features and read the [UPGRADE guide](https://github.com/javiereguiluz/EasyAdminBundle/blob/master/UPGRADE.md).

-----

EasyAdmin
=========

[![Build Status](https://travis-ci.org/javiereguiluz/EasyAdminBundle.svg?branch=master)](https://travis-ci.org/javiereguiluz/EasyAdminBundle)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a3bfb8d9-7b2d-47ab-a95f-382af395bd51/mini.png)](https://insight.sensiolabs.com/projects/a3bfb8d9-7b2d-47ab-a95f-382af395bd51)
[![Coverage Status](https://coveralls.io/repos/javiereguiluz/EasyAdminBundle/badge.svg?branch=master)](https://coveralls.io/r/javiereguiluz/EasyAdminBundle?branch=master)
<sup><kbd>**SUPPORTS SYMFONY 2.x and 3.x**</kbd></sup>

<img src="https://raw.githubusercontent.com/javiereguiluz/EasyAdminBundle/master/Resources/doc/images/easyadmin-promo.png" alt="Symfony Backends created with EasyAdmin" align="right" />

EasyAdmin lets you create administration backends for Symfony applications
with unprecedented simplicity.

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

#### Getting Started Guide

The [Getting Started Guide](Resources/doc/getting-started.md) explains how to
install the bundle, how to create your first backend and provides a quick
overview of the bundle's features. This guide is a must-read before using
EasyAdmin.

#### The Book

  * [Chapter 1 - General Configuration](Resources/doc/book/1-general-configuration.md)
  * [Chapter 2 - Design Configuration](Resources/doc/book/2-design-configuration.md)
  * [Chapter 3 - List, Search and Show Views Configuration](Resources/doc/book/3-list-search-show-configuration.md)
  * [Chapter 4 - New and Edit Views Configuration](Resources/doc/book/4-new-edit-configuration.md)
  * [Chapter 5 - Actions Configuration](Resources/doc/book/5-actions-configuration.md)
  * [Chapter 6 - Menu Configuration](Resources/doc/book/6-menu-configuration.md)
  * [Chapter 7 - About this Project](Resources/doc/book/7-about.md)
  * [Appendix - Full Configuration Reference](Resources/doc/book/configuration-reference.md)



  * [Chapter 4 - Views and Actions](https://github.com/javiereguiluz/EasyAdminBundle/blob/master/Resources/doc/getting-started/4-views-and-actions.md)
  * [Chapter 5 - Backend Design Customization](https://github.com/javiereguiluz/EasyAdminBundle/blob/master/Resources/doc/getting-started/5-design-customization.md)
  * [Chapter 6 - About this Project](https://github.com/javiereguiluz/EasyAdminBundle/blob/master/Resources/doc/getting-started/6-about-this-project.md)

  * [How to Format Dates and Numbers](https://github.com/javiereguiluz/EasyAdminBundle/blob/master/Resources/doc/tutorials/format-date-number.md)


  * [Customizing Backend Actions](https://github.com/javiereguiluz/EasyAdminBundle/blob/master/Resources/doc/tutorials/customizing-backend-actions.md)

  * [Customizing AdminController](https://github.com/javiereguiluz/EasyAdminBundle/blob/master/Resources/doc/tutorials/customizing-admin-controller.md)

    * [Advanced Design Customization](https://github.com/javiereguiluz/EasyAdminBundle/blob/master/Resources/doc/tutorials/advanced-design-customization.md)


#### Tutorials

  * [How to Translate the Backend](Resources/doc/tutorials/i18n.md)
  * [How to Define Custom Actions](Resources/doc/tutorials/custom-actions.md)
  * [How to Define Custom Options for Entity Properties](Resources/doc/tutorials/custom-property-options.md)
  * [How to Use a WYSIWYG Editor](Resources/doc/tutorials/wysiwyg-editor.md)
  * [How to Upload Files and Images](Resources/doc/tutorials/upload-files-and-images.md)
  * [How to Manage Configuration for Complex Backends](Resources/doc/tutorials/complex-backend-config.md)
  * [Tips and Tricks](Resources/doc/tutorials/tips-and-tricks.md)

> **❮ NOTE ❯** you are reading the documentation of the bundle's **development**
> version. You can also [read the documentation of the latest stable version ➜]
> (https://github.com/javiereguiluz/EasyAdminBundle/tree/v1.11.7/).

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
