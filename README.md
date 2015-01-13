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
  * **Lightweight** (less than 500 lines of code).

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
and add the following four lines at the very beginning of it:

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
its interface. In order to add those files to your application, execute the
following command:

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
Browse the `/admin` URL in your Symfony application and you'll get access to
the admin backend:

![Default listing interface](Resources/doc/images/easyadmin-customer-listing.png)


Unlike most of the usual admin generators, EasyAdmin doesn't generate any code.
All resources are served on-the-fly to ensure an exceptional developer
experience.

Without any further configuration, EasyAdmin guesses the best settings for you
admin backend. This ensures that all interfaces look "good enough", which may
be acceptable for simple backends and rapid prototypes. However, most of the
times you may need to customize some parts of the backend. Keep reading to
learn how to do it.

Custom Backends
---------------

### Customize the URL Prefix Used to Access the Backend

By default, EasyAdmin backends are located at the `/admin` URI of your Symfony
application. This value is the one defined in the `prefix` option used when
loading the routes of the bundle. To change the URL of the backend, just change
the value of this option in the main routing configuration file:

```yaml
# app/config/routing.yml
easy_admin_bundle:
    resource: "@EasyAdminBundle/Controller/"
    type:     annotation
    prefix:   /_secret_backend

# ...
```

### Customize the Order of the Main Menu Items

EasyAdmin displays main menu items following the same order of the entities
defined in the configuration file. So you just have to reorder the list of
entities to reorder the main menu elements.

### Customize the Label of the Main Menu Items

By default, EasyAdmin uses the name of the entity as the name of its menu item.
You can define a custom label for each menu item using this alternative
configuration format:

```yaml
# app/config/config.yml
easy_admin:
    entities:
        Clients:
            class: AppBundle\Entity\Customer
        Orders:
            class: AppBundle\Entity\Order
        Inventory:
            class: AppBundle\Entity\Product
```

The keys defined under the `entities` key (in this case, `Clients`, `Orders`
and `Inventory`) will be the labels used in the main menu items. If the labels
include white spaces or any reserved YAML character, enclose it with quotes:

```yaml
# app/config/config.yml
easy_admin:
    entities:
        'Active Clients':
            class: AppBundle\Entity\Customer
        'Pending Orders':
            class: AppBundle\Entity\Order
        'Inventory (2015)':
            class: AppBundle\Entity\Product
```

This alternative configuration format is probably the one you will use in real
backends, because it allows for a lot more customizations, as you'll see in the
next sections.

### Customize the Name of the Backend

By default, the backend will display `Easy Admin` as its name. Use the
`site_name` option to change this value:

```yaml
# app/config/config.yml
easy_admin:
    site_name: 'ACME Megacorp.'
    # ...
```

Companies and organizations needs can be so different, that EasyAdmin doesn't
restrict the contents of this option. In fact, the contents are displayed with
the `raw` Twig filter. This means that you can use any HTML markup to display
the name exactly as you are required:

```yaml
# app/config/config.yml
easy_admin:
    site_name: 'ACME <sup style="color: yellow">Megacorp.</sup>'
    # ...
```

If you want to display the name as an image instead of text, just define a
`<img>` HTML element. The following example will show the beautiful Symfony
logo as the name of your backend:

```yaml
# app/config/config.yml
easy_admin:
    site_name: '<img src="http://symfony.com/logos/symfony_white_01.png" />'
    # ...
```

### Customize the Number of Items Displayed in Listings

By default, EasyAdmin displays a maximum of `15` items in each listing page.
Use the `list_max_results` option to change this value:

```yaml
# app/config/config.yml
easy_admin:
    list_max_results: 30
    # ...
```

### Customize the Actions Displayed for Each Listing Item

By default, listings just display the `Edit` action for each item. If you also
want to add the popular `Show` action, use the `list_actions` option:

```yaml
# app/config/config.yml
easy_admin:
    list_actions: ['edit', 'show']
    # ...
```

In the current version of EasyAdmin you cannot define custom actions.

### Customize the Columns Displayed in Listings

By default, EasyAdmin makes some "smart guesses" to decide which columns to
display in each entity listing to make it look "good enough". Add the `list`
option in each entity which you want to customize:

```yaml
# app/config/config.yml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            list:  ['id', 'firstName', 'lastName', 'phone', 'email']
    # ...
```

#### Virtual Entity Fields

Sometimes it's useful to modify the original entity properties before
displaying them in the listings. For example, if your `Customer` entity
defines a `firstName` and `lastName` properties, you may want to just display
a column called `Name` with both information. These are called `virtual fields`
because they don't really exist as real Doctrine entity fields.

First, add this new virtual field to the entity configuration:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            list:  ['id', 'name', 'phone', 'email']
    # ...
```

Then, you have to add a new public method in your entity. The name of the
method must must be `getXXX()` or `xxx()`, where `xxx` is the name of the
virtual field (in this case, `name`):

```php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Customer
{
    // ...

    public function getName()
    {
        return $this->getFirstName().' '.$this->getLastName();
    }
}
```

That's it. Reload your backend and you'll see the new virtual field displayed
in the entity listing. The only current limitation of virtual fields is that
you cannot reorder listing contents using these type of fields.

### Customize the Columns Displayed in Forms

By default, EasyAdmin includes all the entity fields in the forms used to
create and edit entities. Use the `new` and `edit` options of the entity to
define the form fields displayed in each case:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            edit:  ['firstName', 'secondName', 'phone', 'email']
            new:   ['firstName', 'secondName', 'phone', 'email', 'creditLimit']
    # ...
```

### Customize the Actions Used to Create and Edit Entities

// Coming soon ...

### Customize the Translation of the Backend Interface

// Coming soon ...

### Customize the Security of the Backend Interface

EasyAdmin relies on the built-in Symfony security features to restrict the
access to the backend. In case you need it, checkout the
[Security Chapter](http://symfony.com/doc/current/book/security.html) of the
official Symfony documentation to learn how to restrict the access to the
backend section of your application.

How to Collaborate in this Project
----------------------------------

// Coming soon ...

-----

EasyAdmin, the missing admin generator for Symfony applications.
