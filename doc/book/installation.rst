Chapter 1. Installation
=======================

Installation Using Symfony Flex
-------------------------------

`Symfony Flex`_ is the new way to manage dependencies on Symfony 3.3 and higher
applications. If your project already uses Symfony Flex, execute this command to
download, register and configure the bundle automatically:

.. code-block:: terminal

    $ composer require admin

That's all! You can skip the rest of this chapter and go to the next chapter.

Installation without Symfony Flex
---------------------------------

Installing EasyAdmin without Symfony Flex requires you to edit two files and
execute two console commands:

Step 1: Download the Bundle
~~~~~~~~~~~~~~~~~~~~~~~~~~~

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

.. code-block:: terminal

    $ composer require javiereguiluz/easyadmin-bundle

This command requires you to have Composer installed globally, as explained
in the `Composer documentation`_.

Step 2: Enable the Bundle
~~~~~~~~~~~~~~~~~~~~~~~~~

Then, enable the bundle by adding it to the list of registered bundles in the
``app/AppKernel.php`` file of your project:

.. code-block:: php

    <?php
    // app/AppKernel.php

    // ...
    class AppKernel extends Kernel
    {
        public function registerBundles()
        {
            $bundles = array(
                // ...
                new EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle(),
            );
        }

        // ...
    }

Step 3: Load the Routes of the Bundle
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Load the routes of the bundle by adding this configuration at the very beginning
of the ``app/config/routing.yml`` file:

.. code-block:: yaml

    # app/config/routing.yml
    easy_admin_bundle:
        resource: "@EasyAdminBundle/Controller/AdminController.php"
        type:     annotation
        prefix:   /admin

    # ...

Step 4: Prepare the Web Assets of the Bundle
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This bundle uses several CSS, JavaScript and font files to create the backend
interfaces. Execute the following command to make those assets available in your
Symfony application:

.. code-block:: terminal

    # Symfony 2
    $ php app/console assets:install --symlink

    # Symfony 3
    $ php bin/console assets:install --symlink

That's it! Now everything is ready to create your first admin backend.

.. _`Symfony Flex`: https://github.com/symfony/flex
.. _`Composer documentation`: https://getcomposer.org/doc/00-intro.md
