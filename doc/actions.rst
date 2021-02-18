Actions
=======

.. raw:: html

    <div class="box box--small box--warning">
        <strong class="title">WARNING:</strong>

        You are browsing the documentation for <strong>EasyAdmin 3.x</strong>,
        which has just been released. Switch to
        <a href="https://symfony.com/doc/2.x/bundles/EasyAdminBundle/index.html">EasyAdmin 2.x docs</a>
        if your application has not been upgraded to EasyAdmin 3 yet.
    </div>

**Actions** are each of the tasks that you can perform on CRUD pages. In the
``index``  page for example, you have tasks to "edit" and "delete" each entity
displayed in the listing and you have another task to "create" a new entity.

Actions are configured in the ``configureActions()`` method of your
:doc:`dashboard </dashboards>` or :doc:`CRUD controller </crud>`::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

    class ProductCrudController extends AbstractCrudController
    {
        // ...

        public function configureActions(Actions $actions): Actions
        {
            // ...
        }
    }

Action Names and Constants
--------------------------

Some methods require as argument the name of some action. In addition to plain
strings with the action names (``'index'``, ``'detail'``, ``'edit'``, etc.) you
can also use constants for these values: ``Action::INDEX``, ``Action::DETAIL``,
``Action::EDIT``, etc. (they are defined in the ``EasyCorp\Bundle\EasyAdminBundle\Config\Action`` class).

Built-in Actions
----------------

These are the built-in actions included by default in each page:

* Page ``Crud::PAGE_INDEX`` (``'index'``):

  * Added by default: ``Action::EDIT``, ``Action::DELETE``, ``Action::NEW``
  * Other available actions: ``Action::DETAIL``

* Page ``Crud::PAGE_DETAIL`` (``'detail'``):

  * Added by default: ``Action::EDIT``, ``Action::DELETE``, ``Action::INDEX``
  * Other available actions: -

* Page ``Crud::PAGE_EDIT`` (``'edit'``):

  * Added by default: ``Action::SAVE_AND_RETURN``, ``Action::SAVE_AND_CONTINUE``
  * Other available actions: ``Action::DELETE``, ``Action::DETAIL``, ``Action::INDEX``

* Page ``Crud::PAGE_NEW`` (``'new'``):

  * Added by default: ``Action::SAVE_AND_RETURN``, ``Action::SAVE_AND_ADD_ANOTHER``
  * Other available actions: ``Action::SAVE_AND_CONTINUE``, ``Action::INDEX``

Adding Actions
--------------

Use the ``add()`` method to add any built-in actions and your own custom actions
(which are explained later in this article)::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // ...
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::SAVE_AND_ADD_ANOTHER)
        ;
    }

Removing Actions
----------------

Removing actions makes them unavailable in the interface, so the user can't
click on buttons/links to run those actions. However, users can *hack* the URL
to run the action. To fully disable an action, use the ``disable()``
method explained later::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // ...
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
        ;
    }

Updating an Action
------------------

This is mostly useful to change built-in actions (e.g. to change their icon,
update or remove their label, etc.). The ``update()`` method expects a callable
and EasyAdmin passes the action to it automatically::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // ...
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setIcon('fa fa-file-alt')->setLabel(false);
            })

            // in PHP 7.4 and newer you can use arrow functions
            // ->update(Crud::PAGE_INDEX, Action::NEW,
            //     fn (Action $action) => $action->setIcon('fa fa-file-alt')->setLabel(false))
        ;
    }

Displaying Actions Conditionally
--------------------------------

Some actions must displayed only when some conditions met. For example, a
"View Invoice" action may be displayed only when the order status is "paid".
Use the ``displayIf()`` method to configure when the action should be visible
to users::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function configureActions(Actions $actions): Actions
    {
        $viewInvoice = Action::new('View Invoice', 'fas fa-file-invoice')
            ->displayIf(static function ($entity) {
                return $entity->isPaid();
            });

            // in PHP 7.4 and newer you can use arrow functions
            // ->displayIf(fn ($entity) => $entity->isPaid())

        return $actions
            // ...
            ->add(Crud::PAGE_INDEX, $viewInvoice);
    }

Disabling Actions
-----------------

Disabling an action means that it's not displayed in the interface and the user
can't run the action even if they *hack* the URL. If they try to do that, they
will see a "Forbidden Action" exception.

Actions are disabled globally, you cannot disable them per page::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // ...
            // this will forbid to create or delete entities in the backend
            ->disable(Action::NEW, Action::DELETE)
        ;
    }

Restricting Actions
-------------------

Instead of disabling actions, you can restrict their execution to certain users.
Use the ``setPermission()`` to define the Symfony Security permission needed to
view and run some action.

Permissions are defined globally; you cannot define different permissions per page::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // ...
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
        ;
    }

Reordering Actions
------------------

Use the ``reorder()`` to define the order in which actions are displayed
in some page::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // ...

            // you can reorder built-in actions...
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::DELETE, Action::EDIT])

            // ...and your own custom actions too
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, 'viewInvoice', Action::DELETE, Action::EDIT])

            // you can pass only a few actions to this method and the rest of actions
            // will be appended in their original order. In the following example, the
            // DELETE and EDIT actions are missing but they will be added automatically
            // after DETAIL and 'viewInvoice' actions
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, 'viewInvoice'])
        ;
    }

Dropdown Actions
----------------

If you display lots of fields on each row of the ``index`` page, there won't be
enough room for the item actions. In those cases, you can display the actions in
a dropdown menu instead of the expanded design used by default.

To do so, use the ``showEntityActionsAsDropdown()`` method::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

    class ProductCrudController extends AbstractCrudController
    {
        // ...

        public function configureCrud(Crud $crud): Crud
        {
            return $crud
                // ...
                ->showEntityActionsAsDropdown()
            ;
        }
    }

.. _actions-custom:

Adding Custom Actions
---------------------

In addition to the built-in actions provided by EasyAdmin, you can create your
own actions. First, define the basics of your action (name, label, icon) with
the ``Action`` class constructor::

    // the only mandatory argument is the internal name of the action (which is
    // used to add the action to some pages, to reorder the action position, etc.)
    $viewInvoice = Action::new('viewInvoice');

    // the second optional argument is the label visible to end users
    $viewInvoice = Action::new('viewInvoice', 'Invoice');
    // not defining the label explicitly or setting it to NULL means
    // that the label is autogenerated from the name (e.g. 'viewInvoice' -> 'View Invoice')
    $viewInvoice = Action::new('viewInvoice', null);
    // set the label to FALSE to not display any label for this action (but make sure
    // to display an icon for the action; otherwise users won't see it)
    $viewInvoice = Action::new('viewInvoice', false);

    // the third optional argument is the full CSS class of a FontAwesome icon
    $viewInvoice = Action::new('viewInvoice', 'Invoice', 'fa fa-file-invoice');

Once you've configured the basics, use one of the following methods to define
which method is executed when clicking on the action:

* ``linkToCrudAction()``: to execute some method of the current CRUD controller;
* ``linkToRoute()``: to execute some regular Symfony controller via its route;
* ``linkToUrl()``: to visit an external URL (useful when your action is not
  served by your application).

The following example shows all kinds of actions in practice::

    namespace App\Controller\Admin;

    use App\Entity\Invoice;
    use App\Entity\Order;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

    class OrderCrudController extends AbstractCrudController
    {
        // ...

        public function configureActions(Actions $actions): Actions
        {
            // this action executes the 'renderInvoice()' method of the current CRUD controller
            $viewInvoice = Action::new('viewInvoice', 'Invoice', 'fa fa-file-invoice')
                ->linkToCrudAction('renderInvoice');

            // if the method is not defined in a CRUD controller, link to its route
            $sendInvoice = Action::new('sendInvoice', 'Send invoice', 'fa fa-envelope')
                // if the route needs parameters, you can define them:
                // 1) using an array
                ->linkToRoute('invoice_send', [
                    'send_at' => (new \DateTime('+ 10 minutes'))->format('YmdHis'),
                ])

                // 2) using a callable (useful if parameters depend on the entity instance)
                // (the type-hint of the function argument is optional but useful)
                ->linkToRoute('invoice_send', function (Order $order) {
                    return [
                        'uuid' => $order->getId(),
                        'method' => $order->getUser()->getPreferredSendingMethod(),
                    ];
                });

            // this action points to the invoice on Stripe application
            $viewStripeInvoice = Action::new('viewInvoice', 'Invoice', 'fa fa-file-invoice')
                ->linkToUrl(function (Order $entity) {
                    return 'https://www.stripe.com/invoice/'.$entity->getStripeReference();
                });

            return $actions
                // ...
                ->add(Crud::PAGE_DETAIL, $viewInvoice)
                ->add(Crud::PAGE_DETAIL, $sendInvoice)
                ->add(Crud::PAGE_DETAIL, $viewStripeInvoice)
            ;
        }
        
        public function renderInvoice(AdminContext $context)
        {
            $order = $context->getEntity()->getInstance();
            
            // add your logic here...
        }
    }

Batch Actions
-------------

.. note::

    Batch actions are not ready yet, but we're working on adding support for them.

.. _actions-integrating-symfony:

Integrating Symfony Actions
---------------------------

If the action logic is small and directly related to the backend, it's OK to add
it to the :doc:`CRUD controller </crud>`, because that simplifies a lot its
integration in EasyAdmin. However, sometimes you have some logic that it's too
complex or used in other parts of the Symfony application, so you can't move it
to the CRUD controller. This section explains how to integrate an existing Symfony
action in EasyAdmin so you can reuse the backend layout, menu and other features.

Imagine that your Symfony application has an action to calculate some business
stats about your clients (average order amount, yearly number of purchases, etc.)
All this is calculated in a ``BusinessStatsCalculator`` service, so you can't
create a CRUD controller to display that information. Instead, create a normal
Symfony controller called ``BusinessStatsController``::

    // src/Controller/Admin/BusinessStatsController.php
    namespace App\Controller\Admin;

    use App\Stats\BusinessStatsCalculator;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\Routing\Annotation\Route;

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     */
    class BusinessStatsController extends AbstractController
    {
        public function __construct(BusinessStatsCalculator $businessStatsCalculator)
        {
            $this->businessStatsCalculator = $businessStatsCalculator;
        }

        /**
         * @Route("/admin/business-stats", name="admin_business_stats")
         */
        public function index()
        {
            return $this->render('admin/business_stats/index.html.twig', [
                'data' => $this->businessStatsCalculator->getStatsSummary(),
            ]);
        }

        /**
         * @Route("/admin/business-stats/{id}", name="admin_business_stats_customer")
         */
        public function customer(Customer $customer)
        {
            return $this->render('admin/business_stats/customer.html.twig', [
                'data' => $this->businessStatsCalculator->getCustomerStats($customer),
            ]);
        }
    }

This is a normal Symfony controller (it doesn't extend any EasyAdmin class) with
some logic which renders the result in Twig templates (which will be shown later).
The first step to integrate this into your EasyAdmin backend is to add it to the
main menu using the ``configureMenuItems()`` method::

    // src/Controller/Admin/DashboardController.php
    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

    class DashboardController extends AbstractDashboardController
    {
        // ...

        public function configureMenuItems(): iterable
        {
            // ...

            yield MenuItem::linktoRoute('Stats', 'fa fa-chart-bar', 'admin_business_stats');
        }
    }

If you reload your backend and click on that new menu item, you'll see an error
because the templates used by the BusinessStatsController are not created yet.
However, you'll see the trick used by EasyAdmin to integrate Symfony actions.
Check out the URL of the page. Instead of the expected ``/admin/business-stats``,
the page URL is ``/admin?menuIndex=...&submenuIndex=-1&routeName=admin_business_stats``
This is needed so EasyAdmin knows which Dashboard to use when processing the
Symfony action.

Now, create the template used by the ``index()`` method, which lists a summary
of the stats of all customers and includes a link to the detailed stats of each
of them:

.. code-block:: twig

    {# templates/admin/business_stats/index.html.twig #}
    {% extends '@EasyAdmin/page/content.html.twig' %}

    {% block page_title 'Business Stats' %}
    {% block page_content %}
        <table>
            <thead> {# ... #} </thead>
            <tbody>
                {% for customer_data in data %}
                    <tr>
                        {# ... #}

                        <td>
                            <a href="{{ ea_url().setRoute('admin_business_stats_customer', { id: customer_data.id }) }}">
                                View Details
                            </a>
                        </td>
                    </tr>
                {% endfor %}
            </tbody>
        </table>
    {% endblock %}

The Twig template extends the :ref:`content page template <content_page_template>`
provided by EasyAdmin to reuse all the backend design. The rest of the template
is normal Twig code, except for the URL generation. Instead of using Symfony's
``path()`` function, you must use the :ref:`ea_url() function <ea-url-function>`
and pass the Symfony route name and parameter.

Similar to what happened before, the generated URL is not the expected
``/admin/business-stats/5`` but
``/admin?routeName=admin_business_stats_customer&routeParams%5Bid%5D=5``.
But that's fine. EasyAdmin will run the ``customer()`` method of your
BusinessStatsController, so you can render another Twig template with the
customer stats.

Generating URLs to Symfony Actions Integrated in EasyAdmin
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

As explained in detail in the previous section, when integrating a Symfony
action in an EasyAdmin backend, you need to generate URLs a bit differently.
Instead of using Symfony's UrlGenerator service or the ``$this->generateUrl()``
shortcut in a controller, you must use the AdminUrlGenerator service provided
by EasyAdmin::

    // src/Controller/SomeController.php
    namespace App\Controller;

    use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\Routing\Annotation\Route;

    class SomeController extends AbstractController
    {
        public function __construct(AdminUrlGenerator $adminUrlGenerator)
        {
            $this->adminUrlGenerator = $adminUrlGenerator;
        }

        public function someMethod()
        {
            $url = $this->adminUrlGenerator->setRoute('admin_business_stats_customer', [
                'id' => $this->getUser()->getId(),
            ]->generateUrl());

            // ...
        }
    }
