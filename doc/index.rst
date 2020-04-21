EasyAdmin
=========

.. raw:: html

    <div class="box box--small box--warning">
        <strong class="title">WARNING:</strong>

        You are browsing the documentation for <strong>EasyAdmin 3.x</strong>,
        which hasn't been released as a stable version yet. You are probably
        using EasyAdmin 2.x in your application, so you can switch to
        <a href="https://symfony.com/doc/2.x/bundles/EasyAdminBundle/index.html">EasyAdmin 2.x docs</a>.
    </div>

`EasyAdmin`_ creates beautiful administration backends for your Symfony
applications. It's free, fast and fully documented.

If you already used previous EasyAdmin versions, beware that EasyAdmin 3 uses a
brand new architecture and it's incompatible with previous versions. However,
there's a command to :doc:`upgrade from EasyAdmin 2 to EasyAdmin 3 automatically </upgrade>`.

Technical Requirements
----------------------

EasyAdmin requires the following:

* PHP 7.2 or higher;
* Symfony 4.4 or higher;
* Doctrine ORM entities (Doctrine ODM is not supported).

Installation
------------

Run the following command to install EasyAdmin in your application:

.. code-block:: terminal

    $ composer require easycorp/easyadmin-bundle

Your First Backend
------------------

In previous EasyAdmin versions you could only create one **backend** per
application. In EasyAdmin 3 you can create unlimited backends. Each backend is
defined by a **dashboard**, which links to different **resources**.


.. IMAGE explaining this



Creating Dashboards
-------------------

**Dashboards** are regular `Symfony controllers`_ that include certain methods
needed to build the backends. Instead of creating that structure yourself, you
can open the console terminal and run the following command:

.. code-block:: terminal

    $ cd your-project/
    $ php bin/console make:admin:dashboard

This command generates a default dashboard that responds to the ``/admin`` URL.
All backend pages and actions use the same base URL (only the query string
changes). You can change that URL in the ``index()`` method of the dashboard
controller class. If you have `the Symfony binary`_ installed in your computer,
run your project as follows:

.. code-block:: terminal

    $ cd your-project/
    $ symfony server:start

If your application has support for annotations, you can browse the backend at
``http://127.0.0.1:8000/admin`` Otherwise, run ``composer require annotations``
to add support for them or update your routing configuration to tell Symfony to
load the route defined in the dashboard class. For example, when using YAML:

.. code-block:: yaml

    # config/routes.yaml
    admin:
        path: /admin
        controller: App\Controller\Admin\DashboardController::index

    # ...

This is how the empty dashboard looks by default:


.. TODO: image of the first backend


.. tip::

    If you can't see the dashboard for any reason, run Symfony's
    ``debug:router`` command to troubleshoot any problem with the routes.

.. note::

    If the interface of your backend displays translation keys instead of the
    actual contents, run ``composer require translator`` to add support for
    translation or clear your cache to rebuild the translation files.

Creating Admin Resources
------------------------

**Resources** are linked from **dashboard** to implement the backend features.
The only built-in resource provided by EasyAdmin are "CRUD controllers". They
implement the CRUD operations (create, show, update, delete) for Doctrine entities.

First, make sure that your application defines some Doctrine entity. If you
prefer it, use the ``make:entity`` command from the `Symfony MakerBundle`_ to
generate them.

Instead of creating the CRUD controller yourself, you can open the console
terminal and run the following command:

.. code-block:: terminal

    $ cd your-project/
    $ php bin/console make:admin:crud

This command generates a default CRUD controller for the given Doctrine entity.
Now you can link the entity from your dashboard by adding the following menu item
(:ref:`menu configuration <dashboard-menu>` is explained in another article)::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
    use App\Entity\BlogPost;

    class DashboardController extends AbstractDashboardController
    {
        // ...

        public function configureMenuItems(): iterable
        {
            return [
                yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home'),

                // change BlogPost::class by the class of your own Doctrine entity
                yield MenuItem::linkToCrud('Blog Posts', 'fa-file', BlogPost::class),
            ];
        }
    }

Browse your backend again at ``http://127.0.0.1:8000/admin`` and you'll see the
new menu item linking to the blog post admin. Click on it to see the list of
blog posts and create or edit any of them.

Learn More
----------

This article explained the basics of EasyAdmin. Keep reading to learn more about
each of its features:

* `Creating Dashboards </dashboards>`
* `CRUD controllers </crud>`
* `Design Configuration </design>`
* `Fields </fields>`
* `Filters </filters>`
* `Actions </actions>`
* `Protecting Backend Security </security>`
* `Backend Customization Based on Events </events>`
* `Upgrading from EasyAdmin 2 to 3 </upgrade>`

.. _`EasyAdmin`: https://github.com/EasyCorp/EasyAdminBundle
.. _`Symfony controllers`: https://symfony.com/doc/current/controller.html
.. _`Symfony MakerBundle`: https://symfony.com/doc/current/bundles/SymfonyMakerBundle/index.html
