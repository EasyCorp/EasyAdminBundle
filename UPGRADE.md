EasyAdmin Upgrade Guide
=======================

This document describes the backwards incompatible changes introduced by each
EasyAdminBundle version and the needed changes to be made before upgrading to
the next version.

Upgrade to 1.5.0
----------------

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

Upgrade to 1.4.0
----------------

These changes affect you only if you have customized any of the following
templates in your backend:

1) `form/entity_form.html.twig` template has been renamed to `form.html.twig` 
2) `_list_paginator.html.twig` template has been renamed to `_paginator.html.twig`
3) `_flashes.html.twig` template has been removed because it wasn't used in any other template

Full version details: https://github.com/javiereguiluz/EasyAdminBundle/releases/tag/v1.4.0
