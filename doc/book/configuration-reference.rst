Configuration Reference
=======================

This section describes the entire list of configuration options available to
customize your backends. All options are defined under the root ``easy_admin``
config key:

* `site_name`_
* `formats`_
* `translation_domain`_

* `date`_
* `time`_
* `datetime`_
* `number`_
* `disabled_actions`_
* `user`_

  * `avatar_property_path`_
  * `display_avatar`_
  * `display_name`_
  * `name_property_path`_
* `design`_

  * `brand_color`_
  * `form_theme`_
* `assets`_

  * `css`_
  * `js`_
* `templates`_
* `list`_

  * :ref:`title <reference-list-title>`
  * :ref:`actions <reference-list-actions>`
  * :ref:`batch_actions <reference-list-batch-actions>`
  * :ref:`collapse_actions <reference-list-collapse-actions>`
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

translation_domain
------------------

(**default value**: ``'messages'``, **type**: string)

By default, all the interface elements are translated using the ``messages``
translation domain (which is also the default Symfony behavior). If you use a
another `Symfony translation domain`_, set its name using this option:

.. code-block:: yaml

    easy_admin:
        # The backend will now use the translations defined under the 'admin' domain
        # (e.g. <your-project>/translations/admin.en.xlf)
        translation_domain: 'admin'
        # ...

This option can also be set per entity, which overrides the value of the global
option:

.. code-block:: yaml

    easy_admin:
        # all entities will use 'admin' as the domain ...
        translation_domain: 'admin'
        entities:
            Product:
                # ... except this entity, which uses a different domain
                translation_domain: 'marketing_product'
        # ...

The drawback of defining custom translation domains per entity is that some
elements (such as the main menu) will be untranslated unless you duplicate the
translations in all the different domains used by the backend. That's why you
are strongly encouraged to either keep using the default ``messages`` domain or
define just one custom domain for the entire backend.

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

user
----

avatar_property_path
~~~~~~~~~~~~~~~~~~~~

(**default value**: ``null``, **type**: ``string`` | ``null``)

The value of this option is any valid `PropertyAccess component`_ expression.
It is applied to the ``app.user`` object of the Twig template to get the value
of the user avatar. This value is used in the ``src`` attribute of the ``<img>``
element used to display the avatar.

display_avatar
~~~~~~~~~~~~~~

(**default value**: ``true``, **type**: bool)

If ``true``, the avatar of the logged in user is displayed on all pages. Set it
to ``false`` to hide it. By default, the avatar is a generic user icon. Use the
``avatar_property_path`` to change this.

display_name
~~~~~~~~~~~~

(**default value**: ``true``, **type**: bool)

If ``true``, the name of the logged in user is displayed on all pages. Set it
to ``false`` to hide it. By default, the user name is the string conversion of
the user object returned by ``app.user`` in the Twig template. Use the
``name_property_path`` to change this.

name_property_path
~~~~~~~~~~~~~~~~~~

(**default value**: ``__toString``, **type**: ``string`` | ``null``)

The value of this option is any valid `PropertyAccess component`_ expression.
It is applied to the ``app.user`` object of the Twig template to get the value
of the user name. The special ``__toString`` value is used to perform a string
conversion of the user object.

design
------

This is the parent key of the options that configure the options related to the
visual design of the backend.

brand_color
~~~~~~~~~~~

(**default value**: ``'hsl(230, 55%, 60%)'``, **type**: string, **values**: any valid CSS
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

.. seealso::

    This option is useful when the only design change you want to make is to
    update the main color of the interface. However, if you start changing more
    design elements, it's better to unset this option and use CSS variables as
    explained :ref:`in this section <customizing-the-backend-design>`.

form_theme
~~~~~~~~~~

(**default value**: ``'@EasyAdmin/form/bootstrap_4.html.twig'``, **type**: string or array of strings,
**values**: any valid form theme template path)

The ``edit`` and ``new`` forms use a custom form theme that matches the backend
design. EasyAdmin also uses a Symfony feature to `disable the global form themes`_
in those forms so they don't mess with the rest of your application form themes.

You can add your own themes to the backend forms and you can even replace the
custom theme entirely:

.. code-block:: yaml

    easy_admin:
        design:
            # using only your own custom form theme (disables the default theme)
            form_theme: '@App/custom_form_theme.html.twig'

            # using only multiple custom form themes (disables the default theme)
            form_theme: ['@App/custom_form_theme.html.twig', '@Acme/form/global_theme.html.twig']

            # using EasyAdmin theme and your own custom theme
            form_theme: ['@EasyAdmin/form/bootstrap_4.html.twig', '@App/custom_form_theme.html.twig']

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

.. _reference-list-batch-actions:

batch_actions
~~~~~~~~~~~~~

(**default value**: empty array, **type**: array)

Defines the "batch actions" available in the ``list`` view, which are those
actions applied to multiple items at the same time. The only built-in batch
action is ``delete``, but you can create your own
:ref:`custom batch actions <custom-batch-actions>`.

This option can be defined globally and/or per entity (entity config overrides
the global config). To remove an action, add it to this list prefixing its name
with a dash (``-``):

.. code-block:: yaml

    easy_admin:
        list:
            batch_actions: ['delete', 'myAction']
        # ...
        entities:
            Product:
                # ...
                list:
                    batch_actions: ['-delete', 'myOtherAction']

.. _reference-list-collapse-actions:

collapse_actions
~~~~~~~~~~~~~~~~

(**default value**: ``false``, **type**: boolean)

If set to ``true``, the actions of each listing item are displayed inside a
dropdown menu that is revealed when moving the mouse over it. It's useful for
complex backends that display lots of information on each list row and don't
have enough space to display the actions expanded.

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

.. _`PropertyAccess component`: https://symfony.com/components/PropertyAccess
.. _`Symfony translation domain`: https://symfony.com/doc/current/components/translation.html#using-message-domains
.. _`disable the global form themes`: https://symfony.com/doc/current/form/form_themes.html#disabling-global-themes-for-single-forms
