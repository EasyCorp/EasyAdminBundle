EasyAdmin Pro
=============

`EasyAdmin Pro`_ is a paid extension to EasyAdmin that adds the following features:

* :doc:`Audit Log </audit-log>`: see who changed an entity, when they
  changed it, and what the previous values were.
* :doc:`Content Lock </content-lock>`: keep two people from editing the
  same entity at the same time.
* :doc:`Messenger Inspector </messenger-inspector>`: find failed messages,
  inspect their details, and retry them from your backend.
* :doc:`Markdown Editor Field </fields/MarkdownEditorField>`: write Markdown
  with a formatting toolbar and live preview.

`Buy an EasyAdmin Pro license`_ before using them in your projects.

Installation
------------

You can add EasyAdmin Pro to an existing EasyAdmin application and enable
its features individually. The Markdown editor is ready to use as soon as
you install the bundle and its assets.

Requirements
~~~~~~~~~~~~

Your application must meet the :doc:`EasyAdmin requirements </index>` for
your installed version, as well as these requirements for EasyAdmin Pro:

* PHP 8.1 or higher, with the ``intl`` extension enabled;
* Symfony 5.4, 6.4, 7.x or 8.x;
* EasyAdmin 4.27.6 or later in the 4.x series, or EasyAdmin 5.x;
* Doctrine ORM 2.14 or later in the 2.x series, or 3.6.8 or later in
  the 3.x series;
* Doctrine DBAL 3.7 or later in the 3.x series, or 4.x;
* Doctrine Bundle 2.5 or later in the 2.x series, or 3.x.

Installing the Bundle
~~~~~~~~~~~~~~~~~~~~~

`Purchase a license at easycorp.io`_ and follow the instructions provided
with it to configure the private Composer repository.

Once you have access to the package, install it with Composer:

.. code-block:: terminal

    $ composer require easycorp/easyadmin-pro-bundle

If your application uses `Symfony Flex`_, the bundle is enabled
automatically. Otherwise, register it in the ``config/bundles.php``
file::

    // config/bundles.php
    return [
        // ...
        EasyCorp\Bundle\EasyAdminProBundle\EasyAdminProBundle::class => ['all' => true],
    ];

Installing the Assets
~~~~~~~~~~~~~~~~~~~~~

The bundle provides its own CSS and JavaScript assets, which must be
published in your application's ``public/`` directory:

.. code-block:: terminal

    $ php bin/console assets:install

The assets don't need Webpack Encore, AssetMapper or a separate frontend
build. Run ``assets:install`` again after updating the bundle.

Enabling the Features
~~~~~~~~~~~~~~~~~~~~~

Audit Log, Content Lock and Messenger Inspector are disabled by default.
Enable the ones you need in ``config/packages/easyadmin_pro.yaml``. For
example, to protect edit forms with content locking:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        content_lock:
            enabled: true

Each feature's article walks you through its setup:

* :doc:`Audit Log </audit-log>` also needs a signing key by default.
* :doc:`Content Lock </content-lock>` works with your existing CRUD pages.
* :doc:`Messenger Inspector </messenger-inspector>` requires Symfony
  Messenger and a controller to display the inspector.
* :doc:`Markdown Editor Field </fields/MarkdownEditorField>` needs no bundle
  configuration; add the field to your CRUD controller.

Database Configuration
~~~~~~~~~~~~~~~~~~~~~~

The features that store data (Audit Log, Content Lock and Messenger
Inspector) create their own database tables. By default, they use the
``default`` Doctrine DBAL connection. Use the global
``dbal_connection`` option to change the connection used by all
features, or override it per feature:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        # the DBAL connection used by all features (default: 'default')
        dbal_connection: 'default'

        audit_log:
            # this feature stores its data in a different connection
            dbal_connection: 'audit'

After enabling a feature, generate a Doctrine migration to create its
tables, review the generated SQL, and apply it:

.. code-block:: terminal

    $ php bin/console make:migration
    $ php bin/console doctrine:migrations:migrate

These commands require MakerBundle and DoctrineMigrationsBundle; EasyAdmin
Pro doesn't install them for you. The enabled features' tables are included
in Doctrine's schema automatically.

Use ``table_name`` under each feature to change its main table name. Audit
Log also provides ``integrity.head_table_name`` and
``integrity.checkpoint_table_name``. The optional Messenger events table
uses the main table name with an ``_events`` suffix.

Overriding Templates
~~~~~~~~~~~~~~~~~~~~

To customize any of the Twig templates provided by the bundle, create
a template with the same path inside the
``templates/bundles/EasyAdminProBundle/`` directory of your
application. For example, to customize the page displayed when an
entity is locked, create this template:

.. code-block:: text

    your-project/
    ├── templates/
    │   └── bundles/
    │       └── EasyAdminProBundle/
    │           └── content_lock/
    │               └── _locked_entity.html.twig

.. _`EasyAdmin Pro`: https://easycorp.io/pro
.. _`Buy an EasyAdmin Pro license`: https://easycorp.io/pricing#subscribe
.. _`Purchase a license at easycorp.io`: https://easycorp.io/pro
.. _`Symfony Flex`: https://symfony.com/doc/current/setup.html
