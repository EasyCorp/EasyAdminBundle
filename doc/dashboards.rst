Dashboards
==========

**Dashboards** are the entry point of backends and they link to one ore more
:doc:`admin resources </resources>`. Dashboards also display a main menu to
navigate the resources and the information of the logged in user.

Imagine that you have a simple application with three Doctrine entities: users,
blog posts and categories. Your own employees can create and edit any of them
but external collaborators can only create blog posts.

You can implement this in EasyAdmin as follows:

* Create three admin resources for those entities (e.g. ``UserAdminController``,
  ``BlogPostAdminController`` and ``CategoryAdminController``);
* Create a dashboard for your employees (e.g. ``DashboardController``) and link
  to the three resources;
* Create a dashboard for your external collaborators (e.g. ``ExternalDashboardController``)
  and link only to the ``BlogPostAdminController`` resource.

Technically, dashboards are regular `Symfony controllers`_ so you can do
anything you usually do in a controller, such as injecting services and calling
to shortcuts like ``$this->render()`` or ``$this->isGranted()``.

Dashboard classes must implement the ``DashboardInterface``, which ensures that
certain methods are defined in the dashboard. Instead of implementing the
interface, you can also extend from the ``AbstractDashboardController`` class.
Run the following command to quickly generate a dashboard controller:

.. code-block:: terminal

    $ php bin/console make:admin:dashboard

.. _application-context:

Application Context
-------------------

EasyAdmin initializes a variable of type ``ApplicationContext`` on each backend
request. This object contains all the config about the current dashboard and
:doc:`resource admin </resources>`.

This context variable is automatically injected in every template as a variable
called ``ea`` (the initials of "EasyAdmin"):

.. code-block:: twig

    <h1>{{ ea.dashboard.siteName }}</h1>

    {% for item in ea.menu.items %}
        {# ... #}
    {% endif %}

In controllers and services, inject the ``ApplicationContextProvider`` service
to get the context variable via the ``getContext()`` method.

Dashboard Configuration
-----------------------

The basic dashboard configuration is defined in the ``getConfig()`` method
(the main menu and the user menu are configured in their own methods, as
explained later)::

    use EasyCorp\Bundle\EasyAdminBundle\Config\DashboardConfig;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboard;

    class DashboardController extends AbstractDashboardController
    {
        // ...

        public function getConfig(): DashboardConfig
        {
            return DashboardConfig::new()
                // the name visible to end users
                ->siteName('ACME Corp.')
                // you can include HTML contents too (e.g. to link to an image)
                ->siteName('<img src="..."> ACME <span class="text-small>Corp.</span>')

                // formats applied to date/time and number values in all the resource admins
                // managed by this dashboard (it can be overridden per resource admin)
                ->dateFormat('...')
                ->timeFormat('...')
                ->dateTimeFormat('...')
                ->dateIntervalFormat('%%y Year(s) %%m Month(s) %%d Day(s)')
                ->numberFormat('%.2d')
            ;
        }
    }

.. note::

    In addition to the above configuration options, there are other options
    related to :doc:`actions </actions>`, :doc:`design </design>` and
    :doc:`translation </security>` which are explained in other articles
    dedicated to those features.

.. _dashboard-menu:

Main Menu
---------

The **main menu** links to different :doc:`resources </resources>` from the
dashboard. It's the only way to associate dashboards and resources. For security
reasons, a backend can only access to the resources associated to the dashboard
via the main menu.

The main menu is a collection of ``MenuItem`` objects that configure the look
and behavior of each menu item::

    use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

    class DashboardController extends AbstractDashboardController
    {
        // ...

        public function getMainMenuItems(): iterable
        {
            return [
                MenuItem::new('Dashboard', 'fa-home')->homepage(),

                MenuItem::new('Blog')->section(),
                MenuItem::new('Categories', 'fa-tags')->entity(CategoryAdminController::class),
                MenuItem::new('Blog Posts', 'fa-file-text')->entity(BlogPostAdminController::class),

                MenuItem::new('Users')->section(),
                MenuItem::new('Comments', 'fa-comment')->entity(CommentAdminController::class),
                MenuItem::new('Users', 'fa-user')->entity(UserAdminController::class),
            ];
        }
    }

The first argument of ``MenuItem::new()`` is the label displayed by the item and
the second argument is the name of the FontAwesome icon.

Menu Item Configuration Options
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

All menu items define the following methods to configure some options:

* ``->cssClass(string $cssClass)``, sets the CSS class or classes applied to the
  ``<li>`` parent element of the menu item;
* ``->linkRel(string $rel)``, sets the ``rel`` HTML attribute of the menu item
  link (check out the `allowed values for the "rel" attribute`_);
* ``->linkTarget(string $target)``, sets the ``target`` HTML attribute of the
  menu item link (``_self`` by default);
* ``->permission(string $role)``, sets the `security role`_ that the user must
  have to see this menu item. Read the :ref:`menu security reference <security-menu>`
  for more details.

Menu Item Types
~~~~~~~~~~~~~~~

There are several types of menu items:

Entity Menu Item
................

The most common type is used link to some action of some
:doc:`resource admin </resources>`. The resource is referred as the
fully-qualified class name of its admin controller::

    public function getMainMenuItems(): iterable
    {
        return [
            // ...

            // links to the 'index' action of the Category admin resource
            MenuItem::new('Categories', 'fa-tags')
                ->entity(CategoryAdminController::class),

            // links to a different action
            MenuItem::new('Create category', 'fa-tag')->entity(CategoryAdminController::class, [
                'action' => 'form',
            ]),

            // uses custom sorting options for the listing
            MenuItem::new('Recent categories', 'fa-tags')->entity(CategoryAdminController::class, [
                'sortField' => 'createdAt',
                'sortDirection' => 'DESC',
            ]),
        ];
    }

Homepage Menu Item
..................

It links to the homepage of the current dashboard. You can achieve the same with
a "route menu item" (explained below) but this doesn't require to know or update
the backend route name::

    public function getMainMenuItems(): iterable
    {
        return [
            MenuItem::new('Dashboard', 'fa-home')->homepage(),
            // ...
        ];
    }

Route Menu Item
...............

It links to any of the Symfony application routes::

    public function getMainMenuItems(): iterable
    {
        return [
            MenuItem::new('...')->route('route-name'),
            MenuItem::new('...')->route('route-name', ['parameter-name' => 'parameter-value']),
            // ...
        ];
    }

URL Menu Item
.............

It links to a relative or absolute URL::

    public function getMainMenuItems(): iterable
    {
        return [
            MenuItem::new('Visit public website')->url('/'),
            MenuItem::new('Search in Google')->url('https://google.com'),
            // ...
        ];
    }

To avoid leaking internal backend information to external websites, if the menu
item links to an external URL and doesn't define its ``rel`` option, the
``rel="noreferrer"`` attribute is added automatically.

Section Menu Item
.................

It creates a visual separation between menu items and can optionally display a
label which acts as the title of the menu items below::

    public function getMainMenuItems(): iterable
    {
        return [
            // ...

            MenuItem::new()->section(),
            // ...

            MenuItem::new('Blog')->section(),
            // ...
        ];
    }

Logout Menu Item
................

It links to the URL that the user must visit to log out from the application.
If you know the logout route name, you can achieve the same with the ``route()``
method, but this one is more convenient because it finds the logout URL for the
current security firewall automatically::

    public function getMainMenuItems(): iterable
    {
        return [
            // ...
            MenuItem::new('Logout', 'fa-exit')->logout(),
        ];
    }

Submenus
~~~~~~~~

The main menu can display up to two level nested menus. Submenus are defined
using the ``->subMenu()`` method::

    public function getMainMenuItems(): iterable
    {
        return [
            MenuItem::new('Blog', 'fa-article')->subMenu([
                MenuItem::new('Categories', 'fa-tags')->entity(CategoryAdminController::class),
                MenuItem::new('Posts', 'fa-file-text')->entity(BlogPostAdminController::class),
                MenuItem::new('Comments', 'fa-comment')->entity(CommentAdminController::class),
            ]),
            // ...
        ];
    }

.. note::

    The parent menu item cannot link to any resource, route or URL; it can only
    expand/collapse the submenu.

Complex Main Menus
~~~~~~~~~~~~~~~~~~

The return type of the ``getMainMenuItems()`` is ``iterable``, so you don't have
to always return an array. For example, if your main menu requires complex logic
to decide which items to display for each user, it's more convenient to use a
generator to return the menu items::

    public function getMainMenuItems(): iterable
    {
        yield MenuItem::new('Dashboard', 'fa-home')->homepage();

        if ('... some complex expression ...') {
            yield MenuItem::new('Blog')->section();
            yield MenuItem::new('Categories', 'fa-tags')->entity(CategoryAdminController::class);
            yield MenuItem::new('Blog Posts', 'fa-file-text')->entity(BlogPostAdminController::class);
        }

        // ...
    }

.. _dashboards-user-menu:

User Menu
---------

When accessing a protected backend, EasyAdmin displays the details of the user
who is logged in the application and a menu with some options like "logout" (if
Symfony's `logout feature`_ is enabled).

The user name is the result of calling to the ``__toString()`` method on the
current user object. The user avatar is a generic avatar icon. Use the
``getUserMenu()`` method to configure the features and items of this menu::

    use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

    class DashboardController extends AbstractDashboardController
    {
        // ...

        public function getUserMenu(UserInterface $user): UserMenuConfig
        {
            return UserMenuConfig::new()
                // use the given $user object to get the user name
                ->name($user->getFullName())
                // set it to NULL to not display the user name
                ->name(null)

                // you can return an URL with the avatar image
                ->avatarUrl('https://...')
                ->avatarUrl($user->getProfileImageUrl())
                // set it to NULL to not display the user image
                ->avatarUrl(null)
                // you can also pass an email address to use gravatar's service
                ->gravatarEmail($user->getMainEmailAddress())

                // you can use any type of menu item, except submenus
                ->setMenuItems([
                    MenuItem::new('My Profile', 'fa-id-card')->route('...', ['...' => '...']),
                    MenuItem::new('Settings', 'fa-user-cog')->route('...', ['...' => '...']),
                    MenuItem::new()->section(),
                    MenuItem::new('Logout', 'fa-sign-out')->logout(),
                ]);
        }
    }

Translation
-----------

EasyAdmin uses `Symfony translation`_ and different `translation domains`_ to
translate both the interface elements (buttons, pagination, error messages,
etc.) and your own contents (menu items, entity and field names, etc.):

EasyAdmin's interface is translated using the ``EasyAdminBundle`` domain (thanks
to our community for kindly providing translations for tens of languages).
Everything else is translated by default using the ``messages`` domain but you
can change this value with the ``translationDomain()`` method::

    class DashboardController extends AbstractDashboardController
    {
        // ...

        public function getConfig(): DashboardConfig
        {
            return DashboardConfig::new()
                // ...

                // the argument is the name of any valid Symfony translation domain
                // (default: 'messages')
                ->translationDomain('admin');
        }
    }

The backend uses the language configured in the Symfony application. If you want
to change it, update the value of the ``locale`` parameter in the
``config/services.yaml`` file.

When the locale is Arabic (``ar``), Persian (``fa``) or Hebrew (``he``), the
HTML text direction is set to ``rtl`` (right-to-left) automatically. Otherwise,
the text is displayed as ``ltr`` (left-to-right), but you can configure this
value explicitly::

    class DashboardController extends AbstractDashboardController
    {
        // ...

        public function getConfig(): DashboardConfig
        {
            return DashboardConfig::new()
                // ...

                // most of the times there's no need to configure this explicitly
                // (default: 'rtl' or 'ltr' depending on the language)
                ->textDirection('rtl');
        }
    }

.. tip::

    If you want to make the backend use a different language than the public
    website, you'll need to `work with the user locale`_ to set the request
    locale before the translation service retrieves it.

.. note::

    The contents stored in the database (e.g. the content of a blog post or the
    name of a product) are not translated. EasyAdmin does not support the
    translation of the entity property contents into different languages.

Page Templates
--------------

EasyAdmin provides several page templates which are useful when adding custom
logic in your dashboards.

Login Form Template
~~~~~~~~~~~~~~~~~~~

Twig Template Path: ``@EasyAdmin/page/login.html.twig``

It displays a simple username + password login form that matches the style of
the rest of the backend. The template defines lots of config options, but most
applications can rely on its default values:

.. code-block:: php

    namespace App\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Annotation\Route;
    use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

    class SecurityController extends AbstractControllerController
    {
        /**
         * @Route("/login", name="login")
         */
        public function login(AuthenticationUtils $authenticationUtils): Response
        {
            $error = $authenticationUtils->getLastAuthenticationError();
            $lastUsername = $authenticationUtils->getLastUsername();

            return $this->render('@EasyAdmin/page/login.html.twig', [
                // parameters usually defined in Symfony login forms
                'error' => $error,
                'last_username' => $lastUsername,

                // OPTIONAL parameters to customize the login form:

                // the string used to generate the CSRF token. If you don't define
                // this parameter, the login form won't include a CSRF token
                'csrf_token_intention' => 'authenticate',
                // the URL users are redirected to after the login (default: '/admin')
                'target_path' => $this->generateUrl('admin_dashboard'),
                // the label displayed for the username form field (the |trans filter is applied to it)
                'username_label' => 'Your username',
                // the label displayed for the password form field (the |trans filter is applied to it)
                'password_label' => 'Your password',
                // the label displayed for the Sign In form button (the |trans filter is applied to it)
                'sign_in_label' => 'Log in',
                // the 'name' HTML attribute of the <input> used for the username field (default: '_username')
                'username_parameter' => 'my_custom_username_field',
                // the 'name' HTML attribute of the <input> used for the password field (default: '_password')
                'password_parameter' => 'my_custom_password_field',
            ]);
        }
    }

Content Page Template
~~~~~~~~~~~~~~~~~~~~~

Twig Template Path: ``@EasyAdmin/page/content.html.twig``

It displays a simple page similar to the index/detail/form pages, with the main
header, the sidebar menu and the central content section. The only difference is
that the content section is completely empty, so it's useful to display your own
text contents, custom forms, etc.

Blank Page Template
~~~~~~~~~~~~~~~~~~~

Twig Template Path: ``@EasyAdmin/page/blank.html.twig``

It displays a page with the same header and sidebar menu as the
index/detail/form pages, but without the central content section. It's useful to
define completely custom page, such as a complex dashboard.

.. _`allowed values for the "rel" attribute`: https://developer.mozilla.org/en-US/docs/Web/HTML/Link_types
.. _`security role`: https://symfony.com/doc/current/security.html#roles
.. _`Symfony translation`: https://symfony.com/doc/current/components/translation.html
.. _`translation domain`: https://symfony.com/doc/current/components/translation.html#using-message-domains
.. _`translation domains`: https://symfony.com/doc/current/components/translation.html#using-message-domains
.. _`work with the user locale`: https://symfony.com/doc/current/translation/locale.html
