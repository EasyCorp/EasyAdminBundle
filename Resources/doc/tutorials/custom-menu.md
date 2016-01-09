How to Customize the Main Menu
==============================

By default the main menu of the backend displays the list of the managed
entities in the same order they were defined in the configuration. The menu
items point to the `list` view of each entity.

This behavior is too limited for complex backends, which need to define custom
labels, icons or links for each menu item. In addition, complex backends usually
manage lots of entities, which should be displayed in submenus.

In this article you'll learn all the different ways supported by EasyAdmin to
create a custom navigation menu for your backend.

Reordering Menu Items
---------------------

The easiest way to reorder the menu items is to reorder the contents of the
`entities` option in the EasyAdmin configuration file. However, sometimes the
configuration file is too complex or its contents are scattered into several
files. In those cases, it's easier to use the `menu` option to reorder the menu
items just by defining the names of the entities:

```
easy_admin:
    menu: ['User', 'Product', 'Category']
    # ...
    entities:
        User:
            # ...
        Product:
            # ...
        Category:
            # ...
```

Customizing the Labels and Icons of the Menu Items
--------------------------------------------------

Use the expanded configuration format to define the label and/or icon of any or
all the menu items:

```yaml
easy_admin:
    menu:
        - { entity: 'User', label: 'Users', icon: 'user' }
        - Product
        - { entity: 'Category', label: 'Tags', icon: 'tag' }
    # ...
```

The value of the `icon` option is the name of any of the FontAwesome icons
without the `fa-` prefix (in this example, `user` will display the `fa-user` icon).

Linking Menu Items to Other Views
---------------------------------

The `view` option sets the view to display when the menu item is clicked. Its
default value is `list`, but you can use any valid view name. Views that display
or edit some entity data (`show` and `edit`) also require to define the value of
the entity primary key in the `id` option:

```yaml
easy_admin:
    menu:
        - { entity: 'User', view: 'new' }
        - Product
        - { entity: 'Category', view: 'edit', id: 341 }
    # ...
```

The `list` view also supports the `sortField` and `sortDirection` options to
define the field used to order the listing:

```yaml
easy_admin:
    menu:
        - { entity: 'User', view: 'list', sortField: 'createdAt', sortDirection: 'DESC' }
    # ...
```

Adding Menu Items not Based on Entities
---------------------------------------

Most of the times you just need to link to backend entities. However, the menu
can also contain other types of items not related to entities.

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

They can also be used to display the value of some container parameter:

```yaml
easy_admin:
    menu:
        # ...
        - { label: %app.version% }
    # ...
```

### Link elements

They display a clickable label which points to the given absolute or relative
URL. They are useful to integrate external applications in the backend. To add
a link element, set the `type` option to `link` and define the URL in the `url`
option:

```yaml
easy_admin:
    menu:
        - { label: 'Public Homepage', type: 'link', url: 'http://example.com' }
        - { label: 'Search', type: 'link', url: 'https://google.com' }
        - { label: 'Monitor Systems', type: 'link', url: '/monitor.php' }
    # ...
```

### Route elements

They display a clickable label which points to the path generated with the given
Symfony route name. They are useful to integrate controllers which are defined
anywhere in your application.

To add a route element, set the `type` option to `route`, define the route name
in the `route` option and optionally, define the route parameters in the
`params` option:

```yaml
easy_admin:
    menu:
        - { label: 'Public Homepage', type: 'route', name: 'homepage' }
        - { label: 'Some Task', type: 'route', name: 'user_some_task' }
        - { label: 'Other Task', type: 'route', name: 'user_other_task', params: { max: 7 } }
    # ...
```

### Method elements

They display a clickable label which executes the given method of the
`AdminController`. They are useful to integrate the custom actions defined
inside your EasyAdmin backend.

To add a method element, set the `type` option to `method`, define the method
name in the `name` option and optionally, define the parameters in the `params`
option:

```yaml
easy_admin:
    menu:
        - { label: 'Some Task', type: 'method', name: 'restock' }
        - { label: 'Other Task', type: 'method', name: 'restock', params: { amount: 100 } }
    # ...
```

The first menu item will execute `AdminController::restock()` when clicked and
the second item will execute `AdminController::restock(100)`.

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

In this example, the first level of the menu displays two "empty" (non-clickable
elements) called `Clients` and `Products`. Point to any of these items with your
mouse or your finger and the second level submenu will be displayed. In this
example, the submenus just display regular links to the `list` view of some
entities.

Combining all the options explained in the previous sections you can create very
advanced menus with two-level submenus and all kind of items:

```yaml
easy_admin:
    menu:
        - label: 'Clients'
          entity: 'User'
          sortField: 'name'
          sortDirection: 'ASC'
          icon: 'users'
          children:
            - { label: 'New Invoice', icon: 'file-new', type: 'method', name: 'createInvoice' }
            - { label: 'Invoices', icon: 'file-list', entity: 'Invoice' }
            - { label: 'Payments Received', icon: 'money-bag', entity: 'Payment', sortField: 'paidAt' }
        - label: 'About'
          children:
            - { label: 'Help', type: 'route', name: 'help_index' }
            - { label: 'Docs', type: 'link', url: 'http://example.com/external-docs' }
            - { label: %app.version% }
```
