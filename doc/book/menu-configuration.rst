Chapter 8. Menu Configuration
=============================

The main menu of the backend is created automatically based on the entities
configuration. The default menu displays a list of links pointing to the ``list``
view of each entity.

Most of the times there is no need to configure a custom menu. Keep reading this
chapter only if your backend is complex enough to require a menu with custom
labels, icons and submenus.

Reordering Menu Items
---------------------

The easiest way to reorder the menu items is to reorder the contents of the
``entities`` option in the backend configuration file. However, when the
configuration is too complex or its contents are scattered into several files,
it's easier to define the ``menu`` option under the global ``design`` option.

Just provide the names of the entities in the order you want to display them in
the menu:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        design:
            menu: ['User', 'Product', 'Category']
        # ...
        entities:
            Category:
                # ...
            Product:
                # ...
            User:
                # ...

Customizing the Labels, Icons and Targets of the Menu Items
-----------------------------------------------------------

Labels
~~~~~~

Menu items related to entities display the value of the entity's ``label`` option
(if defined) or the entity's name. If you want to customize this value, use the
``label`` option of the menu item (which must use the expanded configuration
format):

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        design:
            menu: ['User', 'Product', { entity: 'Category', label: 'Tags' }]
        # ...

Consider using this alternative YAML syntax to make menu configuration easier
to maintain:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        design:
            menu:
                - User
                - Product
                - { entity: 'Category', label: 'Tags' }
        # ...

Icons
~~~~~

Menu items display a default icon next to their labels. Use the ``icon`` option to
customize any of these icons. The value of the ``icon`` option is the name of the
FontAwesome icon without the ``fa-`` prefix (in the next example, ``user`` will
display the ``fa-user`` icon):

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        design:
            menu:
                - { entity: 'User', icon: 'user' }
                - Product
                - { entity: 'Category', label: 'Tags', icon: 'tag' }
        # ...

If you want to remove the default icon and only display the menu label, define
the ``icon`` option and leave it empty or set it to ``null``:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        design:
            menu:
                - { entity: 'Product', icon: '' }
        # ...

CSS Classes
~~~~~~~~~~~

Applications that need full customization, for example to display each menu item
with a different color or style, can define the ``css_class`` option for one or
more menu items:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        design:
            menu:
                - { entity: 'User', css_class: 'menu--user' }
                - { entity: 'Product', css_class: 'menu--product text-bold text-center' }
                - { entity: 'Category', css_class: 'default' }
        # ...

The CSS class is applied to the ``<a>`` element that wraps both the icon and
the label of the menu item.

Targets
~~~~~~~

By default, when clicking on a menu item, the linked resource is displayed in
the same browser tab. If you prefer to open the resource in a new tab or in a
specific HTML frame, define the link target using the ``target`` option of the
menu item:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        design:
            menu:
                - { entity: 'Product', target: '_blank' }
                - { entity: 'User', target: '_parent' }
                - { entity: 'Category', target: '_self' }
        # ...

Link Types
~~~~~~~~~~

By default, the links of the menu items don't include any ``rel`` attribute. If
you want to customize their ``rel`` attributes, define the ``rel`` config option
and use any of the `valid link types`_:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        design:
            menu:
                - { entity: 'Product', rel: 'dns-prefetch preconnect' }
                - { label: 'Homepage', url: 'http://example.com', rel: 'index' }
        # ...

.. tip::

    To avoid leaking internal backend information to external websites, if the
    menu item links to an external URL and doesn't define its ``rel`` option,
    the ``rel="noreferrer"`` attribute is added automatically.

Permissions
~~~~~~~~~~~

By default, all backend users can see all menu items. If some of them should be
restricted for certain kind of users, use the ``permission`` option. The value
of this option is either a string or array which defines the
`Symfony security roles`_ that the user must have to see those menu items:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        design:
            menu:
                # no permission defined, so all users can see this menu item
                - { entity: 'Product' }

                # only users with the ROLE_SUPER_ADMIN role will see this menu item
                - { entity: 'User', permission: 'ROLE_SUPER_ADMIN' }

                # when defining multiple roles, the user must have at least one of them
                # or all of them, depending on the configuration of your Symfony application
                # by default: user must have at least one of the roles
                # see https://symfony.com/doc/current/security/access_control.html#access-enforcement
                - { entity: 'Category', permission: ['ROLE_BETA', 'ROLE_ADMIN'] }
        # ...

Changing the Backend Index Page
-------------------------------

By default, when accessing the index page of the backend, you are redirected to
the ``list`` view of the first configured entity. To change this, add
``default: true`` to any other item of your custom menu configuration:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        design:
            menu:
                - User
                - { entity: 'Product', default: true }
                - Category
        # ...

Linking Menu Items to Other Actions
-----------------------------------

By default menu items link to the ``list`` view of the entities. This can be
customized using the ``params`` option of any menu item:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        design:
            menu:
                - { entity: 'User', params: { action: 'new' } }
                - Product
                - { entity: 'Category', params: { action: 'edit', id: 341 } }
        # ...

The ``params`` option is also useful to change the sort field or sort direction
of the ``list`` action:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        design:
            menu:
                - { entity: 'User', params: { sortField: 'createdAt' } }
                - { entity: 'Product', params: { sortDirection: 'ASC' } }
                - { entity: 'Category', params: { sortField: 'name', sortDirection: 'ASC' } }
        # ...

Adding Menu Items not Based on Entities
---------------------------------------

The main menu can also display items not related to the backend entities.

Menu dividers
~~~~~~~~~~~~~

These items display a non-clickable label which acts as a divider in the menu.
They are created by adding a menu item which only defines the ``label`` option.
In this example, ``Inventory`` and ``Users`` are non-clickable labels which
separate the menu items:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        design:
            menu:
                - { label: 'Inventory' }
                - Product
                - Category
                - { label: 'Users' }
                - Customers
                - Providers
        # ...

Absolute or Relative URLs
~~~~~~~~~~~~~~~~~~~~~~~~~

These items display a clickable label which points to the given absolute or
relative URL. They are useful to integrate external applications in the backend.
They are created by adding a menu item which defines the ``url`` option:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        design:
            menu:
                - { label: 'Public Homepage', url: 'http://example.com' }
                - { label: 'Search', url: 'https://google.com' }
                - { label: 'Monitor Systems', url: '/monitor.php' }
        # ...

Symfony Routes
~~~~~~~~~~~~~~

These items display a clickable label which points to the path generated with
the given Symfony route name. They are useful to integrate controllers which are
defined anywhere in your application.

They are created by adding a menu item which defines the route name in the
``route`` option and optionally some route parameters in the ``params`` option:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        design:
            menu:
                - { label: 'Public Homepage', route: 'homepage' }
                - { label: 'Some Task', route: 'user_some_task' }
                - { label: 'Other Task', route: 'other_task', params: { max: 7 } }
        # ...

Adding Submenus
---------------

The main menu items can be displayed in two-level submenus, which is very useful
for complex backends that manage lots of entities. Creating a submenu is as
easy as adding an empty menu item and defining its ``children`` option:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        design:
            menu:
                - label: 'Clients'
                  children: ['Invoice', 'Payment', 'User', 'Provider']
                - label: 'Products'
                  children: ['Product', 'Stock', 'Shipment']
        # ...

In the above example, the main menu displays two "empty" elements called
``Clients`` and ``Products``. Click on any of these items and the second level
submenu will be displayed. In this example, the submenu items just display
regular links to the ``list`` view of some entities.

Combining all the options explained in the previous sections you can create very
advanced menus with two-level submenus and all kind of items:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        design:
            menu:
                - label: 'Clients'
                  icon: 'users'
                  children:
                    - { label: 'New Invoice', icon: 'file-new', route: 'createInvoice' }
                    - { label: 'Invoices', icon: 'file-list', entity: 'Invoice', permission: 'ROLE_ACCOUNTANT' }
                    - { label: 'Payments Received', entity: 'Payment', params: { sortField: 'paidAt' } }
                - label: 'About'
                  children:
                    - { label: 'Help', route: 'help_index' }
                    - { label: 'Docs', url: 'http://example.com/external-docs' }
                    - { label: '%app.version%', permission: 'ROLE_ADMIN' }

-----

Next chapter: :doc:`complex-dynamic-backends`

.. _`valid link types`: https://developer.mozilla.org/en-US/docs/Web/HTML/Link_types
.. _`Symfony security roles`: https://symfony.com/doc/current/security.html#roles
