Design
======

EasyAdmin includes a professional design that's ready to use, so your backend
looks good from the first minute without doing anything. When you want it to
match the look of your project, you can change lots of things, and most of the
time a few lines of PHP are enough::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Option\Font;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Theme;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

    #[AdminDashboard(routePath: '/admin', routeName: 'admin')]
    class DashboardController extends AbstractDashboardController
    {
        public function configureDashboard(): Dashboard
        {
            return Dashboard::new()
                ->setTitle('Acme')
                ->setTheme(Theme::new()
                    ->primaryColor('emerald')
                    ->radius('lg')
                    ->font(base: Font::IbmPlexSans)
                );
        }
    }

Not sure which color or font you want? In your development environment, open
``/admin/_ea_ui_kit``, try them live and copy the PHP code that the
:ref:`UI Kit <design-ui-kit>` gives you.

There are four ways to change the design. Use the first one that solves your
problem: each one gives you more control than the previous one, but also more
code to maintain:

#. :ref:`The theme <design-theme>`: a few options in PHP change the entire
   backend, in both the light and dark color schemes (dark mode);
#. :ref:`CSS variables <design-css-variables>`: change a specific value, such
   as the color of the sidebar;
#. :ref:`Your own CSS <design-custom-css>`: change the style of anything;
#. :ref:`Twig templates <template-customization>`: change the HTML.

If you prefer a ready-made solution, go to the
:ref:`recipes <design-recipes>`.

Which Tool Do I Need?
---------------------

================================================  ================================================================
I want to...                                      Read
================================================  ================================================================
Use my brand color                                :ref:`Primary Color <design-primary-color>`
Make corners rounder or squarer                   :ref:`Corners and Spacing <design-corners-spacing>`
Fit more data on the screen                       :ref:`A Compact Backend <design-recipe-compact>`
Make it look friendly or technical                :ref:`A Friendly or a Technical Backend <design-recipe-personality>`
Change the font                                   :ref:`Fonts <design-fonts>`
Use the font of my company                        :ref:`Your Company's Font <design-recipe-font>`
Compare colors and fonts before deciding          :ref:`The UI Kit <design-ui-kit>`
Display a logo                                    :ref:`A Logo in the Sidebar <design-recipe-logo>`
Make the sidebar narrower or the page wider       :ref:`Layout <design-layout>`
Tell test servers apart from production           :ref:`A Color per Environment <design-recipe-environment>`
Change the color of the sidebar                   :ref:`A Colored Sidebar <design-recipe-sidebar>`
Make a listing with many columns fit              :ref:`A Wider Listing <design-recipe-wide-crud>`
Highlight some table rows                         :ref:`Highlighted Table Rows <design-recipe-rows>`
Change anything else with CSS                     :ref:`Your own CSS <design-custom-css>`
Make a custom page look good                      :ref:`Custom Pages <design-prose>`
Use buttons, alerts and badges in my templates    :ref:`Twig components <using-easyadmin-twig-components>`
Display a value in a different way                :ref:`Field templates <field-and-action-templates>`
Change the HTML of a page                         :ref:`Templates <template-customization>`
Use other icons                                   :ref:`Icons <icon-customization>`
Add my CSS and JavaScript files                   :ref:`Web Assets <crud-design-custom-web-assets>`
Go back to the design before version 5.9          :ref:`The Previous Default Design <design-recipe-previous>`
Fix something that doesn't work                   :ref:`Troubleshooting <design-troubleshooting>`
================================================  ================================================================

.. _design-theme:

Changing the Theme
------------------

The theme is a set of options that you pass to ``setTheme()`` in the
:ref:`dashboard configuration <dashboard-configuration>`. All of them are
optional, and you don't have to write any CSS:

==================  =======================================  ====================
Method              What it changes                          Default
==================  =======================================  ====================
``primaryColor()``  Buttons, switches, active menu item...   ``neutral``
``radius()``        All the rounded corners                  ``md``
``spacing()``       All paddings and gaps                    ``md``
``grays()``         Backgrounds, borders and gray text       ``neutral``
``font()``          The font of the text and of code         Inter, JetBrains Mono
==================  =======================================  ====================

.. versionadded:: 5.9

    The predefined colors of ``primaryColor()`` and the ``font()`` method were
    introduced in EasyAdmin 5.9.

The theme applies to all the pages of a dashboard, so you can't change it for
only one page. Applications with two dashboards (e.g. for employees and for
customers) can use two themes. The standalone login page doesn't use it.

.. _design-primary-color:

Primary Color
~~~~~~~~~~~~~

This is the color of buttons, switches and the active menu item. Use one of
the predefined colors, which work well in both color schemes::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Option\ThemeColor;

    // neutral, red, orange, amber, yellow, lime, green, emerald, teal, cyan, sky,
    // blue, indigo, violet, purple, fuchsia, pink, rose
    Theme::new()->primaryColor('indigo');

    // the same colors are available as cases of the ThemeColor enum
    Theme::new()->primaryColor(ThemeColor::Indigo);

Or use your brand color in hexadecimal, ``rgb()``, ``hsl()`` or ``oklch()``
format, without transparency. By default, it's used in both color schemes. Add
the ``dark`` argument if you have a version for dark backgrounds::

    Theme::new()->primaryColor('#15803d');
    Theme::new()->primaryColor('#15803d', dark: '#22c55e');

EasyAdmin chooses black or white for the text on top of the primary color,
whichever is more readable. Vivid colors, such as orange (``#f97316``), get
black text because white text is hard to read on them. In the light color
scheme, the active menu item is also black. If you prefer white text, use a
darker version of the color (``#c2410c``).

.. _design-corners-spacing:

Corners and Spacing
~~~~~~~~~~~~~~~~~~~

``radius()`` makes the backend look friendly (round) or technical (square)::

    // the presets are 'none', 'xs', 'sm', 'md', 'lg' and 'xl'
    Theme::new()->radius('xl');

    // you can also use any length in 'px' or 'rem'
    Theme::new()->radius('0.3rem');

``spacing()`` changes how much space elements take. Use a smaller value to fit
more data on the screen. The size of the text doesn't change, and controls
(buttons, inputs and switches) only have two sizes: they get one size smaller
with values below ``md`` and they don't grow with values above it::

    // the presets are 'xs', 'sm', 'md', 'lg' and 'xl'
    Theme::new()->spacing('xs');

Gray Colors
~~~~~~~~~~~

Most of the screen is gray (backgrounds, borders and secondary text), so this
option changes the feeling of the backend more than you might think. Warm
grays feel friendly and cool grays feel technical. The available gray scales
are ``neutral``, ``stone`` (warm), ``zinc``, ``gray``, ``slate`` (cool),
``mauve`` (purple tint), ``olive`` (green tint), ``mist`` (blue-green tint)
and ``taupe`` (brown tint). The Colors page of the
:ref:`UI Kit <design-ui-kit>` shows all of them::

    // these values are also available as constants of the GrayScale class
    Theme::new()->grays('stone');

    // use a different scale in the dark color scheme
    Theme::new()->grays('stone', dark: 'neutral');

.. _design-fonts:

Fonts
~~~~~

By default, the backend uses the `Inter`_ font for text and `JetBrains Mono`_
for code. EasyAdmin includes more fonts, which are served by your own
application, so browsers don't make requests to other websites::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Option\Font;

    // the included fonts for text are Inter, IbmPlexSans, Rubik and
    // AtkinsonHyperlegibleNext (designed for low-vision readers)
    Theme::new()->font(base: Font::IbmPlexSans);

    // the fonts of the operating system (Segoe UI on Windows, San Francisco on macOS, etc.)
    Theme::new()->font(base: Font::System, monospace: Font::System);

To use any font from `Google Fonts`_, pass its name. These fonts are loaded
from `Bunny Fonts`_, a mirror of Google Fonts that doesn't track users::

    Theme::new()->font(base: 'Lexend', monospace: 'Fira Code');

For a font that isn't on Google Fonts, see
:ref:`Your Company's Font <design-recipe-font>`.

.. caution::

    If your application defines a `Content Security Policy`_, add
    ``https://fonts.bunny.net`` to its ``style-src`` and ``font-src``
    directives to use these fonts.

Languages that a font doesn't support (e.g. Arabic or Chinese) use the fonts
of the operating system. All fonts are scaled to look the same size (change
this with the ``--font-size-adjust`` CSS variable; ``none`` disables it).

Use the ``baseWeight`` argument if regular text looks too thin or too bold.
The ``features`` argument controls the `OpenType features`_ of the text font,
which are optional behaviors such as digits with the same width. Pass the
cases of the ``FontFeature`` enum or the 4-character tag of any other
feature::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Option\Font;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Option\FontFeature;

    // all digits have the same width; 'ss01' is an optional style of the font
    Theme::new()->font(
        base: Font::Inter,
        baseWeight: 450,
        features: [FontFeature::TabularNumbers, 'ss01'],
    );

Each call to ``font()`` defines all these options, so the arguments that you
don't pass use their default values. Fonts only apply the features included in
their files. In-between weights (e.g. ``450``) only work with variable fonts
(one file with a range of weights), such as the ones included in EasyAdmin.

.. _design-layout:

Layout, Logo and Other Options
------------------------------

The :ref:`dashboard configuration <dashboard-configuration>` includes other
options that change the look of the backend:

* ``setTitle()`` and ``setFaviconPath()`` define the name and icon of your
  backend. The title can contain HTML, so you can
  :ref:`display a logo <design-recipe-logo>`;
* ``setDefaultColorScheme()`` defines the color scheme of users who haven't
  selected one (``'auto'`` follows the operating system) and
  ``disableDarkMode()`` removes the dark mode. If your CSS isn't ready for
  dark mode, use this temporarily;
* ``setBadgeVariant()`` changes the look of badges: ``'light'`` (default),
  ``'solid'``, ``'outline'`` or ``'dot'``. Call it in the ``configureCrud()``
  method of your dashboard (for all the backend) or of a CRUD controller;
* ``renderContentMaximized()`` makes the content as wide as the browser window
  and ``renderSidebarMinimized()`` displays the sidebar as a narrow column of
  icons. You can also call them in the ``configureCrud()`` method of a CRUD
  controller.

Users can change these widths with the two buttons on the edges of the content,
and their browser remembers that choice. If you call one of these methods, your
configuration applies whenever a page loads, and the buttons only change the
width until the next page.

.. _design-ui-kit:

Trying Designs in the UI Kit
----------------------------

The UI Kit is a set of pages inside your own dashboard where you can try the
predefined colors and fonts and get the PHP code of your selection. It also
shows all the HTML elements and :doc:`Twig components </components>` with
their code, using your theme and your CSS. Open ``_ea_ui_kit`` under the URL of
your dashboard (e.g. ``/admin/_ea_ui_kit``) or use the link in the EasyAdmin
panel of the Symfony Profiler.

The UI Kit also has a form with the form fields of EasyAdmin. It shows them
the same way as your CRUD forms, so it's useful to check your CSS changes on a
single page. Use the buttons above the form to see the fields with help
messages, with validation errors or disabled.

The UI Kit is only available in debug mode, so it doesn't exist in production.
To enable it elsewhere (e.g. in a demo server) or disable it in debug mode,
use this parameter:

.. code-block:: yaml

    # config/services.yaml
    parameters:
        easyadmin.ui_kit.enabled: true

.. _design-css-variables:

Changing Values with CSS Variables
----------------------------------

Use CSS variables when the theme doesn't have an option for what you want to
change. Most colors and sizes of the backend are CSS variables. Change one in
a CSS file and everything that uses it changes too. First, create that file
in the ``assets/`` directory and load it in your dashboard::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;

    public function configureAssets(): Assets
    {
        return Assets::new()->addCssFile('styles/admin.css');
    }

The path is relative to ``assets/``, and AssetMapper adds the hash to the URL
of the file. If you use `Webpack Encore`_, import the file in an entry and
call ``addWebpackEncoreEntry('admin')`` instead.

Then, redefine the variables that you want to change:

.. code-block:: css

    /* assets/styles/admin.css */
    :root, .ea-dark-scheme {
        --font-size-base: 15px;
        --body-max-width: 1600px;
    }

.. caution::

    If you only use ``:root``, your change doesn't apply in dark mode, because
    the dark color scheme defines its own value of the variables. Use
    ``:root, .ea-dark-scheme`` to change both color schemes.

Colors usually need a different value in each color scheme. Use the
`light-dark()`_ CSS function to set both:

.. code-block:: css

    :root, .ea-dark-scheme {
        --body-bg: light-dark(#fffdf7, #1c1917);
    }

This function only accepts colors. For other values, use a ``:root`` rule and
a separate ``.ea-dark-scheme`` rule.

These are the variables that you'll need most often:

==============================  =======================================================
Variable                        What it changes
==============================  =======================================================
``--body-bg``                   The background of the content
``--body-max-width``            The maximum width of the content (``1440px``)
``--sidebar-bg``                The background of the sidebar and around the content
``--sidebar-max-width``         The width of the sidebar (``230px``)
``--sidebar-menu-color``        The text of the main menu
``--font-size-base``            The size of the text (``14px``)
``--font-family-sans-serif``    The font of the text
``--font-family-monospace``     The font of code
``--text-color``                The color of the text
``--text-muted``                The color of secondary text
``--link-color``                The color of the links
``--table-cell-border-color``   The lines between the rows of tables
``--form-input-border-color``   The border of form fields
``--table-hover-cell-bg``       The background of the table row under the pointer
``--form-control-height``       The height of form fields
==============================  =======================================================

To find the rest, inspect an element with the developer tools of your browser,
or read these two files of the
``vendor/easycorp/easyadmin-bundle/assets/css/easyadmin-theme/`` directory:

* ``design-tokens.css`` defines the few global values that the rest of the
  design derives from, such as ``--ea-spacing``;
* ``variables-theme.css`` defines the hundreds of specific values, such as
  ``--sidebar-bg``.

Prefer the theme for the primary color, the radius, the spacing and the gray
colors. It does things that CSS can't do: for example, ``primaryColor()`` also
calculates the color of the text on top of the primary color and of the check
icons.

.. note::

    Some Bootstrap components are styled by assigning EasyAdmin variables to
    Bootstrap's own ``--bs-*`` variables (at the end of
    ``variables-theme.css``). If a Bootstrap style doesn't change, redefine
    its ``--bs-*`` variable in the ``body`` selector (not in ``:root``),
    because ``<body>`` declares them in both color schemes.

.. _design-custom-css:

Writing Your Own CSS
--------------------

When no variable does what you need, write your own CSS. It wins over the
theme, and the theme wins over the default design. You don't need
``!important`` or very specific selectors, because all the EasyAdmin styles
are in :ref:`CSS cascade layers <design-css-layers>`. For example, this
displays the logo in shades of gray:

.. code-block:: css

    .ea-sidebar-logo img {
        filter: grayscale(1);
    }

Use these CSS classes and attributes to target a specific page:

==============  ==========================================  =================================
Page            ``<body>`` ``id`` attribute                 ``<body>`` CSS classes
==============  ==========================================  =================================
``index``       ``ea-index-<entity_name>``                  ``ea-index ea-index-<entity_name>``
``detail``      ``ea-detail-<entity_name>-<entity_id>``     ``ea-detail ea-detail-<entity_name>``
``edit``        ``ea-edit-<entity_name>-<entity_id>``       ``ea-edit ea-edit-<entity_name>``
``new``         ``ea-new-<entity_name>``                    ``ea-new ea-new-<entity_name>``
Custom page     (none)                                      ``page-content``
==============  ==========================================  =================================

The ``<entity_name>`` is the short class name (e.g. ``BlogPost``). On
``index`` pages without results, the ``id`` is empty and the only class is
``ea-index``.

These other classes and attributes are also available:

* The ``<body>`` also has the ``ea-light-scheme`` or ``ea-dark-scheme`` class;
* In tables, the cells of a column have the ``data-column="<property>"``
  attribute and the CSS class of the field (e.g. ``field-money``);
* On all pages, you can add your own class to a field with ``addCssClass()``;
* Actions have the ``action-<name>`` class (e.g. ``action-delete``).

For example, this displays order totals in bold in the listing, and changes
the background of a field on all pages:

.. code-block:: css

    .ea-index-Order td[data-column="total"] {
        font-weight: var(--font-weight-semibold);
    }

    /* after calling addCssClass('is-important') in the field */
    .is-important {
        background: var(--secondary-bg);
    }

.. tip::

    Call ``addCssClass()`` instead of ``setCssClass()``, which replaces the
    default CSS classes of fields and actions, such as ``field-money``.

If CSS isn't enough because you need different HTML, read
:ref:`Customizing Templates <template-customization>`.

.. _design-recipes:

Recipes
-------

These are ready to copy. The PHP code goes in your dashboard, unless otherwise
noted. The CSS code goes in :ref:`your CSS file <design-css-variables>`.

.. _design-recipe-environment:

A Color per Environment
~~~~~~~~~~~~~~~~~~~~~~~

Nobody wants to delete production data thinking that it's the test server. Use
a different primary color on each one. Your test servers probably run in the
``prod`` environment too, so define your own environment variable
(``APP_STAGE`` in this example) on each server. The theme is regular PHP code,
so it can use any condition::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Option\ThemeColor;
    use Symfony\Component\DependencyInjection\Attribute\Autowire;

    public function __construct(
        #[Autowire(env: 'APP_STAGE')] private string $stage,
    ) {
    }

    public function configureDashboard(): Dashboard
    {
        $isProduction = 'production' === $this->stage;
        $testBadge = '<span class="badge badge-danger">TEST</span>';

        return Dashboard::new()
            ->setTitle($isProduction ? 'Acme' : 'Acme '.$testBadge)
            ->setTheme(Theme::new()
                ->primaryColor($isProduction ? '#c2410c' : ThemeColor::Red)
            );
    }

.. _design-recipe-compact:

A Compact Backend
~~~~~~~~~~~~~~~~~

For people who work with lots of data all day::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTheme(Theme::new()->spacing('xs'))
            ->renderContentMaximized();
    }

    public function configureCrud(): Crud
    {
        return Crud::new()->setPaginatorPageSize(50);
    }

.. code-block:: css

    /* optional: spacing() doesn't change the size of the text */
    :root, .ea-dark-scheme {
        --font-size-base: 13px;
    }

.. _design-recipe-personality:

A Friendly or a Technical Backend
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

A few theme options together give the backend a personality::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Option\Font;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Option\ThemeColor;

    // friendly: round corners, more space, warm grays
    Theme::new()
        ->primaryColor(ThemeColor::Rose)
        ->radius('xl')
        ->spacing('lg')
        ->grays('stone')
        ->font(base: Font::Rubik);

    // technical: square corners, less space, cool grays
    Theme::new()
        ->primaryColor(ThemeColor::Blue)
        ->radius('none')
        ->spacing('sm')
        ->grays('slate')
        ->font(base: Font::IbmPlexSans);

A More Readable Backend
~~~~~~~~~~~~~~~~~~~~~~~

For users who read for hours, or who have low vision::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Option\Font;

    Theme::new()->font(base: Font::AtkinsonHyperlegibleNext)->spacing('lg');

.. code-block:: css

    :root, .ea-dark-scheme {
        --font-size-base: 15px;
    }

.. _design-recipe-sidebar:

A Colored Sidebar
~~~~~~~~~~~~~~~~~

Changing ``--sidebar-bg`` isn't enough, because the text must remain readable
on the new background. This example uses the same colors in both color
schemes:

.. code-block:: css

    :root, .ea-dark-scheme {
        --sidebar-bg: #0f172a;
        --sidebar-border-color: #334155;
        --sidebar-logo-color: #fff;
        --sidebar-menu-color: #cbd5e1;
        --sidebar-menu-submenu-color: #94a3b8;
        --sidebar-menu-header-color: #94a3b8;
        --sidebar-menu-icon-color: #94a3b8;
        --sidebar-menu-active-item-bg: #1e293b;
        --sidebar-menu-active-item-color: #fff;
        --sidebar-menu-hover-color: #fff;
        --sidebar-item-hover-bg: #1e293b;
        /* on small screens, a header replaces the sidebar */
        --responsive-header-bg: #0f172a;
        --responsive-header-logo-color: #fff;
    }

To use your own dark color, change ``--sidebar-bg`` and
``--responsive-header-bg``. The active and hover backgrounds look best a bit
lighter than that color, and the other values work with most dark colors.
``--sidebar-bg`` also colors the area around the content.

.. _design-recipe-logo:

A Logo in the Sidebar
~~~~~~~~~~~~~~~~~~~~~

The title of the dashboard can contain HTML::

    Dashboard::new()->setTitle('<img src="/images/logo.svg" alt="Acme" height="28">');

Most logos don't look good on both a light and a dark background. In that
case, add one image for each and hide the other one with CSS::

    Dashboard::new()->setTitle(
        '<img class="logo-light" src="/images/logo.svg" alt="Acme" height="28">'
        .'<img class="logo-dark" src="/images/logo-dark.svg" alt="Acme" height="28">'
    );

.. code-block:: css

    .ea-dark-scheme .logo-light,
    .ea-light-scheme .logo-dark {
        display: none;
    }

.. _design-recipe-font:

Your Company's Font
~~~~~~~~~~~~~~~~~~~

If the font isn't included in EasyAdmin or Google Fonts, load it in your CSS.
Use ``Font::System`` in the theme. This stops browsers from loading the
default font and it uses the usual ``400``, ``500``, ``600`` and ``700`` font
weights::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Option\Font;

    Theme::new()->font(base: Font::System);

.. code-block:: css

    @font-face {
        font-family: 'Acme Sans';
        src: url('/fonts/acme-sans.woff2') format('woff2');
        font-weight: 400 700;
    }

    :root, .ea-dark-scheme {
        --font-family-sans-serif: 'Acme Sans', var(--font-family-system-sans-serif);
    }

This example uses one font file with all the weights. If you have one file per
weight, add one ``@font-face`` for each file (with ``font-weight: 400``,
``font-weight: 700``, etc.).

.. _design-recipe-wide-crud:

A Wider Listing
~~~~~~~~~~~~~~~

When a table has lots of columns, use the entire width of the browser window in
its CRUD controller::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->renderContentMaximized();
    }

To use a specific width instead, set it in CSS:

.. code-block:: css

    body.ea-index-Order {
        --body-max-width: 1800px;
    }

If the table still doesn't fit, hide some columns with ``hideOnIndex()``,
because very wide tables are hard to read anyway.

.. _design-recipe-rows:

Highlighted Table Rows
~~~~~~~~~~~~~~~~~~~~~~

To highlight orders that are overdue, :ref:`use your own index template
<replacing-templates>` in ``OrderCrudController``::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->overrideTemplate('crud/index', 'admin/order/index.html.twig');
    }

Then, add a CSS class to the rows with the ``entity_row_attributes`` block:

.. code-block:: twig

    {# templates/admin/order/index.html.twig #}
    {% extends '@EasyAdmin/crud/index.html.twig' %}

    {% block entity_row_attributes %}
        {% if entity.instance.isOverdue %}class="is-overdue"{% endif %}
    {% endblock %}

.. code-block:: css

    tr.is-overdue:not(.selected-row) td {
        background: color-mix(in oklab, var(--red-500) 12%, transparent);
    }

Change ``12%`` to make the red stronger. ``transparent`` keeps the text
readable in both color schemes, and ``:not(.selected-row)`` keeps the
highlight of the rows selected for batch actions.

Numbers That Line Up
~~~~~~~~~~~~~~~~~~~~

The digits of most fonts have different widths. The table cells of numeric
fields (``IntegerField``, ``MoneyField``, etc.) already align their digits. If
you display numbers in other places (e.g. codes in a ``TextField``), do the
same for all the text of the backend::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Option\FontFeature;

    Theme::new()->font(features: [FontFeature::TabularNumbers]);

.. _design-recipe-previous:

The Previous Default Design
~~~~~~~~~~~~~~~~~~~~~~~~~~~

The default design of EasyAdmin changed in version 5.9. This brings back the
previous primary color, gray colors and fonts. Everything else keeps the new
design, such as the links, which keep using the color of the text::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Option\Font;

    Theme::new()
        ->primaryColor('hsl(230, 61%, 58%)', dark: 'hsl(224, 84%, 49%)')
        ->grays('slate', dark: 'neutral')
        ->font(base: Font::System, monospace: Font::System);

.. _design-prose:

Styling Custom Pages
--------------------

Write regular HTML in :ref:`your custom pages <content-page-template>` and it
will look good without any CSS. Headings, paragraphs, lists, tables, images,
code blocks and more are styled automatically:

.. code-block:: twig

    {# templates/admin/release_notes.html.twig #}
    {% extends '@EasyAdmin/page/content.html.twig' %}

    {% block content_title %}Release notes{% endblock %}

    {% block main %}
        <p class="lead">This paragraph is displayed bigger than the others.</p>

        <h2>New features <twig:ea:Badge color="success">3</twig:ea:Badge></h2>
        <ul>
            <li>Invoices can be downloaded as PDF files.</li>
        </ul>

        {# wide tables scroll horizontally inside this element #}
        <div class="ea-prose-scroll">
            <table>...</table>
        </div>
    {% endblock %}

These styles are called **prose**, and the :ref:`UI Kit <design-ui-kit>`
displays all of them in its Typography page. They apply to the ``main`` block
of the templates that extend ``@EasyAdmin/page/content.html.twig``:

* If you override the ``body_class`` block, call ``{{ parent() }}`` to keep
  the class that enables them;
* Templates that extend ``@EasyAdmin/layout.html.twig`` don't get these
  styles. Make them extend ``@EasyAdmin/page/content.html.twig``, which has
  the same blocks;
* Elsewhere, add the ``ea-prose`` CSS class to the element that wraps the content;
* Inside the :ref:`Modal component <components-modal>`, add the ``ea-prose``
  class to an element of its ``body`` block, not to the ``bodyClass`` option.

Not everything inside those elements gets these styles:

* Add the ``ea-not-prose`` CSS class (or the ``data-ea-not-prose`` attribute)
  to exclude part of the content. You can't enable prose again inside that
  element;
* Bootstrap and EasyAdmin components (buttons, cards, tables with the
  ``table`` class, etc.), Bootstrap typography classes (``h1``, ``display-*``,
  ``list-unstyled``, etc.) and forms keep their own styles. Other CSS classes
  don't disable prose: ``<p class="text-center">`` is still a styled
  paragraph;
* Inside an ``ea-prose`` element, components without their own text size
  (alerts, cards, tables, etc.) inherit its font size and line height. To use
  the default typography of the backend, wrap them in an ``ea-not-prose``
  element. This doesn't happen in the ``main`` block of custom pages.

.. tip::

    Remove your own CSS for headings, paragraphs, lists and links in custom
    pages. It always wins over these styles, so while it exists, your pages
    won't look like the rest of the backend. Keep only the CSS of the elements
    unique to your pages. If you have a file with that CSS, don't load it with
    ``addCssFile()`` in the dashboard, because it would affect all pages. Load
    it only in that page:

    .. code-block:: twig

        {% block configured_stylesheets %}
            {{ parent() }}
            <link rel="stylesheet" href="{{ asset('css/sales_report.css') }}">
        {% endblock %}

To change these styles, use these variables, either for the entire backend or
only in part of a page (e.g. ``<div class="ea-prose release-notes">``):

.. code-block:: css

    :root, .ea-dark-scheme {
        --ea-prose-font-size: 16px;
        --ea-prose-line-height: 1.8;
        /* the space between paragraphs, lists, tables, etc.; all vertical gaps are
           multiples of this value */
        --ea-prose-spacing: 1.5em;
        --ea-prose-font-family: Georgia, serif;
        --ea-prose-heading-font-family: Georgia, serif;
        --ea-prose-code-font-family: 'Fira Code', monospace;
    }

    .ea-prose.release-notes {
        --ea-prose-line-height: 1.5;
    }

The colors use the ``--prose-*`` variables of ``variables-theme.css`` (e.g.
``--prose-link-color``, ``--prose-code-color``). Links are blue here to make
them visible in long texts, and they use the text color in the rest of the
interface.

Two exceptions: ``--prose-color`` only applies to elements with the
``ea-prose`` class, and ``--heading-font-weight``,
``--heading-letter-spacing`` and ``--link-font-weight`` also change the rest
of the backend.

.. _using-easyadmin-twig-components:

Using EasyAdmin Twig Components
-------------------------------

Before writing HTML and CSS for a button or a badge, use the
`Twig Components`_ of EasyAdmin. They are already installed, they look like
the rest of the backend and they follow your theme:

.. code-block:: twig

    <twig:ea:Button color="primary" htmlElement="a" href="{{ invoice_url }}">
        Download invoice
    </twig:ea:Button>
    <twig:ea:Badge color="success">Paid</twig:ea:Badge>
    <twig:ea:Alert variant="warning">This invoice is overdue.</twig:ea:Alert>

Read the :doc:`Twig Components reference </components>` to learn about all of
them (buttons, badges, icons, modals, dropdown menus, etc.) and see them live
in the :ref:`UI Kit <design-ui-kit>`.

.. _template-customization:

Customizing Templates
---------------------

Change the templates only when you need different HTML. For anything else, use
CSS: it's safer, because your copied templates may need changes when you
upgrade EasyAdmin. For the same reason, change only the Twig blocks that you
need. You can modify backend templates in two ways:

* **Override EasyAdmin templates** using Symfony's mechanism to override
  templates (this is the same for all bundles, not only EasyAdmin);
* **Replace EasyAdmin templates** using EasyAdmin features. This is more
  powerful, because you can use different templates per CRUD controller.

Overriding Templates
~~~~~~~~~~~~~~~~~~~~

Following Symfony's mechanism to `override templates from bundles`_, create
the ``templates/bundles/EasyAdminBundle/`` directory in your application and
add templates with the same path as the original templates. For example:

.. code-block:: text

    your-project/
    ├─ ...
    └─ templates/
       └─ bundles/
          └─ EasyAdminBundle/
             ├─ layout.html.twig
             └─ crud/
                ├─ index.html.twig
                └─ field/
                   └─ text.html.twig

Instead of creating the new templates from scratch, extend from the original
templates and change only the parts you want. Use a special syntax inside
``extends`` to avoid an infinite loop:

.. code-block:: twig

    {# templates/bundles/EasyAdminBundle/layout.html.twig #}

    {# DON'T DO THIS: it will cause an infinite loop #}
    {% extends '@EasyAdmin/layout.html.twig' %}

    {# DO THIS: the '!' symbol tells Symfony to extend from the original template #}
    {% extends '@!EasyAdmin/layout.html.twig' %}

    {% block content_title %}
        {# ... #}
    {% endblock %}

The original templates are in ``vendor/easycorp/easyadmin-bundle/templates/``.
These are the blocks of ``layout.html.twig`` that you'll need most often:

======================  ===================================================
Block                   What it contains
======================  ===================================================
``header_logo``         The title or logo at the top of the sidebar
``main_menu_wrapper``   The main menu
``content_top_header``  The bar above the content (search, user menu, etc.)
``content_header``      The page title and the page actions
``content_title``       Only the page title
``page_actions``        Only the page actions
``main``                The content of the page
``content_footer``      The footer of the content
======================  ===================================================

.. _replacing_templates:
.. _replacing-templates:

Replacing Templates
~~~~~~~~~~~~~~~~~~~

This option renders certain parts of the backend with your own Twig templates.
You can replace some templates globally in the
:doc:`dashboard </dashboards>`::

    use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

    #[AdminDashboard(routePath: '/admin', routeName: 'admin')]
    class DashboardController extends AbstractDashboardController
    {
        // ...

        public function configureCrud(): Crud
        {
            return Crud::new()
                // ...

                // the first argument is the "template name", which is the same as the
                // Twig path but without the `@EasyAdmin/` prefix
                ->overrideTemplate('label/null', 'admin/labels/my_null_label.html.twig')

                ->overrideTemplates([
                    'crud/index' => 'admin/pages/index.html.twig',
                    'crud/field/textarea' => 'admin/fields/dynamic_textarea.html.twig',
                ])
            ;
        }
    }

The full list of :ref:`template names <template-names>` is detailed in the
CRUD reference.

You can also replace templates per :doc:`CRUD controller </crud>` (this
overrides any change done in the dashboard)::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

    class ProductCrudController extends AbstractCrudController
    {
        // ...

        public function configureCrud(Crud $crud): Crud
        {
            return $crud
                // ...

                ->overrideTemplate('layout', 'admin/advanced_layout.html.twig')

                ->overrideTemplates([
                    'crud/field/text' => 'admin/product/field_id.html.twig',
                    'label/null' => 'admin/labels/null_product.html.twig',
                ])
            ;
        }
    }

Detail Page Templates
~~~~~~~~~~~~~~~~~~~~~

The ``detail`` page is made of small templates, so you don't need to copy the
entire ``crud/detail.html.twig`` template to change a specific element. These
are the available template names:

* ``crud/detail/field_group``: the label and value of each regular field;
* ``crud/detail/layout_field``: decides which of the following templates
  renders each form layout field (tabs, columns, fieldsets, etc.);
* ``crud/detail/tab_list``: the clickable list of tab names;
* ``crud/detail/tab_group_open`` and ``crud/detail/tab_group_close``: the
  element that wraps all tab panes;
* ``crud/detail/tab_open`` and ``crud/detail/tab_close``: each tab pane;
* ``crud/detail/column_group_open`` and ``crud/detail/column_group_close``:
  the element that wraps all columns;
* ``crud/detail/column_open`` and ``crud/detail/column_close``: each column;
* ``crud/detail/fieldset_open`` and ``crud/detail/fieldset_close``: each
  fieldset.

Use them as any other template name, either by overriding them (e.g.
``templates/bundles/EasyAdminBundle/crud/detail/fieldset_open.html.twig``) or
by replacing them::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->overrideTemplate('crud/detail/fieldset_open', 'admin/detail/fieldset_open.html.twig')
        ;
    }

.. caution::

    The ``*_open`` and ``*_close`` templates render unbalanced HTML on
    purpose: the first one only renders the opening HTML tags and the second
    one renders the closing tags. When replacing one of them, make sure that
    the tags remain consistent with the other template of the pair.

.. _field-and-action-templates:

Fields and Actions Templates
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Each :doc:`field </fields>` (and each :doc:`action </actions>`) defines a
``setTemplatePath()`` method to change how that specific field (or action) is
rendered. For example, to display the stock of a product as a colored label::

    IntegerField::new('stock')->setTemplatePath('admin/fields/stock.html.twig');

.. code-block:: twig

    {# templates/admin/fields/stock.html.twig #}
    <twig:ea:Badge color="{{ field.value > 0 ? 'success' : 'danger' }}">
        {{ field.formattedValue }}
    </twig:ea:Badge>

The same method works for actions::

    Action::new('invoice', 'Invoice')
        // ...
        ->setTemplatePath('admin/actions/invoice.html.twig');

The ``setTemplatePath()`` method only applies to fields displayed on the
``index`` and ``detail`` pages. Read the next section to learn how to
customize fields in the ``new`` and ``edit`` pages, which use Symfony forms.

.. _form-field-templates:

Form Field Templates
~~~~~~~~~~~~~~~~~~~~

EasyAdmin provides a ready-to-use Symfony `form theme`_ (not related to
``setTheme()``). Dashboards and CRUD controllers define ``addFormTheme(string $themePath)``
and ``setFormThemes(array $themePaths)`` methods so you can
`customize individual form fields`_ using your own form theme.

.. tip::

    EasyAdmin's form theme also works for regular Symfony forms not built with
    EasyAdmin fields, so forms in your custom pages can look like the rest of
    the backend. See
    :ref:`how to render Symfony forms in custom pages <custom-pages-symfony-forms>`.

Imagine a form field where you want to include an ``<a>`` element that links
to additional information. If the field is called ``title`` and belongs to a
``Product`` entity, the configuration would look like this::

    TextField::new('title')
        // ...
        ->setFormTypeOptions([
            'block_name' => 'custom_title',
        ]);

The next step is to define the template fragment used by that field, which
requires you to know the `form fragment naming rules`_ defined by Symfony:

.. code-block:: twig

    {# templates/admin/form.html.twig #}
    {# note that the Twig block name starts with an uppercase letter
       ('_Product_...' instead of '_product_...') because the first part
       of the block name is the unmodified entity name #}
    {% block _Product_custom_title_widget %}
        {# ... #}
        <a href="...">More information</a>
    {% endblock %}

Finally, add this custom theme to the list of themes used to render backend
forms::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

    class ProductCrudController extends AbstractCrudController
    {
        // ...

        public function configureCrud(Crud $crud): Crud
        {
            return $crud
                // ...

                // don't forget to add EasyAdmin's form theme at the end of the list
                // (otherwise you'll lose all the styles for the rest of form fields)
                ->setFormThemes(['admin/form.html.twig', '@EasyAdmin/crud/form_theme.html.twig'])
            ;
        }
    }

.. note::

    You can also override the form widget by using the original field name.
    In the example above it would look like this:
    ``{% block _Product_title_widget %}``. The full syntax is:
    ``{% block _<Entity name>_<Field name>_widget %}``.

.. _icon-customization:

Changing the Backend Icons
--------------------------

By default, EasyAdmin uses `FontAwesome icons`_ both for the built-in
interface icons and any custom icons that you add to menu items, fields, form
tabs, etc. The full FontAwesome icon set (~2,000 icons) is already included in
EasyAdmin, so you don't need to download any of these icons.

If you prefer to use other icons, call the ``useCustomIconSet()`` method in
your dashboard::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

    #[AdminDashboard(routePath: '/admin', routeName: 'admin')]
    class DashboardController extends AbstractDashboardController
    {
        public function configureAssets(): Assets
        {
            return Assets::new()
                ->useCustomIconSet()
            ;
        }

        // ...
    }

Then, whenever you define a custom icon for any EasyAdmin feature, use the
full icon prefix and name (``lucide:map-pin``, ``ic:baseline-calendar-month``,
etc.) that you would typically use in `Symfony UX Icons`_.

If all your icons use a common prefix (e.g. ``tabler:`` when using the
`Tabler`_ icons), pass it to the ``useCustomIconSet()`` method::

    return Assets::new()->useCustomIconSet('tabler');

Now, the ``tabler:`` prefix will be added automatically to all your custom
icon names. This way, you can use names like ``user`` and ``file`` instead of
``tabler:user`` and ``tabler:file``.

.. _customizing-flash-messages:

Customizing Flash Messages
--------------------------

EasyAdmin displays the `flash messages`_ added by your application using the
``ea:Alert`` :doc:`Twig component </components>`. Flash messages are usually
plain strings, which are translated using the translation domain configured in
the dashboard::

    $this->addFlash('success', 'post.published');

To add a title or an icon, pass an array with a mandatory ``message`` key and
optional ``title`` and ``icon`` keys::

    $this->addFlash('success', [
        'message' => 'post.published',
        'title' => 'post.published.title',
        'icon' => 'fa-circle-check',
    ]);

The ``message`` and ``title`` values are translated like plain string flash
messages; the ``icon`` value accepts the same values as the ``ea:Icon``
:doc:`Twig component </components>` (icon names from the icon set configured
in the backend).

.. _crud-design-custom-web-assets:

Adding Custom Web Assets
------------------------

Use the ``configureAssets()`` method in the :doc:`dashboard </dashboards>`
and/or the :doc:`CRUD controllers </crud>` to add your own CSS and JavaScript
files::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

    class ProductCrudController extends AbstractCrudController
    {
        // ...

        public function configureAssets(Assets $assets): Assets
        {
            return $assets
                // imports the given entrypoint defined in the importmap.php file of AssetMapper
                // it's equivalent to adding this inside the <head> element:
                // {{ importmap('admin') }}
                ->addAssetMapperEntry('admin')
                // you can also import multiple entries
                // it's equivalent to calling {{ importmap(['app', 'admin']) }}
                ->addAssetMapperEntry('app', 'admin')

                // adds the CSS and JS assets associated with the given Webpack Encore entry
                // it's equivalent to adding these inside the <head> element:
                // {{ encore_entry_link_tags('...') }} and {{ encore_entry_script_tags('...') }}
                ->addWebpackEncoreEntry('admin-app')

                // adds the CSS and JS assets associated with the given Symfony Reprise entry
                // it's equivalent to adding these inside the <head> element:
                // {{ reprise_entry_link_tags('...') }} and {{ reprise_entry_script_tags('...') }}
                ->addRepriseEntry('admin-app')

                // it's equivalent to adding this inside the <head> element:
                // <link rel="stylesheet" href="{{ asset('...') }}">
                ->addCssFile('build/admin.css')
                ->addCssFile('https://example.org/css/admin2.css')

                // it's equivalent to adding this inside the <head> element:
                // <script src="{{ asset('...') }}"></script>
                ->addJsFile('build/admin.js')
                ->addJsFile('https://example.org/js/admin2.js')

                // use these generic methods to add any code before </head> or </body>
                // the contents are included "as is" in the rendered page (without escaping them)
                ->addHtmlContentToHead('<link rel="dns-prefetch" href="https://assets.example.com">')
                ->addHtmlContentToBody('<script> ... </script>')
                ->addHtmlContentToBody('<!-- generated at '.time().' -->')
            ;
        }
    }

If you need to customize the HTML attributes or other features of the
``<link>`` and ``<script>`` tags, pass an ``Asset`` object to the
``addCssFile()``, ``addJsFile()``, ``addWebpackEncoreEntry()`` and
``addRepriseEntry()`` methods::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
    // ...

    return $assets
        ->addCssFile(Asset::new('build/admin.css')->preload()->nopush())
        ->addCssFile(Asset::new('build/admin-print.css')->htmlAttr('media', 'print'))

        ->addJsFile(Asset::new('build/admin.js')->defer())
        ->addJsFile(Asset::new('build/admin.js')->preload())
        ->addJsFile(Asset::new('build/admin.js')->htmlAttr('referrerpolicy', 'strict-origin'))

        ->addWebpackEncoreEntry(Asset::new('admin-app')->webpackEntrypointName('...'))

        // Symfony Reprise entries only support defining the Symfony Asset package
        // that they belong to; the HTML attributes of their <link> and <script>
        // tags are configured globally in the Symfony Reprise bundle configuration
        ->addRepriseEntry(Asset::new('admin-app')->reprisePackageName('...'))

        ->addCssFile(Asset::new('build/admin-detail.css')->onlyOnDetail())
        ->addJsFile(Asset::new('build/admin.js')->onlyWhenCreating())
        ->addWebpackEncoreEntry(Asset::new('admin-app')->ignoreOnForm())

        // you can also define the Symfony Asset package which the asset belongs to
        ->addCssFile(Asset::new('css/legacy.css')->package('legacy_assets'))
    ;

Use ``async()`` to add the ``async`` attribute to the ``<script>`` tag and
``htmlAttrs()`` to set several HTML attributes at once (instead of calling
``htmlAttr()`` repeatedly). Webpack Encore entries also accept
``webpackPackageName()`` to define the Symfony Asset package used to load
them::

    return $assets
        ->addJsFile(Asset::new('build/admin.js')->async())
        ->addCssFile(Asset::new('build/admin.css')->htmlAttrs([
            'media' => 'print',
            'data-turbo-track' => 'reload',
        ]))
        ->addWebpackEncoreEntry(Asset::new('admin-app')->webpackPackageName('...'))
    ;

Assets are added to all pages by default. Restrict them to some pages with the
following methods:

* ``onlyOnDetail()``, ``onlyOnIndex()``, ``onlyOnForms()``,
  ``onlyWhenCreating()`` and ``onlyWhenUpdating()`` add the asset only to
  those pages;
* ``ignoreOnDetail()``, ``ignoreOnIndex()``, ``ignoreOnForm()``,
  ``ignoreWhenCreating()`` and ``ignoreWhenUpdating()`` add the asset to all
  pages except those.

.. tip::

    :doc:`Fields </fields>` can also add CSS and JavaScript assets to the
    rendered pages. :ref:`Read this section <custom-fields>` to learn how.

.. note::

    If you want to unload the default assets included by EasyAdmin, override
    the default ``layout.html.twig`` template and empty the
    ``head_stylesheets`` and ``head_javascript`` Twig blocks.

Content Security Policy (CSP) Support
-------------------------------------

`Content Security Policy`_ (CSP) is a security feature that helps prevent
cross-site scripting (XSS) and other code injection attacks. When your
application uses strict CSP headers, all inline scripts and dynamically loaded
scripts must include a cryptographic nonce to be executed by the browser.

To use CSP nonces, install and configure `NelmioSecurityBundle`_ in your
application. EasyAdmin detects the ``csp_nonce()`` Twig function and adds the
nonce attribute to all its script tags.

Using CSP Nonces in Custom Templates
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you :ref:`override EasyAdmin templates <template-customization>` and add
your own ``<script>`` tags, use the ``{% guard %}`` Twig tag to conditionally
include the nonce. This ensures your templates work both with and without
NelmioSecurityBundle:

.. code-block:: twig

    {% guard function csp_nonce %}
        <script src="{{ asset('js/custom.js') }}" nonce="{{ csp_nonce('script') }}"></script>
    {% else %}
        <script src="{{ asset('js/custom.js') }}"></script>
    {% endguard %}

.. _design-troubleshooting:

Troubleshooting
---------------

**The backend is displayed without any styles.** The assets of the bundle
weren't installed in the ``public/bundles/`` directory of your application.
Install them with this command (remove the ``--symlink`` option if your system
doesn't support symbolic links):

.. code-block:: terminal

    $ php bin/console assets:install --symlink

**My CSS file doesn't apply.** Check in your browser that the file is loaded.
The path of ``addCssFile()`` is relative to the ``assets/`` directory if you
use AssetMapper (e.g. ``styles/admin.css``) and to the ``public/`` directory
otherwise. Your browser may also be using an old version of the file.

**My CSS rule doesn't win.** The element has a Bootstrap utility class that
uses ``!important``. See :ref:`CSS Cascade Layers <design-css-layers>`.

**My CSS variable only works in the light color scheme.** You used the
``:root`` selector. Use ``:root, .ea-dark-scheme``.

**The UI Kit returns a 404 error.** It's only available in debug mode.

**My Google Font doesn't load.** Check its name, because EasyAdmin doesn't
validate it. Otherwise, your Content Security Policy is blocking it. See
:ref:`the section about fonts <design-fonts>`.

**My custom page doesn't have the default text styles.** The template extends
``@EasyAdmin/layout.html.twig`` or it overrides ``body_class`` without calling
``parent()``. See :ref:`the section about custom pages <design-prose>`.

Reference
---------

.. _design-css-layers:

CSS Cascade Layers
~~~~~~~~~~~~~~~~~~

All backend styles are assigned to `CSS cascade layers`_, which are declared
in this order in ``assets/css/layers.css``:

* ``ea-overrides``: rules that must beat third-party styles;
* ``ea-defaults``: fallback values that lose to any other style;
* ``vendor``: third-party styles (Bootstrap, FontAwesome, etc.);
* ``ea``: EasyAdmin styles, divided into these sublayers:

  * ``ea.tokens``: CSS variables;
  * ``ea.base``: generic styles of HTML elements;
  * ``ea.prose``: typography of HTML content in custom pages;
  * ``ea.components``: styles of components (buttons, alerts, forms, etc.);
  * ``ea.utilities``: utility classes.

``ea-overrides`` comes first, and not last, because the layer order is inverted
for ``!important`` declarations.

CSS outside any layer always wins over CSS in a layer, so your rules override
the backend styles, no matter their specificity or loading order. The only
exception are the ``!important`` rules of the backend, such as the Bootstrap
utility classes (``d-none``, ``text-center``, etc.), which you can't override
without removing the class from the HTML.

Using the Source Assets with Webpack
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

EasyAdmin provides both the source files and the compiled versions of all its
CSS and JavaScript assets, so you don't have to install anything to use them.
If you want total control over the backend styles, you can integrate the
source files of the ``assets/`` directory in your application with `Webpack`_
(via Symfony's `Webpack Encore`_). EasyAdmin builds its assets with Webpack
Encore and loads them with its own Symfony Asset package, which uses the
hashed file names of ``public/manifest.json``, not the asset configuration of
your application.

.. _`Inter`: https://rsms.me/inter/
.. _`JetBrains Mono`: https://www.jetbrains.com/lp/mono/
.. _`Google Fonts`: https://fonts.google.com/
.. _`Bunny Fonts`: https://fonts.bunny.net/
.. _`OpenType features`: https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_fonts/OpenType_fonts_guide
.. _`Webpack`: https://webpack.js.org/
.. _`Webpack Encore`: https://symfony.com/doc/current/frontend.html
.. _`override templates from bundles`: https://symfony.com/doc/current/bundles/override.html#templates
.. _`customize individual form fields`: https://symfony.com/doc/current/form/form_customization.html
.. _`form fragment naming rules`: https://symfony.com/doc/current/form/form_themes.html#form-fragment-naming
.. _`form theme`: https://symfony.com/doc/current/form/form_themes.html
.. _`FontAwesome icons`: https://fontawesome.com/v6/search?m=free
.. _`Symfony UX Icons`: https://symfony.com/bundles/ux-icons/current/index.html
.. _`Tabler`: https://tabler.io/icons
.. _`Content Security Policy`: https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP
.. _`NelmioSecurityBundle`: https://github.com/nelmio/NelmioSecurityBundle
.. _`Twig Components`: https://symfony.com/bundles/ux-twig-component/current/index.html
.. _`flash messages`: https://symfony.com/doc/current/session.html#flash-messages
.. _`CSS cascade layers`: https://developer.mozilla.org/en-US/docs/Web/CSS/@layer
.. _`light-dark()`: https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/Values/color_value/light-dark
