Getting Started with EasyAdmin
==============================

Welcome to the **EasyAdmin Project**, the new (and simple) admin generator for
Symfony applications. In this guide you'll learn how to install the bundle and
how to create your first backend.

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

What's Next?
------------

  * Read the [EasyAdmin Documentation][1] to learn everything about its dozens
    of features and configuration options.
  * Check out the [EasyAdmin Demo application][2] to see how to easily create a
    backend in a real Symfony application.
  * Read the [EasyAdmin Tutorials][3] to learn about advanced features and
    integrations with third-party bundles, such as VichUploaderBundle and
    IvoryCKEditorBundle.

Do you have any question about this bundle? [Open an issue][4] in our official
repository or [post a question][5] in StackOverflow.

[1]: ./book/configuration-reference.md
[2]: https://github.com/javiereguiluz/easy-admin-demo
[3]: ./tutorials/
[4]: https://github.com/javiereguiluz/EasyAdminBundle/issues
[5]: http://stackoverflow.com/questions/tagged/symfony2-easyadmin
