EasyAdmin
=========

`EasyAdmin`_ creates beautiful administration backends for your Symfony
applications. It's free, fast, and fully documented.

.. admonition:: Screencast
    :class: screencast

    Like video tutorials? See the `EasyAdmin Screencast on SymfonyCasts`_.

Table of Contents
-----------------

* :doc:`Dashboards </dashboards>` (main menu, user menu, admin context, translations)
* :doc:`CRUD controllers </crud>` (entities, pagination, search, sorting, forms)
* :doc:`Design </design>` (customization, templates, custom CSS/JS assets, Bootstrap
  theming, CSS variables)
* :doc:`Fields </fields>` (field configurators, custom fields, form columns, tabs
  and fieldsets)
* :doc:`Filters </filters>` (custom filters, filtering unmapped properties)
* :doc:`Actions </actions>` (CRUD actions, custom actions, batch actions,
  permissions, conditional display)
* :doc:`Security </security>` (access control, menu/action/field/entity permissions,
  custom voters)
* :doc:`Events </events>` (entity events, CRUD events, JavaScript events)
* :doc:`Tests </tests>` (functional testing, utilities, assertions)
* :doc:`AI Coding Agents </ai-coding-agents>` (agent skills for Claude Code,
  Codex, Cursor, GitHub Copilot, and other agents)
* :doc:`Upgrade </upgrade>` (from EasyAdmin 4)
* :doc:`Appendix: Twig Components </components>` (reusable UI components: badges, buttons,
  icons, modals, dropdown menus)

Technical Requirements
----------------------

EasyAdmin requires the following:

* PHP 8.2 or higher;
* Symfony 6.4 or higher;
* Doctrine ORM entities (Doctrine ODM is not supported).

Installation
------------

Run the following command to install EasyAdmin in your application:

.. code-block:: terminal

    $ composer require easycorp/easyadmin-bundle

If you use `Symfony Flex`_ in your application, you are ready to :doc:`create your first Dashboard </dashboards>`.
Otherwise, keep reading because you need to perform some manual configuration.

Manual Configuration for Applications Not Using Symfony Flex
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In most Symfony applications **you don't need to make any of the following changes**.
These steps are only required for applications that do not use Symfony Flex.

First, register two bundles in your application. Edit the ``config/bundles.php``
file and add the following::

    return [
        // ...
        EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle::class => ['all' => true],
        Symfony\UX\TwigComponent\TwigComponentBundle::class => ['all' => true],
    ];

The ``TwigComponentBundle`` is also required because EasyAdmin uses `Twig Components`_
to build its interface (see :doc:`the Twig components reference </components>`).
Next, create the following configuration file for Twig Components:

.. code-block:: yaml

    # config/packages/twig_component.yaml
    twig_component:
        anonymous_template_directory: 'components/'
        defaults:
            # Namespace & directory for components
            App\Twig\Components\: 'components/'

If this file already exists in your application, leave it as it is. Otherwise,
copy its contents from the `configuration recipe of Symfony UX Twig Component`_.

That's all! You are now ready to use EasyAdmin in your application. Start by
:doc:`creating your first Dashboard </dashboards>`.

.. _`EasyAdmin`: https://github.com/EasyCorp/EasyAdminBundle
.. _`EasyAdmin Screencast on SymfonyCasts`: https://symfonycasts.com/screencast/easyadminbundle
.. _`Symfony Flex`: https://symfony.com/doc/current/setup/flex.html
.. _`Twig Components`: https://symfony.com/bundles/ux-twig-component/current/index.html
.. _`configuration recipe of Symfony UX Twig Component`: https://github.com/symfony/recipes/tree/main/symfony/ux-twig-component/
