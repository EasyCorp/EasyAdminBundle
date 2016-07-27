EasyAdmin Upgrade Guide
=======================

This document describes the backwards incompatible changes introduced by each
EasyAdminBundle version and the needed changes to be made before upgrading to
the next version.

Upgrade to 2.0.0 (XX/XXX/201X)
------------------------------

 * The route used to generate every backend URL is now called `easyadmin` instead
   of `admin`. This change has been introduce to prevent collisions with your
   existing backend routes, where is common to use the `admin` route name.

   In order to upgrade, you just need to replace `admin` by `easyadmin` in all
   `path()`, `generateUrl()` and `redirectToRoute()` calls.

Upgrade to 1.15.X (XX/October/2016)
-----------------------------------

* The template fragments used to render each property value (e.g.
  `field_array.html.twig`, `label_null.html.twig`, etc.) now receive two new
  variables called `entity_config` and `backend_config`, which are useful for
  advanced backends.
* The `image` fields and the VichUploader files and images now are rendered
  using the `asset()` Twig function. Depending on your configuration, you may
  need to change or remove EasyAdmin's `base_path` option and define the proper
  base path using Symfony's asset configuration.

Upgrade to 1.13.0 (11/May/2016)
---------------------------------

* The configuration of the backend is no longer processed in a compiler pass
  but generated with a cache warmer. This is done to avoid issues with Doctrine
  and Twig services, which are needed to process the configuration but they are
  not fully available during the container compilation.
* In the development environment, the backend config is fully processed for each
  request, so you might notice a slight performance impact. In exchange, you
  won't suffer any cache problem or any outdated config problem. In production
  the backend config is fully processed in the cache warmer or, if any problem
  happened, during the first request. Then the config is cached in the file
  system and reused in the following requests.
* The `easyadmin.configurator` service has been renamed to `easyadmin.config.manager`
* The `easyadmin.config` container parameter no longer contains the fully
  processed backend configuration. Now it only contains the configuration that
  the developer defined in their YAML files. The equivalent way to get the
  fully processed backend config is to use the `easyadmin.config.manager`
  service:

  // Before
  $backendConfig = $this->getParameter('easyadmin.config');

  // After
  $backendConfig = $this->get('easyadmin.config.manager')->getBackendConfig();

Upgrade to 1.12.6 (15/April/2016)
---------------------------------

* Web assets are now combined and minified to improve frontend performance. In
  previous versions, CSS and JS were included by loading lots of small files.
  Starting from this version, the backend only loads one CSS file (called
  `easyadmin-all.min.css`) and one JS file (called `easyadmin-all.min.js`).
  The individual CSS/JS files are still available in case you override the
  backend design and want to pick some of them individually.
  The new CSS/JSS files should be available in your application after upgrading
  this version bundle. If you have any problem, install the new assets executing
  the `assets:install --symlinks` console command.

Upgrade to 1.12.5 (03/March/2016)
---------------------------------

 * The `renderCssAction()` method of the AdminController has been deprecated and
   its associated route `@Route("/_css/easyadmin.css", name="_easyadmin_render_css")`
   has been removed. The custom CSS now is preprocessed during container compilation
   and the result is stored in the `_internal.custom_css` option of the processed
   backend configuration.

Upgrade to 1.11.6 (26/February/2016)
------------------------------------

 * `findBy()` and `createSearchQueryBuilder()` methods now receive two new
   parameters called `$sortField` and `$sortDirection` to allow sorting the
   search results.

Upgrade to 1.9.5 (13/December/2015)
-----------------------------------

 * The `isReadable` and `isWritable` options are no longer available for each
   property metadata. These options were needed when we introspected the getters
   and setters of the properties ourselves. We now use the Symfony PropertyAccessor
   component to get and set values for entity properties.

 * The `Configurator::introspectGettersAndSetters()` method, the
   `Reflection/ClassPropertyReflector` class and the `easyadmin.property_reflector`
   service have been deleted and replaced by the use of the `PropertyAccessor`
   class, its `getValue()` and `setValue()` methods and the `@property_accessor`
   service.

Upgrade to 1.9.2 (24/November/2015)
-----------------------------------

 * The `render404error()` utility method has been removed from `AdminController`.
   This method was no longer used since we started throwing custom exceptions
   when an error occurs.

 * The `ajaxEdit()` method of the `AdminController` has been removed. This method
   had nothing to do with editing an entity via Ajax. It was just used to toggle
   the value of boolean properties. It has been replaced by a private method
   called `updateEntityProperty()`.

Upgrade to 1.8.0 (8/November/2015)
----------------------------------

 * The options that define if a entity property is readable and/or writable have
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

 * The `form.html.twig` template has been removed and therefore, you cannot define
   the `easy_admin.design.templates.form` to override it by your own template.
   If you want to customize the forms of the backend, use a proper Symfony form
   theme and enable it in the `easy_admin.design.form_theme` option.

Upgrade to 1.5.5 (22/June/2015)
-------------------------------

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

Upgrade to 1.5.3 (26/May/2015)
------------------------------

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

Upgrade to 1.5.0 (17/May/2015)
------------------------------

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

Upgrade to 1.4.0 (1/May/2015)
-----------------------------

These changes affect you only if you have customized any of the following
templates in your backend:

1) `form/entity_form.html.twig` template has been renamed to `form.html.twig`
2) `_list_paginator.html.twig` template has been renamed to `_paginator.html.twig`
3) `_flashes.html.twig` template has been removed because it wasn't used in any other template

Full version details: https://github.com/javiereguiluz/EasyAdminBundle/releases/tag/v1.4.0
