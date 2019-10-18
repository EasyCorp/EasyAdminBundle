Design
======

The design of the backend looks and feels modern and it's agnostic, so you can
use it for any kind of application. It's been created with `Bootstrap 4`_,
`Font Awesome icons`_ and some custom CSS and JavaScript code; all managed by
`Webpack`_ via Symfony's `Webpack Encore`_.

Depending on your needs, there are several ways of customizing the design. Some
of them require pure CSS/JavaScript code and others require overriding and/or
creating new Twig templates.

Modifying Backend Templates
---------------------------

Backend pages are created with multiple Twig templates and fragments. You can
modify them in two ways:

* **Override EasyAdmin templates** using Symfony's mechanism to override
  templates from bundles;
* **Replace EasyAdmin templates** entirely to use your own Twig templates.

Overriding Templates
~~~~~~~~~~~~~~~~~~~~

Following Symfony's mechanism to `override templates from bundles`_, you must
create the ``templates/bundles/EasyAdminBundle/`` directory in your application
and then create new templates with the same path as the original templates.
For example::

    your-project/
    ├─ ...
    └─ templates/
       └─ bundles/
          └─ EasyAdminBundle/
             ├─ field/
             │  ├─ country.html.twig
             │  └─ text.html.twig
             ├─ label/
             │  └─ null.html.twig
             └─ page/
                ├─ index.html.twig
                ├─ form.html.twig
                └─ paginator.html.twig

Instead of creating the new templates from scratch, you can extend from the
original templates and change only the parts you want to override. However, you
must use a special syntax inside ``extends`` to avoid an infinite loop:

.. code-block:: twig

    {# templates/bundles/EasyAdminBundle/page/layout.html.twig #}

    {# DON'T DO THIS: it will cause an infinite loop #}
    {% extends '@EasyAdmin/page/layout.html.twig' %}

    {# DO THIS: the '!' symbol tells Symfony to extend from the original template #}
    {% extends '@!EasyAdmin/page/layout.html.twig' %}

    {% block sidebar %}
        {# ... #}
    {% endblock %}

Replacing Templates
~~~~~~~~~~~~~~~~~~~

This option allows you to render certain part of the backend with your own Twig
template. First, you can replace templates globally per :doc:`dashboard </dashboards>`
or :doc:`resource admin </resources>` using their config methods::

    public static function getConfig(): DashboardConfig
    {
        return DashboardConfig::new()
            // ...

            ->labelTemplates([
                'null' => 'admin/labels/my_null_label.html.twig',
            ]),
            ->fieldTemplates([
                'id' => 'form/custom_fields/entity_ids.html.twig',
                'textarea' => 'admin/fields/dynamic_textarea.html.twig',
            ]),
            ->pageTemplates([
                'index' => 'admin/pages/index.html.twig',
                'layout' => 'admin/advanced_layout.html.twig',
            ]);
    }

Fields And Actions Templates
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In addition to overriding and replacing the templates used to render all fields
of certain type, each :doc:`field </fields>` (and each :doc:`action </actions>`)
defines a ``template()`` method to set the Twig template used to render that
specific field::

    TextField::new('...', '...')
        // ...
        ->template('custom_fields/text.html.twig');

    // ...

    Action::new('...', '...')
        // ...
        ->template('admin/actions/my_custom_action.html.twig');

The ``->template()`` field option only applies to fields displayed on the
``index`` and ``detail`` pages. Read the next section to learn how to customize
fields in the ``form`` pages using Symfony forms.

Form Field Templates
~~~~~~~~~~~~~~~~~~~~

EasyAdmin provides a ready-to-use `form theme`_ based on Boostrap 4. Dashboards
and resource admins define a ``->formThemes(string ...$themes)`` method so you
can `customize individual form fields`_ using your own form theme.

Imagine a form field where you want to include a ``<a>`` element that links to
additional information. If the field is called ``title`` and belongs to a
``Product`` entity, the configuration would look like this::

    TextField::new('title')
        // ...
        ->formTypeOptions([
            'block_name' => 'custom_title',
        ]);

The next step is to define the template fragment used by that field, which
requires to know the `form fragment naming rules`_ defined by Symfony:

.. code-block:: twig

    {# templates/admin/form.html.twig #}
    {% block _product_custom_title_widget %}
        {# ... #}
        <a href="...">More information</a>
    {% endblock %}

Finally, add this custom theme to the list of themes used to render backend forms::

    class ProductAdminController extends AbstractResourceAdminController
    {
        // ...

        public function getFormPageConfig(bool $isEditForm): FormPageConfig
        {
            return FormPageConfig::new()
                // ...

                // don't forget to add EasyAdmin's bootstrap_4.html.twig theme
                // to apply it to any field which is not customized by your theme
                ->formThemes('admin/form.html.twig', '@EasyAdmin/form/bootstrap_4.html.twig');
        }
    }

Adding Custom Web Assets
------------------------

Use the ``addAssets()`` method in the :doc:`dashboard </dashboards>` controller
and/or the :doc:`resource admin </resources>` controllers to add your own CSS
and JavaScript files::

    class ProductAdminController extends AbstractResourceAdminController
    {
        // ...

        public function addAssets(): AssetCollection
        {
            return AssetCollection::new()
                // the argument of these methods is passed to the asset() Twig function
                // CSS assets are added just before the closing </head> element
                // and JS assets are added just before the closing </body> element
                ->addCss('build/admin.css')
                ->addCss('https://example.org/css/admin2.css')
                ->addJs('build/admin.js');
                ->addJs('https://example.org/js/admin2.js')

                // use these generic methods to add any code before </head> or </body>
                // the contents are included "as is" in the rendered page (without escaping them)
                ->addToHead('<link rel="icon" type="image/png" href="/favicon-admin.png" />')
                ->addToBody('<script> ... </script>')
                ->addToBody('<!-- generated at '.time().' -->');
        }
    }

.. tip::

    :doc:`Fields </fields>` can also add CSS and JavaScript assets to the
    rendered pages. :ref:`Read this section <fields-custom-field>` to learn how.

.. note::

    If you want to unload the default assets included by EasyAdmin, override the
    default ``layout.html.twig`` template and empty the ``head_stylesheets`` and
    ``head_javascript`` Twig blocks.

Customizing the Backend Design
------------------------------

The design of the backend is created with lots of CSS variables. This makes it
easier to customize it to your own needs. You'll find all variables in the
``assets/css/easyadmin-theme/variables.scss`` file. To override any of them,
create a CSS file and redefine the variable values:

.. code-block:: css

    /* public/css/admin.css */
    :root {
        /* make the backend contents as wide as the browser window */
        --body-max-width: 100%;
        /* change the background color of the <body> */
        --body-bg: #f5f5f5;
        /* make the base font size smaller */
        --font-size-base: 13px;
        /* remove all border radius to make corners straight */
        --border-radius: 0px;
    }

Then, load this CSS file in your dashboard and/or resource admin::

    class ProductAdminController
    {
        // ...

        public function addAssets(): AssetCollection
        {
            return AssetCollection::new()
                ->addCss('css/admin.css');
        }
    }

.. note::

    Because of how Bootstrap styles are defined, it's not possible to use CSS
    variables to override every style. Sometimes you may need to also override
    the value of some `Sass`_ variables (which are also defined in the same
    ``assets/css/easyadmin-theme/variables.scss`` file).

CSS Selectors
~~~~~~~~~~~~~

The ``<body>`` element of every backend page includes different ``id`` and ``class``
attributes to help you target your own styles. The ``id`` follows this pattern:

==========  ==============================================
Page        ``<body>`` ID attribute
==========  ==============================================
``detail``  ``easyadmin-detail-<entity_name>-<entity_id>``
``form``    ``easyadmin-form-<entity_name>-<entity_id>``
``index``   ``easyadmin-index-<entity_name>``
==========  ==============================================

If you are editing for example the element with ``id = 200`` of the ``User`` entity,
the ``<body>`` of that page will be ``<body id="easyadmin-form-User-200" ...>``.

The pattern of the ``class`` attribute is different because it applies several
CSS classes at the same time:

==========  ============================================
Page        ``<body>`` CSS class
==========  ============================================
``detail``  ``easyadmin detail detail-<entity_name>``
``form``    ``easyadmin form form-<entity_name>``
``index``   ``easyadmin index index-<entity_name>``
==========  ============================================

If you are displaying for example the listing of ``User`` entity elements, the
``<body>`` of that page will be ``<body class="easyadmin index index-User" ...>``.

Managing the Backend Assets with Webpack
----------------------------------------

EasyAdmin uses `Webpack`_ (via Symfony's `Webpack Encore`_) to manage its CSS
and JavaScript assets. This bundle provides both the source files and the
compiled versions of all assets, so you don't have to install Webpack to use
this bundle.

However, if you want total control over the backend styles, you can use Webpack
to integrate the SCSS and JavaScript source files provided in the ``assets/``
directory. The only caveat is that EasyAdmin doesn't use Webpack Encore yet when
loading the assets, so you can't use features like versioning. This will be
fixed in future versions.

.. _`Bootstrap 4`: https://github.com/twbs/bootstrap
.. _`Sass`: https://sass-lang.com/
.. _`Font Awesome icons`: https://github.com/FortAwesome/Font-Awesome
.. _`Webpack`: https://webpack.js.org/
.. _`Webpack Encore`: https://symfony.com/doc/current/frontend.html
.. _`override templates from bundles`: https://symfony.com/doc/current/bundles/override.html#templates
.. _`customize individual form fields`: https://symfony.com/doc/current/form/form_customization.html
.. _`form theme`: https://symfony.com/doc/current/form/form_themes.html
