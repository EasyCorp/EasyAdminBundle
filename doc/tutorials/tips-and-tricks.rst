Tips and Tricks
===============

Improving Backend Performance
-----------------------------

EasyAdmin does an intense use of Doctrine metadata introspection to generate
the backend on the fly without generating any file or resource. For complex
backends, this process can add a noticeable performance overhead.

Fortunately, Doctrine provides a simple caching mechanism for entity metadata.
If your server has APC installed, enable this cache just by adding the
following configuration:

.. code-block:: yaml

    # config/packages/prod/doctrine.yaml
    doctrine:
        orm:
            metadata_cache_driver: apc

In addition to ``apc``, Doctrine metadata cache supports ``memcache``,
``memcached``, ``xcache`` and ``service`` (for using a custom cache service).
Read the documentation about `Doctrine caching drivers`_.

Note that the previous example configures metadata caching in ``config_prod.yaml``
file, which is the configuration used for the production environment. It's not
recommended to enable this cache in the development environment to avoid having
to clear APC cache or restart the web server whenever you make any change to
your Doctrine entities.

This simple metadata cache configuration can improve your backend performance
between 20% and 30% depending on the complexity and number of your entities.

Create a Read-Only Backend
--------------------------

Disable the ``delete``, ``edit`` and ``new`` actions for all views and the users
won't be able to add, modify or remove any information:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        disabled_actions: ['delete', 'edit', 'new']

Unloading the Default JavaScript and Stylesheets
------------------------------------------------

EasyAdmin uses Bootstrap CSS and jQuery frameworks to build the interface.
In case you want to unload these files in addition to loading your own assets,
override the default ``layout.html.twig`` template and empty the
``head_stylesheets`` and ``head_javascript`` Twig blocks.

Read the :ref:`Advanced Design Customization <list-search-show-advanced-design-configuration>`
section to learn how to override default templates.

Making the Backend Use a Different Language Than the Public Website
-------------------------------------------------------------------

Imagine that the public part of your website uses French as its default locale.
EasyAdmin uses the same locale as the underlying Symfony application, so the
backend would be displayed in French too. How could you define a different
language for the backend?

You must create an event listener or subscriber that sets the request locale
before the translation service retrieves it, as explained in the following
Symfony Docs article: `How to Work with the User's Locale`_.

Don't Apply Global Doctrine Filters in the Backend
--------------------------------------------------

`Doctrine filters`_ add conditions to your queries automatically. They are
useful to solve cases like *"never display products which haven't been published"*
or *"don't display comments marked as deleted"*.

These filters can be enabled for each query, but they are usually enabled
globally for the entire application thanks to a request listener:

.. code-block:: php

    use Symfony\Component\HttpKernel\Event\GetResponseEvent;

    class DoctrineFilterListener
    {
        // ...

        public function onKernelRequest(GetResponseEvent $event)
        {
            $this->em->getFilters()->enable('is_published');
        }
    }

When using global Doctrine filters, you probably don't want to apply them in the
backend. Otherwise you won't see unpublished items or deleted comments in the
listings. Given that all EasyAdmin URLs are generated with a single route called
``easyadmin``, you can add the following to disable the Doctrine filters in the
backend:

.. code-block:: php

    public function onKernelRequest(GetResponseEvent $event)
    {
        if ('easyadmin' === $event->getRequest()->attributes->get('_route')) {
            return;
        }

        // ...
    }

Defining Dynamic Actions per Item
---------------------------------

By default, in the ``list`` view all items display the same actions. If you need
to show/hide actions dynamically per item, you can do that in a custom template
configured in the ``template`` option of the action.

Consider a backend that displays the ``Delete`` action only for items that haven't
been published yet (their ``status`` property is not ``PUBLISHED``):

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        # ...
        entities:
            Product:
                list:
                    actions:
                        - { name: 'delete', template: 'admin/product/action_delete.html.twig' }

You can give any name to this action template and store it anywhere in your
application. Then, add the needed code to display actions dynamically according
to your needs:

.. code-block:: twig

    {# templates/admin/product/action_delete.html.twig #}
    {% if item.status != 'PUBLISHED' %}
        {{ include('@EasyAdmin/default/action.html.twig') }}
    {% endif %}

Avoid Repeating Configuration using YAML Variables
--------------------------------------------------

Sometimes, certain blocks of YAML config are repeated in different places. For
example, when filtering entities with ``dql_filter`` while displaying the same
columns, you can end up with duplicated lines like:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        entities:
            VipCustomers:
                class: App\Entity\User
                label: 'VIP customers'
                list:
                    dql_filter: 'entity.budget > 100000'
                    fields:
                        - 'id'
                        - 'email'
                        - 'createdAt'
                form:
                    fields:
                        - 'email'
                search:
                    fields: ['email']

            RegularCustomers:
                class: App\Entity\User
                label: 'Regular customers'
                list:
                    dql_filter: 'entity.budget <= 100000'
                    fields:
                        - 'id'
                        - 'email'
                        - 'createdAt'
                form:
                    fields:
                        - 'email'
                search:
                    fields: ['email']

To avoid repetition, you can use "YAML variables", which are reusable blocks
that use the following syntax: ``&foo`` creates a block named "foo" (this is
like declaring a variable) and ``<<: *foo`` prints the content of the "foo"
block (is like "echo" a variable):

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        entities:
            VipCustomers: &customer_template
                class: App\Entity\User
                label: 'VIP customers'
                list:  &customer_list_template
                    dql_filter: 'entity.budget > 100000'
                    fields:
                        - 'id'
                        - 'email'
                        - 'createdAt'
                form:
                    fields:
                        - 'email'
                search:
                    fields: ['email']

            # this entity reuses the config variables defined in the other
            # entity, avoiding most repeated config
            RegularCustomers:
                <<: *customer_template
                label: 'Regular customers' # Overwrite configuration above
                list:
                    <<: *customer_list_template
                    dql_filter: 'entity.budget <= 100000'  # Overwrite configuration above

The ``customer_list_template`` is used to avoid repeating ``fields``
configuration several times, the ``fields`` configuration from ``VipCustomers``
will be reused in ``RegularCustomers``, and the second ``dql_filter``
configuration will overwrite the first one because of the merge strategy.

.. _`Doctrine caching drivers`: https://symfony.com/doc/current/reference/configuration/doctrine.html#caching-drivers
.. _`How to Work with the User's Locale`: https://symfony.com/doc/current/translation/locale.html
.. _`Doctrine filters`: https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/filters.html
