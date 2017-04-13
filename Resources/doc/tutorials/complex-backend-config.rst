How to Manage Configuration for Complex Backends
================================================

The recommended way to start configuring your backend is to use the
``app/config/config.yml`` file and put your configuration under the
``easy_admin`` key. However, for medium-sized and large backends this
configuration can be very long and hard to maintain.

In those cases, it's better to create a new ``app/config/easyadmin.yml`` file to
define all the configuration related to the backend and then, import that file
from the general ``config.yml`` file:

.. code-block:: yaml

    # app/config/config.yml
    imports:
        - { resource: parameters.yml }
        - { resource: security.yml }
        - { resource: services.yml }
        - { resource: easyadmin.yml }  # <-- add this line

    # app/config/easyadmin.yml      # <-- create this file
    easy_admin:
        # ...
        # copy all the configuration originally defined in config.yml
        # ...

Splitting Configuration into Several Files
------------------------------------------

If your application keeps growing, moving its configuration to ``easyadmin.yml``
file won't solve your problem. In this case it's better to split the
configuration into different files.

Consider an application which defines the following configuration:

.. code-block:: yaml

    # app/config/easyadmin.yml
    easy_admin:
        site_name: '...'
        # ...
        design:
            # ...
        entities:
            Product:
                # ...
            User:
                # ...
            Category:
                # ...
            # ...

This configuration is going to be divided into four different files:

* ``design.yml`` for design related configuration;
* ``product.yml`` for the configuration related to ``Product`` entity;
* ``user.yml`` for the configuration related to ``User`` entity;
* ``basic.yml`` for the rest of the configuration, including any entity
  different from ``Product`` and ``User``.

First, create a new ``app/config/easyadmin/`` directory to store the new files
so they don't mess with the other Symfony configuration files. Then, create the
four files with these contents:

.. code-block:: yaml

    # app/config/easyadmin/basic.yml
    easy_admin:
        site_name: '...'
        # ...

    # app/config/easyadmin/design.yml
    easy_admin:
        design:
            # ...

    # app/config/easyadmin/product.yml
    easy_admin:
        entities:
            Product:
                # ...

    # app/config/easyadmin/user.yml
    easy_admin:
        entities:
            User:
                # ...

Beware that each configuration file must define its contents under the ``easy_admin``
key. Otherwise, Symfony won't be able to merge the different configurations.

The last step is to import those files from any configuration file loaded for
Symfony, usually ``config.yml``:

.. code-block:: yaml

    # Before Symfony 2.8
    # app/config/config.yml
    imports:
        - { resource: easyadmin/basic.yml }
        - { resource: easyadmin/design.yml }
        - { resource: easyadmin/product.yml }
        - { resource: easyadmin/user.yml }

    # Symfony 2.8 and higher
    # app/config/config.yml
    imports:
        - { resource: easyadmin/ }

The imported files can define any number of EasyAdmin configuration options. You
can even define the same option in several files and Symfony will take care of
merging all values (the last one always wins).

Importing EasyAdmin Configuration from Different Bundles
--------------------------------------------------------

This technique is also useful when your entities are scattered across different
bundles. You can define their backend configuration separately in each bundle
and then load those files through the service configuration loading mechanism.

Consider an application which contains a ``ProductBundle`` bundle where the
``Product`` entity is defined. First, create the configuration file for that
entity:

.. code-block:: yaml

    # src/ProductBundle/Resources/config/product.yml
    easy_admin:
        entities:
            Product:
                # ...

Then, import the ``product.yml`` file from the DependencyInjection extension
defined by the bundle:

.. code-block:: php

    namespace ProductBundle\DependencyInjection;

    use Symfony\Component\Config\FileLocator;
    use Symfony\Component\DependencyInjection\ContainerBuilder;
    use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
    use Symfony\Component\HttpKernel\DependencyInjection\Extension;

    // ...
    public function load(array $configs, ContainerBuilder $container)
    {
        // ...

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('product.yml');
    }

Alternatively, if you don't want to use a DependencyInjection extension, you can
import the bundle's file from the main Symfony configuration file:

.. code-block:: yaml

    imports:
        # ...
        - { resource: "@ProductBundle/Resources/config/product.yml" }
