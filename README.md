**❮ NOTE ❯** This bundle releases new versions on a regular basis. Make sure
to update your dependencies frequently to get the latest version.
[Check out the changelog](https://github.com/javiereguiluz/EasyAdminBundle/releases)
to learn about the new features.

-----

EasyAdmin
=========

[![Build Status](https://travis-ci.org/javiereguiluz/EasyAdminBundle.svg?branch=master)](https://travis-ci.org/javiereguiluz/EasyAdminBundle)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a3bfb8d9-7b2d-47ab-a95f-382af395bd51/mini.png)](https://insight.sensiolabs.com/projects/a3bfb8d9-7b2d-47ab-a95f-382af395bd51)

<img src="https://cloud.githubusercontent.com/assets/73419/5748254/e0697de0-9c3e-11e4-8b42-792a25538676.png" alt="EasyAdmin creates Symfony Backends" title="EasyAdmin" align="right" />

EasyAdmin lets you create administration backends for Symfony applications
with unprecedented simplicity.

**Features**

  * **CRUD** operations on Doctrine entities (create, edit, list, delete).
  * Full-text **search**, **pagination** and column **sorting**.
  * Fully **responsive** design (smartphones, tablets and desktops).
  * Translated into tens of languages.
  * **Fast**, **simple** and **smart** where appropriate.

**Requirements**

  * Symfony 2.3+ applications (Silex not supported).
  * Doctrine ORM entities (Doctrine ODM and Propel not supported).
  * Entities with simple primary keys (composite keys not supported).
  * All kinds of entity associations are supported, except `many-to-many`.
  * Entities using inheritance are not supported.

Installation
------------

The EasyAdmin installation requires you to edit two files and execute two
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

### Step 3: Load the Routes of the Bundle

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

### Step 4: Prepare the Web Assets of the Bundle

This bundle includes several CSS, JavaScript and font files which are used in
the backend interface. Execute the following command to make those assets
available in your Symfony application:

```cli
php app/console assets:install --symlink
```

That's it! Now everything is ready to create your first admin backend.

Your First Backend
------------------

Creating your first backend will take you around 30 seconds because you just
have to create a simple configuration file.

Let's suppose that you already have defined in your Symfony application three
Doctrine ORM entities called `Customer`, `Order` and `Product`. Open your main
application configuration file (usually `app/config/config.yml`) and add the
following configuration:

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

Creating a backend is that simple because EasyAdmin doesn't generate any code.
All resources are served on-the-fly to ensure an exceptional developer experience.

Without any further configuration, EasyAdmin guesses the best settings to make
your admin backend look "good enough". This may be acceptable for simple
backends and rapid prototypes, but most of the times, you need to customize
some parts of the backend. Keep reading the rest of the documentation to learn
how to do it.

-----

**❮ NOTE ❯** you are reading the documentation of the bundle's **development
version**, but in your application you probably have installed a **stable
version**. [Read the documentation of the most recent stable version ➜](https://github.com/javiereguiluz/EasyAdminBundle/tree/v1.1.0/Resources/doc).

-----

  * [Chapter 3 - Customizing your first backend](Resources/doc/3-customizing-first-backend.md)
  * [Chapter 4 - Customizing the List View](Resources/doc/4-customizing-list-view.md)
  * [Chapter 5 - Customizing the Show View](Resources/doc/5-customizing-show-view.md)
  * [Chapter 6 - Customizing the Edit and New Views](Resources/doc/6-customizing-new-edit-views.md)
  * [Chapter 7 - Customizing the Search View](Resources/doc/7-customizing-search-view.md)
  * [Chapter 8 - Customizing the View Actions](Resources/doc/8-customizing-view-actions.md)
  * [Chapter 9 - Advanced Techniques for Complex Backends](Resources/doc/9-advanced-techniques.md)
  * [Chapter 10 - Customizing the Visual Design of the Backend](Resources/doc/10-customizing-design.md)
  * [Chapter 11 - Configuration Reference](Resources/doc/11-configuration-reference.md)
  * [Chapter 12 - About this Project](Resources/doc/12-about-this-project.md)

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
