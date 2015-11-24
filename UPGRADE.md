EasyAdmin Upgrade Guide
=======================

This document describes the backwards incompatible changes introduced by each
EasyAdminBundle version and the needed changes to be made before upgrading to
the next version.

Upgrade to 2.0.0
----------------

 * The route used to generate every backend URL is now called `easyadmin` instead
   of `admin`. This change has been introduce to prevent collisions with your
   existing backend routes, where is common to use the `admin` route name.

   In order to upgrade, you just need to replace `admin` by `easyadmin` in all
   `path()`, `generateUrl()` and `redirectToRoute()` calls.

Upgrade to 1.9.2
----------------

 * The `render404error()` utility method has been removed from `AdminController`.
   This method was no longer used since we started throwing custom exceptions
   when an error occurs.

 * The `ajaxEdit()` method of the `AdminController` has been removed. This method
   had nothing to do with editing an entity via Ajax. It was just used to toggle
   the value of boolean properties. It has been replaced by a private method
   called `updateEntityProperty()`.

Upgrade to 1.8.0
----------------

The options that define if a entity property is readable and/or writable have
changed their name to match the names used by Symfony:

```php
// Before
$propertyMetadata['canBeGet'];
$propertyMetadata['canBeSet'];

// After
$propertyMetadata['isReadable'];
$propertyMetadata['isWritable'];
```

This only affects you if you make a very advance use of the bundle and override
lots of its functionalities.

Upgrade to 1.5.5
----------------

In order to improve the consistency of the backend design, all CSS class names
have been updated to use dashes instead of underscores, to match the syntax
used by Bootstrap classes. This means that `field_date` is now `field-date`,
`theme_boostrap...` is now `theme-bootstrap...`, etc.

Moreover, the global `css` class applied to the `<body>` element of each view
has changed:

| View   | OLD `<body>` CSS class     | NEW `<body>` CSS class
| ------ | -------------------------- | ---------------------------------------
| `edit` | `admin edit <entity name>` | `easyadmin edit edit-<entity name>`
| `list` | `admin list <entity name>` | `easyadmin list list-<entity name>`
| `new`  | `admin new <entity name>`  | `easyadmin new new-<entity name>`
| `show` | `admin show <entity name>` | `easyadmin show show-<entity name>`

All these changes only affect you if your backend uses a custom stylesheet.

Upgrade to 1.5.3
----------------

The `class` option has been renamed to `css_class`.

Before:

```yaml
easy_admin:
    actions:
        # ...
            - { name: 'edit', class: 'danger' }
    entities:
        # ...
        fields:
            - { property: 'id', class: 'col-md-12' }
```

After:

```yaml
easy_admin:
    actions:
        # ...
            - { name: 'edit', css_class: 'danger' }
    entities:
        # ...
        fields:
            - { property: 'id', css_class: 'col-md-12' }
```

Upgrade to 1.5.0
----------------

### Some methods used to tweak AdminController behaviour have changed


```php
// Before
protected function prepareNewEntityForPersist($entity) { ... }

// After
protected function prePersistEntity($entity) { ... }

// You can also create custom methods for each entity
protected function prePersistUserEntity($entity) { ... }
protected function prePersistProductEntity($entity) { ... }
// ...
```

```php
// Before
protected function prepareEditEntityForPersist($entity) { ... }

// After
protected function preUpdateEntity($entity) { ... }

// You can also create custom methods for each entity
protected function preUpdateUserEntity($entity) { ... }
protected function preUpdateProductEntity($entity) { ... }
// ...
```

### New strategy to determine the entity name

The strategy used to determine the entity name has change in preparation for
some planned features.

Previously, the entity name was infered from the entity class name. Now the
entity name is the value used as the YAML key of the configuration file:

```yaml
# Before (label = name = TestEntity)
easy_admin:
    entities:
        MyEntity: 'AppBundle\Entity\TestEntity'

# After (label = name = MyEntity)
easy_admin:
    entities:
        MyEntity: 'AppBundle\Entity\TestEntity'
```

This change probably doesn't affect your backend, because so far the entity
name is mostly an internal thing used as part as the URL of the backend pages.
In the next version of the bundle this value will be used as some PHP method
name. Therefore, developer must have absolute control over the entity name and
EasyAdmin should not autogenerate it.

### Entity names no longer can include unsafe characters

Previously, the YAML key of the configuration file was used to set the entity
label for the entities which didn't define the `label` option. This label is
used in some buttons, the main menu and the page title. Therefore, you could
use any character for the entity name, including white spaces.

Now entity names can only contain numbers, characters and underscores, and the
first character cannot be a number. This allows to use the entity name as part
of the name of some PHP methods. In order to use a fancy entity label, just
define the `label` option:

```yaml
# BEFORE
# this will throw an exception in the new bundle version
easy_admin:
    entities:
        'My Fancy Entity!': 'AppBundle\Entity\TestEntity'

# AFTER
easy_admin:
    entities:
        MyEntity:
            class: 'AppBundle\Entity\TestEntity'
            label: 'My Fancy Entity!'
```

### Changed variables names in twig views

The former `_entity` variable was used to retrieve the current entity configuration.
This variable has been renamed to `_entity_config` for convenience and readability reasons.

The old `item` variable was used to carry the currently created/edited entity.
This variable has been renamed to `entity` for better understandability.

Be sure that you did not override these variables, if so, you just have to change the name.

Upgrade to 1.4.0
----------------

These changes affect you only if you have customized any of the following
templates in your backend:

1) `form/entity_form.html.twig` template has been renamed to `form.html.twig`
2) `_list_paginator.html.twig` template has been renamed to `_paginator.html.twig`
3) `_flashes.html.twig` template has been removed because it wasn't used in any other template

Full version details: https://github.com/javiereguiluz/EasyAdminBundle/releases/tag/v1.4.0
