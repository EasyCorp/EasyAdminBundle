Security
========

EasyAdmin relies on `Symfony Security`_ for everything related to security.
That's why before restricting access to some parts of the backend, you need
to properly set up security in your Symfony application:

#. `Create users`_ in your application and assign them proper permissions
   (e.g. ``ROLE_ADMIN``);
#. `Define a firewall`_ that covers the URL of the backend.

Logged-in User Information
--------------------------

When accessing a protected backend, EasyAdmin displays the details of the user
who is logged in to the application and a menu with some options like "logout".
Read the :ref:`user menu reference <dashboards-user-menu>` for more details.

.. _security-entire-backend:

Restrict Access to the Entire Backend
-------------------------------------

All the routes belonging to a given dashboard :ref:`share a common prefix <dashboard-route>`
(e.g. ``/admin``). This makes it straightforward to secure the backend because
you can use the `access_control option`_ to tell Symfony to require certain
permissions when browsing that URL path:

.. code-block:: yaml

    # config/packages/security.yaml
    security:
        # ...

        access_control:
            # change '/admin' to the prefix used by your Dashboard URLs
            - { path: ^/admin, roles: ROLE_ADMIN }
            # ...

If the dashboard route is restricted with the ``routeOptions`` of the
``#[AdminDashboard]`` attribute (e.g. a ``host`` or the ``requirements`` of a
placeholder), all the routes of the dashboard have the same restrictions, so a
security rule that matches those same requests covers the entire backend (unless
some ``#[AdminRoute]`` attribute defines its own ``host``, ``schemes`` or
``requirements``, which win over the ones of the dashboard).

.. note::

    Some custom actions dispatch the request to a different Symfony controller.
    In those cases, EasyAdmin re-evaluates the ``access_control`` rules against
    the route of the target controller, so any rule protecting that route is
    still applied.

.. _security-controllers:

Restrict Access to Some CRUD Controllers
----------------------------------------

When using more than one :doc:`Dashboard </dashboards>` you might need to restrict
which :doc:`CRUD controllers </crud>` are accessible for each of them.

Consider that in your application you have two dashboards (``DashboardController``
used by your employees and ``GuestDashboardController`` used by external collaborators).
In the guest dashboard you only want to allow certain actions related to your blog.

By default, EasyAdmin generates routes for all CRUD controllers in all dashboards. This means that
there will be undesired routes like ``admin_guest_invoice_index``, ``admin_guest_user_detail``, etc.
The best way to restrict which CRUD controllers are accessible via each dashboard
is to use the :ref:`#[AdminDashboard] attribute <dashboard-route>`::

    // src/Controller/Admin/GuestDashboardController.php
    use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

    #[AdminDashboard(routePath: '/admin/guest', routeName: 'admin_guest', allowedControllers: [
        BlogPostCrudController::class,
        BlogCategoryCrudController::class,
    ])]
    class GuestDashboardController extends AbstractDashboardController
    {
        // ...
    }

The ``allowedControllers`` option defines the only CRUD controllers that will be
available in the dashboard via Symfony routes. In practice, the above configuration
will make EasyAdmin generate only the routes ``admin_guest_blog_post_*`` and
``admin_guest_blog_category_*``, skipping all the other routes that would have
allowed to access the other controllers.

.. tip::

    You can also define the opposite option (``deniedControllers``) to allow all
    controllers except the ones included in that list.

When EasyAdmin finds the CRUD controller of an entity automatically (e.g. to
render the embedded form of an ``AssociationField`` or the entries of a
``CollectionField``), it prefers the CRUD controllers available in the current
dashboard. If the dashboard has none for that entity, EasyAdmin uses a CRUD
controller excluded from it. Pass the CRUD controller explicitly (e.g.
``renderAsEmbeddedForm(GuestUserCrudController::class)``) when more than one
CRUD controller manages the same entity.

.. _security-menu:

Restrict Access to Menu Items
-----------------------------

Use the ``setPermission()`` method to define the security permission that the
user must have to see the menu item::

    public function configureMenuItems(): iterable
    {
        return [
            // ...

            MenuItem::linkTo(BlogPostCrudController::class, 'Blog Posts')
                ->setPermission('ROLE_EDITOR'),
        ];
    }

.. note::

    This permission only shows/hides menu items. The actions associated with those
    menu items are still executable, even if the user can't see the menu items.
    Use the :ref:`actions permissions <security-permissions-actions>` to also
    restrict the access to those actions.

If your needs are more advanced, remember that the dashboard class is a regular
Symfony controller, so you can use any service related to security to evaluate
complex expressions. In those cases, it's more convenient to use the alternative
menu item definition to not have to deal with array merges::

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        if ($this->isGranted('ROLE_EDITOR') && '...') {
            yield MenuItem::linkTo(BlogPostCrudController::class, 'Blog Posts');
        }

        // ...
    }

.. _security-permissions-actions:

Restrict Access to Actions
--------------------------

Use the ``setPermission()`` method to define the security permission required to
see the action link/button on any of the :ref:`backend pages <crud-pages>` and to
run the action. This works both for :ref:`built-in actions <actions-built-in>`
and for :ref:`custom actions <actions-custom>`. If users without this permission
open the URL of the action, they see a "Forbidden Action" error::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function configureActions(Actions $actions): Actions
    {
        $viewInvoice = Action::new('invoice', 'View invoice', 'fa fa-file-invoice')
            ->linkToCrudAction('renderInvoice');

        return $actions
            // ...
            ->add(Crud::PAGE_DETAIL, $viewInvoice)
            // use the 'setPermission()' method to set the permission of actions
            // (the same permission is granted to the action on all pages)
            ->setPermission('invoice', 'ROLE_FINANCE')

            // you can set permissions for built-in actions in the same way
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
        ;
    }

If several actions run the same CRUD controller method (for example, two actions
on different pages that call ``linkToCrudAction('renderInvoice')``), users can
run that method when at least one of those actions is enabled and they have its
permission. An action without a permission allows everyone to run the method,
even if other actions that run the same method require a permission.

To require the same permission no matter which action runs the method, set that
permission on all those actions, or add the ``#[IsGranted]`` attribute to the
controller method.

.. caution::

    Permissions only hide the buttons of the actions that use ``linkToUrl()``,
    or ``linkToRoute()`` with a route that isn't a custom action of the same
    CRUD controller, because EasyAdmin can't check those URLs. Protect them with
    the ``#[IsGranted]`` attribute or with the ``access_control`` option of
    Symfony Security.

.. _security-permissions-other-crud:

Check the Permissions of Another CRUD Controller
------------------------------------------------

EasyAdmin applies the ``setPermission()`` and ``setEntityPermission()``
configuration of a CRUD controller before rendering a link to it (for example,
the link of an ``AssociationField`` to the related entity). If the entity
permission denies the related entity, the link is removed but its string
representation is still displayed. The entity permission is not applied either
to the lists of choices that display the entities of another CRUD controller,
as explained in :ref:`security-fields`.

If your own code renders links or embedded blocks that belong to another CRUD
controller (e.g. in a custom field configurator), use the
``CrudPermissionCheckerInterface`` service to apply the same rules instead of
duplicating the permission names::

    use App\Controller\Admin\InvoiceCrudController;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
    use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
    use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
    use EasyCorp\Bundle\EasyAdminBundle\Contracts\Security\CrudPermissionCheckerInterface;
    use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
    use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;

    final class InvoicePreviewConfigurator implements FieldConfiguratorInterface
    {
        public function __construct(
            private CrudPermissionCheckerInterface $permissionChecker,
        ) {
        }

        public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
        {
            $invoiceDto = // ... the EntityDto of the related invoice

            // TRUE only if the user can run the 'detail' action of InvoiceCrudController
            // for this invoice, according to that controller's own permissions
            $isGranted = $this->permissionChecker->isGranted(
                $context, InvoiceCrudController::class, Action::DETAIL, $invoiceDto
            );
            if (!$isGranted) {
                // ... hide the field or render a notice instead of the preview
            }
        }

        // ...
    }

Pass the ``EntityDto`` of the target entity whenever you have it: the
``setEntityPermission()`` check needs the entity instance. When you pass
``null`` (or an ``EntityDto`` without an entity instance, as in the ``index``
action), only the action-level permission is checked.

The second argument must be the FQCN of a CRUD controller. Otherwise, the
``isGranted()`` method throws an ``\InvalidArgumentException``.

.. _security-fields:

Restrict Access to Fields
-------------------------

There are several options to restrict the information displayed in the page
depending on the logged-in user. First, you can show/hide the entire field with
the ``setPermission()`` method::

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('price'),
            IntegerField::new('stock'),
            // users must have this permission/role to see this field
            IntegerField::new('sales')->setPermission('ROLE_ADMIN'),
            NumberField::new('commission')->setPermission('ROLE_FINANCE'),
            // ...
        ];
    }

You can also restrict which items users can see in the ``index`` and ``detail``
pages thanks to the ``setEntityPermission()`` method of the
:doc:`CRUD controller configuration </crud>`. This value is passed as the first
argument of the call to the ``is_granted($permission, $item)`` function to
decide whether the current user can see the given item::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

    class ProductCrudController extends AbstractCrudController
    {
        // ...

        public function configureCrud(Crud $crud): Crud
        {
            return $crud
                ->setEntityPermission('ROLE_ADMIN')
                // ...
            ;
        }
    }

In the ``detail`` page, if the user doesn't have permission they will see an
appropriate error message (and you'll see a detailed error message in the
application logs).

In the ``index`` page, to avoid confusion and pagination errors, if the user
doesn't have permission to see some items, an empty row will be displayed at the
bottom of the list with a message explaining that they don't have enough
permissions to see some items:

.. image:: images/easyadmin-list-hidden-results.png
   :alt: Index page with some results hidden because user does not have enough permissions

The entity permission is checked for each item after the database query runs.
The search, sorting, filters and pagination are part of that query, so the
denied items still count for the pagination and for the message about hidden
results, and the search terms, the sort order and the filters still consider
their values.

The entity permission also applies when the form of the CRUD controller is
nested in the form of another CRUD controller (with the
``renderAsEmbeddedForm()`` option of ``AssociationField`` or with the entries
of a ``CollectionField``). If the user doesn't have permission to access the
associated entity, or any of the entries of the collection, the whole field is
removed from the form and its contents don't change when submitting the form.
If the entity permission denies the creation of new entities (it's checked
with a ``null`` subject, as in the ``new`` page), users can't add new entries
to the collection, and the embedded form of an empty association is removed.
The permissions of the actions of that CRUD controller (configured with
``setPermission()``) don't apply to these nested forms.

.. caution::

    The entity permission only applies to the pages and forms of the CRUD
    controller that defines it. Other features can still display the ID and
    the string representation of the denied items:

    * the ``autocomplete`` action of the same CRUD controller, which returns
      all the items that match the query of its ``createIndexQueryBuilder()``
      method;
    * the ``AssociationField`` of other CRUD controllers, which display the
      related item in the ``index`` and ``detail`` pages and list all its
      items as choices in forms (the same happens in an ``EntityFilter``).

To keep some items away from a user, remove them from the database query.
Override the ``createIndexQueryBuilder()`` method of the CRUD controller,
which is used by the ``index`` page and by the ``autocomplete`` action, and
keep the ``setEntityPermission()`` option to protect the other actions::

    namespace App\Controller\Admin;

    use Doctrine\ORM\QueryBuilder;
    use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
    use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
    use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
    use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

    class InvoiceCrudController extends AbstractCrudController
    {
        // ...

        public function createIndexQueryBuilder(
            SearchDto $searchDto,
            EntityDto $entityDto,
            FieldCollection $fields,
            FilterCollection $filters,
        ): QueryBuilder {
            $queryBuilder = parent::createIndexQueryBuilder(
                $searchDto, $entityDto, $fields, $filters
            );

            return $queryBuilder
                ->andWhere('entity.owner = :owner')
                ->setParameter('owner', $this->getUser());
        }
    }

In the CRUD controllers that display these items in an
:doc:`AssociationField </fields/AssociationField>`, use its
``setQueryBuilder()`` option to remove them from the list of choices. In the
``index`` and ``detail`` pages, the field displays the item related to each
entity, so use the ``setPermission()`` option of the field to hide it from
the users who must not see it.

.. _security-expressions:

Restricting Access with Expressions
-----------------------------------

The `Symfony ExpressionLanguage component`_ allows you to define complex configuration
logic using simple expressions. In EasyAdmin, all ``setPermission()`` methods
allow you to pass not only a string with some security role name (e.g. ``ROLE_ADMIN``)
but also a full ``Expression`` object.

First, install the component in your project using Composer:

.. code-block:: terminal

    $ composer require symfony/expression-language

Now, you can pass a Symfony Expression object to any ``setPermission()`` method
like this::

    use Symfony\Component\ExpressionLanguage\Expression;

    MenuItem::linkTo(ExampleCrudController::class, 'Restricted menu-item')
        ->setPermission(new Expression('"ROLE_DEVELOPER" in role_names and "ROLE_EXTERNAL" not in role_names'));

Expressions enable the definition of much more detailed permissions, based on
several role names, user attributes, or the given subject. The expressions can
include any of these variables:

* ``user`` - the current user object
* ``role_names`` - all the roles of current user as an array
* ``subject`` or ``object`` - the current subject being checked
* ``token`` - the authentication token
* ``trust_resolver`` - the authentication trust resolver
* ``auth_checker`` - an instance of the authorization checker service

Custom Security Voters
----------------------

EasyAdmin implements a Symfony `security voter`_ to check the permissions
defined for actions, entities, menu items, etc. The actual security permissions
are defined as constants in the ``EasyCorp\Bundle\EasyAdminBundle\Security\Permission``
class:

* ``Permission::EA_ACCESS_ENTITY``: checked before displaying an entity in the
  ``index`` and ``detail`` pages;
* ``Permission::EA_EXECUTE_ACTION``: checked before displaying and running an action;
* ``Permission::EA_VIEW_MENU_ITEM``: checked before displaying a menu item;
* ``Permission::EA_VIEW_FIELD``: checked before displaying a field;
* ``Permission::EA_EXIT_IMPERSONATION``: checked before displaying the link that
  stops impersonating another user.

If you define a custom security voter for the backend, consider changing the
`access decision strategy`_ used by your application. The default strategy,
called ``affirmative``, grants access as soon as one voter grants access (if
the EasyAdmin voter grants access, your custom voter won't be able to deny it).

That's why you should change the default strategy to ``unanimous``, which
grants access only if there are no voters denying access:

.. code-block:: yaml

    # config/packages/security.yaml
    security:
        access_decision_manager:
            strategy: unanimous

.. _`Symfony Security`: https://symfony.com/doc/current/security.html
.. _`Create users`: https://symfony.com/doc/current/security.html#a-create-your-user-class
.. _`Define a firewall`: https://symfony.com/doc/current/security.html#a-authentication-firewalls
.. _`access_control option`: https://symfony.com/doc/current/security/access_control.html
.. _`security voter`: https://symfony.com/doc/current/security/voters.html
.. _`access decision strategy`: https://symfony.com/doc/current/security/voters.html#changing-the-access-decision-strategy
.. _`Symfony ExpressionLanguage component`: https://symfony.com/doc/current/components/expression_language.html
