Design
======

The design of the backend is ready for any kind of application. It's been
created with `Bootstrap 5`_, and some custom CSS and JavaScript code; all
managed by `Webpack`_ via Symfony's `Webpack Encore`_.

As with any other Symfony bundle, its assets are copied to (or symlinked from) the
``public/bundles/`` directory of your application when installing or updating
the bundle. If this doesn't work for any reason, your backend won't display
properly. In those cases, run this command to install those assets manually:

.. code-block:: terminal

    # remove the --symlink option if your system doesn't support symbolic links
    $ php bin/console assets:install --symlink

Depending on your needs, there are several ways of customizing the design. Some
of them require pure CSS/JavaScript code and others require overriding and/or
creating new Twig templates.

.. _icon-customization:

Changing the Backend Icons
--------------------------

By default, EasyAdmin uses `FontAwesome icons`_ both for the built-in interface
icons and any custom icons that you add to menu items, fields, form tabs, etc.
The full FontAwesome icon set (~2,000 icons) is already included in EasyAdmin,
so you don't need to download any of these icons.

If you prefer to use other icons, call the ``useCustomIconSet()`` method in
your dashboard::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Option\IconSet;
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

Then, whenever you define a custom icon for any EasyAdmin feature, use the full
icon prefix and name (``lucide:map-pin``, ``ic:baseline-calendar-month``, etc.)
that you would typically use in `Symfony UX Icons`_.

If all your icons use a common prefix (e.g. ``tabler:`` when using the `Tabler`_
icons), pass it to the ``useCustomIconSet()`` method::

    return Assets::new()->useCustomIconSet('tabler');

Now, the ``tabler:`` prefix will be added automatically to all your custom icon
names. This way, you can use names like ``user`` and ``file`` instead of
``tabler:user`` and ``tabler:file``.

.. _template-customization:

Modifying Backend Templates
---------------------------

Backend pages are created with multiple Twig templates and fragments. You can
modify them in two ways:

* **Override EasyAdmin templates** using Symfony's mechanism to override templates
  (this is the same for all bundles, not only EasyAdmin);
* **Replace EasyAdmin templates** using EasyAdmin features.

Overriding Templates
~~~~~~~~~~~~~~~~~~~~

.. tip::

    Instead of using Symfony's mechanism to override templates, you may consider
    using a similar but more powerful feature provided by EasyAdmin to replace
    templates, as explained in :ref:`the next section <replacing-templates>`.

Following Symfony's mechanism to `override templates from bundles`_, you must
create the ``templates/bundles/EasyAdminBundle/`` directory in your application
and then create new templates with the same path as the original templates.
For example:

.. code-block:: text

    your-project/
    ├─ ...
    └─ templates/
       └─ bundles/
          └─ EasyAdminBundle/
             ├─ layout.html.twig
             ├─ menu.html.twig
             ├─ crud/
             │  ├─ index.html.twig
             │  ├─ detail.html.twig
             │  ├─ detail/
             │  │  └─ fieldset_open.html.twig
             │  └─ field/
             │     ├─ country.html.twig
             │     └─ text.html.twig
             ├─ label/
             │  └─ null.html.twig
             └─ page/
                ├─ content.html.twig
                └─ login.html.twig

Instead of creating the new templates from scratch, you can extend from the
original templates and change only the parts you want to override. However, you
must use a special syntax inside ``extends`` to avoid an infinite loop:

.. code-block:: twig

    {# templates/bundles/EasyAdminBundle/layout.html.twig #}

    {# DON'T DO THIS: it will cause an infinite loop #}
    {% extends '@EasyAdmin/layout.html.twig' %}

    {# DO THIS: the '!' symbol tells Symfony to extend from the original template #}
    {% extends '@!EasyAdmin/layout.html.twig' %}

    {% block sidebar %}
        {# ... #}
    {% endblock %}

.. _replacing_templates:

.. _replacing-templates:

Replacing Templates
~~~~~~~~~~~~~~~~~~~

This option allows you to render certain parts of the backend with your own Twig
templates. First, you can replace some templates globally in the
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

The first argument of these methods is the "template name". The full list of
:ref:`template names <template-names>` is detailed in the CRUD reference.

You can also replace templates per :doc:`CRUD controller </crud>` (this overrides
any change done in the dashboard)::

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

The contents of the ``detail`` page are composed of small templates that you can
replace individually, so you don't need to copy the entire ``crud/detail.html.twig``
template to customize how a specific element renders. These are the available
template names:

* ``crud/detail/field_group``: the label and value of each regular field;
* ``crud/detail/layout_field``: decides which of the following templates renders
  each form layout field (tabs, columns, fieldsets, etc.);
* ``crud/detail/tab_list``: the clickable list of tab names;
* ``crud/detail/tab_group_open`` and ``crud/detail/tab_group_close``: the element
  that wraps all tab panes;
* ``crud/detail/tab_open`` and ``crud/detail/tab_close``: each tab pane;
* ``crud/detail/column_group_open`` and ``crud/detail/column_group_close``: the
  element that wraps all columns;
* ``crud/detail/column_open`` and ``crud/detail/column_close``: each column;
* ``crud/detail/fieldset_open`` and ``crud/detail/fieldset_close``: each fieldset.

Use them as any other template name, either with Symfony's mechanism to override
bundle templates (e.g. ``templates/bundles/EasyAdminBundle/crud/detail/fieldset_open.html.twig``)
or with the EasyAdmin feature to replace templates::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->overrideTemplate('crud/detail/fieldset_open', 'admin/detail/fieldset_open.html.twig')
        ;
    }

.. caution::

    The ``*_open`` and ``*_close`` template pairs deliberately render unbalanced
    HTML contents: the first one only renders the opening HTML tags of the
    element and the second one renders the closing HTML tags. When replacing one
    template of a pair, make sure that the opened/closed HTML tags remain
    consistent with the other template of the pair.

.. _index-page-listing:

Index Page Listing
~~~~~~~~~~~~~~~~~~

The ``index`` page displays the entity listing as a ``<table>`` element. This
entire element is wrapped in a Twig block called ``datagrid``, so you can
replace the listing markup completely (e.g. to display entities as cards or
boxes) while keeping the rest of the page (search form, filters, global
actions, paginator) intact:

.. code-block:: twig

    {# templates/bundles/EasyAdminBundle/crud/index.html.twig #}
    {% extends '@!EasyAdmin/crud/index.html.twig' %}

    {% block datagrid %}
        <div class="datagrid product-cards" data-default-action-trigger="{{ ea.crud.defaultRowActionTrigger }}">
            {% for entity in entities %}
                {% if entity.isAccessible %}
                    <article data-id="{{ entity.primaryKeyValueAsString }}"
                        {% if entity.defaultActionUrl %}data-default-action-url="{{ entity.defaultActionUrl }}" role="link" tabindex="0"{% endif %}>
                        {% for field in entity.fields %}
                            {% set is_searchable = null == ea.crud.searchFields or field.property in ea.crud.searchFields %}
                            <div class="{{ is_searchable ? 'searchable' }} {{ field.cssClass }}">
                                {{ include(field.templatePath, {field: field, entity: entity}, with_context: false) }}
                            </div>
                        {% endfor %}
                    </article>
                {% endif %}
            {% endfor %}
        </div>
    {% endblock %}

EasyAdmin's JavaScript features don't depend on the ``<table>`` markup but on
some HTML attributes and CSS classes. Keep them in your custom markup so those
features keep working:

* ``datagrid`` CSS class on the element that wraps the entire listing;
* ``data-default-action-trigger`` attribute on that same wrapper element; it
  defines whether a ``single`` or ``double`` click runs the
  :ref:`default action <default-row-action>`;
* ``data-id`` attribute on the element of each entity; when using
  :ref:`batch actions <batch-actions>`,
  this element must also contain the ``input.form-batch-checkbox`` checkbox of
  the entity and it gets the ``selected-row`` CSS class when checked;
* ``data-default-action-url`` attribute on the element of each entity (add also
  ``role="link"`` and ``tabindex="0"`` for accessibility); clicking on the
  element runs the :ref:`default action <default-row-action>`;
* ``searchable`` CSS class on the elements that render field values; the terms
  of search queries are highlighted inside these elements;
* the CSS classes of each field (e.g. ``field-boolean``), which are needed for
  example to turn the checkboxes of boolean fields into Ajax toggles.

.. note::

    EasyAdmin styles the default listing with table-specific CSS selectors, so
    custom markup receives almost no default styling. Add your own CSS as
    explained in :ref:`the section about custom assets <crud-design-custom-web-assets>`.

.. _field-and-action-templates:

Fields and Actions Templates
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Each :doc:`field </fields>` (and each :doc:`action </actions>`) defines a
``setTemplatePath()`` method to set the Twig template used to render that
specific field (or action)::

    TextField::new('...', '...')
        // ...
        ->setTemplatePath('custom_fields/text.html.twig');

    // ...

    Action::new('...', '...')
        // ...
        ->setTemplatePath('admin/actions/my_custom_action.html.twig');

The ``setTemplatePath()`` method only applies to fields displayed on the
``index`` and ``detail`` pages. Read the next section to learn how to customize
fields in the ``new`` and ``edit`` pages, which use Symfony forms.

.. _form-field-templates:

Form Field Templates
~~~~~~~~~~~~~~~~~~~~

EasyAdmin provides a ready-to-use `form theme`_ based on Bootstrap 5. Dashboards
and CRUD controllers define ``addFormTheme(string $themePath)`` and
``setFormThemes(array $themePaths)`` methods so you can
`customize individual form fields`_ using your own form theme.

.. tip::

    EasyAdmin's form theme also works for regular Symfony forms not built with
    EasyAdmin fields. This is useful to make the forms rendered on your own
    backend pages look exactly like the rest of the backend forms. See
    :ref:`how to render Symfony forms in custom pages <custom-pages-symfony-forms>`.

Imagine a form field where you want to include an ``<a>`` element that links to
additional information. If the field is called ``title`` and belongs to a
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

Finally, add this custom theme to the list of themes used to render backend forms::

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

.. _using-easyadmin-twig-components:

Using EasyAdmin Twig Components
-------------------------------

EasyAdmin uses `Twig Components`_ to render many parts of its interface. These
components are registered under the ``ea:`` prefix and you can also use them in
your own admin templates (e.g. when overriding backend templates or creating
custom pages):

.. code-block:: twig

    <twig:ea:Badge variant="success">Published</twig:ea:Badge>

Read the :doc:`Twig Components reference </components>` to learn about all the
available components (buttons, badges, icons, modals, dropdown menus, etc.)
with practical examples of how to use them.

.. _customizing-flash-messages:

Customizing Flash Messages
--------------------------

EasyAdmin displays the `flash messages`_ added by your application using the
``ea:Alert`` :doc:`Twig component </components>`. Flash messages are usually
plain strings, which are translated using the translation domain configured in
the dashboard::

    $this->addFlash('success', 'post.published');

If you want to customize the rendered alert, pass an array with a mandatory
``message`` key and optional ``title`` and ``icon`` keys::

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

Use the ``configureAssets()`` method in the :doc:`dashboard </dashboards>` and/or
the :doc:`CRUD controllers </crud>` to add your own CSS and JavaScript files::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
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

If you need to customize the HTML attributes or other features of the ``<link>``
and ``<script>`` tags, pass an ``Asset`` object to the ``addCssFile()``,
``addJsFile()``, ``addWebpackEncoreEntry()`` and ``addRepriseEntry()`` methods::

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

        // adding full Asset objects for AssetMapper entries works too, but it's
        // useless because entries can't define any property, only their name
        ->addAssetMapperEntry(Asset::new('admin'))

        ->addCssFile(Asset::new('build/admin-detail.css')->onlyOnDetail())
        ->addJsFile(Asset::new('build/admin.js')->onlyWhenCreating())
        ->addWebpackEncoreEntry(Asset::new('admin-app')->ignoreOnForm())

        // you can also define the Symfony Asset package which the asset belongs to
        ->addCssFile(Asset::new('some-path/foo.css')->package('legacy_assets'))
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
  ``onlyWhenCreating()`` and ``onlyWhenUpdating()`` add the asset only to those
  pages;
* ``ignoreOnDetail()``, ``ignoreOnIndex()``, ``ignoreOnForm()``,
  ``ignoreWhenCreating()`` and ``ignoreWhenUpdating()`` add the asset to all
  pages except those.

.. tip::

    :doc:`Fields </fields>` can also add CSS and JavaScript assets to the
    rendered pages. :ref:`Read this section <custom-fields>` to learn how.

.. note::

    If you want to unload the default assets included by EasyAdmin, override the
    default ``layout.html.twig`` template and empty the ``head_stylesheets`` and
    ``head_javascript`` Twig blocks.

Customizing the Backend Design
------------------------------

The design of the backend is created with many CSS variables. This makes it
easier to customize it to your own needs. They are defined in two layers:

* **Design tokens** are the few global settings that the rest of the design
  derives from. They live in
  ``vendor/easycorp/easyadmin-bundle/assets/css/easyadmin-theme/design-tokens.css``.
  Changing one of them re-themes the whole backend at once.
* **Theme variables** are the hundreds of specific values built on top of those
  tokens (the sidebar background, the table border color, etc.). They live in
  ``vendor/easycorp/easyadmin-bundle/assets/css/easyadmin-theme/variables-theme.css``.

.. tip::

    The most common design changes (primary color, border radius, spacing
    density and gray scale) don't require writing any CSS: use the
    ``Dashboard::setTheme()`` method explained in the
    :ref:`dashboard configuration reference <dashboard-configuration>`. The CSS
    overrides explained in this section are the way to customize everything
    else (fonts, backgrounds, layout dimensions, etc.) and they always win over
    the ``setTheme()`` values.

Start with the design tokens, because each one changes many things consistently:

.. code-block:: css

    /* public/css/admin.css */
    :root,
    .ea-dark-scheme {
        /* the accent color of buttons, links, switches, etc. Pick one with
           enough contrast on white, because it is also used for link text */
        --ea-primary: #15803d;
        /* the base of the spacing scale; increase it for a more spacious backend,
           decrease it for a more compact one. All paddings, margins, gaps and the
           height of buttons and switches are multiples of this value */
        --ea-spacing: 0.125rem;
        /* the base of the border radius scale; set it to 0 for square corners */
        --ea-radius: 0.25rem;
    }

.. tip::

    If your primary color is light (e.g. a yellow), the white text drawn on top
    of it (button labels, etc.) becomes unreadable. Set ``--ea-primary-foreground``
    to a dark color in that case:

    .. code-block:: css

        :root, .ea-dark-scheme {
            --ea-primary: #facc15;
            --ea-primary-foreground: #1a1a1a;
        }

.. caution::

    Override the design tokens on ``:root, .ea-dark-scheme``, not just on
    ``:root``. The backend applies its
    :ref:`dark scheme <dashboard-configuration>` with a class on the ``<body>``
    element, and it defines its own value of these tokens there, so a
    ``:root``-only override applies to the light scheme but is ignored in dark
    mode. Use separate rules if you want a different value per scheme.

Then override any of the more specific theme variables the same way:

.. code-block:: css

    /* public/css/admin.css */
    :root {
        /* make the backend contents as wide as the browser window */
        --body-max-width: 100%;
        /* change the background color of the <body> */
        --body-bg: #f5f5f5;
        /* make the base font size smaller */
        --font-size-base: 13px;
    }

Then, load this CSS file in your dashboard and/or CRUD controller::

    use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

    #[AdminDashboard(routePath: '/admin', routeName: 'admin')]
    class DashboardController extends AbstractDashboardController
    {
        // ...

        public function configureAssets(): Assets
        {
            return Assets::new()->addCssFile('css/admin.css');
        }
    }

.. tip::

    The backend styles some Bootstrap components by mapping Bootstrap's own
    ``--bs-*`` variables onto the EasyAdmin ones (at the end of
    ``variables-theme.css``). If some Bootstrap style resists your changes,
    override the relevant ``--bs-*`` variable in your own CSS file.

CSS Cascade Layers
~~~~~~~~~~~~~~~~~~

In addition to redefining CSS variables, you can override any style with your
own CSS rules. All backend styles are assigned to `CSS cascade layers`_:
``ea-overrides`` for the rules that must beat third-party styles, ``vendor``
for third-party styles (Bootstrap, FontAwesome, etc.) and ``ea`` for the
EasyAdmin styles (with ``ea.tokens``, ``ea.base``, ``ea.components`` and
``ea.utilities`` sublayers). They are declared in that order in
``assets/css/layers.css``: ``ea-overrides`` comes first, and not last, because
the layer order is inverted for ``!important`` declarations.

Unlayered CSS always wins over layered CSS, so any rule in your own CSS files
overrides the backend styles, no matter its specificity or loading order.
You don't need ``!important`` flags or artificially specific selectors:

.. code-block:: css

    /* public/css/admin.css */
    .sidebar {
        background: linear-gradient(180deg, #1e293b, #0f172a);
    }

CSS Selectors
~~~~~~~~~~~~~

The ``<body>`` element of every backend page includes different ``id`` and ``class``
attributes to help you target your own styles. The ``id`` follows this pattern:

==========  ==============================================
Page        ``<body>`` ID attribute
==========  ==============================================
``detail``  ``ea-detail-<entity_name>-<entity_id>``
``edit``    ``ea-edit-<entity_name>-<entity_id>``
``index``   ``ea-index-<entity_name>``
``new``     ``ea-new-<entity_name>``
==========  ==============================================

For example, if you are editing the ``User`` entity whose ``id`` is ``200``,
the ``<body>`` of that page will be ``<body id="ea-edit-User-200" ...>``.

The pattern of the ``class`` attribute is different because it applies several
CSS classes:

==========  ============================================
Page        ``<body>`` CSS class
==========  ============================================
``detail``  ``ea-detail ea-detail-<entity_name>``
``edit``    ``ea-edit ea-edit-<entity_name>``
``index``   ``ea-index ea-index-<entity_name>``
``new``     ``ea-new ea-new-<entity_name>``
==========  ============================================

For example, if you are displaying the listing of ``User`` entity elements, the
``<body>`` of that page will be ``<body class="ea ea-index ea-index-User" ...>``.

Managing the Backend Assets with Webpack
----------------------------------------

EasyAdmin uses `Webpack`_ (via Symfony's `Webpack Encore`_) to manage its CSS
and JavaScript assets. This bundle provides both the source files and the
compiled versions of all assets, so you don't have to install Webpack to use
this bundle.

However, if you want total control over the backend styles, you can use Webpack
to integrate the CSS and JavaScript source files provided in the ``assets/``
directory. Note that EasyAdmin builds its assets with Webpack Encore, but it
loads them with the standard ``asset()`` function, so features like asset
versioning are not applied to EasyAdmin's own assets.

Content Security Policy (CSP) Support
-------------------------------------

`Content Security Policy`_ (CSP) is a security feature that helps prevent cross-site
scripting (XSS) and other code injection attacks. When your application uses strict
CSP headers, all inline scripts and dynamically loaded scripts must include a
cryptographic nonce to be executed by the browser.

EasyAdmin fully supports CSP nonces. First, install and configure `NelmioSecurityBundle`_
in your application. Then, EasyAdmin will automatically detect the ``csp_nonce()`` Twig
function and add the nonce attribute to all its script tags.

Using CSP Nonces in Custom Templates
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you :ref:`override EasyAdmin templates <template-customization>` and add your own
``<script>`` tags, use the ``{% guard %}`` Twig tag to conditionally include the nonce.
This ensures your templates work both with and without NelmioSecurityBundle:

.. code-block:: twig

    {% guard function csp_nonce %}
        <script src="{{ asset('js/custom.js') }}" nonce="{{ csp_nonce('script') }}"></script>
    {% else %}
        <script src="{{ asset('js/custom.js') }}"></script>
    {% endguard %}

The ``{% guard function csp_nonce %}`` syntax checks if the ``csp_nonce()`` function
is available before using it, allowing graceful fallback when NelmioSecurityBundle
is not installed.

.. _`Bootstrap 5`: https://github.com/twbs/bootstrap
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
