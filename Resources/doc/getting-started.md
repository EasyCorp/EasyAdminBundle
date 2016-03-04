Getting Started with EasyAdmin
==============================


Installation
------------

Installing EasyAdmin requires you to edit two files and execute two console
commands:

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require javiereguiluz/easyadmin-bundle
```

This command requires you to have Composer installed globally, as explained
in the [Composer documentation](https://getcomposer.org/doc/00-intro.md).

### Step 2: Enable the Bundle

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

### Step 3: Load the Routes of the Bundle

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

### Step 4: Prepare the Web Assets of the Bundle

This bundle uses several CSS, JavaScript and font files to create the backend
interfaces. Execute the following command to make those assets available in your
Symfony application:

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

### Expanded Configuration Format

This simple backend uses the shortcut configuration format. In order to
customize the backend, you must use the extended configuration syntax instead,
which allows to configure lots of options for each entity:

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

Entities are configured as elements under the `entities` key. The name of the
entities are used as the YAML keys. These names must be unique in the backend
and it's recommended to use the CamelCase syntax (e.g. `BlogPost` and not
`blog_post` or `blogPost`).

The only required option in this configuration format is called `class` and
defines the fully qualified class name of the Doctrine entity managed by the
backend.

Views and Actions
-----------------

EasyAdmin backends consist of **views** and **actions**. The **view** is the
page where you are (`list`, `edit`, `show`, etc.) and the **action** is what
you do on that page (`search`, `delete`, etc.)

There are five different **views** defined for each entity: `edit`, `list`,
`new`, `search` and `show`. The `list` view is mandatory for all entities, but
the rest of the views can be disabled if needed.

Each view can include one or more **actions** to perform operations on the
items displayed in that view. For example, the default `list` view interface
includes four actions as buttons or links:

![List view interface](../images/easyadmin-list-view-actions.png)

These are the built-in actions included by default in each view:

| View   | Actions
| ------ | -----------------------------------------
| `list` | `delete`, `edit`, `new`, `search`
| `edit` | `delete`, `list`
| `new`  | `list`
| `show` | `delete`, `edit`, `list`

Built-in actions can be enabled/disabled and fully customized. Moreover, you
can [create your own actions][1] to perform tasks in the backend.

[1]: ../tutorials/customizing-backend-actions.md
