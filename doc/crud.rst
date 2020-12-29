CRUD Controllers
================

.. raw:: html

    <div class="box box--small box--warning">
        <strong class="title">WARNING:</strong>

        You are browsing the documentation for <strong>EasyAdmin 3.x</strong>,
        which has just been released. Switch to
        <a href="https://symfony.com/doc/2.x/bundles/EasyAdminBundle/index.html">EasyAdmin 2.x docs</a>
        if your application has not been upgraded to EasyAdmin 3 yet.
    </div>

**CRUD controllers** provide the CRUD operations (create, show, update, delete)
for Doctrine ORM entities. Each CRUD controller can be associated to one or more
dashboards.

Technically, these CRUD controllers are regular `Symfony controllers`_ so you can
do anything you usually do in a controller, such as injecting services and using
shortcuts like ``$this->render()`` or ``$this->isGranted()``.

CRUD controllers must implement the
``EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface``,
which ensures that certain methods are defined in the controller. Instead of implementing
the interface, you can also extend from the ``AbstractCrudController`` class.
Run the following command to generate the basic structure of a CRUD controller:

.. code-block:: terminal

    $ php bin/console make:admin:crud

.. _crud-pages:

CRUD Controller Pages
---------------------

The four main pages of the CRUD controllers are:

* ``index``, displays a list of entities which can be paginated, sorted by
  column and refined with search queries and filters;
* ``detail``, displays the contents of a given entity;
* ``new``, allows to create new entity instances;
* ``edit``, allows to update any property of a given entity.

These pages are generated with four actions with the same name in the
``AbstractCrudController`` controller. This controller defines other secondary
actions (e.g. ``delete`` and ``autocomplete``) which don't match any page.

The default behavior of these actions in the ``AbstractCrudController`` is
appropriate for most backends, but you can customize it in several ways:
:doc:`EasyAdmin events </events>`, :ref:`custom EasyAdmin templates <template-customization>`, etc.

Page Names and Constants
~~~~~~~~~~~~~~~~~~~~~~~~

Some methods require as argument the name of some CRUD page. You can use any of
the following strings: ``'index'``, ``'detail'``, ``'edit'`` and ``'new'``. If
you prefer to use constants for these values, use ``Crud::PAGE_INDEX``,
``Crud::PAGE_DETAIL``, ``Crud::PAGE_EDIT`` and ``Crud::PAGE_NEW`` (they are
defined in the ``EasyCorp\Bundle\EasyAdminBundle\Config\Crud`` class).

CRUD Controller Configuration
-----------------------------

The only mandatory config option of a CRUD controller is the FQCN of the
Doctrine entity being managed by the controller. This is defined as a public
static method::

    namespace App\Controller\Admin;

    use App\Entity\Product;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

    class ProductCrudController extends AbstractCrudController
    {
        // it must return a FQCN (fully-qualified class name) of a Doctrine ORM entity
        public static function getEntityFqcn(): string
        {
            return Product::class;
        }

        // ...
    }

The rest of CRUD options are configured using the ``configureCrud()`` method::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

    class ProductCrudController extends AbstractCrudController
    {
        // ...

        public function configureCrud(Crud $crud): Crud
        {
            return $crud
                ->setEntityLabelInSingular('...')
                ->setDateFormat('...')
                // ...
            ;
        }
    }

Design Options
~~~~~~~~~~~~~~

::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // set this option if you prefer the page content to span the entire
            // browser width, instead of the default design which sets a max width
            ->renderContentMaximized()

            // set this option if you prefer the sidebar (which contains the main menu)
            // to be displayed as a narrow column instead of the default expanded design
            ->renderSidebarMinimized()
        ;
    }

.. _crud_entity_options:

Entity Options
~~~~~~~~~~~~~~

::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // the labels used to refer to this entity in titles, buttons, etc.
            ->setEntityLabelInSingular('Product')
            ->setEntityLabelInPlural('Products')

            // in addition to a string, the argument of the singular and plural label methods
            // can be a closure that receives both the current entity instance (which will
            // be null in 'index' and 'new' pages) and the page name
            ->setEntityLabelInSingular(
                fn (?Product $product, string $pageName) => $product ? $product->toString() : 'Product'
            )
            ->setEntityLabelInPlural(function (?Category $category, string $pageName) {
                return 'edit' === $pageName ? $category->getLabel() : 'Categories';
            })

            // the Symfony Security permission needed to manage the entity
            // (none by default, so you can manage all instances of the entity)
            ->setEntityPermission('ROLE_EDITOR')
        ;
    }

Title and Help Options
~~~~~~~~~~~~~~~~~~~~~~

By default, the page titles of the ``index`` and ``new`` pages are based on the
:ref:`entity option <crud_entity_options>` values defined with the
``setEntityLabelInSingular()`` and ``setEntityLabelInPlural()`` methods. In the
``detail`` and ``edit`` pages, EasyAdmin tries first to convert the entity into
a string representation and falls back to a generic title otherwise.

You can override the default page titles with the following methods::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // the visible title at the top of the page and the content of the <title> element
            // it can include these placeholders: %entity_id%, %entity_label_singular%, %entity_label_plural%
            ->setPageTitle('index', '%entity_label_plural% listing')

            // you can pass a PHP closure as the value of the title
            ->setPageTitle('new', fn () => new \DateTime('now') > new \DateTime('today 13:00') ? 'New dinner' : 'New lunch')

            // in DETAIL and EDIT pages, the closure receives the current entity
            // as the first argument
            ->setPageTitle('detail', fn (Product $product) => (string) $product)
            ->setPageTitle('edit', fn (Category $category) => sprintf('Editing <b>%s</b>', $category->getName()))

            // the help message displayed to end users (it can contain HTML tags)
            ->setHelp('edit', '...')
        ;
    }

Date, Time and Number Formatting Options
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // the argument must be either one of these strings: 'short', 'medium', 'long', 'full', 'none'
            // (the strings are also available as \EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField::FORMAT_* constants)
            // or a valid ICU Datetime Pattern (see http://userguide.icu-project.org/formatparse/datetime)
            ->setDateFormat('...')
            ->setTimeFormat('...')

            // first argument = datetime pattern or date format; second optional argument = time format
            ->setDateTimeFormat('...', '...')

            ->setDateIntervalFormat('%%y Year(s) %%m Month(s) %%d Day(s)')
            ->setTimezone('...')

            // used to format numbers before rendering them on templates
            ->setNumberFormat('%.2d');
        ;
    }

Search and Pagination Options
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // the names of the Doctrine entity properties where the search is made on
            // (by default it looks for in all properties)
            ->setSearchFields(['name', 'description'])
            // use dots (e.g. 'seller.email') to search in Doctrine associations
            ->setSearchFields(['name', 'description', 'seller.email', 'seller.address.zipCode'])
            // set it to null to disable and hide the search box
            ->setSearchFields(null)

            // defines the initial sorting applied to the list of entities
            // (user can later change this sorting by clicking on the table columns)
            ->setDefaultSort(['id' => 'DESC'])
            ->setDefaultSort(['id' => 'DESC', 'title' => 'ASC', 'startsAt' => 'DESC'])

            // the max number of entities to display per page
            ->setPaginatorPageSize(30)
            // these are advanced options related to Doctrine Pagination
            // (see https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/tutorials/pagination.html)
            ->setPaginatorUseOutputWalkers(true)
            ->setPaginatorFetchJoinCollection(true)
        ;
    }

.. note::

    When using `Doctrine filters`_, listings may not include some items because
    they were removed by those global Doctrine filters. Use the dashboard route
    name to not apply the filters when the request URL belongs to the dashboard
    You can also get the dashboard route name via the :ref:`application context variable <admin-context>`.

The default Doctrine query executed to get the list of entities displayed in the
``index`` page takes into account the sorting configuration, the optional search
query, the optional :doc:`filters </filters>` and the pagination. If you need to
fully customize this query, override the ``createIndexQueryBuilder()`` method in
your CRUD controller.

Templates and Form Options
~~~~~~~~~~~~~~~~~~~~~~~~~~

::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // this method allows to use your own template to render a certain part
            // of the backend instead of using EasyAdmin default template
            // the first argument is the "template name", which is the same as the
            // Twig path but without the `@EasyAdmin/` prefix and the `.html.twig` suffix
            ->overrideTemplate('crud/field/id', 'admin/fields/my_id.html.twig')

            // the theme/themes to use when rendering the forms of this entity
            // (in addition to EasyAdmin default theme)
            ->addFormTheme('foo.html.twig')
            // this method overrides all existing the form themes (including the
            // default EasyAdmin form theme)
            ->setFormThemes(['my_theme.html.twig', 'admin.html.twig'])

            // this sets the options of the entire form (later, you can set the options
            // of each form type via the methods of their associated fields)
            // pass a single array argument to apply the same options for the new and edit forms
            ->setFormOptions([
                'validation_groups' => ['Default', 'my_validation_group']
            ]);

            // pass two array arguments to apply different options for the new and edit forms
            // (pass an empty array argument if you want to apply no options to some form)
            ->setFormOptions(
                ['validation_groups' => ['my_validation_group']],
                ['validation_groups' => ['Default'], '...' => '...'],
            );
        ;
    }

Same Configuration in Different CRUD Controllers
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you want to do the same config in all CRUD controllers, there's no need to
repeat the config in each controller. Instead, add the ``configureCrud()`` method
in your dashboard and all controllers will inherit that configuration::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

    class DashboardController extends AbstractDashboardController
    {
        // ...

        public function configureCrud(): Crud
        {
            return Crud::new()
                // this defines the pagination size for all CRUD controllers
                // (each CRUD controller can override this value if needed)
                ->setPaginatorPageSize(30)
            ;
        }
    }

Fields
------

Fields allow to display the contents of your Doctrine entities on each
:ref:`CRUD page <crud-pages>`. EasyAdmin provides built-in fields to display
all the common data types, but you can also :ref:`create your own fields <custom-fields>`.

If your CRUD controller extends from the ``AbstractCrudController``, the fields
are configured automatically. In the ``index`` page you'll see a few fields and
in the rest of pages you'll see as many fields as needed to display all the
properties of your Doctrine entity.

Read the :doc:`chapter about Fields </fields>` to learn how to configure which
fields to display on each page, how to configure the way each field is rendered, etc.

Customizing CRUD Actions
------------------------

The default CRUD actions (``index()``, ``detail()``, ``edit()``, ``new()`` and
``delete()`` methods in the controller) implement the most common behaviors
used in applications.

The first way to customize their behavior is to override those methods in your
own controllers. However, the original actions are so generic that they contain
quite a lot of code, so overriding them is not that convenient.

Instead, you can override other smaller methods that implement certain features
needed by the CRUD actions. For example, the ``index()`` action calls to a
method named ``createIndexQueryBuilder()`` to create the Doctrine query builder
used to get the results displayed on the index listing. If you want to customize
that listing, it's better to override the ``createIndexQueryBuilder()`` method
instead of the entire ``index()`` method. There are many of these methods, so
you should check the ``EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController`` class.

The other alternative to customize CRUD actions is to use the
:doc:`events triggered by EasyAdmin </events>`, such as ``BeforeCrudActionEvent``
and ``AfterCrudActionEvent``.

Creating, Persisting and Deleting Entities
------------------------------------------

Most of the actions of a CRUD controller end up creating, persisting or deleting
entities. If your CRUD controller extends from the ``AbstractCrudController``,
these methods are already implemented, but you can customize them overriding
methods and listening to events.

First, you can override the ``createEntity()``, ``updateEntity()``, ``persistEntity()``
and ``deleteEntity()`` methods. The ``createEntity()`` method for example only
executes ``return new $entityFqcn()``, so you need to override it if your entity
needs to pass constructor arguments or set some of its properties::

    namespace App\Controller\Admin;

    use App\Entity\Product;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

    class ProductCrudController extends AbstractCrudController
    {
        public static function getEntityFqcn(): string
        {
            return Product::class;
        }

        public function createEntity(string $entityFqcn)
        {
            $product = new Product();
            $product->createdBy($this->getUser());

            return $product;
        }

        // ...
    }

The other way of overriding this behavior is listening to the
:doc:`events triggered by EasyAdmin </events>` when an entity is created, updated,
persisted, deleted, etc.

Passing Additional Variables to CRUD Templates
----------------------------------------------

The default CRUD actions implemented in ``AbstractCrudController`` don't end
with the usual ``$this->render('...')`` instruction to render a Twig template
and return its contents in a Symfony ``Response`` object.

Instead, CRUD actions return a ``EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore``
object with the variables passed to the template that renders the CRUD action
contents. This ``KeyValueStore`` object is similar to Symfony's ``ParameterBag``
object. It's like an object-oriented array with useful methods such as ``get()``,
``set()``, ``has()``, etc.

Before ending each CRUD action, their ``KeyValueStore`` object is passed to a
method called ``configureResponseParameters()`` which you can override in your
own controller to add/remove/change those template variables::

    namespace App\Controller\Admin;

    use App\Entity\Product;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

    class ProductCrudController extends AbstractCrudController
    {
        // ...

        public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore
        {
            if (Crud::PAGE_DETAIL === $responseParameters->get('pageName')) {
                $responseParameters->set('foo', '...');

                // keys support the "dot notation", so you can get/set nested
                // values separating their parts with a dot:
                $responseParameters->setIfNotSet('bar.foo', '...');
                // this is equivalent to: $parameters['bar']['foo'] = '...'
            }

            return $responseParameters;
        }
    }

You can add as many or as few parameters to this ``KeyValueStore`` object as you
need. The only mandatory parameter is either ``templateName`` or
``templatePath`` to set respectively the name or path of the template to render
as the result of the CRUD action.

Template Names and Template Paths
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

All the templates used by EasyAdmin to render its contents are configurable.
That's why EasyAdmin deals with "template names" instead of normal Twig
template paths.

A template name is the same as the template path but without the ``@EasyAdmin``
prefix and the ``.html.twig`` suffix. For example, ``@EasyAdmin/layout.html.twig``
refers to the built-in layout template provided by EasyAdmin. However, ``layout``
refers to "whichever template is configured as the layout in the application".

Working with template names instead of paths gives you full flexibility to
customize the application behavior while keeping all the customized templates.
In Twig templates, use the ``ea.templatePath()`` function to get the Twig path
associated to the given template name:

.. code-block:: twig

    <div id="flash-messages">
        {{ include(ea.templatePath('flash_messages')) }}
    </div>

    {% if some_value is null %}
        {{ include(ea.templatePath('label/null')) }}
    {% endif %}

.. _crud-generate-urls:
.. _generate-admin-urls:

Generating Admin URLs
---------------------

.. versionadded:: 3.2

    The ``AdminUrlGenerator`` class was introduced in EasyAdmin 3.2.0. In earlier
    versions, you had to use the ``CrudUrlGenerator`` class.

:ref:`As explained <dashboard-route>` in the article about Dashboards, all URLs
of a given dashboard use the same route and they only differ in the query string
parameters. Instead of having to deal with that, you can use the ``AdminUrlGenerator``
service to generate URLs in your PHP code.

When generating a URL, you don't start from scratch. EasyAdmin reuses all the
query parameters existing in the current request. This allows generating because
generating new URLs based on the current URL is the most common scenario. Use
the ``unsetAll()`` method to remove all existing query parameters::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
    use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

    class SomeCrudController extends AbstractCrudController
    {
        // ...

        public function someMethod()
        {
            // if you prefer, you can inject the AdminUrlGenerator service in the
            // constructor and/or action of this controller
            $adminUrlGenerator = $this->get(AdminUrlGenerator::class);

            // the existing query parameters are maintained, so you only
            // have to pass the values you want to change.
            $url = $adminUrlGenerator->set('page', 2)->generateUrl();

            // you can remove existing parameters
            $url = $adminUrlGenerator->unset('menuIndex')->generateUrl();
            $url = $adminUrlGenerator->unsetAll()->set('foo', 'someValue')->generateUrl();

            // the URL builder provides shortcuts for the most common parameters
            $url = $adminUrlGenerator->build()
                ->setController(SomeCrudController::class)
                ->setAction('theActionName')
                ->generateUrl();

            // ...
        }
    }

.. _ea-url-function:

The exact same features are available in templates thanks to the ``ea_url()``
Twig function. In templates you can omit the call to the ``generateUrl()``
method (it will be called automatically for you):

.. code-block:: twig

    {# both are equivalent #}
    {% set url = ea_url({ page: 2 }).generateUrl() %}
    {% set url = ea_url({ page: 2 }) %}

    {% set url = ea_url().set('page', 2) %}

    {% set url = ea_url()
        .setController('App\\Controller\\Admin\\SomeCrudController')
        .setAction('theActionName') %}

Generating CRUD URLs from outside EasyAdmin
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

When generating URLs of EasyAdmin pages from outside EasyAdmin (e.g. from a
regular Symfony controller) the :ref:`admin context variable <admin-context>`
is not available. That's why you must always set the CRUD controller associated
to the URL. If you have more than one dashboard, you must also set the Dashboard::

    use App\Controller\Admin\DashboardController;
    use App\Controller\Admin\ProductCrudController;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
    use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

    class SomeSymfonyController extends AbstractController
    {
        private $adminUrlGenerator;

        public function __construct(AdminUrlGenerator $adminUrlGenerator)
        {
            $this->adminUrlGenerator = $adminUrlGenerator;
        }

        public function someMethod()
        {
            // if your application only contains one Dashboard, it's enough
            // to define the controller related to this URL
            $url = $this->adminUrlGenerator
                ->setController(ProductCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl();

            // in applications containing more than one Dashboard, you must also
            // define the Dashboard associated to the URL
            $url = $this->adminUrlGenerator
                ->setDashboard(DashboardController::class)
                ->setController(ProductCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl();

            // some actions may require to pass additional parameters
            $url = $this->adminUrlGenerator
                ->setController(ProductCrudController::class)
                ->setAction(Action::EDIT)
                ->setEntityId($product->getId())
                ->generateUrl();

            // ...
        }
    }

The same applies to URLs generated in Twig templates:

.. code-block:: twig

    {# if your application defines only one Dashboard #}
    {% set url = ea_url()
        .setController('App\\Controller\\Admin\\ProductCrudController')
        .setAction('index') %}
    {# if you prefer PHP constants, use this:
       .setAction(constant('EasyCorp\\Bundle\\EasyAdminBundle\\Config\\Action::INDEX')) #}

    {# if your application defines multiple Dashboards #}
    {% set url = ea_url()
        .setDashboard('App\\Controller\\Admin\\DashboardController')
        .setController('App\\Controller\\Admin\\ProductCrudController')
        .setAction('index') %}

    {# some actions may require to pass additional parameters #}
    {% set url = ea_url()
        .setController('App\\Controller\\Admin\\ProductCrudController')
        .setAction('edit')
        .setEntityId(product.id) %}

.. _`Symfony controllers`: https://symfony.com/doc/current/controller.html
.. _`Doctrine filters`: https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/filters.html
