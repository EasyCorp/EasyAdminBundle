How to Define Custom Options for Entity Properties
==================================================

This article explains how to define custom options for any entity property and
how to use those options in the ``list``, ``search`` or ``show`` views. This
technique is useful for complex or highly customized backends, but it should be
used sparingly because it could require you some maintenance work when new
versions of this bundle are released.

Default Property Options
------------------------

Properties included in the ``fields`` option of any entity can define several
options (``property``, ``label``, ``template``, ``type``, ``help`` and
``css_class``):

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        entities:
            User:
                class: App\Entity\User
                list:
                    fields:
                        - { property: 'email', label: 'Contact' }
                        - { property: 'biography', help: 'Markdown allowed' }
                        # ...

Custom Property Options
-----------------------

.. note::

    After the publication of this article, EasyAdmin added a new configuration
    option called ``translation_domain`` which defines the domain used when
    translating contents (default value = ``messages``).

Adding custom options is as simple as defining their names and values in each
property configuration. Imagine that you want to translate the contents of a
property called ``name`` in the ``list`` view. To do so, define a custom option
called ``trans`` which indicates if the property should be translated and another
option called ``domain`` which defines the name of the translation domain to use:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    Product:
        class: App\Entity\Product
        label: 'Products'
        list:
            fields:
                - id
                - { property: 'name', trans: true, domain: 'messages' }
                # ...

Using Custom Property Options in Templates
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Property templates receive a parameter called ``field_options`` which is an array
that contains all the options defined in the configuration file for that
property. If you add custom options, they will also be available in that
``field_options`` parameter. This allows you to add custom logic to templates very
easily.

Considering that the ``name`` property is of type ``string``, override the
built-in ``field_string.html.twig`` template to add support for the ``trans``
and ``domain`` options:

.. code-block:: twig

    {# templates/bundles/EasyAdminBundle/default/field_string.html.twig #}
    {% extends '@!EasyAdminBundle/default/field_string.html.twig' %}

    {% if field_options.trans|default(false) %}
        {# translate fields defined as "translatable" #}
        {{ value|trans({}, field_options.domain|default('messages')) }}
    {% else %}
        {# if not translatable, simply include the default template #}
        {{ parent() }}
    {% endif %}

If the custom logic is too complex, it may be better to render the property with
its own custom template to not mess the default templates too much. In the
following example, the backend wants to display a collection of tags with the
colors configured for the property.

Since this business logic is too specific, it's better to not reuse the
corresponding default template. The solution is to define a custom template just
for this property and make use of the ``label_colors`` custom option:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        entities:
            Product:
                class: App\Entity\Product
                list:
                    fields:
                        # ...
                        - { property: 'tags', template: 'admin/tag_collection.html.twig',
                            label_colors: ['primary', 'success', 'info'] }

The custom ``tag_collection.html.twig`` template would look as follows:

.. code-block:: twig

    {# templates/admin/tag_collection.html.twig #}
    {% set colors = field_options.label_colors|default(['primary']) %}

    {% for tag in value %}
        <span class="label label-{{ cycle(colors, loop.index) }}">{{ tag }}</span>
    {% endfor %}

Custom Entity Options
---------------------

This very same technique can be applied to entities too. Since the configuration
options are not constrained, you can add as many custom entity properties as
needed. Just define their name and value to use them everywhere on the backend:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        entities:
            User:
                class: App\Entity\User
                export_path: '%kernel.project_dir/var/export/user'
                password_encoding: { algorithm: 'bcrypt', cost: 12 }
                # ...

In the above example, the backend defines the ``export_path`` and
``password_encoding`` custom options, which will be included by EasyAdmin in the
processed ``User`` configuration.

Instead of defining the custom options at the same level of the built-in
options, it's better to define them under a custom parent option. This eases the
maintenance of your custom options and reduces the risk of option name
collisions. You can even use the name of your project as the name of the parent
option:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        entities:
            User:
                class: App\Entity\User
                acme_project:
                    export_path: '%kernel.project_dir/var/export/user'
                    password_encoding: { algorithm: 'bcrypt', cost: 12 }
                # ...
