CRUD Controllers
================

.. raw:: html

    <div class="box box--small box--warning">
        <strong class="title">WARNING:</strong>

        You are browsing the documentation for <strong>EasyAdmin 3.x</strong>,
        which hasn't been released as a stable version yet. You are probably
        using EasyAdmin 2.x in your application, so you can switch to
        <a href="https://symfony.com/doc/2.x/bundles/EasyAdminBundle/index.html">EasyAdmin 2.x docs</a>.
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

Entity Options
~~~~~~~~~~~~~~

::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // the labels used to refer to this entity in titles, buttons, etc.
            ->setEntityLabelInSingular('Product')
            ->setEntityLabelInPlural('Products')

            // the Symfony Security permission needed to manage the entity
            // (none by default, so you can manage all instances of the entity)
            ->setEntityPermission('ROLE_EDITOR')
        ;
    }

Title and Help Options
~~~~~~~~~~~~~~~~~~~~~~

::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // the visible title at the top of the page and the content of the <title> element
            // it can include these placeholders: %entity_id%, %entity_label_singular%, %entity_label_plural%
            ->setPageTitle('index', '%entity_label_plural% listing')

            // the help message displayed to end users (it can contain HTML tags)
            ->setHelpMessage('edit', '...')
        ;
    }

Date, Time and Number Formatting Options
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // the argument must be either one of these strings: 'short', 'medium', 'long', 'full'
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
            ->setSearchFields(['name', 'description', 'seller.email', 'seller.phone'])
            // set it to null to disable and hide the search box
            ->setSearchFields(null);

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
            // Twig path but without the `@EasyAdmin/` prefix
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
            ->formOptions([
                'validation_groups' => ['Default', 'my_validation_group']
            ]);

            // pass two array arguments to apply different options for the new and edit forms
            // (pass an empty array argument if you want to apply no options to some form)
            ->formOptions(
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
used to get the results dispalyed on the index listing. If you want to customize
that listing, it's better to override the ``createIndexQueryBuilder()`` method
instead of the entire ``index()`` method. There are many of these methods, so
you should check the :class:`EasyCorp\\Bundle\\EasyAdminBundle\\Controller\\AbstractCrudController` class.

The other alternative to customize CRUD actions is to use the
:doc:`events triggered by EasyAdmin </events>`, such as ``BeforeCrudActionEvent``
and ``AfterCrudActionEvent``.

Creating, Persisting and Deleting Entities
------------------------------------------

Most of the actions of a CRUD controller end up creating, persisting or deleting
entities. If your CRUD controller extends from the ``AbstractCrudController``,
these methods are already implemented, but you can customize them overriding
methods and listening to events.

First, you can override the ``createEntity()``, ``updateEntity()``, persistEntity()``
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

Instead, CRUD actions return a :class:`EasyCorp\\Bundle\\EasyAdminBundle\\Config\\KeyValueStore`
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

.. _crud-generate-urls:

Generating CRUD URLs
--------------------

:ref:`As explained <dashboard-route>` in the article about Dashboards, all URLs
of a given dashboard use the same route and they only differ in the query string
parameters. Instead of having to deal with that, you can use the ``CrudUrlGenerator``
service to generate URLs in your PHP code::

    use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;

    class SomeClass
    {
        private $crudUrlGenerator;

        public function __construct(CrudUrlGenerator $crudUrlGenerator)
        {
            $this->crudUrlGenerator = $crudUrlGenerator;
        }

        public function someMethod()
        {
            // new URLs are generated starting from the current URL, but you can add,
            // change or remove parameters from the current URL with the given methods

            // the constructor argument is the new value of the given query parameters
            // the rest of existing query parameters are maintained, so you only
            // have to pass the values you want to change.
            $url = $this->crudUrlGenerator->build(['page' => 2])->generateUrl();

            // this other syntax is also possible
            $url = $this->crudUrlGenerator->build()->set('page', 2)->generateUrl();

            // you can remove existing parameters
            $url = $this->crudUrlGenerator->build()->unset('menuIndex')->generateUrl();
            $url = $this->crudUrlGenerator->build()->unsetAll()->set('foo', 'someValue')->generateUrl();

            // the URL builder provides shortcuts for the most common parameters
            $url = $this->crudUrlGenerator->build()
                ->setCrudFqcn(SomeController::class)
                ->setAction('theActionName')
                ->generateUrl();

            // ...
        }
    }

The exact same features are available in templates thanks to the ``ea_url()``
Twig function. In templates you can omit the call to the ``generateUrl()``
method (it will be called automatically for you):

.. code-block:: twig

    {# both are equivalent #}
    {% set url = ea_url({ page: 2 }).generateUrl() %}
    {% set url = ea_url({ page: 2 }) %}

    {% set url = ea_url().set('page', 2) %}

    {% set url = ea_url()
        .setCrudFqcn('App\\Controller\\Admin\\SomeController')
        .setAction('theActionName') %}

.. _`Symfony controllers`: https://symfony.com/doc/current/controller.html
.. _`How to Create a Custom Form Field Type`: https://symfony.com/doc/current/cookbook/form/create_custom_field_type.html
.. _`Symfony Form types`: https://symfony.com/doc/current/reference/forms/types.html
.. _`customize individual form fields`: https://symfony.com/doc/current/form/form_customization.html#how-to-customize-an-individual-field
.. _`form fragment naming rules`: https://symfony.com/doc/current/form/form_themes.html#form-template-blocks
.. _`override any part of third-party bundles`: https://symfony.com/doc/current/bundles/override.html
.. _`Trix editor`: https://trix-editor.org/
.. _`Symfony security voters`: https://symfony.com/doc/current/security/voters.html
.. _`form data transformer`: https://symfony.com/doc/current/form/data_transformers.html
.. _`Doctrine filters`: https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/filters.html
