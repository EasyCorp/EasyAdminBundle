Upgrading from EasyAdmin 2 to EasyAdmin 3
=========================================

In EasyAdmin 2 most of the backend configuration was defined in YAML files,
while custom behavior was created with PHP. This worked great for small
applications, but it was hard to maintain and not flexible enough for medium
and large applications.

Starting from EasyAdmin 3, **backends are created exclusively with PHP**.
YAML is no longer used in any part of EasyAdmin. However, you will be even more
productive than before because you can autocomplete 100% of the new PHP code and
the bundle also provides commands to generate some of the needed code.

Automatic Upgrade from EasyAdmin 2 to EasyAdmin 3
-------------------------------------------------

To simplify migrations, EasyAdmin includes a command to migrate the EasyAdmin 2
YAML configuration into the PHP files needed in EasyAdmin 3. In some complex
projects this command won't be able to do all the needed work, but it will help
you making most of the work.

**Step 1.** In your existing Symfony application, run this command:

.. code-block:: terminal

    $ cd your-project/
    $ php bin/console make:admin:migration

This command will back up the EasyAdmin 2 YAML configuration in a file so you
can later access to it from EasyAdmin 3.

**Step 2.** Update your ``composer.json`` dependencies to upgrade to EasyAdmin 3:

.. code-block:: diff

    -"easycorp/easyadmin-bundle": "^2.0",
    +"easycorp/easyadmin-bundle": "^3.0",

Now, run this command to actually update the dependencies:

.. code-block:: terminal

    $ composer update

**Step 3.** Depending on your project configuration, you may need to fix some
minor routing and configuration issues after the upgrade. Symfony's error
messages will guide you.

**Step 4.** Run this command again from your EasyAdmin 3 application:

.. code-block:: terminal

    $ php bin/console make:admin:migration

Select the configuration that contains EasyAdmin 2 YAML configuration and the
command will generate all the needed PHP files to replicate that configuration.
In most applications this command generates all the needed code, but in complex
application you may need to tweak, fix or add some additional changes.

Changes Breaking Backward Compatibility
---------------------------------------

The ``make:admin:migration`` command tries its best to generate the PHP files
that are equivalent to the previous YAML configuration. However, there are some
other changes that you may need to make in your application manually:

* The event names have changed in favor of object-based events, as recommended
  starting from Symfony 4.3. For example, ``EasyAdminEvents::PRE_PERSIST`` is
  now ``BeforeCreatingEntity::class``. Read :doc:`the events article </events>`
  for details.
* Most default template paths have changed: ``@EasyAdmin/default/*.html.twig``
  templates have been split into three different directories:
  ``@EasyAdmin/page/*.html.twig``, ``@EasyAdmin/label/*.html.twig`` and
  ``@EasyAdmin/field/*.html.twig``.
* Some HTML elements and their CSS classes and IDs have changed. This only
  affects you if you have created some custom CSS/JS code for your backend.
* ...

Removed Features
----------------

EasyAdmin 3 removes some features of the previous versions which are no longer
considered useful:

* The ``design.brand_color`` config option has been removed because you can't
  customize the backend design by changing just this value. If you still want to
  do that, use the following code in your dashboard class::

      class DashboardController extends AbstractDashboardController
      {
          // ...

          public function configureAssets(): Assets
          {
              return Assets::new()
                  // ...
                  ->addHtmlContentToHead('<style>:root { --color-primary: #123456; }</style>');
          }
      }

* The ``default: true`` option to set the default backend page has been removed.
  Use the :doc:`dashboard </dashboards>` index as the default page or redirect
  to the desired page inside the dashboard controller action.
* The global options ``easy_admin.list.title``, ``easy_admin.show.title``, etc.
  have been removed in favor of the ``setPageTitle()`` method in the ``Crud`` class.
* The global ``help`` option for entities has been removed in favor of the
  ``setHelp()`` method in the ``Crud`` class.
* The global ``easy_admin.list.max_results`` option has been removed in favor of
  the ``setPaginatorPageSize()`` method in the ``Crud`` class.
* The ``dql_filter`` option to quickly filter the entity listings has been removed.
  Instead, use the ``createIndexQueryBuilder()`` method in the
  :doc:`CRUD controller </crud>`.
* The ``PRE_INITIALIZE`` and ``POST_INITIALIZE`` events have been removed. If you
  want to modify the configuration in :ref:`the AdminContext <admin-context>`
  variable, use a Symfony listener/subscriber and run it after EasyAdmin one. You
  can also decorate the ``AdminContextProvider`` service.
* The ``PRE_DELETE``, ``POST_DELETE``, ``PRE_EDIT``, ``POST_EDIT``, ``PRE_LIST``,
  ``POST_LIST``, ``PRE_NEW``, ``POST_NEW``, ``PRE_SEARCH``, ``POST_SEARCH``,
  ``PRE_SHOW``, ``POST_SHOW`` events have been removed. Use instead the
  ``BeforeCrudActionEvent`` and ``AfterCrudActionEvent`` events.
* ...
