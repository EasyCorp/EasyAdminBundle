Chapter 3. Basic Configuration
==============================

This chapter explains all the basic and general configuration options available
for your backends. It's common to change some of these options as soon as the
backend is created.

Changing the URL Used to Access the Backend
-------------------------------------------

By default, the backend is accessible at the ``/admin`` URL of your Symfony
application. This value is defined in the ``prefix`` option when loading the
routes of the bundle. Change its value to meet your own requirements:

.. code-block:: yaml

    # config/routes/easy_admin.yaml
    easy_admin_bundle:
        resource: '@EasyAdminBundle/Controller/AdminController.php'
        prefix: /_secret_backend  # <-- change this value
        type: annotation

.. note::

    Depending on your existing routing configuration, Symfony may ignore this
    configuration. Run the ``debug:router`` command to troubleshoot any problem
    with the application routes.

Changing the Name of the Backend
--------------------------------

By default, the backend displays ``Easy Admin`` as its name. Use the
``site_name`` option to change this value:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        site_name: 'ACME Megacorp.'
        # ...

The contents of this option are not escaped in the template, so you can use
HTML tags if needed:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        site_name: 'ACME <em style="font-size: 80%;">Megacorp.</em>'
        # ...

This flexibility allows to use an ``<img>`` HTML tag to display an image-based
logo instead of a text-based logo:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        site_name: '<img src="https://symfony.com/logos/symfony_white_01.png" />'
        # ...

Changing the Homepage of the Backend
------------------------------------

By default, the homepage of the backend shows the items of the first configured
entity. Read the :doc:`menu-configuration` to learn how to change this homepage.

Restricting the Access to the Backend
-------------------------------------

EasyAdmin relies on the underlying Symfony security mechanism to restrict the
access to your backend. Read the `Symfony Security documentation`_ to learn
how to protect the backend URLs.

When accessing a protected backend, EasyAdmin displays the name of user who is
logged in the application. Otherwise it displays *"Anonymous User"*.

.. _`Symfony Security documentation`: https://symfony.com/doc/current/book/security.html

-----

Next chapter: :doc:`design-configuration`
