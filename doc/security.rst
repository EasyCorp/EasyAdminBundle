Security
========

.. raw:: html

    <div class="box box--small box--warning">
        <strong class="title">WARNING:</strong>

        You are browsing the documentation for <strong>EasyAdmin 3.x</strong>,
        which hasn't been released as a stable version yet. You are probably
        using EasyAdmin 2.x in your application, so you can switch to
        <a href="https://symfony.com/doc/2.x/bundles/EasyAdminBundle/index.html">EasyAdmin 2.x docs</a>.
    </div>

EasyAdmin relies on `Symfony Security`_ for everything related to security.
That's why before restricting access to some parts of the backend, you need
to properly setup security in your Symfony application:

#. `Create users`_ in your application and assign them proper permissions
   (e.g. ``ROLE_ADMIN``);
#. `Define a firewall`_ that covers the URL of the backend.

Logged in User Information
--------------------------

When accessing a protected backend, EasyAdmin displays the details of the user
who is logged in the application and a menu with some options like "logout".
Read the :ref:`user menu reference <dashboards-user-menu>` for more details.

Restrict Access to the Entire Backend
-------------------------------------

Using the `access_control option`_, you can tell Symfony to require certain
permissions to browse the URL associated to the backend. This is simple to do
because each dashboard only uses a single URL (the query string parameters
define the action to run and other config):

.. code-block:: yaml

    # config/packages/security.yaml
    security:
        # ...

        access_control:
            - { path: ^/admin, roles: ROLE_ADMIN }
            # ...

Another option is to `add security annotations`_ to the dashboard controller::

    // app/Controller/Admin/DashboardController.php
    use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

    /**
     * @IsGranted("ROLE_ADMIN")
     */
    class DashboardController extends AbstractDashboardController
    {
        // ...
    }

.. _security-menu:

Restrict Access to Menu Items
-----------------------------

Use the ``setPermission()`` method to define the security permission that the
user must have in order to see the menu item::

    public function configureMenuItems(): iterable
    {
        return [
            // ...

            MenuItem::linkToCrud('Blog Posts', null, BlogPost::class)
                ->setPermission('ROLE_EDITOR'),
        ];
    }

If your needs are more advanced, remember that the dashboard class is a regular
Symfony controller, so you can use any service related to security to evaluate
complex expressions. In those cases, it's more convenient to use the alternative
menu item definition to not have to deal with array merges::

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        if ($this->isGranted('ROLE_EDITOR') && '...') {
            yield MenuItem::linkToCrud('Blog Posts', null, BlogPost::class);
        }

        // ...
    }

Restrict Access to Actions
--------------------------

.. TODO: update this when updating the 'actions' chapter

Use the ``setPermission()`` method to define the security role required to see
the action link/button::

    class ProductAdminController extends AbstractResourceAdminController
    {
        // ...

        public function getIndexPageConfig(): IndexPageConfig
        {
            // this action is only visible and can only be executed by
            // users with the ROLE_FINANCE permission
            $viewInvoiceAction = Action::new('See invoice', 'fa-file-invoice')
                ->method('invoice')->permission('ROLE_FINANCE');

            return IndexPageConfig::new()
                // ...
                ->addAction('invoice', $viewInvoiceAction);
        }
    }

.. _security-fields:

Restrict Access to Fields
-------------------------

.. TODO: update this when updating the 'fields' chapter

There are several options to restrict the information displayed in the page
depending on the logged in user. First, you can show/hide the entire field with
the ``permission()`` option::

    public function getFields(string $action): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('price'),
            IntegerField::new('stock'),
            // users must have this role to see this field
            IntegerField::new('sales')->permission('ROLE_ADMIN'),
            FloatField::new('comission')->permission('ROLE_FINANCE'),
            // ...
        ];
    }

You can also restrict which items users can see in the ``index`` and ``detail``
pages thanks to the ``itemPermission()`` option. The role defined in that option
is passed to the ``is_granted($roles, $item)`` function to decide if the current
user can see the given item::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Config\DetailPageConfig;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractResourceAdminController;

    class ProductAdminController extends AbstractResourceAdminController
    {
        // ...

        public function getIndexPageConfig(): IndexPageConfig
        {
            return IndexPageConfig::new()
                // ...
                ->itemPermission('ROLE_ADMIN');
        }

        public function getDetailPageConfig(): DetailPageConfig
        {
            return DetailPageConfig::new()
                // ...
                ->itemPermission('ROLE_ADMIN');
        }
    }

In the ``detail`` page, if the user doesn't have permission they will see an
appropriate error message (and you'll see a detailed error message in the
application logs).

In the ``index`` page, to avoid confusion and pagination errors, if the user
doesn't have permission to see some items, an empty row will be displayed at the
bottom of the list with a message explaining that they don't have enough
permissions to see some items:

.. image:: ../images/easyadmin-list-hidden-results.png
   :alt: Index page with some results hidden because user does not have enough permissions

.. tip::

    Combine the ``itemPermission()`` option with custom `Symfony security voters`_
    to better decide if the current user can see any given item.

.. _`Create users`: https://symfony.com/doc/current/security.html#a-create-your-user-class
.. _`Define a firewall`: https://symfony.com/doc/current/security.html#a-authentication-firewalls
.. _`add security annotations`: https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/security.html
.. _`access_control option`: https://symfony.com/doc/current/security/access_control.html
.. _`logout feature`: https://symfony.com/doc/current/security.html#logging-out
.. _`Symfony security voters`: https://symfony.com/doc/current/security/voters.html
