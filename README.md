<img src="https://cloud.githubusercontent.com/assets/73419/5748433/476724ec-9c40-11e4-8f7a-5916c1212a22.png" alt="EasyAdmin" title="EasyAdmin" />

EasyAdmin
=========

<img src="https://cloud.githubusercontent.com/assets/73419/5748254/e0697de0-9c3e-11e4-8b42-792a25538676.png" alt="EasyAdmin creates Symfony Backends" title="EasyAdmin" align="right" />

EasyAdmin lets you create administration backends for Symfony applications
with unprecedented simplicity.

**Features**

  * **CRUD** operations on Doctrine entities (create, edit, list, delete).
  * Full-text **search**, **pagination** and column **sorting**.
  * Fully **responsive** design with four break points.
  * **Lightweight** (less than 500 lines of code).
  * **Fast**, **simple** and **smart** where appropriate.

**Requirements**

EasyAdmin is compatible with Symfony 2.3+ applications that use Doctrine ORM
entities to define their model. These entities must include a simple primary
key called `id` and `many-to-many` associations are not supported.

Installation
------------

In order to install EasyAdmin you have to edit two files and execute two
console commands, as explained in the following steps.

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require javiereguiluz/easyadmin-bundle
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
            new JavierEguiluz\Bundle\EasyAdminBundle\EasyAdminBundle(),
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


EasyAdmin doesn't generate any code, not even for the templates. All resources
are served on-the-fly to ensure an exceptional developer experience.

Without any further configuration, EasyAdmin guesses the best settings to make
your admin backend look "good enough". This may be acceptable for simple
backends and rapid prototypes, but most of the times, you need to customize
some parts of the backend. Keep reading to learn how to do it.

Custom Backends
---------------

### Customize the URL Prefix Used to Access the Backend

By default, your backend will be accessible at the `/admin` URI of your Symfony
application. This value is defined in the `prefix` option used when loading
the routes of the bundle. You are free to change its value to meet your own
backend requirements:

```yaml
# app/config/routing.yml
easy_admin_bundle:
    resource: "@EasyAdminBundle/Controller/"
    type:     annotation
    prefix:   /_secret_backend  # <-- change this value

# ...
```

### Customize the Order of the Main Menu Items

Main menu items follow the same order of the entities defined in the admin
configuration file. So you just have to reorder the list of entities to
reorder the main menu elements.

### Customize the Label of the Main Menu Items

By default, main menu items are called after the entities that they represent.
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
and `Inventory`) will be displayed in the main menu items. If the labels
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

This extended configuration format is not only useful to customize main menu
labels, but to define additional options, as you'll see in the next sections.

### Customize the Name of the Backend

By default, the backend will display `Easy Admin` as its name. Use the
`site_name` option to change this value:

```yaml
# app/config/config.yml
easy_admin:
    site_name: 'ACME Megacorp.'
    # ...
```

Companies and organizations needs can be so different, that the contents of
this option are not restricted. In fact, the contents are displayed with
the `raw` Twig filter. This means that you can use any HTML markup to display
the name exactly as you are required:

```yaml
# app/config/config.yml
easy_admin:
    site_name: 'ACME <em style="font-size: 80%; color: yellow">Megacorp.</em>'
    # ...
```

If you want to display an image of your logo, use an `<img>` HTML element as
the site name. The following example would show the beautiful Symfony logo as
the name of your backend:

```yaml
# app/config/config.yml
easy_admin:
    site_name: '<img src="http://symfony.com/logos/symfony_white_01.png" />'
    # ...
```

### Customize the Number of Items Displayed in Listings

By default, listings display a maximum of `15` items. Define the
`list_max_results` option to change this value:

```yaml
# app/config/config.yml
easy_admin:
    list_max_results: 30
    # ...
```

### Customize the Actions Displayed for Each Listing Item

By default, listings just display the `Edit` action for each item. If you also
want to add the popular `Show` action, define the `list_actions` option:

```yaml
# app/config/config.yml
easy_admin:
    list_actions: ['edit', 'show']
    # ...
```

In the current version of EasyAdmin you cannot define custom actions.

### Customize the Columns Displayed in Listings

By default, the backend makes some "smart guesses" to decide which columns to
display in each entity listing to make it look "good enough". If you want to override this behavior for some entity, define the fields to show using the
`list` option as follows:

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
defines `firstName` and `lastName` properties, you may want to just display
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

If you reload your backend, you'll get an error because the `name` field does
not match any of the entity's properties. To fix this issue, add a new public
method in your entity called `getXxx()` or `xxx()`, where `xxx` is the name of
the virtual field (in this case, `name`):

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
        return $this->firstName.' '.$this->lastName;
    }
}
```

That's it. Reload your backend and you'll see the new virtual field displayed
in the entity listing. The only current limitation of virtual fields is that
you cannot reorder listings using these fields.

### Customize the Fields Displayed in Forms

By default, the forms used to create and edit entities display all their
properties. Customize any of these forms for any of your entities using the
`new` and `edit` options:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            edit:  ['firstName', 'secondName', 'phone', 'email']
            new:   ['firstName', 'secondName', 'phone', 'email', 'creditLimit']
    # ...
```

### Customize the Visual Design of the Backend

The current version of EasyAdmin doesn't support the concept of themes, but you
can fully customize its design using CSS and JavaScript files. Define the
`assets` option to load your own web assets:

```yaml
easy_admin:
    assets:
        css:
            - 'bundles/app/css/admin1.css'
            - 'bundles/acmedemo/css/admin2.css'
        js:
            - 'bundles/app/js/admin1.js'
            - 'bundles/acmedemo/js/admin2.js'
    # ...
```

EasyAdmin supports any kind of web asset (internal, external, relative and
absolute) and links to them accordingly:

```yaml
easy_admin:
    assets:
        css:
            # HTTP protocol-relative URL
            - '//example.org/css/admin1.css'
            # absolute non-secure URL
            - 'http://example.org/css/admin2.css'
            # absolute secure URL
            - 'https://example.org/css/admin3.css'
            # absolute internal bundle URL
            - '/bundles/acmedemo/css/admin4.css'
            # relative internal bundle URL
            - 'bundles/app/css/admin5.css'
        js:
            # this option works exactly the same as the 'css' option
            - '//example.org/js/admin1.js'
            - 'http://example.org/js/admin2.js'
            - 'https://example.org/js/admin3.js'
            - '/bundles/acmedemo/js/admin4.js'
            - 'bundles/app/js/admin5.js'
    # ...
```

### Customize the Templates of the Backend

In addition to loading your own stylesheets and scripts, you can also override
the templates used to build the backend interface. To do so, follow the well-
known Symfony bundle [inheritance mechanism](http://symfony.com/doc/current/book/templating.html#overriding-bundle-templates).

The most important templates used by EasyAdmin are the following:

  * `layout.html.twig`
  * `new.html.twig`
  * `show.html.twig`
  * `edit.html.twig`
  * `list.html.twig`
  * `_list_paginator.html.twig`
  * `_flashes.html.twig`

Suppose you want to modify the paginator displayed at the bottom of each
listing. This element is built with the `_list_paginator.html.twig` template,
so you have to create the following new template to override it:

```
your-project/
    ├─ app/
    │  ├─ ...
    │  └─ Resources/
    │     └─ EasyAdminBundle/
    │        └─ views/
    │           └─ _list_paginator.html.twig
    ├─ src/
    ├─ vendor/
    └─ web/
```

Be careful to use those exact folder and file names. If you do, the backend
will use your template instead of the default one. Please note that when
adding a template in a new location, **you may need to clear your cache** (with
the command `php app/console cache:clear`), **even if you are in debug mode**.

### Customize the Actions Used to Create and Edit Entities

By default, new and edited entities are persisted without any further
modification. In case you want to manipulate the entity before persisting it,
you can override the methods used by EasyAdmin.

Similarly to customizing templates, you need to use the Symfony bundle
[inheritance mechanism](http://symfony.com/doc/current/book/templating.html#overriding-bundle-templates)
to override the controller used to generate the backend. Among many other
methods, this controller contains two methods which are called just before the
entity is persisted:

```php
protected function prepareEditEntityForPersist($entity)
{
    return $entity;
}

protected function prepareNewEntityForPersist($entity)
{
    return $entity;
}
```

Suppose you want to automatically set the slug of some entity called `Article`
whenever the entity is persisted. First, create a new controller inside any of
your own bundles. Make this bundle extend the `AdminController` provided by
EasyAdmin and include, at least, the following contents:

```php
// src/AppBundle/Controller/AdminController.php
namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as EasyAdminController;

class AdminController extends EasyAdminController
{
    /**
     * @Route("/admin/", name="admin")
     */
    public function indexAction(Request $request)
    {
        return parent::indexAction($request);
    }
}
```

Now you can add in this new controller any of the original controller's
methods to override them. Let's add `prepareEditEntityForPersist()` and
`prepareNewEntityForPersist()` to set the `slug` of the `Article` entity:

```php
// src/AppBundle/Controller/AdminController.php
namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as EasyAdminController;
use AppBundle\Entity\Article;

class AdminController extends EasyAdminController
{
    /**
     * @Route("/admin/", name="admin")
     */
    public function indexAction(Request $request)
    {
        return parent::indexAction($request);
    }

    public function prepareEditEntityForPersist($entity)
    {
        if ($entity instanceof Article) {
            return $this->updateSlug($entity);
        }
    }
    public function prepareNewEntityForPersist($entity)
    {
        if ($entity instanceof Article) {
            return $this->updateSlug($entity);
        }
    }

    private function updateSlug($entity)
    {
        $slug = $this->get('app.slugger')->slugify($entity->getTitle());
        $entity->setSlug($slug);

        return $entity;
    }
}
```

The example above is trivial, but your custom admin controller can be as
complex as needed. In fact, you can override any of the original controller's
methods to customize the backend as much as you need.

### Customize the Translation of the Backend Interface

The first version of EasyAdmin is only available in English. But thanks to the
generous Symfony community, the interface will probably be translated to lots
of new languages very soon.

### Customize the Security of the Backend Interface

EasyAdmin relies on the built-in Symfony security features to restrict the
access to the backend. In case you need it, checkout the
[Security Chapter](http://symfony.com/doc/current/book/security.html) of the
official Symfony documentation to learn how to restrict the access to the
backend section of your application.

In addition, when accessing a protected backend, EasyAdmin will display the
name of user who is logged in the application.

How to Collaborate in this Project
----------------------------------

**1.** Ideas, Feature Requests, Issues, Bug Reports and Comments (positive or
negative) are more than welcome.

Feature Requests will be accepted if they are useful for a majority of users,
don't overcomplicate the code and prioritize the user and developer experience.

**2.** Unsolicited Pull Requests are currently not accepted.

EasyAdmin is a very young project. In order to protect the original vision of
the project, we don't accept unsolicited Pull Requests. This decision will of
course be revised in the near term, once we fully realize how the project is
being used and what do users expect from us.

LEGAL DISCLAIMER
----------------

This software is published under the MIT License, which states that:

> THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
> IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
> FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
> AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
> LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
> OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
> SOFTWARE.

-----

EasyAdmin, the missing admin generator for Symfony applications.
