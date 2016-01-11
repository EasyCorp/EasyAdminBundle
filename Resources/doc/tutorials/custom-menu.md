How to Customize the Main Menu
==============================

The main menu of the backend displays by default the list of the managed
entities. They are displayed in the same order they were defined in the
configuration and all of them point to the `list` view of each entity.

This behavior is too limited for complex backends, which need to define custom
labels, icons or links for each menu item. In addition, complex backends usually
manage lots of entities, which should be grouped in submenus.

In this article you'll learn all the different ways supported by EasyAdmin to
create a custom navigation menu for your backend.

Reordering Menu Items
---------------------

The easiest way to reorder the menu items is to reorder the contents of the
`entities` option in the EasyAdmin configuration file. However, when the
configuration file is too complex or its contents are scattered into several
files, it's easier to use the `menu` option. Just provide the names of the
entities in the order you want to display them in the menu:

```yaml
easy_admin:
    menu: ['User', 'Product', 'Category']
    # ...
    entities:
        Category:
            # ...
        Product:
            # ...
        User:
            # ...
```

Customizing the Labels and Icons of the Menu Items
--------------------------------------------------

Menu items that link to entities, display the `label` option for the related
entity. If you want to customize this value, use the `label` option of the menu
item:

```yaml
easy_admin:
    menu: ['User', 'Product', { entity: 'Category', label: 'Tags' }]
    # ...
```

When using the expanded configuration format, consider using this alternative
YAML syntax to make configuration easier to maintain:

```yaml
easy_admin:
    menu:
        - User
        - Product
        - { entity: 'Category', label: 'Tags' }
    # ...
```

Menu items can also display an icon next to their labels. Just define the `icon`
option and use the name of any of the FontAwesome icons as its value, without
the `fa-` prefix (in this example, `user` will display the `fa-user` icon):

```yaml
easy_admin:
    menu:
        - { entity: 'User', label: 'Users', icon: 'user' }
        - Product
        - { entity: 'Category', label: 'Tags', icon: 'tag' }
    # ...
```

Linking Menu Items to Other Views
---------------------------------

Instead of linking to the `list` view of an entity, you can also link to any
of its views. Just define the `params` option to define the parameters used to
generate the link of the menu item:

```yaml
easy_admin:
    menu:
        - { entity: 'User', params: { view: 'new' } }
        - Product
        - { entity: 'Category', params: { view: 'edit', id: 341 } }
    # ...
```

The `params` option is also useful to change the sort field or direction of the
`list` view:

```yaml
easy_admin:
    menu:
        - { entity: 'User', params: { sortField: 'createdAt', sortDirection: 'DESC' } }
    # ...
```

Adding Menu Items not Based on Entities
---------------------------------------

Most of the times you just need to link to backend entities. However, the main
menu can also contain other types of items not related to entities.

### Empty elements

They just display a non-clickable label. They mainly make sense when used in
combination with submenus, as explained later. To add an empty element, just
create one menu item which only defines the `label` option:

```yaml
easy_admin:
    menu:
        - { label: 'User' }
        - Product
        - Category
    # ...
```

### Link elements

They display a clickable label which points to the given absolute or relative
URL. They are useful to integrate external applications in the backend. To add
a link element, define the `url` option:

```yaml
easy_admin:
    menu:
        - { label: 'Public Homepage', url: 'http://example.com' }
        - { label: 'Search', url: 'https://google.com' }
        - { label: 'Monitor Systems', url: '/monitor.php' }
    # ...
```

### Route elements

They display a clickable label which points to the path generated with the given
Symfony route name. They are useful to integrate controllers which are defined
anywhere in your application.

To add a route element, define the `route` option and set the route name as its
value. Optionally, define the route parameters in the `params` option:

```yaml
easy_admin:
    menu:
        - { label: 'Public Homepage', route: 'homepage' }
        - { label: 'Some Task', route: 'user_some_task' }
        - { label: 'Other Task', route: 'other_task', params: { max: 7 } }
    # ...
```

Adding Submenus
---------------

The main menu items can be displayed in two-level submenus, which is very useful
for complex backends that manage lots of entities. Creating a submenu is as
easy as defining the `children` option for any first-level menu item:

```yaml
easy_admin:
    menu:
        - label: 'Clients'
          children: ['Invoice', 'Payment', 'User', 'Provider']
        - label: 'Products'
          children: ['Product', 'Stock', 'Shipment']
    # ...
```

In this example, the main menu displays two "empty" (non-clickable elements)
called `Clients` and `Products`. Point to any of these items with your mouse or
your finger and the second level submenu will be displayed. In this example, the
submenus just display regular links to the `list` view of some entities.

Combining all the options explained in the previous sections you can create very
advanced menus with two-level submenus and all kind of items:

```yaml
easy_admin:
    menu:
        - label: 'Clients'
          entity: 'User'
          params: { sortField: 'name', sortDirection: 'ASC' }
          icon: 'users'
          children:
            - { label: 'New Invoice', icon: 'file-new', route: 'createInvoice' }
            - { label: 'Invoices', icon: 'file-list', entity: 'Invoice' }
            - { label: 'Payments Received', entity: 'Payment', params: { sortField: 'paidAt' } }
        - label: 'About'
          children:
            - { label: 'Help', route: 'help_index' }
            - { label: 'Docs', url: 'http://example.com/external-docs' }
            - { label: %app.version% }
```
