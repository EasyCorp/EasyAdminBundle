CRUD Controllers
================

**CRUD controllers** provide the CRUD operations (create, read, update, delete)
for Doctrine ORM entities. Each CRUD controller can be associated with one or more
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
* ``new``, allows you to create new entity instances;
* ``edit``, allows you to update any property of a given entity.

These pages are generated with four actions with the same name in the
``AbstractCrudController`` controller. This controller defines other secondary
:ref:`built-in actions <actions-built-in>` (e.g. ``delete`` and ``autocomplete``)
which don't match any page.

.. _crud_routes:
.. _crud-routes:

CRUD Routes
~~~~~~~~~~~

Each of the CRUD actions defines an admin route following this name and path by default:

==================  ======================
CRUD route name     CRUD route path
==================  ======================
``*_index``         ``/``
``*_new``           ``/new``
``*_batch_delete``  ``/batch-delete``
``*_autocomplete``  ``/autocomplete``
``*_edit``          ``/{entityId}/edit``
``*_delete``        ``/{entityId}/delete``
``*_detail``        ``/{entityId}``
==================  ======================

For example, for a CRUD controller called ``ProductCrudController`` that belongs
to a backend with a route named ``admin`` and with the path ``/admin``, it will
generate the following routes:

==============================  ===============================
Admin route name                Admin route path
==============================  ===============================
``admin_product_index``         ``/admin/product``
``admin_product_new``           ``/admin/product/new``
``admin_product_batch_delete``  ``/admin/product/batch-delete``
``admin_product_autocomplete``  ``/admin/product/autocomplete``
``admin_product_edit``          ``/admin/product/324/edit``
``admin_product_delete``        ``/admin/product/324/delete``
``admin_product_detail``        ``/admin/product/324``
==============================  ===============================

.. tip::

    By default, EasyAdmin generates routes for all CRUD controllers on all
    dashboards. You can :ref:`restrict which controllers are accessible <security-controllers>`
    on each dashboard to not generate all these routes.

.. note::

    Because route names join the controller and action names with underscores, two
    different controllers can generate the same route name when one entity name is a
    prefix of another. For example, a ``FooCrudController`` (whose ``batchDelete`` action
    generates ``admin_foo_batch_delete``) collides with a ``FooBatchCrudController`` (whose
    ``delete`` action also generates ``admin_foo_batch_delete``). EasyAdmin detects this and
    throws a descriptive exception. To resolve it, use the ``#[AdminRoute]`` attribute (see
    below) to set a different ``name`` on one of the colliding controllers or actions.

You can customize the route names and/or paths of the actions of all the CRUD controllers
served by some dashboard using the ``routes`` option of the ``#[AdminDashboard]`` attribute::

    use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
    // ...

    #[AdminDashboard(routePath: '/admin', routeName: 'admin', routes: [
        'index' => ['routePath' => '/all'],
        'new' => ['routePath' => '/create', 'routeName' => 'create'],
        'edit' => ['routePath' => '/editing-{entityId}', 'routeName' => 'editing'],
        'delete' => ['routePath' => '/remove/{entityId}'],
        'detail' => ['routeName' => 'view'],
    ])]
    class SomeDashboardController extends AbstractDashboardController
    {
        // ...
    }

With this configuration, the routes for the ``ProductCrudController`` actions will be:

==============================  =====================================
Admin route name                Admin route path
==============================  =====================================
``admin_product_index``         ``/admin/product/all``
``admin_product_create``        ``/admin/product/create``
``admin_product_batch_delete``  ``/admin/product/batch-delete``
``admin_product_autocomplete``  ``/admin/product/autocomplete``
``admin_product_editing``       ``/admin/product/editing-324``
``admin_product_delete``        ``/admin/product/remove/324``
``admin_product_view``          ``/admin/product/324``
==============================  =====================================

.. tip::

    The route paths of the ``edit``, ``delete`` and ``detail`` actions must contain
    the ``{entityId}`` placeholder, which identifies the current entity. You can also
    use ``{id}`` as an alias of ``{entityId}`` (e.g. ``'detail' => ['routePath' => '/{id}']``).
    Both placeholders work the same in EasyAdmin, but ``{id}`` also allows Symfony
    to inject the entity as a typed controller argument automatically
    (see :ref:`custom actions <actions-custom>`).

You can also customize the path and/or route name of CRUD controllers using the
``#[AdminRoute]`` attribute with the following options:

* ``path``: the value that represents the controller in the entire route path
  (e.g. a ``/foo`` path here will result in a route with the path ``/admin`` + ``/foo`` + ``/<action>``);
* ``name``: the value that represents the controller in the full route name
  (e.g. a ``foo_bar`` name here will result in a route named ``admin_`` + ``foo_bar`` + ``_<action>``);
* ``options``: an array of additional options passed as is to the generated
  Symfony route (``requirements``, ``options``, ``defaults``, ``host``,
  ``methods``, ``schemes``, ``condition``, ``locale``, ``format``, ``utf8``
  and ``stateless``);
* ``allowedDashboards``: if set, this route is only available for the given
  dashboards (use ``null`` to explicitly allow all of them and ``[]`` to allow
  none);
* ``deniedDashboards``: if set, this route is not available for the given
  dashboards (use ``null`` or ``[]`` to exclude no dashboard).

The ``#[AdminRoute]`` attribute is repeatable, so you can apply it several
times to the same class or method. See :ref:`actions-integrating-symfony` for
more details about the ``options``, ``allowedDashboards`` and
``deniedDashboards`` options.

Using the same example as above, you can configure the route names and paths of
the controller as follows::

    use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
    // ...

    #[AdminRoute(path: '/stock/current', name: 'stock')]
    class ProductCrudController extends AbstractCrudController
    {
        // ...
    }

The route names/paths will no longer be ``admin_product_*`` and ``/admin/product/*``
but the following:

==============================  =====================================
Admin route name                Admin route path
==============================  =====================================
``admin_stock_index``           ``/admin/stock/current``
``admin_stock_new``             ``/admin/stock/current/new``
``admin_stock_batch_delete``    ``/admin/stock/current/batch-delete``
``admin_stock_autocomplete``    ``/admin/stock/current/autocomplete``
``admin_stock_edit``            ``/admin/stock/current/324/edit``
``admin_stock_delete``          ``/admin/stock/current/324/delete``
``admin_stock_detail``          ``/admin/stock/current/324``
==============================  =====================================

Finally, you can also customize the route name and/or path of each CRUD controller
action using the ``#[AdminRoute]`` attribute::

    use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
    use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
    use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
    use Symfony\Component\HttpFoundation\Response;
    // ...

    class ProductCrudController extends AbstractCrudController
    {
        // ...

        #[AdminRoute(path: '/latest-products', name: 'latest')]
        public function index(AdminContext $context): KeyValueStore|Response
        {
            // ...
        }
    }

The ``index()`` action of this controller will no longer use the ``admin_product_index``
route name and the ``/admin/product`` path in the URL. Instead, the route name
will be ``admin_product_latest`` and the path will be ``/admin/product/latest-products``.

.. tip::

    You can combine the ``#[AdminDashboard]`` and ``#[AdminRoute]``
    attributes to customize some or all route names and paths.

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

The rest of the CRUD options are configured using the ``configureCrud()`` method::

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

.. _crud-design-options:

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
.. _crud-entity-options:

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
            // can be a closure that defines two nullable arguments: entityInstance (which will
            // be null in 'index' and 'new' pages) and the current page name
            ->setEntityLabelInSingular(
                fn (?Product $product, ?string $pageName) => $product ? (string) $product : 'Product'
            )
            ->setEntityLabelInPlural(
                fn (?Product $product, ?string $pageName) => $product ? $product->getName() : 'Products'
            )

            // the Symfony Security permission needed to manage the entity
            // (none by default, so you can manage all instances of the entity)
            ->setEntityPermission('ROLE_EDITOR')
        ;
    }

Read the section about how to
:ref:`restrict access to entities <security-fields>` for more details about the
``setEntityPermission()`` method.

Title and Help Options
~~~~~~~~~~~~~~~~~~~~~~

By default, the page titles of the ``index`` and ``new`` pages are based on the
:ref:`entity option <crud-entity-options>` values defined with the
``setEntityLabelInSingular()`` and ``setEntityLabelInPlural()`` methods. In the
``detail`` and ``edit`` pages, EasyAdmin tries first to convert the entity into
a string representation and falls back to a generic title otherwise.

You can override the default page titles with the following methods::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // the visible title at the top of the page and the content of the <title> element
            // it can include these placeholders:
            //   %entity_name%, %entity_as_string%,
            //   %entity_id%, %entity_short_id%
            //   %entity_label_singular%, %entity_label_plural%
            ->setPageTitle('index', '%entity_label_plural% listing')

            // you can pass a PHP closure as the value of the title
            ->setPageTitle('new', fn () => new \DateTime('now') > new \DateTime('today 13:00') ? 'New dinner' : 'New lunch')

            // in DETAIL and EDIT pages, the closure receives the current entity
            // as the first argument
            ->setPageTitle('detail', fn (Product $product) => (string) $product)
            ->setPageTitle('edit', fn (Product $product) => sprintf('Editing <b>%s</b>', $product->getName()))

            // the help message displayed to end users (it can contain HTML tags)
            ->setHelp('edit', '...')
        ;
    }

EasyAdmin applies the ``raw`` filter to all titles, labels, help messages, etc.
displayed in templates. This is done to allow you to customize everything with
HTML tags (because those tags will be rendered instead of escaped).

That's why the default page titles used by EasyAdmin only include safe contents
like the entity name and ID. Otherwise, your backend could be vulnerable to
`XSS attacks`_.

If you change the default page title to include the placeholder ``%entity_as_string%``,
check that you don't include user-created contents in the value returned by the
``__toString()`` method of the related entity. If you can't avoid that, make sure
to sanitize any user submitted data with the Symfony `HtmlSanitizer component`_.

.. _crud-date-time-number-format-options:

Date, Time and Number Formatting Options
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // the argument must be either one of these strings: 'short', 'medium', 'long', 'full'
            // (the strings are also available as \EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField::FORMAT_* constants)
            // or a valid ICU Datetime Pattern (see https://unicode-org.github.io/icu/userguide/format_parse/datetime/)
            ->setDateFormat('...')
            ->setTimeFormat('...')

            // first argument = datetime pattern or date format; second optional argument = time format
            // unlike the two methods above, both arguments also accept the 'none' format
            ->setDateTimeFormat('...', '...')

            ->setTimezone('...')

            // this option renders numeric values with a sprintf()
            // call using this value as the first argument.
            // this option overrides any formatting option for all numeric values
            // (e.g. setNumDecimals(), setRoundingMode(), etc. are ignored)
            // NumberField and IntegerField can override this value with their
            // own setNumberFormat() methods, which work in the same way
            ->setNumberFormat('%.2f')

            // Sets the character used to separate each thousand group in a number
            // e.g. if separator is ',' then 12345 is formatted as 12,345
            // By default, EasyAdmin doesn't add any thousands separator to numbers;
            // NumberField and IntegerField can override this value with their
            // own setThousandsSeparator() methods, which work in the same way
            ->setThousandsSeparator(',')

            // Sets the character used to separate the decimal part of a non-integer number
            // e.g. if separator is '.' then 1/10 is formatted as 0.1
            // by default, EasyAdmin displays the default decimal separator used by PHP;
            // NumberField and IntegerField can override this value with their
            // own setDecimalSeparator() methods, which work in the same way
            ->setDecimalSeparator('.')
        ;
    }

.. _crud-search-sort-pagination:

Search, Order, and Pagination Options
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // ...

            // the Doctrine entity properties to search in
            // (by default, all properties are searched)
            ->setSearchFields(['name', 'description'])
            // use dots (e.g. 'seller.email') to search in Doctrine associations
            ->setSearchFields(['name', 'description', 'seller.email', 'seller.address.zipCode'])
            // set it to null to disable and hide the search box
            ->setSearchFields(null)
            // call this method to focus the search input automatically when loading the 'index' page
            ->setAutofocusSearch()

            // by default, the search results match all the terms (SearchMode::ALL_TERMS):
            // term1 in (field1 or field2) AND term2 in (field1 or field2)
            // e.g. if you look for 'lorem ipsum' in [title, description],
            // results require matching 'lorem' in either title or description
            // (or both) AND 'ipsum' in either title or description (or both)
            ->setSearchMode(SearchMode::ALL_TERMS)

            // use the SearchMode::ANY_TERMS option to change the search mode to
            // match at least one of the terms:
            // term1 in (field1 or field2) OR term2 in (field1 or field2)
            // e.g. if you look for 'lorem ipsum' in [title, description],
            // results will match either 'lorem' in title or description (or both)
            // OR 'ipsum' in title or description (or both)
            ->setSearchMode(SearchMode::ANY_TERMS)
        ;
    }

.. tip::

    The search engine splits all terms by default (searching for ``foo bar``
    returns items with ``foo`` and ``bar``). You can wrap all or part of your
    query with quotes to make an exact search: ``"foo bar"`` only returns
    items with that exact content, including the middle white space.

::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // ...

            // defines the initial sorting applied to the list of entities
            // (users can later change this sorting by clicking on the table columns)
            ->setDefaultSort(['id' => 'DESC'])
            ->setDefaultSort(['id' => 'DESC', 'title' => 'ASC', 'startsAt' => 'DESC'])
            // you can sort by nested Doctrine associations of any depth
            ->setDefaultSort(['seller.name' => 'ASC'])
            ->setDefaultSort(['seller.address.country' => 'ASC'])
        ;
    }

::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // ...

            // the max number of entities to display per page
            ->setPaginatorPageSize(30)
            // the number of pages to display on each side of the current page
            // e.g. if num pages = 35, current page = 7 and you set ->setPaginatorRangeSize(4)
            // the paginator displays: [Previous]  1 ... 3  4  5  6  [7]  8  9  10  11 ... 35  [Next]
            // set this number to 0 to display a simple "< Previous | Next >" pager
            ->setPaginatorRangeSize(4)

            // these are advanced options related to Doctrine Pagination
            // (see https://www.doctrine-project.org/projects/doctrine-orm/en/current/tutorials/pagination.html)
            ->setPaginatorUseOutputWalkers(true)
            ->setPaginatorFetchJoinCollection(true)
        ;
    }

.. tip::

    The pagination UI of the ``index`` page is rendered with a
    :ref:`custom Twig component <using-easyadmin-twig-components>` called
    ``<twig:ea:Pagination>``, which you can also use in your own pages
    and customize via its props and override blocks:

    .. code-block:: twig

        {# pass an EasyAdmin paginator object (e.g. when overriding backend templates) #}
        <twig:ea:Pagination paginator="{{ paginator }}"/>

        {# or pass raw values to use it anywhere, even outside EasyAdmin backends;
           'urlPattern' is required and must contain the '{page}' placeholder #}
        <twig:ea:Pagination currentPage="42" totalItems="1567" pageSize="25" urlPattern="?page={page}"/>

.. note::

    When using `Doctrine filters`_, listings may not include some items because
    they were removed by those global Doctrine filters. Use the dashboard route
    name to avoid applying those filters when the request URL belongs to the dashboard.
    You can also get the dashboard route name via the :ref:`application context variable <admin-context>`.

The default Doctrine query executed to get the list of entities displayed in the
``index`` page takes into account the sorting configuration, the optional search
query, the optional :doc:`filters </filters>` and the pagination. If you need to
fully customize this query, override the ``createIndexQueryBuilder()`` method in
your CRUD controller.

.. _crud-autocomplete:

Autocomplete Options
~~~~~~~~~~~~~~~~~~~~

:doc:`Association fields </fields/AssociationField>` allow you to
:ref:`customize the autocomplete display <field-association-autocomplete>` per field.
You can also set a default autocomplete display for all association fields in a
CRUD controller. This default display is applied to all fields that don't
configure their own autocomplete, giving them a consistent formatting.

There are two ways of configuring the autocomplete: using a **callback** (useful for
simple formatting) and using a **Twig template** (allowing you to use HTML tags for
more advanced formatting).

**1) Using a Callback**

Define a callback that applies to all autocomplete fields::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->autocomplete(
                callback: static fn ($entity): string => method_exists($entity, 'getFullName') ? $entity->getFullName() : (string) $entity
            )
        ;
    }

The ``autocomplete()`` method also accepts an ``enable`` parameter to apply
this default configuration conditionally. When ``enable`` is ``false``,
EasyAdmin ignores the ``callback``, ``template`` and ``renderAsHtml``
arguments. It does not turn the autocomplete widget on or off, which is
configured per field::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->autocomplete(
                enable: $this->isGranted('ROLE_ADMIN'),
                callback: static fn ($entity): string => (string) $entity
            )
        ;
    }

**2) Using a Twig Template**

Define a default template for all autocomplete fields::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->autocomplete(
                template: 'admin/autocomplete/default.html.twig',
                renderAsHtml: false
            )
        ;
    }

The template receives the entity as the ``entity`` variable. When
``renderAsHtml`` is ``false`` (the default), the output is escaped to
prevent XSS attacks. Set it to ``true`` only when you trust the content
and need to display HTML.

Templates and Form Options
~~~~~~~~~~~~~~~~~~~~~~~~~~

::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // this method allows you to use your own template to render a certain part
            // of the backend instead of using EasyAdmin default template
            // the first argument is the "template name", which is the same as the
            // Twig path but without the `@EasyAdmin/` prefix and the `.html.twig` suffix
            ->overrideTemplate('crud/field/id', 'admin/fields/my_id.html.twig')

            // the theme/themes to use when rendering the forms of this entity
            // (in addition to EasyAdmin default theme)
            ->addFormTheme('foo.html.twig')
            // this method overrides all existing form themes (including the
            // default EasyAdmin form theme)
            ->setFormThemes(['my_theme.html.twig', 'admin.html.twig'])

            // this sets the options of the entire form (later, you can set the options
            // of each form type via the methods of their associated fields)
            // pass a single array argument to apply the same options for the new and edit forms
            ->setFormOptions([
                'validation_groups' => ['Default', 'my_validation_group']
            ])

            // pass two array arguments to apply different options for the new and edit forms
            // (pass an empty array argument if you want to apply no options to some form)
            ->setFormOptions(
                ['validation_groups' => ['my_validation_group']],
                ['validation_groups' => ['Default'], '...' => '...'],
            )
        ;
    }

Read the section about how to
:ref:`override EasyAdmin templates <template-customization>` for more details
about the ``overrideTemplate()`` method.

.. _default-row-action:

Default Row Action
~~~~~~~~~~~~~~~~~~

By default, when you click on any row of the ``index`` page, you navigate to
the ``edit`` page of that entity. If the ``edit`` action is not available, it
falls back to the ``detail`` action. This behavior is called the "default row action"
and you can configure it with the ``setDefaultRowAction()`` method::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // this is the default behavior: first try 'edit', then fallback to 'detail'
            ->setDefaultRowAction([Action::EDIT, Action::DETAIL])

            // use a single action (no fallback)
            ->setDefaultRowAction(Action::EDIT)

            // navigate to 'detail' only
            ->setDefaultRowAction(Action::DETAIL)

            // use any action name, including custom actions
            ->setDefaultRowAction('review')

            // define a custom fallback chain (first available action wins)
            ->setDefaultRowAction([Action::DETAIL, 'preview', Action::EDIT])

            // pass null to disable the row click behavior entirely
            ->setDefaultRowAction(null)
        ;
    }

The values of the ``Action`` constants used above are listed in the section
about :ref:`action names and constants <action-names>`.

.. note::

    If none of the configured actions (in the fallback chain) are available for
    some entity (disabled action, no permission, or condition not met), the row
    won't be clickable for that entity. This also applies to actions defined
    inside :ref:`action groups <actions-grouping>`.

.. tip::

    The default row action can be configured
    :ref:`globally in your dashboard <crud-shared-config>` (so it applies to all
    CRUD controllers) and overridden in specific CRUD controllers::

        // in your Dashboard
        public function configureCrud(): Crud
        {
            return Crud::new()
                // all CRUD controllers will navigate to 'detail' by default
                ->setDefaultRowAction(Action::DETAIL)
            ;
        }

        // in a specific CRUD controller
        public function configureCrud(Crud $crud): Crud
        {
            return $crud
                // only this CRUD controller will navigate to 'edit'
                ->setDefaultRowAction(Action::EDIT)
            ;
        }

.. tip::

    By default, clicking on a row executes the default row action
    (this is the ``ClickTrigger::SINGLE`` trigger). If you prefer to require a
    double click instead, configure this behavior with the
    ``setDefaultRowActionTrigger()`` method::

        use EasyCorp\Bundle\EasyAdminBundle\Config\Option\ClickTrigger;
        // ...

        public function configureCrud(Crud $crud): Crud
        {
            return $crud
                // require a double click to execute the default row action
                ->setDefaultRowActionTrigger(ClickTrigger::DOUBLE)
            ;
        }

The row click behavior is fully accessible via keyboard (using Enter or Space keys).
Clicks on checkboxes, buttons, links, or any action elements within the row won't
trigger the navigation to preserve the expected behavior of those elements.
Also, rows selected in :ref:`batch mode <batch-actions>` won't navigate when
clicked.

Other Options
~~~~~~~~~~~~~

::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // by default, when the value of some field is `null`, EasyAdmin displays
            // a label with the `null` text. You can change that by overriding
            // the `label/null` template. However, if you have lots of `null` values
            // and want to simplify your backend display, you can use
            // the following option to not display anything when some value is `null`
            // (this option is applied both in the `index` and `detail` pages)
            ->hideNullValues()
        ;
    }

Custom Redirect After Creating or Editing Entities
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

By default, when you click the "Save" button when creating or editing entities
you are redirected to the previous page. If you want to change this behavior,
override the ``getRedirectResponseAfterSave()`` method.

For example, if you've added a :ref:`custom action <actions-custom>` called
"Save and view detail", you may prefer to redirect to the detail page after
saving the changes::

    protected function getRedirectResponseAfterSave(AdminContext $context, string $action): RedirectResponse
    {
        $submitButtonName = $context->getRequest()->request->all()['ea']['newForm']['btn'];

        if ('saveAndViewDetail' === $submitButtonName) {
            return $this->redirectToRoute('admin_product_detail', [
                'entityId' => $context->getEntity()->getPrimaryKeyValue(),
            ]);
        }

        return parent::getRedirectResponseAfterSave($context, $action);
    }

Read the section about :ref:`generating admin URLs <generate-admin-urls>` to
learn about the other ways of building the URL passed to ``redirectToRoute()``.

.. _crud-shared-config:

Same Configuration in Different CRUD Controllers
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you want to do the same config in all CRUD controllers, there's no need to
repeat the config in each controller. Instead, add the ``configureCrud()`` method
in your dashboard and all controllers will inherit that configuration::

    use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

    #[AdminDashboard(routePath: '/admin', routeName: 'admin')]
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

Fields allow you to display the contents of your Doctrine entities on each
:ref:`CRUD page <crud-pages>`. EasyAdmin provides built-in fields to display
all the common data types, but you can also :ref:`create your own fields <custom-fields>`.

If your CRUD controller extends from the ``AbstractCrudController``, the fields
are configured automatically. In the ``index`` page you'll see a few fields and
in the rest of the pages you'll see as many fields as needed to display all the
properties of your Doctrine entity.

Read the :doc:`chapter about Fields </fields>` to learn how to configure which
fields to display on each page, how to configure the way each field is rendered, etc.

Customizing CRUD Actions
------------------------

The default :ref:`built-in actions <actions-built-in>` (``index()``, ``detail()``,
``edit()``, ``new()`` and ``delete()`` methods in the controller) implement the
most common behaviors used in applications.

The first way to customize their behavior is to override those methods in your
own controllers. However, the original actions are so generic that they contain
quite a lot of code, so overriding them can be tedious.

Instead, you can override other smaller methods that implement certain features
needed by the CRUD actions. For example, the ``index()`` action calls a
method named ``createIndexQueryBuilder()`` to create the Doctrine query builder
used to get the results displayed on the index listing. If you want to customize
that listing, it's better to override the ``createIndexQueryBuilder()`` method
instead of the entire ``index()`` method. There are many of these methods, so
you should check the ``EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController`` class.

Another way to customize CRUD actions is to use the
:doc:`events triggered by EasyAdmin </events>`, such as ``BeforeCrudActionEvent``
and ``AfterCrudActionEvent``.

Creating, Persisting and Deleting Entities
------------------------------------------

Most of the actions of a CRUD controller end up creating, persisting or deleting
entities. If your CRUD controller extends from the ``AbstractCrudController``,
these methods are already implemented, but you can customize them by overriding
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

        public function createEntity(string $entityFqcn): object
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

Instead, CRUD actions return an ``EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore``
object with the variables passed to the template that renders the CRUD action
contents. This ``KeyValueStore`` object is similar to Symfony's ``ParameterBag``
object. It's like an object-oriented array with useful methods such as ``get()``,
``set()``, ``has()``, etc.

Before each CRUD action ends, its ``KeyValueStore`` object is passed to a
method called ``configureResponseParameters()`` which you can override in your
own controller to add/remove/change those template variables::

    namespace App\Controller\Admin;

    use App\Entity\Product;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
    use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
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

.. _template-names:

Template Names and Template Paths
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

All the templates used by EasyAdmin to render its contents are
:ref:`configurable <template-customization>`. That's why EasyAdmin deals with
"template names" instead of normal Twig template paths.

A template name is the same as the template path but without the ``@EasyAdmin``
prefix and the ``.html.twig`` suffix. For example, ``@EasyAdmin/layout.html.twig``
refers to the built-in layout template provided by EasyAdmin. However, ``layout``
refers to "whichever template is configured as the layout in the application".

Working with template names instead of paths means you can replace a template
once, and every part of EasyAdmin that renders it uses your version.
In Twig templates, use the ``ea().templatePath()`` method to get the Twig path
associated with the given template name:

.. code-block:: twig

    <div id="flash-messages">
        {{ include(ea().templatePath('flash_messages')) }}
    </div>

    {% if some_value is null %}
        {{ include(ea().templatePath('label/null')) }}
    {% endif %}

.. _crud-generate-urls:
.. _generate-admin-urls:

Generating Admin URLs
---------------------

EasyAdmin generates one route for each CRUD action of each :doc:`dashboard </dashboards>`.
You can list them all with the following command:

.. code-block:: terminal

    $ php bin/console debug:router

If you don't see some or any of your admin routes, clear the cache of your
Symfony application so the EasyAdmin route loader can generate them again:

.. code-block:: terminal

    $ php bin/console cache:clear

You can use any of these routes to generate the admin URLs thanks to the
`utilities provided by Symfony to generate URLs`_::

    // redirecting to an admin URL inside a controller
    return $this->redirectToRoute('admin_product_new');

    // generating an admin URL inside a service
    $userProfileUrl = $this->router->generate('admin_user_detail', [
        'entityId' => $user->getId(),
    ]);

And in Twig templates:

.. code-block:: twig

    {# generating an admin URL in a Twig template #}
    <a href="{{ path('admin_blog_post_edit', {entityId: post.id}) }}">Edit Blog Post</a>

Building Admin URLs
~~~~~~~~~~~~~~~~~~~

The ``AdminUrlGenerator`` helps you build backend URLs dynamically. This is needed
e.g. when the controller/action parts of the URL are stored in variables and you
can't know the route name beforehand.

When you generate a URL this way, you don't start from an empty URL. EasyAdmin reuses
all the query parameters existing in the current request. This is done on purpose
because generating new URLs based on the current URL is the most common scenario.
Use the ``unsetAll()`` method to remove all existing query parameters::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
    use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

    class SomeCrudController extends AbstractCrudController
    {
        public function __construct(
            private AdminUrlGenerator $adminUrlGenerator,
        ) {
        }

        // ...

        public function someMethod()
        {
            // instead of injecting the AdminUrlGenerator service in the constructor,
            // you can also get it from inside a controller action as follows:
            // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

            // the existing query parameters are maintained, so you only
            // have to pass the values you want to change.
            $url = $this->adminUrlGenerator->set('page', 2)->generateUrl();
            // use setAll() to set several parameters at once
            $url = $this->adminUrlGenerator->setAll(['page' => 2, 'foo' => 'bar'])->generateUrl();

            // read the current value of any parameter (returns null if it's not set)
            $currentPage = $this->adminUrlGenerator->get('page');

            // you can remove existing parameters
            $url = $this->adminUrlGenerator->unset('page')->generateUrl();
            $url = $this->adminUrlGenerator->unsetAll()->set('foo', 'someValue')->generateUrl();
            // unsetAllExcept() removes all parameters except the given ones
            $url = $this->adminUrlGenerator->unsetAllExcept('page', 'filters')->generateUrl();

            // the URL builder provides shortcuts for the most common parameters
            $url = $this->adminUrlGenerator
                ->setController(SomeCrudController::class)
                ->setAction('theActionName')
                ->generateUrl();

            // use setRoute() to generate the URL of any Symfony route; it removes
            // all existing parameters except the dashboard the URL belongs to
            $url = $this->adminUrlGenerator
                ->setRoute('some_route_name', ['someParameter' => 'someValue'])
                ->generateUrl();

            // ...
        }
    }

.. tip::

    If you need to deal with the admin URLs manually for any reason, the names
    of the query string parameters are defined as constants in the
    ``EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA`` class.

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

When the URL points to an action that operates on a specific entity (e.g. ``edit``,
``detail`` or a custom action), set the entity identifier with the ``setEntityId()``
method. You can also use ``setId()``, a more concise alias of ``setEntityId()``
(both work no matter if the route path uses the ``{entityId}`` placeholder or
its ``{id}`` alias):

.. code-block:: twig

    {% set url = ea_url()
        .setController('App\\Controller\\Admin\\SomeCrudController')
        .setAction('detail')
        .setId(product.id) %}

.. note::

    The ``setId()`` method is not part of ``AdminUrlGeneratorInterface`` yet (it will
    be added in the next major version of EasyAdmin). If you generate URLs in PHP code
    and type-hint the ``AdminUrlGeneratorInterface``, use ``setEntityId()`` instead.

Generating CRUD URLs From Outside EasyAdmin
...........................................

When generating URLs of EasyAdmin pages from outside EasyAdmin (e.g. from a
regular Symfony controller) the :ref:`admin context variable <admin-context>`
is not available. That's why you must always set the CRUD controller associated
with the URL. If you have more than one dashboard, you must also set the Dashboard::

    use App\Controller\Admin\DashboardController;
    use App\Controller\Admin\ProductCrudController;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
    use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

    class SomeSymfonyController extends AbstractController
    {
        public function __construct(
            private AdminUrlGenerator $adminUrlGenerator,
        ) {
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
            // define the Dashboard associated with the URL
            $url = $this->adminUrlGenerator
                ->setDashboard(DashboardController::class)
                ->setController(ProductCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl();

            // some actions require additional parameters
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

    {# some actions require additional parameters #}
    {% set url = ea_url()
        .setController('App\\Controller\\Admin\\ProductCrudController')
        .setAction('edit')
        .setEntityId(product.id) %}

.. _`Symfony controllers`: https://symfony.com/doc/current/controller.html
.. _`Doctrine filters`: https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/filters.html
.. _`XSS attacks`: https://en.wikipedia.org/wiki/Cross-site_scripting
.. _`HtmlSanitizer component`: https://symfony.com/components/HTML%20Sanitizer
.. _`utilities provided by Symfony to generate URLs`: https://symfony.com/doc/current/routing.html#generating-urls
