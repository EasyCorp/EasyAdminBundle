Dashboard Widgets
=================

**Widgets** are the boxes displayed on the main page of a
:doc:`dashboard </dashboards>`: key numbers, charts, progress bars, lists of
recent items or any contents rendered by your own Twig templates. You define
them in PHP, in the dashboard controller, and EasyAdmin places them in a
responsive grid, checks their permissions, and can load them later, cache
them and reload them periodically.

.. versionadded:: 5.11

    Dashboard widgets were introduced in EasyAdmin 5.11.

Quick Start
-----------

Add the ``configureWidgets()`` method to your dashboard controller and
``yield`` one object for each widget::

    // src/Controller/Admin/DashboardController.php
    namespace App\Controller\Admin;

    use App\Repository\OrderRepository;
    use App\Repository\UserRepository;
    use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
    use EasyCorp\Bundle\EasyAdminBundle\Widget\ChartWidget;
    use EasyCorp\Bundle\EasyAdminBundle\Widget\StatWidget;

    #[AdminDashboard(routePath: '/admin', routeName: 'admin')]
    class DashboardController extends AbstractDashboardController
    {
        public function __construct(
            private UserRepository $userRepository,
            private OrderRepository $orderRepository,
        ) {
        }

        public function configureWidgets(): iterable
        {
            yield StatWidget::new('Customers')
                ->setValue(fn () => $this->userRepository->count([]));

            yield ChartWidget::new('Orders per month')
                ->setData(fn () => $this->orderRepository->countPerMonth());
        }

        // ...
    }

The ``index()`` method of ``AbstractDashboardController`` renders the
``@EasyAdmin/page/dashboard.html.twig`` template, which displays the
widgets. You don't have to change ``index()``: if your dashboard defines it,
make it ``return parent::index();``.

EasyAdmin displays its default Welcome Page only when your dashboard doesn't
override the ``configureWidgets()`` method. When the method is overridden
but the current user can't see any of its widgets, the page displays a
message saying that there are no widgets.

The dashboard page can only be displayed through the route that EasyAdmin
generates for the dashboard. When a dashboard defines widgets and a request
matches another route that runs its ``index()`` method (e.g. a ``#[Route]``
attribute of your own), ``index()`` returns a ``404`` error.

.. tip::

    The ``configureWidgets()`` method runs on every request that displays
    the dashboard or loads one of its widgets. Keep it fast: don't run
    database queries in it. Run them inside closures (like in the example
    above), because EasyAdmin calls them only when it renders the widget.
    Also cache as many widgets as you can with ``setCache()``, so most
    requests don't run those queries at all (see
    :ref:`widgets-lazy-cache-refresh`).

Options Shared by All Widgets
-----------------------------

All widget classes use the static ``new()`` method to create the widget.
Its only argument is the label displayed in the header of the widget (a
string, a translatable object or ``null``). Labels and help messages are
translated with the :ref:`translation domain <translation-domain>` of the
dashboard. These are the options of every widget:

``setName(string $name)``
    The name identifies the widget in templates, in the URL used to load it
    and in its cache keys. It can only contain letters, numbers, dashes and
    underscores. By default, it's the slug of the label (e.g.
    ``latest-orders`` for ``'Latest orders'``). When the label isn't a
    string (e.g. a translatable object) or it's empty, the name is
    ``widget-<position>`` (e.g. ``widget-3``). That name changes when you add
    or remove widgets, so widgets that use ``setLazy()``, ``setCache()`` or
    ``setRefreshInterval()`` must call ``setName()`` in that case. Two
    widgets of the same dashboard can't have the same name.
``setIcon(?string $icon)``
    The icon displayed before the label (e.g. ``'fa fa-users'``).
``setHelp(TranslatableInterface|string $help)``
    A short text displayed under the label.
``setLink(string $url, TranslatableInterface|string|null $label = null)``
    A link displayed at the end of the header (e.g. to the page that lists
    all the items). Without a label, the link only displays an arrow icon.
``setPermission(string|Expression $permission)``
    The widget is hidden for the users who don't have this permission (see
    :ref:`widgets-security`).
``setColumns(int $columns)``
    The number of columns (from 1 to 12) that the widget takes in the
    12-column grid (see :ref:`widgets-layout`).
``setRows(int $rows)``
    The number of grid rows (from 1 to 6) that the widget takes in the
    12-column grid (see :ref:`widgets-layout`). By default, the height of a
    widget is the height of its contents.
``setLazy(bool $lazy = true)``
    Displays a placeholder and loads the contents in a separate request
    (see :ref:`widgets-lazy-cache-refresh`).
``setCache(int $seconds, bool $shared = false)``
    Caches the rendered contents for the given number of seconds (1 or
    more). Once called, there's no way to turn off the cache of that
    widget.
``setRefreshInterval(int $seconds)``
    Reloads the contents periodically. The interval must be between 10 and
    86400 seconds (one day). Once called, there's no way to turn off the
    refresh of that widget.
``setCssClass(string $cssClass)`` and ``addCssClass(string $cssClass)``
    ``setCssClass()`` replaces the custom CSS classes of the widget and
    ``addCssClass()`` adds a class to them. Neither of them removes the CSS
    classes that EasyAdmin adds to all widgets.
``setTemplatePath(string $path)`` and ``setTemplateName(string $name)``
    The Twig template that renders the contents of the widget (see
    :ref:`widgets-templates`).
Asset methods
    ``addCssFiles()``, ``addJsFiles()``, ``addAssetMapperEntries()``,
    ``addWebpackEncoreEntries()``, ``addRepriseEntries()``,
    ``addHtmlContentsToHead()`` and ``addHtmlContentsToBody()`` add the web
    assets that the widget needs. They work like the similar methods of
    :ref:`the dashboard assets <crud-design-custom-web-assets>`, but they
    accept several values and they are loaded only on the page that
    displays the widgets (see :ref:`widgets-custom`). Except for the HTML
    methods, they accept paths or entry names and also ``Asset`` objects
    (e.g. ``Asset::new('stock.js')->package('acme_widgets')->defer()``).
``setPlacement(WidgetPlacement $placement)``
    Use ``WidgetPlacement::Toolbar`` to display the widget next to the page
    title instead of in the grid (see :ref:`widgets-toolbar`).
``renderWithoutCard(bool $withoutCard = true)``
    Displays only the contents of the widget, without the card that
    surrounds them and without its header (label, icon, help and link).
    ``StatWidget`` displays its label inside the stat in that case.
``setCustomOption(string $optionName, mixed $optionValue)``
    Stores a configuration value that the template of the widget reads
    with ``widget.customOption('...')`` (see :ref:`widgets-custom`).

.. caution::

    The labels, help messages and link labels of widgets and sections can
    contain HTML contents and they are not escaped. Don't include in them
    any value entered by users. The values displayed inside the widgets
    (stat values, list items, etc.) are escaped.

.. _widgets-values-and-closures:

Values and Closures
-------------------

The options that define what a widget displays (the value of a stat, the
items of a list, the data of a chart, etc.) accept the value itself or a
closure (or an invokable object) that returns it::

    // the value is computed when configureWidgets() runs
    yield StatWidget::new('Products')->setValue($this->productRepository->count([]));

    // the value is computed only when the widget is rendered
    yield StatWidget::new('All products')
        ->setValue(fn () => $this->productRepository->count([]));

    // first-class callables are closures too
    yield StatWidget::new('Published products')
        ->setValue($this->productRepository->countPublished(...));

Use closures for any value that is slow to compute. EasyAdmin calls them
only when it renders the widget, so:

* they are never called for widgets that the current user can't see;
* for lazy widgets, they are only called in the request that loads the
  widget, not when rendering the dashboard page;
* for cached widgets, they are only called when the contents are not in the
  cache.

When the first parameter of the closure can receive an
``EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetContext`` object (because it
has no type, or its type is ``WidgetContext``, ``object`` or ``mixed``), the
closure receives it. Otherwise, the closure is called without arguments.
The context gives you access to the current request and user and to the
page-wide :ref:`context attributes <widgets-context-providers>`::

    use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetContext;

    yield StatWidget::new('My open tickets')
        ->setValue(fn (WidgetContext $context) =>
            $this->ticketRepository->countAssignedTo($context->getUser())
        );

These are the methods of ``WidgetContext``:

* ``getRequest()``: the current ``Request`` object;
* ``getUser()``: the current user (or ``null``);
* ``getDashboardControllerFqcn()``: the class of the dashboard controller;
* ``getWidget()``: the ``WidgetDto`` object of the widget being rendered;
* ``getAttributes()``, ``hasAttribute(string $name)`` and
  ``getAttribute(string $name, mixed $default = null)``: the attributes
  returned by the :ref:`context providers <widgets-context-providers>`;
* ``resolve(mixed $valueOrCallable)``: returns the given value or, for
  closures and invokable objects, the result of calling them (with the
  context only when their first parameter can receive it, as explained
  above).

.. note::

    Strings and arrays are never called as functions, even when they are
    the name of a function or a method. For example,
    ``StatWidget::new('Time')->setValue('time')`` displays the text
    ``time``. The options that only accept numbers, arrays or closures
    throw an exception when you pass them a string.

Built-in Widgets
----------------

All built-in widgets are defined in the
``EasyCorp\Bundle\EasyAdminBundle\Widget\`` namespace.

Stat Widget
~~~~~~~~~~~

``StatWidget`` displays a single value, like the number of users or the
revenue of this month. It takes 3 columns by default::

    use EasyCorp\Bundle\EasyAdminBundle\Widget\StatWidget;
    use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetContext;

    yield StatWidget::new('Revenue')
        ->setIcon('fa fa-euro-sign')
        ->setValue(fn () => $this->orderRepository->getRevenueOfThisMonth())
        // the percentage change compared to a previous period (8.2 means +8.2%)
        ->setDelta(fn () => $this->orderRepository->getRevenueChange())
        ->setDescription('Paid orders of the current month');

    yield StatWidget::new('Average response time')
        ->setValue(fn () => $this->metrics->getAverageResponseTime())
        // pass false when a lower value is better
        ->setDelta(-12.5, increaseIsGood: false)
        ->formatValue(fn (?float $value, WidgetContext $context) => null === $value
            ? ''
            : sprintf('%.0f ms', $value)
        );

The value can be an integer, a float, a string, a ``Stringable`` object or
``null`` (or a closure that returns any of them). Integers and floats are
formatted for the locale of the current user. Strings are displayed as they
are, so cast the numbers that database queries return as strings (e.g. the
result of ``SUM()``) to display them formatted. When the value to display
is ``null`` or an empty string, the widget displays a "no data" message
instead of the stat.

``setDelta()`` accepts an integer, a float, or a closure or invokable object
that returns a number or ``null``. The delta is displayed rounded to one
decimal, with an icon that shows if the value increased or decreased. Its
color tells good news from bad news: an increase is good unless you pass
``increaseIsGood: false``.

``formatValue()`` receives a closure or invokable object that turns the
value into the text displayed. It's always called with two arguments: the
value (as it is, so it can be any object, like a date or a money object)
and the ``WidgetContext``. It's also called when the value is ``null``, so
make its first parameter nullable. It must return a string or a
``Stringable`` object, which is escaped unless it's a ``Twig\Markup``
object. Return an empty string to display the "no data" message.

Chart Widget
~~~~~~~~~~~~

``ChartWidget`` displays a bar chart of a series of numbers indexed by their
labels. It takes 6 columns by default::

    use EasyCorp\Bundle\EasyAdminBundle\Widget\ChartWidget;

    yield ChartWidget::new('Sign-ups per month')
        ->setData(['January' => 120, 'February' => 95, 'March' => 143]);

    yield ChartWidget::new('Orders per day')
        ->setData(fn () => $this->orderRepository->countPerDayOfLastWeek())
        ->setColumns(12);

The values must be finite integers or floats (numeric strings are converted
to numbers). Negative values are displayed below a zero line. When the data
is empty, the widget displays a "no data" message. The chart is rendered as
an SVG image, without JavaScript, and it includes a hidden table with the
data for screen readers.

Charts and progress bars are gray by default. Use the ``chartColor()``
method of the :ref:`theme <design-chart-color>` to change their color.

Progress Widget
~~~~~~~~~~~~~~~

``ProgressWidget`` displays the progress of a value towards a target. It
takes 3 columns by default::

    use EasyCorp\Bundle\EasyAdminBundle\Widget\ProgressWidget;

    yield ProgressWidget::new('Monthly sales goal')
        ->setValue(fn () => $this->orderRepository->getRevenueOfThisMonth())
        ->setTarget(50000);

The value is ``0`` and the target is ``100`` by default. Both accept a
number or a closure or invokable object that returns a number. The widget
displays a progress bar and the percentage. Values above the target fill the
whole bar, but the percentage shows the real number (e.g. ``120%``).

List Widget
~~~~~~~~~~~

``ListWidget`` displays a list of items, like the latest orders. It takes 6
columns by default. Each item is a ``ListItem`` object with a title and an
optional description, value, URL and icon::

    use EasyCorp\Bundle\EasyAdminBundle\Widget\ListItem;
    use EasyCorp\Bundle\EasyAdminBundle\Widget\ListWidget;

    yield ListWidget::new('Latest orders')
        ->setLink($this->generateUrl('admin_order_index'), 'See all')
        ->setItems(function () {
            foreach ($this->orderRepository->findLatest(5) as $order) {
                yield ListItem::new($order->getReference())
                    ->setDescription($order->getCustomerName())
                    ->setValue($order->getFormattedTotal())
                    ->setUrl($this->generateUrl('admin_order_detail', [
                        'entityId' => $order->getId(),
                    ]))
                    ->setIcon('fa fa-receipt');
            }
        })
        ->setEmptyMessage('There are no orders yet');

``setItems()`` accepts an iterable of ``ListItem`` objects or a closure or
invokable object that returns it (a closure that uses ``yield`` works too).
The title, description and value of the items are escaped. Values are
displayed as they are: unlike stat values, numbers are not formatted for
the locale of the user, so format them yourself (like in the example
above). ``setUrl()`` throws an ``InvalidArgumentException`` when the URL
uses the ``javascript:``, ``data:``, ``vbscript:`` or ``file:`` schemes.
When there are no items, the widget displays the message set with
``setEmptyMessage()`` or a generic "no data" message.

Twig Widget
~~~~~~~~~~~

``TwigWidget`` displays the contents rendered by any Twig template of your
application. It takes all the 12 columns by default::

    use EasyCorp\Bundle\EasyAdminBundle\Widget\TwigWidget;

    yield TwigWidget::new('Server status')
        ->setTemplate('admin/widget/server_status.html.twig', fn () => [
            'servers' => $this->monitoring->getServers(),
        ])
        ->setColumns(6);

The second argument of ``setTemplate()`` defines the variables passed to the
template: an array or a closure or invokable object that returns an array.
The template also receives the ``widget`` variable (the ``WidgetDto`` object)
and the ``context`` variable (the ``WidgetContext`` object):

.. code-block:: html+twig

    {# templates/admin/widget/server_status.html.twig #}
    <ul class="server-status">
        {% for server in servers %}
            <li>
                {{ server.name }}
                <twig:ea:Badge variant="dot"
                    color="{{ server.online ? 'success' : 'danger' }}">
                    {{ server.online ? 'Online' : 'Offline' }}
                </twig:ea:Badge>
            </li>
        {% endfor %}
    </ul>

Use the ``setTemplate()`` method of this widget, not ``setTemplatePath()``:
``setTemplatePath()`` replaces the template that renders the widget itself.

The :doc:`EasyAdmin Twig components </components>` (``Card``, ``Stat``,
``Progress``, ``BarChart``, ``EmptyState``, etc.) are useful in the
templates of your widgets to match the look of the built-in ones.

.. _widgets-layout:

Layout
------

You can control the layout of the widgets at four levels. Use the simplest
one that does what you need.

Level 1: Default Layout
~~~~~~~~~~~~~~~~~~~~~~~

Without any configuration, widgets are displayed in a 12-column grid in the
same order as ``configureWidgets()`` yields them. Each widget type takes its
default number of columns (3 for stats and progress bars, 6 for charts and
lists, 12 for Twig widgets) and the grid creates new rows as needed.

The grid follows the width of the area that displays it (using
`CSS container queries`_), not the width of the browser window. So it also
works when the sidebar is collapsed or when the widgets are displayed in a
narrow part of your own template:

* when that area is less than ``36rem`` wide, the grid has one column and
  every widget takes the full width;
* from ``36rem``, the grid has six columns: widgets of 1 to 6 columns take
  half of the width and widgets of 7 to 12 columns take the full width;
* from ``62rem``, the grid has twelve columns and every widget takes the
  columns that you configured.

Level 2: Columns, Rows and Sections
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Use ``setColumns()`` and ``setRows()`` to change the size of the widgets::

    yield ChartWidget::new('Sales')->setData(/* ... */)->setColumns(8)->setRows(2);
    yield StatWidget::new('Orders')->setValue(/* ... */)->setColumns(4);
    yield StatWidget::new('Refunds')->setValue(/* ... */)->setColumns(4);

In the twelve-column grid, ``setRows(N)`` with N from 1 to 6 makes the
widget span N rows of the grid, so other widgets can be displayed next to
it, and gives it at least the height of N rows. The contents of the widget
don't make it taller: when they don't fit, they scroll inside the widget.
The widget can still be taller than N rows when the widgets next to it
make those rows taller. In narrower grids, ``setRows()`` is ignored and the
height of the widget is the height of its contents. The
``--ea-widget-row-size`` and ``--ea-widget-gap`` CSS variables, defined in
the ``.ea-widgets`` element, set the height of each row and the space
between widgets.

Use ``WidgetSection`` to divide the dashboard in groups of widgets. All the
widgets yielded after a section (and before the next section) are displayed
in their own grid, below the optional title, icon and help message of the
section::

    use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetSection;

    yield StatWidget::new('Visitors')->setValue(/* ... */);

    yield WidgetSection::new('Sales', 'fa fa-chart-line')
        ->setHelp('The sales of the current month');
    yield StatWidget::new('Revenue')->setValue(/* ... */);
    yield ProgressWidget::new('Sales goal')->setValue(/* ... */)->setTarget(50000);

    yield WidgetSection::new('Finance')
        ->setPermission('ROLE_FINANCE');
    yield ChartWidget::new('Expenses')->setData(/* ... */);

Sections also define ``setName()`` (by default, the name is the slug of the
label), ``setPermission()`` (it hides the section and all its widgets),
``setCssClass()`` and ``addCssClass()``. A section without any widget
visible for the current user isn't displayed. Widgets yielded before the
first section belong to a section without title and name, so the
``section`` prop of the ``Widget:Grid`` component can't select them.

Level 3: CSS
~~~~~~~~~~~~

Each widget is rendered in an element like this:

.. code-block:: html

    <div data-ea-widget="revenue"
         class="ea-widget ea-widget-stat ea-widget-cols-3 ea-widget-rows-2 my-class">
        ...
    </div>

* ``ea-widget-<type>`` is the type of the widget: ``stat``, ``chart``,
  ``progress``, ``list``, ``twig`` or, for your own widget classes, the
  class name in kebab case without the ``Widget`` suffix (e.g.
  ``ea-widget-stock-level`` for ``StockLevelWidget``). Anonymous classes
  get ``ea-widget-custom``, and the types that are also the name of a
  layout class get a ``-widget`` suffix (e.g. ``ea-widget-toolbar-widget``
  for ``ToolbarWidget``);
* ``ea-widget-cols-<N>`` and ``ea-widget-rows-<N>`` are the columns and
  rows of the widget;
* ``ea-widget-cardless`` is added to widgets rendered without a card;
* the other classes are the ones that you added with ``setCssClass()`` or
  ``addCssClass()``.

Sections are rendered in ``<section class="ea-widget-section">`` elements
with a ``data-ea-widget-section`` attribute (the name of the section). The
grid of each section is the ``.ea-widgets`` element.

The CSS rules that place widgets in the grid use the ``:where()`` selector,
so they have zero specificity. In addition, all EasyAdmin styles are
defined in :ref:`CSS cascade layers <design-css-layers>`. This means that
a single rule of your own stylesheet wins over the default placement, so you
can use any CSS Grid property (``grid-column``, ``grid-row``, ``order``,
etc.)::

    yield ListWidget::new('Latest orders')
        ->setItems(/* ... */)
        ->addCssClass('latest-orders');

.. code-block:: css

    /* assets/styles/admin.css */
    @container ea-widget-grid (min-width: 62rem) {
        .latest-orders {
            grid-column: 5 / span 8;
            grid-row: span 2;
        }
    }

Put the rules inside the ``@container ea-widget-grid (min-width: 62rem)``
query to apply them only to the twelve-column grid. Without that query,
a rule like ``grid-column: span 8`` also applies when the grid has one or
six columns, and the browser adds extra columns to the grid to make room
for the widget. Load this stylesheet with the
:ref:`assets of your dashboard <crud-design-custom-web-assets>`.

.. _widgets-custom-template:

Level 4: Your Own Template
~~~~~~~~~~~~~~~~~~~~~~~~~~

When you need a completely different layout, render your own template in
the ``index()`` method of the dashboard and display the widgets in it with
these Twig components:

* ``<twig:ea:Widget:Grid/>`` renders all the sections and widgets;
* ``<twig:ea:Widget:Grid section="sales"/>`` renders only the section with
  that name. It renders no widgets when the section doesn't exist or when
  the current user can't see it;
* ``<twig:ea:Widget:Grid placement="toolbar"/>`` renders the
  :ref:`toolbar widgets <widgets-toolbar>`. The ``placement`` prop accepts
  ``grid`` (the default) and ``toolbar``; any other value is ignored and
  the component renders the grid widgets;
* ``<twig:ea:Widget name="latest-orders"/>`` renders a single widget. It
  renders nothing when the current user can't see the widget, and it throws
  an exception when the dashboard has no widget with that name.

::

    use Symfony\Component\HttpFoundation\Response;

    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

.. code-block:: html+twig

    {# templates/admin/dashboard.html.twig #}
    {% extends '@EasyAdmin/page/dashboard.html.twig' %}

    {% block dashboard_widgets %}
        <twig:ea:Widget:Grid section="sales"/>

        <div class="dashboard-columns">
            <twig:ea:Widget name="latest-orders"/>
            <twig:ea:Widget name="pending-reviews"/>
        </div>

        <twig:ea:Widget:Grid section="customers"/>
    {% endblock %}

Your template still uses ``configureWidgets()`` to define the widgets, so
their permissions, lazy loading, cache and refresh keep working.
``@EasyAdmin/page/dashboard.html.twig`` already renders the toolbar widgets
in the ``page_actions`` block. If your template extends
``@EasyAdmin/page/content.html.twig`` instead, add
``<twig:ea:Widget:Grid placement="toolbar"/>`` yourself where you need it.

.. _widgets-custom-contents:

Adding Other Contents to the Dashboard
--------------------------------------

There are two ways to display your own contents in the dashboard page:

* a ``TwigWidget`` with ``renderWithoutCard()``, which displays any
  template inside the grid, without the card around it;
* the ``dashboard_before_widgets`` and ``dashboard_after_widgets`` blocks
  of ``@EasyAdmin/page/dashboard.html.twig``, which display contents above
  and below all the widgets (the ``dashboard_widgets`` block renders the
  widgets themselves).

::

    yield TwigWidget::new()
        ->setName('announcement')
        ->setTemplate('admin/widget/announcement.html.twig')
        ->renderWithoutCard()
        ->setPermission('ROLE_EDITOR');

.. code-block:: html+twig

    {# templates/admin/dashboard.html.twig #}
    {% extends '@EasyAdmin/page/dashboard.html.twig' %}

    {% block dashboard_before_widgets %}
        <twig:ea:Alert variant="warning">
            The shop will be closed on Monday.
        </twig:ea:Alert>
    {% endblock %}

Use a widget when the contents are part of the grid or when they need any
widget feature (permissions, lazy loading, cache or refresh). Use the
blocks for simple contents displayed above or below the grid (and render
your template in the ``index()`` method, as explained in the previous
section).

.. _widgets-toolbar:

Toolbar Widgets
---------------

Widgets with the ``WidgetPlacement::Toolbar`` placement are displayed next
to the page title, where other pages display their actions. They are useful
for controls that change what all the widgets display, like a date range
selector::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Option\WidgetPlacement;

    yield TwigWidget::new()
        ->setName('date-range')
        ->setTemplate('admin/widget/date_range.html.twig')
        ->setPlacement(WidgetPlacement::Toolbar);

Toolbar widgets are always rendered without a card, so their label, icon,
help and link are not displayed (``StatWidget`` displays its label inside
the stat), and their columns and rows are ignored.
Permissions, lazy loading, cache and refresh work like in other widgets.
See :ref:`widgets-context-providers` for a full example of a date range
selector.

.. _widgets-lazy-cache-refresh:

Lazy Loading, Cache and Refresh
-------------------------------

Lazy Loading
~~~~~~~~~~~~

Lazy widgets display a placeholder when the page loads. When the widget is
about to enter the visible part of the page, a JavaScript code loads its
contents in a separate request. Use this option for slow widgets, so they
don't delay the rest of the page::

    yield ChartWidget::new('Revenue per country')
        ->setData(fn () => $this->reports->getRevenuePerCountry())
        ->setLazy();

The contents of lazy widgets are loaded from the
``<dashboard route>_widget`` route (e.g. ``admin_widget``), whose path is
``<dashboard path>/_widget/{widgetName}`` (e.g.
``/admin/_widget/revenue-per-country``). This route runs the
``renderWidget(string $widgetName)`` method of
``AbstractDashboardController``, so EasyAdmin only creates it for the
dashboards that extend that class. In other dashboards, the widgets that
use ``setLazy()`` or ``setRefreshInterval()`` throw an exception.

The URL keeps the query string and the route parameters of the dashboard
page, so the widget sees the same parameters as the page that displays it.
When a query parameter and a route parameter have the same name, the route
parameter wins. The ``routeName`` and ``routeParams`` query parameters,
which EasyAdmin uses to run other controllers, are removed. If the URL of a
lazy widget can't be generated, the widget is rendered with the page, like
a widget that isn't lazy.

The JavaScript code handles as a failure any response that isn't the
contents of the widget (e.g. an error response, or the login page
displayed after a redirect when the session expires) and it cancels the
request after 30 seconds. When a lazy widget can't be loaded, it displays
an error message with a button to retry, and a status region of the page
announces the error to screen readers.

The default session handler of PHP locks the session while a request uses
it, so ``renderWidget()`` saves and closes the session before rendering
the widget. This way, the widgets of a page load in parallel. If the code
of a widget uses the session again (e.g. it reads a flash message or
generates a CSRF token), Symfony opens the session again and the other
widgets of the page wait until this one finishes.

Cache
~~~~~

``setCache()`` stores the rendered contents of the widget in the
``cache.app`` cache pool for the given number of seconds::

    // each user has their own cached contents for 5 minutes
    yield StatWidget::new('My open tickets')
        ->setValue(fn (WidgetContext $context) =>
            $this->ticketRepository->countAssignedTo($context->getUser())
        )
        ->setCache(300);

    // all users share the same cached contents for 1 hour
    yield ChartWidget::new('Sign-ups per month')
        ->setData(fn () => $this->userRepository->countSignUpsPerMonth())
        ->setCache(3600, shared: true);

The cache key includes the EasyAdmin version, the dashboard, the name of
the widget, the locale, the host, the scalar route parameters of the page
(e.g. ``{tenant}`` in a ``/{tenant}/admin`` path), the
:ref:`context attributes <widgets-context-providers>` and, unless the cache
is shared, the user identifier and the roles of the user. Permissions are
always checked before reading the cache. When the widget fails, its
contents are not cached, so they are computed again in the next request.

Use ``shared: true`` only when all users see exactly the same contents.
Shared contents are computed for the first user who displays the widget
and then served to everyone, so don't display anything that depends on the
user in them: user names, contents that depend on roles (e.g. an
``is_granted()`` check in the template) or CSRF tokens (other users would
get a token that isn't valid for them).

.. caution::

    The query string is not part of the cache key. If the data of a cached
    widget depends on a query parameter (e.g. ``?range=30d``), don't read
    it with ``$context->getRequest()``. Return it from a
    :ref:`context provider <widgets-context-providers>` and read it with
    ``$context->getAttribute()``. Do the same in lazy widgets, so their
    contents stay consistent with the rest of the page.

.. caution::

    The cache key doesn't include anything that changes when you deploy a
    new version of your application. If ``cache.app`` uses a persistent
    adapter (e.g. Redis, Memcached or a database), cached widgets keep
    displaying their old contents (e.g. the ones rendered with an old
    template) until they expire. Clear the pool when you deploy changes
    that affect cached widgets:

    .. code-block:: terminal

        $ php bin/console cache:pool:clear cache.app

Refresh
~~~~~~~

``setRefreshInterval()`` reloads the contents of the widget periodically.
The interval must be between 10 seconds and one day::

    yield StatWidget::new('Orders today')
        ->setValue(fn () => $this->orderRepository->countToday())
        ->setRefreshInterval(60);

While the browser tab is hidden, the widget is not reloaded. When the tab
is visible again, the widget is reloaded at once if the interval has
passed. During a reload, the old contents stay visible until the new ones
arrive. When the keyboard focus is inside the widget, the reload is
postponed until the next interval, so the focus doesn't move. When a
reload fails, the widget keeps its previous contents and tries again at
the next interval. Combine it with ``setCache()`` to limit the work done
in the server when many users have the dashboard open.

JavaScript Events
~~~~~~~~~~~~~~~~~

The contents loaded by lazy and refreshing widgets (and by the retry
button) are inserted in the page with JavaScript, so the ``<script>``
elements inside them are not executed. Listen to these events to run your
own JavaScript code:

* ``ea.widget.loaded``: dispatched after the contents of a widget are
  loaded and inserted in the page;
* ``ea.widget.error``: dispatched when the request fails (e.g. a network
  error, an error response or an expired session). After a failed first
  load or retry, the widget displays its error message; after a failed
  reload, it keeps its previous contents.

They are dispatched on the widget element and they bubble, so you can
listen to them on ``document``. Their ``detail`` property contains the
``name`` of the widget and the ``widget`` element. They aren't dispatched
for the widgets rendered with the page:

.. code-block:: javascript

    document.addEventListener('ea.widget.loaded', (event) => {
        if ('sales-map' !== event.detail.name) {
            return;
        }

        initializeSalesMap(event.detail.widget.querySelector('.sales-map'));
    });

Error Handling
--------------

When a widget fails (e.g. its closure throws an exception or its template
has an error), the behavior depends on the environment:

* in debug mode (e.g. the ``dev`` environment), the exception is thrown,
  so you see the usual Symfony error page. For lazy and refreshing widgets,
  the request of the widget fails and the JavaScript code handles it as
  explained in the previous sections (use the Symfony profiler to see the
  exception);
* otherwise, the exception is logged (with the ``error`` level, in the
  ``easyadmin`` channel when you use Monolog) and the widget displays an
  error message with a button to retry. The other widgets of the page are
  displayed as usual. For lazy and refreshing widgets, the URL of the
  widget returns a ``500`` error (with the error message as its contents),
  so the JavaScript code handles it like any other failed request.

The retry button loads the widget again with the same route used by lazy
widgets, so it only works in dashboards that extend
``AbstractDashboardController``. The failures of
:ref:`context providers <widgets-context-providers>` are handled in the
same way.

.. _widgets-security:

Permissions and Security
------------------------

``setPermission()`` hides a widget for the users who don't have the given
permission. It accepts a role or any other attribute supported by your
security voters, and also a Symfony ``Expression``
(:ref:`see details <security-expressions>`). The ``setPermission()`` method
of ``WidgetSection`` hides the section and all its widgets::

    use Symfony\Component\ExpressionLanguage\Expression;

    yield StatWidget::new('Revenue')
        ->setValue(fn () => $this->orderRepository->getRevenueOfThisMonth())
        ->setPermission('ROLE_FINANCE');

    yield StatWidget::new('Pending reviews')
        ->setValue(fn () => $this->reviews->countPending())
        ->setPermission(new Expression(
            '"ROLE_EDITOR" in role_names or "ROLE_ADMIN" in role_names'
        ));

The data of hidden widgets is never computed (their closures are not
called) and their assets are not loaded.

Every widget has its own URL (the one used by lazy and refreshing
widgets), and any widget that the current user can see can be requested
with it, even when it's not lazy. This URL runs an action of your
dashboard controller (``renderWidget()``) and its path starts with the
path of the dashboard. That's why the security that protects the
dashboard also protects the contents of its widgets:

* the ``access_control`` rules that match the dashboard page. EasyAdmin
  also checks them for the URL of the widgets, even when they don't match
  that URL (e.g. a rule for the exact path or the route name of the
  dashboard);
* the ``#[IsGranted]`` attributes applied to the dashboard class;
* the ``#[IsGranted]`` attributes applied to the ``index()`` method of the
  dashboard, including your own attributes that extend it.
  ``renderWidget()`` checks them too, but it can't resolve the ``subject``
  of an attribute, so the attributes that define one always deny access to
  the URL of the widgets.

When the user isn't logged in, the firewall handles the request as usual
(e.g. it returns a ``401`` error or redirects to the login page). Logged
in users get a ``403`` error when they can't see the widget (because of
the security of the dashboard or because of ``setPermission()``) and a
``404`` error when the widget doesn't exist.

.. caution::

    The code that you write inside the ``index()`` method (e.g. a call to
    ``denyAccessUnlessGranted()`` or a redirect) doesn't run when loading
    a widget through its URL. Don't protect the dashboard that way. Use
    ``#[IsGranted]`` on the dashboard class, ``access_control`` rules or
    the ``setPermission()`` method of the widgets.

The responses of the widget URL include the ``private`` and ``no-store``
directives of the ``Cache-Control`` header, so browsers and proxies don't
store them.

.. _widgets-context-providers:

Context Providers
-----------------

Context providers define page-wide values that all the widgets can use,
like a date range or the current store. They are services that implement
the ``WidgetContextProviderInterface`` (defined in the
``EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget\`` namespace). Its
``getAttributes()`` method receives the current request and returns an
array of attributes::

    // src/Admin/DateRangeContextProvider.php
    namespace App\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget\WidgetContextProviderInterface;
    use Symfony\Component\HttpFoundation\Request;

    class DateRangeContextProvider implements WidgetContextProviderInterface
    {
        public const RANGES = ['7d' => 7, '30d' => 30, '90d' => 90];

        public function getAttributes(Request $request): array
        {
            $range = $request->query->getString('range');
            if (!\array_key_exists($range, self::RANGES)) {
                $range = '30d';
            }

            return ['range' => $range, 'days' => self::RANGES[$range]];
        }
    }

If you use the default service configuration, this service is registered
and tagged automatically thanks to autoconfiguration. Otherwise, add the
``ea.widget_context_provider`` tag to it (the tag name is also available
in the ``EasyAdminBundle::TAG_WIDGET_CONTEXT_PROVIDER`` constant).

Now, add a toolbar widget that changes the range and read the attributes in
the closures of your widgets::

    use App\Admin\DateRangeContextProvider;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Option\WidgetPlacement;
    use EasyCorp\Bundle\EasyAdminBundle\Widget\ChartWidget;
    use EasyCorp\Bundle\EasyAdminBundle\Widget\StatWidget;
    use EasyCorp\Bundle\EasyAdminBundle\Widget\TwigWidget;
    use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetContext;

    public function configureWidgets(): iterable
    {
        yield TwigWidget::new()
            ->setName('date-range')
            ->setTemplate('admin/widget/date_range.html.twig', [
                'ranges' => array_keys(DateRangeContextProvider::RANGES),
            ])
            ->setPlacement(WidgetPlacement::Toolbar);

        yield StatWidget::new('Revenue')
            ->setValue(fn (WidgetContext $context) =>
                $this->orderRepository->getRevenue($context->getAttribute('days'))
            )
            ->setCache(600, shared: true);

        yield ChartWidget::new('Orders per day')
            ->setData(fn (WidgetContext $context) =>
                $this->orderRepository->countPerDay($context->getAttribute('days'))
            )
            ->setLazy();
    }

.. code-block:: html+twig

    {# templates/admin/widget/date_range.html.twig #}
    {% set current_range = context.getAttribute('range') %}
    {% for range in ranges %}
        <twig:ea:Button htmlElement="a" size="sm"
            href="{{ path(ea().dashboardRouteName, {range: range}) }}"
            variant="{{ range == current_range ? 'solid' : 'outline' }}">
            {{ range }}
        </twig:ea:Button>
    {% endfor %}

When the user clicks on a link, the page loads again with the new
``range`` query parameter. The lazy widget gets the same attributes,
because the URL that loads it keeps the query string of the page. The
cached widget gets a different cache entry per range, because the
attributes are part of the cache key.

Keep these things in mind:

* the attributes of all providers are merged into a single array (when two
  providers return the same attribute, the last one wins);
* providers apply to all the dashboards of the application. The request
  attributes tell you which dashboard is rendered, so a provider can
  return its attributes only for some dashboards (e.g.
  ``$request->attributes->get(EA::DASHBOARD_CONTROLLER_FQCN)`` returns
  ``App\Controller\Admin\DashboardController``, using the
  ``EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA`` class);
* providers run once per request (they run again only after a failure),
  and only when a widget is rendered;
* the attributes are part of the cache key, so they must be values that PHP
  can serialize (scalars, arrays, enums or serializable objects);
* accept only a small and fixed set of values (like the ranges of the
  example). If a provider returns any value of the query string as it is,
  each different value creates new cache entries for all cached widgets;
* when a provider throws an exception, or returns attributes that can't be
  serialized while rendering a cached widget, the widget fails as explained
  in the previous section.

.. _widgets-custom:

Creating Custom Widgets
-----------------------

Create a custom widget when you need the same kind of widget in several
places, or when you want to give it its own options. A widget is a class
that implements ``WidgetInterface`` and uses ``WidgetTrait``, which provides
all the options shared by widgets (and a private constructor). Define a
static ``new()`` method to create it::

    // src/Admin/Widget/StockLevelWidget.php
    namespace App\Admin\Widget;

    use EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget\WidgetInterface;
    use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetTrait;
    use Symfony\Contracts\Translation\TranslatableInterface;

    final class StockLevelWidget implements WidgetInterface
    {
        use WidgetTrait;

        public static function new(
            TranslatableInterface|string|null $label = null,
        ): self {
            return (new self())
                ->setLabel($label)
                ->setTemplatePath('admin/widget/stock_level.html.twig')
                ->setDefaultColumns(4)
                ->addCssFiles('styles/admin/stock_level.css')
                ->setDataEntry('products', [])
                ->setCustomOption('lowStockThreshold', 10);
        }

        public function setProducts(array|\Closure $products): self
        {
            return $this->setDataEntry('products', $products);
        }

        public function setLowStockThreshold(int $threshold): self
        {
            return $this->setCustomOption('lowStockThreshold', $threshold);
        }
    }

There are two ways to store the information of the widget:

* ``setCustomOption()`` stores a configuration value as it is, like the
  threshold of the example. The template reads it with
  ``widget.customOption('lowStockThreshold')``;
* ``setDataEntry(string $name, mixed $valueOrCallable)`` (a protected
  method of ``WidgetTrait``) stores what the widget displays: the value
  itself or a closure (or invokable object) that returns it. Closures
  follow the rules explained in :ref:`widgets-values-and-closures`: they
  are called only when the widget is rendered and they receive the
  ``WidgetContext`` when their first parameter can receive it. The
  template gets the result as ``data.<name>`` (``data.products`` in the
  example). Define a default value in ``new()``, so the entry exists even
  when the setter isn't called.

The template of the widget receives three variables: ``widget`` (the
``WidgetDto`` object, which gives access to the custom options), ``data``
(the data entries, with their closures already resolved) and ``context``
(the ``WidgetContext`` object):

.. code-block:: html+twig

    {# templates/admin/widget/stock_level.html.twig #}
    {% set threshold = widget.customOption('lowStockThreshold') %}
    <ul class="stock-levels">
        {% for product in data.products %}
            <li class="{{ product.stock < threshold ? 'stock-low' }}">
                {{ product.name }}: {{ product.stock }}
            </li>
        {% endfor %}
    </ul>

Use your widget as any other widget::

    yield StockLevelWidget::new('Low stock')
        ->setProducts(fn () => $this->productRepository->findWithLowestStock(10))
        ->setLowStockThreshold(5);

``setDefaultColumns()`` defines the columns used when the widget doesn't
call ``setColumns()`` (if you don't set it, it's 12). The CSS class of this
widget is ``ea-widget-stock-level``. Don't call ``setCache()`` or
``setRefreshInterval()`` in ``new()`` unless all the uses of the widget
need them: once called, they can't be turned off.

The assets added with ``addCssFiles()``, ``addJsFiles()`` and the other
asset methods are loaded only on the dashboard page rendered by the
``index()`` method (also when you override that method). If you render a
widget with ``<twig:ea:Widget>`` in any other backend page, add its assets
with the ``configureAssets()`` method of the dashboard or of the CRUD
controller that renders that page.

``setTemplatePath()`` accepts any Twig template path, including the ones
that use a bundle namespace (e.g. ``@AcmeWidgets/widget/count.html.twig``),
so you can also distribute your widgets in a bundle.

.. note::

    Widget classes have a private constructor (you create them with
    ``new()``), so they can't be services. In a default Symfony
    application, the classes of ``src/`` are registered as services, but
    the services that nothing uses are removed, so nothing breaks. However,
    if your configuration loads a directory with the
    ``controller.service_arguments`` tag (e.g. ``src/Controller/``), the
    services of that directory are kept and the container fails because of
    the private constructor. Don't put widget classes in that directory or
    exclude them from that configuration.

.. _widgets-configurators:

Using Services in Custom Widgets
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Widget objects are plain value objects: they can't get services injected.
When a widget needs a service to get its data (e.g. a Doctrine query),
create a **widget configurator**: a service that prepares the widget right
before EasyAdmin renders its contents.

First, create the widget. It only stores its options and declares the data
entry that the configurator fills::

    // src/Admin/Widget/EntityCountWidget.php
    namespace App\Admin\Widget;

    use EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget\WidgetInterface;
    use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetTrait;
    use Symfony\Contracts\Translation\TranslatableInterface;

    final class EntityCountWidget implements WidgetInterface
    {
        use WidgetTrait;

        public static function new(
            TranslatableInterface|string|null $label = null,
        ): self {
            return (new self())
                ->setLabel($label)
                ->setTemplatePath('admin/widget/entity_count.html.twig')
                ->setDefaultColumns(3)
                ->setCustomOption('entity', null)
                ->setDataEntry('count', null);
        }

        /**
         * @param class-string $entityFqcn
         */
        public function setEntity(string $entityFqcn): self
        {
            return $this->setCustomOption('entity', $entityFqcn);
        }
    }

Then, create a class that implements ``WidgetConfiguratorInterface``. The
``supports()`` method tells which widgets the configurator applies to and
``configure()`` changes the ``WidgetDto`` object of those widgets::

    // src/Admin/Widget/EntityCountWidgetConfigurator.php
    namespace App\Admin\Widget;

    use Doctrine\Persistence\ManagerRegistry;
    use EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget\WidgetConfiguratorInterface;
    use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;
    use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetContext;

    final class EntityCountWidgetConfigurator implements WidgetConfiguratorInterface
    {
        public function __construct(
            private ManagerRegistry $doctrine,
        ) {
        }

        public function supports(WidgetDto $widget): bool
        {
            return EntityCountWidget::class === $widget->getWidgetFqcn();
        }

        public function configure(WidgetDto $widget, WidgetContext $context): void
        {
            $repository = $this->doctrine->getRepository(
                $widget->getCustomOption('entity')
            );

            $widget->setData('count', $repository->count([]));
        }
    }

The template displays the data entry like in any other widget:

.. code-block:: html+twig

    {# templates/admin/widget/entity_count.html.twig #}
    <twig:ea:Stat :value="data.count"/>

Dashboards use the widget without knowing how it gets its data::

    yield EntityCountWidget::new('Products')->setEntity(Product::class);

If your application uses the default Symfony service configuration, the
configurator is registered and tagged automatically. Otherwise, register
it as a service and add the ``ea.widget_configurator`` tag to it. Use the
``priority`` attribute of the tag to change the order in which
configurators run.

EasyAdmin calls the configurators only when it computes the contents of a
widget. That's why they can run database queries directly, without
closures:

* they don't run for the widgets that the current user can't see;
* they don't run when the contents come from the cache (see ``setCache()``);
* for lazy widgets, they run in the request that loads the contents, not in
  the dashboard page;
* when a configurator fails, the widget displays its error message and the
  rest of the dashboard keeps working, like when the widget itself fails.

``configure()`` receives the same ``WidgetContext`` object as closures, so
it can read the current user and the attributes of the
:ref:`context providers <widgets-context-providers>`.

.. caution::

    Configurators can change what the template of the widget displays: its
    data entries (``setData()``), its custom options (``setCustomOption()``)
    and its template (``setTemplatePath()``). The rest of the options
    (name, label, icon, columns, permission, assets, cache, etc.) are
    already applied when configurators run, so changing them has no effect.

.. _widgets-templates:

Overriding the Widget Templates
-------------------------------

The contents of the built-in widgets are rendered with these
:ref:`template names <template-names>`:

=====================  ===================================================
Template name          What it renders
=====================  ===================================================
``page/dashboard``     The dashboard page
``widget/stat``        The contents of ``StatWidget``
``widget/chart``       The contents of ``ChartWidget``
``widget/progress``    The contents of ``ProgressWidget``
``widget/list``        The contents of ``ListWidget``
``widget/twig``        The contents of ``TwigWidget``
=====================  ===================================================

You can :ref:`override them <template-customization>` with Symfony's
mechanism (e.g. creating the
``templates/bundles/EasyAdminBundle/widget/stat.html.twig`` file) or
:ref:`replace them <replacing-templates>` in the ``configureCrud()`` method
of your dashboard::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->overrideTemplate('widget/list', 'admin/widget/list.html.twig');
    }

To change the template of a single widget, call its ``setTemplatePath()``
method (except in ``TwigWidget``, which uses ``setTemplate()``). These
templates receive the same ``widget``, ``data`` and ``context`` variables
as the templates of custom widgets. These are the data entries of each
built-in widget:

* ``StatWidget``: ``data.value`` and ``data.delta`` (the description and
  the ``increaseIsGood`` flag are custom options:
  ``widget.customOption('description')``);
* ``ChartWidget``: ``data.bars``;
* ``ProgressWidget``: ``data.value`` and ``data.target``;
* ``ListWidget``: ``data.items`` (the empty message is the
  ``emptyMessage`` custom option);
* ``TwigWidget``: ``data.template`` and ``data.vars``.

The error message displayed by failed widgets is rendered by the
``@EasyAdmin/widget/_error.html.twig`` template. It doesn't have a template
name, so you can only change it with Symfony's mechanism
(``templates/bundles/EasyAdminBundle/widget/_error.html.twig``).

.. _`CSS container queries`: https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_containment/Container_queries
