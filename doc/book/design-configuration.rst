Chapter 4. Design Configuration
===============================

The design of the backend is loosely based on the popular `AdminLTE template`_
and it's created with `Bootstrap 4`_, `jQuery`_ and `Font Awesome icons`_. You
can customize this design in two ways:

1. For **simple backends**, you can change the value of some YAML configuration
   options and create a CSS file to override some CSS variables.
2. For **more complex backends**, you can process CSS and JavaScript assets with
   Webpack and you can override every template and fragment used to render the
   backend pages.

All the configuration options explained in this chapter are defined under the
global ``design`` YAML key:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        design:
            # ... design configuration options

Changing the Main Backend Color
-------------------------------

Define the ``brand_color`` option to change the default accent color used by the
backend interface:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        # ...
        design:
            brand_color: '#1ABC9C'

            # the value of this option can be any valid CSS color
            brand_color: 'red'
            brand_color: 'rgba(26, 188, 156, 0.85)'

            # if the color includes a '%', you must double it to escape it in the YAML file
            brand_color: 'hsl(0, 100%%, 50%%);'

Adding Custom Web Assets
------------------------

Some backends may require to load your own CSS and JavaScript files. Use the
``assets`` option to define the paths of the web assets to load:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        # ...
        design:
            assets:
                # all kinds of assets are supported and linked accordingly
                css:
                    - '//example.org/css/admin1.css'
                    - 'http://example.org/css/admin2.css'
                    - 'https://example.org/css/admin3.css'
                    - '/bundles/user/css/admin4.css'
                    - 'bundles/app/css/admin5.css'
                js:
                    - '//example.org/js/admin1.js'
                    - 'http://example.org/js/admin2.js'
                    - 'https://example.org/js/admin3.js'
                    - '/bundles/user/js/admin4.js'
                    - 'bundles/app/js/admin5.js'

CSS Selectors
~~~~~~~~~~~~~

The ``<body>`` element of every backend page includes different ``id`` and ``class``
attributes to help you target your own styles. The ``id`` follows this pattern:

========  ============================================
View      ``<body>`` ID attribute
========  ============================================
``edit``  ``easyadmin-edit-<entity_name>-<entity_id>``
``list``  ``easyadmin-list-<entity_name>``
``new``   ``easyadmin-new-<entity_name>``
``show``  ``easyadmin-show-<entity_name>-<entity_id>``
========  ============================================

If you are editing for example the element with ``id = 200`` of the ``User`` entity,
the ``<body>`` of that page will be ``<body id="easyadmin-edit-User-200" ...>``.

The pattern of the ``class`` attribute is different because it applies several
CSS classes at the same time:

========  ============================================
View      ``<body>`` CSS class
========  ============================================
``edit``  ``easyadmin edit edit-<entity_name>``
``list``  ``easyadmin list list-<entity_name>``
``new``   ``easyadmin new new-<entity_name>``
``show``  ``easyadmin show show-<entity_name>``
========  ============================================

If you are displaying for example the listing of ``User`` entity elements, the
``<body>`` of that page will be ``<body class="easyadmin list list-User" ...>``.

Changing the favicon
--------------------

A nice trick for backends is to change their favicon to better differentiate
the backend from the public website (this is specially useful when opening lots
of tabs in your browser).

If you want to apply this technique, create the favicon image (using any common
format: ``.ico``, ``.png``, ``.gif``, ``.jpg``) and set the ``favicon`` option:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        design:
            # ...
            assets:
                favicon: '/assets/backend/favicon.png'

            # if the favicon uses an uncommon graphic format, define its MIME type
            assets:
                favicon:
                    path: '/assets/backend/favicon.xxx'
                    mime_type: 'image/xxx'

The value of the ``favicon`` option is used as the value of the ``href`` attribute
of the ``<link rel="icon" ...>`` element in the backend's layout.

Enabling RTL Support
--------------------

The RTL writing support is enabled automatically in the interface when the
locale of the application is ``ar`` (Arabic), ``fa`` (Persian) or ``he``
(Hebrew). If you need a more precise control over this setting, configure the
``rtl`` boolean option:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        design:
            # ...
            rtl: true

Loading the Entire Bootstrap Framework
--------------------------------------

In order to improve performance, the backend doesn't load the entire CSS and
JavaScript code from Bootstrap but only the parts that uses it. If you create
custom backends, you may need to load the missing Bootstrap parts.

Instead of downloading and including the entire Bootstrap yourself, you can use
the ``bootstrap-all.css`` and ``bootstrap-all.js`` files provided by EasyAdmin
which contains all the Bootstrap parts not included by default by the backend:

.. code-block:: yaml

    easy_admin:
        # ...
        design:
            assets:
                css:
                    # ...
                    - 'bundles/easyadmin/bootstrap-all.css'
                js:
                    - 'bundles/easyadmin/bootstrap-all.js'

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

Then, load this CSS file in your backend:

.. code-block:: yaml

    easy_admin:
        # ...
        design:
            assets:
                css:
                    # ...
                    - 'css/admin.css'

.. note::

    Because of how Bootstrap styles are defined, it's not possible to use CSS
    variables to override every style. Sometimes you may need to also override
    the value of some Sass variables (which are also defined in the same
    ``assets/css/easyadmin-theme/variables.scss`` file).

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

Advanced Customization of Backend Pages
---------------------------------------

In addition to customizing the CSS and JavaScript files used to create the
backend interface, EasyAdmin lets you customize every single Twig template used
to render contents.

In read-only pages (``list``, ``search`` and ``show``) you can override or
create new Twig template fragments to customize the rendering of each property
for any entity. Read the :ref:`Advanced Design Customization <list-search-show-advanced-design-configuration>`
section to learn more about it.

In read-write pages (``edit`` and ``new``) EasyAdmin relies on Symfony's Form
component to render contents, so you'll need to create a new form theme to
override the default design. In addition, this bundle defines some elements not
available by default in Symfony (form tabs, fieldsets, sections, etc.) so you
can create complex forms. Read the :ref:`Advanced Form Design <edit-new-advanced-form-design>`
section to learn more about it.

.. _`AdminLTE template`: https://github.com/almasaeed2010/AdminLTE
.. _`Bootstrap 4`: https://github.com/twbs/bootstrap
.. _`Sass`: https://sass-lang.com/
.. _`jQuery`: https://github.com/jquery/jquery
.. _`Font Awesome icons`: https://github.com/FortAwesome/Font-Awesome
.. _`Webpack`: https://webpack.js.org/
.. _`Webpack Encore`: https://symfony.com/doc/current/frontend.html

-----

Next chapter: :doc:`list-search-show-configuration`
