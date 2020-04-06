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
        resource: '@EasyAdminBundle/Controller/EasyAdminController.php'
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
        site_name: '<img src="https://symfony.com/logos/symfony_white_01.png"/>'
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
logged in the application. Otherwise it displays *"Anonymous User"*. In
addition, if you enable the `logout feature`_ in your firewall, EasyAdmin
displays a link to logout from the backend.

Configuring the Logged In User Information
------------------------------------------

By default, all pages display the details of the logged in user. The user name
is the result of calling to the ``__toString()`` method on the current user
object. The user avatar is a generic avatar icon. If you want to hide any of
this information, use these config options:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        user:
            display_name: true
            display_avatar: false
        # ...

If you store the user name and their avatar URL in other properties/methods of
the user object, define the ``name_property_path`` and ``avatar_property_path``
options. Their values are any valid `PropertyAccess component`_ expression,
which is applied to the user object:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        user:
            # this method/property must return the string representation of the user
            # (Symfony will look for getFullName(), isFullName(), ..., and 'fullName' property)
            name_property_path: 'fullName'

            # this method/property must return the absolute URL of the user avatar image
            # (Symfony will look for getGravatar(), isGravatar(), ..., and 'gravatar' property)
            avatar_property_path: 'gravatar'
        # ...

-----

Next chapter: :doc:`design-configuration`

.. _`Symfony Security documentation`: https://symfony.com/doc/current/book/security.html
.. _`logout feature`: https://symfony.com/doc/current/security.html#logging-out
.. _`PropertyAccess component`: https://symfony.com/components/PropertyAccess
