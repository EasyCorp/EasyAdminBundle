Chapter 1. Installation
=======================

In a Symfony application using `Symfony Flex`_, run this command to install and
integrate EasyAdmin in your application:

.. code-block:: terminal

    $ composer require easycorp/easyadmin-bundle

This command executes a Symfony Flex recipe that creates the following file to
enable the routes of the bundle:

.. code-block:: yaml

    # config/routes/easy_admin.yaml
    easy_admin_bundle:
        resource: '@EasyAdminBundle/Controller/EasyAdminController.php'
        prefix: /admin
        type: annotation

Depending on your existing routing configuration, Symfony may ignore this
configuration. Run the ``debug:router`` command to troubleshoot any problem with
the application routes.

That's it! Now everything is ready to create your first admin backend.

.. _`Symfony Flex`: https://github.com/symfony/flex

-----

Next chapter: :doc:`your-first-backend`
