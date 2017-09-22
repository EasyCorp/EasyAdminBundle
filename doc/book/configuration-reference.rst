Configuration Reference
=======================

This section describes the entire list of configuration options available to
customize your backends. All options are defined under the root ``easy_admin``
config key:

* `site_name`_
* `formats`_

* `date`_
* `time`_
* `datetime`_
* `number`_
* `disabled_actions`_
* `design`_

* `theme`_
* `color_scheme`_
* `brand_color`_
* `form_theme`_
* `assets`_

  * `css`_
  * `js`_
* `templates`_
* `list`_

  * :ref:`title <reference-list-title>`
  * :ref:`actions <reference-list-actions>`
  * :ref:`max_results <reference-list-max-results>`
* `edit`_
* `new`_
* `show`_
* `entities`_

site_name
---------

(**default value**: ``'Easy Admin'``, **type**: string)

The name displayed as the title of the administration zone (e.g. your company
name, the project name, etc.) Example:

.. code-block:: yaml

    easy_admin:
        site_name: 'ACME Inc.'
        # ...

This value is displayed in the backend "as is", so you can include HTML tags and
they will be rendered as HTML content. Example:

.. code-block:: yaml

    easy_admin:
        site_name: '<strong>ACME</strong>'
        # ...

formats
-------

This is the parent key of the four options that configure the formats used to
display dates and numbers.

date
~~~~

(**default value**: ``'Y-m-d'``, **type**: string)

The format applied in the ``list`` and ``show`` views to display the properties
of type ``date``. This format doesn't affect to ``time`` and ``datetime``
properties. The value must be a valid PHP date format according to the syntax
options defined in http://php.net/date. Example:

.. code-block:: yaml

    easy_admin:
        formats:
            date: 'd/m/Y'
        # ...

time
~~~~

(**default value**: ``'H:i:s'``, **type**: string)

The format applied in the ``list`` and ``show`` views to display the properties
of type ``time``. This format doesn't affect to ``date`` and ``datetime``
properties. The value must be a valid PHP time format according to the syntax
options defined in http://php.net/date. Example:

.. code-block:: yaml

    easy_admin:
        formats:
            time: 'h:i A e'
        # ...

datetime
........

(**default value**: ``'F j, Y H:i'``, **type**: string)

The format applied in the ``list`` and ``show`` views to display the properties
of type ``datetime``. This format doesn't affect to ``date`` and ``time``
properties. The value must be a valid PHP time format according to the syntax
options defined in http://php.net/date. Example:

.. code-block:: yaml

    easy_admin:
        formats:
            datetime: 'd/m/Y h:i A e'
        # ...

number
~~~~~~

(**default value**: none, **type**: string)

The format applied in the ``list`` and ``show`` views to display the numeric
properties. The value must be a format according to the syntax options defined
in http://php.net/sprintf. Example:

.. code-block:: yaml

    easy_admin:
        formats:
            number: '%0.2f'
        # ...

disabled_actions
----------------

(**default value**: empty array, **type**: array)

The names of the actions disabled for all backend entities. This value can be
overridden in a entity-by-entity basis, so you can disable some actions globally
and then re-enable some of them for some entities. Example:

.. code-block:: yaml

    easy_admin:
        disabled_actions: ['new', 'edit']
        # ...

design
------

This is the parent key of the options that configure the options related to the
visual design of the backend.

theme
~~~~~

(**default value**: ``'default'``, **type**: string)

The name of the theme used to create the backend. The only theme available is
called ``default``. This option is in fact a placeholder for future use. You can
safely ignore it.

color_scheme
~~~~~~~~~~~~

(**default value**: ``'dark'``, **type**: string, **values**: ``'dark'`` or ``'light'``)

It defines the colors used in the backend design. If you find the default
``dark`` color scheme too dark, try the ``light`` color scheme. Example:

.. code-block:: yaml

    easy_admin:
        design:
            color_scheme: 'light'
        # ...

brand_color
~~~~~~~~~~~

(**default value**: ``'#E67E22'``, **type**: string, **values**: any valid CSS
expression to define a color)

This is the color used to highlight important elements of the backend, such as
the site name, links and buttons. Use the main color of your company or project
to create a backend that matches your branding perfectly. Example:

.. code-block:: yaml

    easy_admin:
        design:
            brand_color: '#3B5998'
            # any valid CSS color syntax can be used
            # brand_color: 'rgba(59, 89, 152, 0.5)'
        # ...

form_theme
~~~~~~~~~~

(**default value**: ``'horizontal'``, **type**: string or array of strings,
**values**: ``'horizontal'``, ``'vertical'``, any valid form theme template path)

The form theme used to render the form fields in the ``edit`` and ``new`` views.
The default ``'horizontal'`` value is a shortcut of ``@EasyAdmin/form/bootstrap_3_horizontal_layout.html.twig``
which displays the form fields using the default horizontal Bootstrap 3 design.

The ``'vertical'`` value is a shortcut of ``@EasyAdmin/form/bootstrap_3_layout.html.twig``
which displays the form fields using the more common vertical Bootstrap 3 design.
This style is better than ``'horizontal'`` when you want to increase the space
available to edit the property values. Example:

.. code-block:: yaml

    easy_admin:
        design:
            form_theme: 'vertical'
        # ...

Moreover, you can use your own form theme just by defining the path to its
templates. Example:

.. code-block:: yaml

    easy_admin:
        design:
            form_theme: '@App/custom_form_theme.html.twig'
        # ...

You can even use several form themes at the same time. Example:

.. code-block:: yaml

    easy_admin:
        design:
            form_theme: ['@App/custom_form_theme.html.twig', '@Acme/form/global_theme.html.twig']
        # ...

assets
~~~~~~

This is the parent key of the ``css`` and ``js`` keys that allow to include any
number of CSS and JavaScript assets in the backend layout.

css
...

(**default value**: empty array, **type**: array, **values**: any valid link
to CSS files)

This option defines the custom CSS file (or files) that are included in the
backend layout after loading the default CSS files. It's useful to link to the
CSS files that customize the design of your backends. The values of this option
are output directly in a ``<link>`` HTML element, so you can use relative or
absolute links. Example:

.. code-block:: yaml

    easy_admin:
        design:
            assets:
                css: ['/bundles/app/custom_backend.css', 'https://example.com/css/theme.css']
        # ...

CSS files are included in the same order as defined. This option cannot be used
to remove the default CSS files loaded by EasyAdmin. To do so, you must override
the ``<head>`` part of the layout template using a custom template.

js
..

(**default value**: empty array, **type**: array, **values**: any valid link
to JavaScript files)

This option defines the custom JavaScript file (or files) that are included in
the backend layout after loading the default JavaScript files. It's useful to
link to the JavaScript files that customize the behavior of your backends. The
values of this option are output directly in a ``<script>`` HTML element, so you
can use relative or absolute links. Example:

.. code-block:: yaml

    easy_admin:
        design:
            assets:
                js: ['/bundles/app/custom_widgets.js', 'https://example.com/js/animations.js']
        # ...

JavaScript files are included in the same order as defined. This option cannot
be used to remove the default JavaScript files loaded by EasyAdmin. To do so,
you must override the ``<head>`` part of the layout template using a custom template.

templates
~~~~~~~~~

(**default value**: none, **type**: strings, **values**: any valid Twig template path)

This option allows to redefine the template used to render each backend element,
from the global layout to the micro-templates used to render each form field type.
For example, to use your own template to display the properties of type ``boolean``
redefine the ``field_boolean`` template:

.. code-block:: yaml

    easy_admin:
        design:
            templates:
                field_boolean: '@MyBundle/backend/boolean.html.twig'
        # ...

Similarly, to customize the entire backend layout (used to render all pages)
redefine the ``layout`` template:

.. code-block:: yaml

    easy_admin:
        design:
            templates:
                layout: '@MyBundle/backend/base.html.twig'
        # ...

This is the full list of templates that can be redefined:

.. code-block:: yaml

    easy_admin:
        design:
            templates:
                # Used to decorate the main templates (list, edit, new and show)
                layout: '...'
                # Used to render the page where entities are edited
                edit: '...'
                # Used to render the listing page and the search results page
                list: '...'
                # Used to render the page where new entities are created
                new: '...'
                # Used to render the contents stored by a given entity
                show: '...'
                # Used to render the form displayed in the new and edit pages
                form: '...'
                # Used to render the notification area were flash messages are displayed
                flash_messages: '...'
                # Used to render the paginator in the list page
                paginator: '...'
                # Used to render array field types
                field_array: '...'
                # Used to render fields that store Doctrine associations
                field_association: '...'
                # Used to render bigint field types
                field_bigint: '...'
                # Used to render boolean field types
                field_boolean: '...'
                # Used to render date field types
                field_date: '...'
                # Used to render datetime field types
                field_datetime: '...'
                # Used to render datetimetz field types
                field_datetimetz: '...'
                # Used to render decimal field types
                field_decimal: '...'
                # Used to render float field types
                field_float: '...'
                # Used to render the field called "id". This avoids formatting its
                # value as any other regular number (with decimals and thousand separators)
                field_id: '...'
                # Used to render image field types (a special type that displays the image contents)
                field_image: '...'
                # Used to render integer field types
                field_integer: '...'
                # Used to render unescaped values
                field_raw: '...'
                # Used to render simple array field types
                field_simple_array: '...'
                # Used to render smallint field types
                field_smallint: '...'
                # Used to render string field types
                field_string: '...'
                # Used to render text field types
                field_text: '...'
                # Used to render time field types
                field_time: '...'
                # Used to render toggle field types (a special type that display
                # booleans as flip switches)
                field_toggle: '...'
                # Used when the field to render is an empty collection
                label_empty: '...'
                # Used when is not possible to access the value of the field
                # to render (there is no getter or public property)
                label_inaccessible: '...'
                # Used when the value of the field to render is null
                label_null: '...'
                # Used when any kind of error or exception happens when trying to
                # access the value of the field to render
                label_undefined: '...'
        # ...

The ``label_*`` and ``field_*`` templates are only applied in the ``list`` and
``show`` templates. In order to customize the fields of the forms displayed in
the ``new`` and ``edit`` views, use the ``easy_admin.design.form_theme`` option.

list
----

Defines the options applied globally for the ``list`` view of all entities.

.. _reference-list-title:

title
~~~~~

(**type**: string)

The default title for all entities (it can be overridden individually by each
entity).

.. code-block:: yaml

    easy_admin:
        list:
            title: 'list.%%entity_label%%'

.. _reference-list-actions:

actions
~~~~~~~

(**default value**: empty array, **type**: array)

Defines the actions available in the ``list`` view, which can be built-in
actions (``edit``, ``list``, ``new``, ``search``, ``show``) or
:doc:`custom actions <../tutorials/custom-actions>`.

.. code-block:: yaml

    easy_admin:
        list:
            actions: ['new', 'show', 'myAction', 'myOtherAction']

The actions defined in this option are added to the default ones for each view.
To remove an action, add it to this list prepending its name with a dash (``-``):

.. code-block:: yaml

    easy_admin:
        list:
            actions: ['-new', '-show', 'myAction', 'myOtherAction']

.. _reference-list-max-results:

max_results
~~~~~~~~~~~

(**default value**: 15, **type**: integer)

The maximum number of rows displayed in the ``list`` view and in the search
result page.

edit
----

Defines the options applied globally for the ``edit`` view of all entities. The
available options are ``actions`` and ``title``, which behave in the same way as
explained above for the ``list`` view.

new
---

Defines the options applied globally for the ``new`` view of all entities. The
available options are ``actions`` and ``title``, which behave in the same way as
explained above for the ``list`` view.

show
----

Defines the options applied globally for the ``show`` view of all entities.

title
~~~~~

(**type**: string)

The default title for all entities (it can be overridden individually by each
entity).

.. code-block:: yaml

    easy_admin:
        show:
            title: 'show.%%entity_label%%'

actions
~~~~~~~

(**default value**: empty array, **type**: array)

It works as explained above for the ``list`` view.

max_results
~~~~~~~~~~~

(**default value**: 10, **type**: integer)

If some entity property defines a relation with another entity, in the ``show``
view this property is displayed as a list of links to the related items. For
example, if your ``User`` and ``Article`` entities are related, when displaying
the details of any user you'll also see a list of links to their articles.

This option defines the maximum number of items displayed for those relations,
preventing issues when relations contains lots of elements. This option is also
used as the maximum number of suggestions displayed for autocomplete fields.

entities
--------

(**default value**: empty array, **type**: array)

Defines the list of entities managed by the bundle.

Deprecated Configuration Options
--------------------------------

EasyAdmin handles deprecated options transparently, so your backend will keep
working even if your configuration is outdated. However, it's a good practice to
not use these deprecated options:

list_actions
~~~~~~~~~~~~

.. code-block:: yaml

    # DEPRECATED
    easy_admin:
        list_actions: ['new', 'edit']
        # ...

    # USE THIS INSTEAD
    easy_admin:
        list:
            actions: ['new', 'edit']
        # ...

list_max_results
~~~~~~~~~~~~~~~~

.. code-block:: yaml

    # DEPRECATED
    easy_admin:
        list_max_results: 20
        # ...

    # USE THIS INSTEAD
    easy_admin:
        list:
            max_results: 20
        # ...

assets.css
~~~~~~~~~~

.. code-block:: yaml

    # DEPRECATED
    easy_admin:
        assets:
            css: ['/bundles/app/custom_backend.css']
        # ...

    # USE THIS INSTEAD
    easy_admin:
        design:
            assets:
                css: ['/bundles/app/custom_backend.css']
        # ...

assets.js
.........

.. code-block:: yaml

    # DEPRECATED
    easy_admin:
        assets:
            js: ['/bundles/app/custom_widgets.js']
        # ...

    # USE THIS INSTEAD
    easy_admin:
        design:
            assets:
                js: ['/bundles/app/custom_widgets.js']
        # ...
