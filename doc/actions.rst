Actions
=======

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

  * Added by default globally: ``Action::NEW``
  * Added by default per entry: ``Action::EDIT``, ``Action::DELETE``
  * Other available actions per entry: ``Action::DETAIL``

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
            ->update(Crud::PAGE_INDEX, Action::NEW,
                static fn (Action $action) => $action->setIcon('fa fa-file-alt')->setLabel(false)
            )
        ;
    }

Generating Dynamic Action Labels
--------------------------------

Action labels can be dynamically generated based on the related entity they
belong to. For example, an ``Invoice`` entity can be paid with multiple payments.
At the top of each ``Invoice`` detail page, administrators want to have an action
link (or button) that brings them to a custom page that shows the received payments
for that invoice. In order to provide a better user experience, the action link
(or button) label must display the current number of received payments
(e.g.: ``3 payments``)::

        use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
        use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
        use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

        public function configureActions(Actions $actions): Actions
        {
            $viewPayments = Action::new('payments')
                ->setLabel(static fn (Invoice $invoice): string => \count($invoice->getPayments()) . ' payments')

            return $actions
                // ...
                ->add(Crud::PAGE_DETAIL, $viewPayments);
        }

If the related entity object is not enough for computing the action label,
then any more specific service object can be used as a delegator. For example,
a Doctrine repository service object can be used for counting the related number
of payments for the administered invoice::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    private InvoicePaymentRepository $invoicePaymentRepository;

    public function __construct(InvoicePaymentRepository $invoicePaymentRepository)
    {
        $this->invoicePaymentRepository = $invoicePaymentRepository;
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewPayments = Action::new('payments')
            ->setLabel(function (Invoice $invoice)) {
                return $this->invoicePaymentRepository->countByInvoice($invoice) . ' payments';
            });

        return $actions
            // ...
            ->add(Crud::PAGE_DETAIL, $viewPayments);
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
            ->displayIf(static fn (Invoice $invoice): bool => $invoice->isPaid())

        return $actions
            // ...
            ->add(Crud::PAGE_INDEX, $viewInvoice);
    }

.. note::

    The ``displayIf()`` method also works for :ref:`global actions <global-actions>`.
    However, your closure won't receive the object that represents the current
    entity because global actions are not associated to any specific entity.

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

By default, actions are ordered by type: "primary" actions are displayed first,
followed by "default", "success", "warning", and, lastly, "danger" actions. This
ordering also applies to your :ref:`custom actions <actions-custom>`, as explained below.

This ordering usually produces the best visual result. However, you can disable
this behavior in your application by calling the following method::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // ...
            ->disableAutomaticOrdering();
    }
}

You can also use the ``reorder()`` method to define an explicit order in which
actions are displayed on a page::

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

.. note::

    When using the ``reorder()`` method, the smart sorting feature is
    automatically disabled.

Dropdown and Inline Entity Actions
----------------------------------

In the ``index`` page, the entity actions (``edit``, ``delete``, etc.) are
displayed by default in a dropdown. This is done to better display the field
contents on each row. If you prefer to display all the actions *inline*
(that is, without a dropdown) use the ``showEntityActionsInlined()`` method::

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
                ->showEntityActionsInlined()
            ;
        }
    }

Grouping Actions
----------------

In addition to individual actions, you can group multiple related actions into
a single button. This is useful when you have many actions and want to organize
them better or save space in the interface. Use the ``ActionGroup`` class
to create these grouped actions::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
    use EasyCorp\Bundle\EasyAdminBundle\Config\ActionGroup;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function configureActions(Actions $actions): Actions
    {
        $publishActions = ActionGroup::new('publish', 'Publish')
            ->addAction(Action::new('publishNow', 'Publish Now')->linkToCrudAction('...'))
            ->addAction(Action::new('schedule', 'Schedule...')->linkToCrudAction('...'))
            ->addAction(Action::new('publishDraft', 'Save as Draft')->linkToCrudAction('...'));

        return $actions
            // ...
            ->add(Crud::PAGE_EDIT, $publishActions)
        ;
    }

This is how the action group looks in practice:

.. image:: images/easyadmin-action-group.gif
   :alt: An action group that includes three different actions into a single button

Similar to standalone actions, on the index page there are two types of action
groups: those associated with each entity and those associated with the entire page::

    public function configureActions(Actions $actions): Actions
    {
        $createActions = ActionGroup::new('create')
            ->createAsGlobalActionGroup()
            ->addAction(Action::new('new', 'Create Post')->linkToCrudAction('...'))
            ->addAction(Action::new('draft', 'Draft Post')->linkToCrudAction('...'))
            ->addAction(Action::new('template', 'Create from Template')->linkToCrudAction('...'));

        $sendActions = ActionGroup::new('send', 'Send ...')
            ->addAction(Action::new('sendEmail', 'Send by Email')->linkToCrudAction('...'))
            ->addAction(Action::new('sendSlack', 'Send to Slack')->linkToCrudAction('...'))
            ->addAction(Action::new('sendTelegram', 'Send via Telegram')->linkToCrudAction('...'));

        return $actions
            // ...
            ->add(Crud::PAGE_INDEX, $createActions)
            ->add(Crud::PAGE_INDEX, $sendActions)
        ;
    }

The ``createAsGlobalActionGroup()`` method creates an action group associated
with the entire page rather than any specific entity. It appears like the image
shown above for action groups.

When not using the ``createAsGlobalActionGroup()`` method on the index page, the
action group is displayed as a nested dropdown on each entity row (see the image
in the next section below).

Split Button Dropdowns
~~~~~~~~~~~~~~~~~~~~~~

If one of the grouped actions is more common than the others, you can render the
group as a "split button". This displays the **main action** as a clickable button,
with the other actions available in the dropdown::

    $publishActions = ActionGroup::new('publish', 'Publish')
        ->addMainAction(Action::new('publishNow', 'Publish Now')->linkToCrudAction('...'))
        ->addAction(Action::new('schedule', 'Schedule...')->linkToCrudAction('...'))
        ->addAction(Action::new('publishDraft', 'Save as Draft')->linkToCrudAction('...'));

Now, the action group will look as follows:

.. image:: images/easyadmin-action-group-split-button.gif
   :alt: An action group that defines a main action and a list of secondary actions

On the index page, if the action group is associated with each entity, it's
displayed as a dropdown. In the following image, the first action group is a
simple dropdown because it doesn't define a main action. The second action
group is a split dropdown, where the main action is a clickable element and the
remaining actions appear when hovering over the submenu marker:

.. image:: images/easyadmin-action-group-entity-dropdown.gif
   :alt: An action group inside an entity dropdown. The second group defines a main action.

Headers and Dividers
~~~~~~~~~~~~~~~~~~~~

For better organization, especially with many actions in a dropdown, you can add
headers and dividers to create logical groups::

    $actionsGroup = ActionGroup::new('actions', 'Actions', 'fa fa-cog')
        ->addHeader('Quick Actions')
        ->addAction(Action::new('approve', 'Approve')->linkToCrudAction('approve'))
        ->addAction(Action::new('reject', 'Reject')->linkToCrudAction('reject'))
        ->addDivider()
        ->addHeader('Advanced')
        ->addAction(Action::new('archive', 'Archive')->linkToCrudAction('archive'))
        ->addAction(Action::new('delete', 'Delete')->linkToCrudAction('delete')
            ->addCssClass('text-danger'));

Headers help users understand the purpose of each group, while dividers provide
visual separation between different sections.

Conditional Dropdown Display
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Like regular actions, dropdowns can be displayed conditionally based on the
entity state or user permissions::

    $moderationGroup = ActionGroup::new('moderation', 'Moderation')
        // the callable receives the current entity instance or null (in the index page)
        ->displayIf(static function ($entity) {
            return null !== $entity && 'pending' === $entity->getStatus();
        })
        ->addAction(Action::new('approve', 'Approve')->linkToCrudAction('approve'))
        ->addAction(Action::new('reject', 'Reject')->linkToCrudAction('reject'));

The dropdown will only appear when the condition is met. Individual actions
within the dropdown can also have their own display conditions.

Customizing Dropdown Appearance
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Dropdowns support the same customization options as regular actions for styling
and HTML attributes::

    $customGroup = ActionGroup::new('custom', 'Options')
        // use only an icon, no label
        ->setLabel(false)
        ->setIcon('fa fa-ellipsis-v')

        // create different variants of action groups
        ->asPrimaryActionGroup()
        ->asDefaultActionGroup()
        ->asSuccessActionGroup()
        ->asWarningActionGroup()
        ->asDangerActionGroup()

        // add custom CSS classes
        ->addCssClass('my-custom-dropdown')

        // add HTML attributes
        ->setHtmlAttributes(['data-foo' => 'bar']);

You can also customize individual actions within the dropdown using the standard
action configuration methods.

.. _actions-custom:

Adding Custom Actions
---------------------

.. tip::

    If you already have a controller action that implements the logic for your
    custom action, you can :ref:`integrate any Symfony controller into your EasyAdmin backend <actions-integrating-symfony>`
    without defining a new custom action.

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
    // to display an icon for the action; otherwise users can't see or click on the action)
    $viewInvoice = Action::new('viewInvoice', false);

    // the third optional argument is the icon name
    $viewInvoice = Action::new('viewInvoice', 'Invoice', 'fa fa-file-invoice');

By default, EasyAdmin assumes that icon names correspond to `FontAwesome`_ CSS
classes. The necessary CSS styles and web fonts are included by default too,
so you don't need to take any additional steps to use FontAwesome icons. Alternatively,
you can :ref:`use your own icon sets <icon-customization>` instead of FontAwesome.

Then you can configure the basic HTML/CSS attributes of the button/element
that will represent the action::

    $viewInvoice = Action::new('viewInvoice', 'Invoice', 'fa fa-file-invoice')
        // by default, actions are rendered with `<button>` HTML elements;
        // use this method to use an `<a>` element instead. Visually, this will
        // look the same as a button
        ->renderAsLink()

        // by default, actions are rendered as `<button type="submit" ...>` elements.
        // this method allows to change it and use a `<button type="button" ...>` element.
        ->renderAsButton('submit')
        // also available as EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonType
        ->renderAsButton(ButtonType::Submit)

        // by default, custom actions are rendered as <a> elements that trigger GET requests.
        // use this method to render them as <button> elements with an associated hidden <form>,
        // so that custom actions send a POST request to the action URL.
        ->renderAsForm()

        // a key-value array of attributes to add to the HTML element
        ->setHtmlAttributes(['data-foo' => 'bar', 'target' => '_blank'])

        // by default, actions are shown as `btn-secondary` elements; use the
        // following actions to change their style and priority accordingly
        ->asDefaultAction()
        ->asPrimaryAction()
        ->asSuccessAction()
        ->asWarningAction()
        ->asDangerAction()

        // by default, actions are rendered as solid buttons; this method makes
        // the action to be rendered as a simple text link without button background
        // (the background is shown when hovering the action link).
        ->asTextLink()
        // you can combine it with the styling methods (e.g. to create a "text danger" action)
        ->asTextLink()->asDangerAction()

        // removes all existing CSS classes of the action and sets
        // the given value as the CSS class of the HTML element;
        ->setCssClass('btn btn-primary action-foo')

        // adds the given value to the existing CSS classes of the action (this is
        // useful when customizing a built-in action, which already has CSS classes)
        ->addCssClass('some-custom-css-class text-danger')

This is how the different button style variants look in light and dark mode:

.. image:: images/easyadmin-buttons-light-mode.gif
   :alt: EasyAdmin button variants in light mode

.. image:: images/easyadmin-buttons-dark-mode.gif
   :alt: EasyAdmin button variants in dark mode

.. note::

    When using ``setCssClass()`` or ``addCssClass()`` methods, the action loses
    the default CSS classes applied by EasyAdmin (``.btn-*`` and
    ``.action-<the-action-name>``). You might want to add those CSS classes
    manually to make your actions look as expected.

Once you've configured the basics, use one of the following methods to define
which method runs when you click the action:

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
                ->linkToRoute('invoice_send', function (Order $order): array {
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

.. tip::

    CRUD controllers in EasyAdmin extend the `Symfony base controller class`_.
    When actions are defined as methods of CRUD controllers, they can use any
    of the shortcuts and utilities available in regular `Symfony controllers`_,
    such as ``$this->render()``, ``$this->redirect()``, and others.

Custom actions can define the ``#[AdminRoute]`` attribute to
:ref:`customize their route name, path and methods <crud_routes>`::

    use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
    // ...


    #[AdminRoute(path: '/invoice', name: 'view_invoice')]
    public function renderInvoice(AdminContext $context)
    {
        // ...
    }

.. _global-actions:

Global Actions
--------------

On pages that list entries (e.g. ``Crud::PAGE_INDEX``) you can configure actions
per entry as well as global actions. Global actions are displayed above the
listed entries.

An example of creating a custom action and adding it globally to the ``index``
page::

    $goToStripe = Action::new('goToStripe')
        ->linkToUrl('https://www.stripe.com/')
        ->createAsGlobalAction()
    ;

    $actions->add(Crud::PAGE_INDEX, $goToStripe);

Batch Actions
-------------

Batch actions are a special kind of action which is applied to multiple items at
the same time. They are only available in the ``index`` page.

Imagine that you manage users with a ``User`` entity and a common task is to
approve their sign ups. Instead of creating a normal ``approve`` action as
explained in the previous sections, create a batch action to be more productive
and approve multiple users at once.

First, add it to your action configuration using the ``addBatchAction()`` method::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

    class UserCrudController extends AbstractCrudController
    {
        // ...

        public function configureActions(Actions $actions): Actions
        {
            return $actions
                // ...
                ->addBatchAction(Action::new('approve', 'Approve Users')
                    ->linkToCrudAction('approveUsers')
                    ->addCssClass('btn btn-primary')
                    ->setIcon('fa fa-user-check'))
            ;
        }
    }

Batch actions support the same configuration options as the other actions and
they can link to a CRUD controller method, to a Symfony route or to some URL.
If there's at least one batch action, the backend interface is updated to add some
"checkboxes" that allow selecting more than one row of the index listing.

When the user clicks on the batch action link/button, a form is submitted using
the ``POST`` method to the action or route configured in the action. The easiest
way to get the submitted data is to type-hint some argument of your batch action
method with the ``EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto`` class.
If you do that, EasyAdmin will inject a DTO with all the batch action data::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
    use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;

    class UserCrudController extends AbstractCrudController
    {
        // ...

        public function approveUsers(BatchActionDto $batchActionDto)
        {
            $className = $batchActionDto->getEntityFqcn();
            $entityManager = $this->container->get('doctrine')->getManagerForClass($className);
            foreach ($batchActionDto->getEntityIds() as $id) {
                $user = $entityManager->find($className, $id);
                $user->approve();
            }

            $entityManager->flush();

            return $this->redirectToRoute('admin_user_index');
        }
    }

.. note::

    As an alternative, instead of injecting the ``BatchActionDto`` variable, you can
    also inject Symfony's ``Request`` object to get all the raw submitted batch data
    (e.g. ``$request->request->all('batchActionEntityIds')``).

.. _actions-integrating-symfony:

Integrating Symfony Actions
---------------------------

If the action logic is small and directly related to the backend, it's OK to add
it to the :doc:`CRUD controller </crud>` as a quick and simple way of integrating
it into your EasyAdmin backend. However, sometimes the logic is too complex or
also used in other parts of the Symfony application, so you can't move it into
the CRUD controller. This section explains how to integrate an existing Symfony
controller action in EasyAdmin so you can reuse the backend layout, menu, and other features.

Imagine that your Symfony application has an action that calculates business
statistics about your clients (average order amount, yearly number of purchases, etc.).
All of this is calculated in a ``BusinessStatsCalculator`` service, so you can't
create a CRUD controller to display that information. Instead, create a standard
Symfony controller called ``BusinessStatsController``::

    // src/Controller/BusinessStatsController.php
    namespace App\Controller;

    use App\Stats\BusinessStatsCalculator;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\Component\Security\Http\Attribute\IsGranted;

    #[IsGranted('ROLE_ADMIN')]
    class BusinessStatsController extends AbstractController
    {
        public function __construct(BusinessStatsCalculator $businessStatsCalculator)
        {
            $this->businessStatsCalculator = $businessStatsCalculator;
        }

        #[Route("/admin/business-stats", name: "business_stats_index")]
        public function index()
        {
            return $this->render('admin/business_stats/index.html.twig', [
                'data' => $this->businessStatsCalculator->getStatsSummary(),
            ]);
        }

        #[Route("/admin/business-stats/{id}", name: "business_stats_customer")]
        public function customer(Customer $customer)
        {
            return $this->render('admin/business_stats/customer.html.twig', [
                'data' => $this->businessStatsCalculator->getCustomerStats($customer),
            ]);
        }
    }

This is a regular Symfony controller (it doesn't extend any EasyAdmin class)
with some logic that renders results in Twig templates (shown later). The first
step to integrate this into your EasyAdmin backend is to create **admin routes**
for the actions using the ``#[AdminRoute]`` attribute::

    // src/Controller/BusinessStatsController.php
    namespace App\Controller;

    use App\Stats\BusinessStatsCalculator;
    use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\Component\Security\Http\Attribute\IsGranted;

    #[IsGranted('ROLE_ADMIN')]
    #[AdminRoute('/business-stats', name: 'business_stats')]
    class BusinessStatsController extends AbstractController
    {
        public function __construct(BusinessStatsCalculator $businessStatsCalculator)
        {
            $this->businessStatsCalculator = $businessStatsCalculator;
        }

        #[Route("/admin/business-stats", name: "business_stats_index")]
        #[AdminRoute("/", name: "index")]
        public function index()
        {
            return $this->render('admin/business_stats/index.html.twig', [
                'data' => $this->businessStatsCalculator->getStatsSummary(),
            ]);
        }

        #[Route("/admin/business-stats/{id}", name: "business_stats_customer")]
        #[AdminRoute("/{id}", name: "customer")]
        public function customer(Customer $customer)
        {
            return $this->render('admin/business_stats/customer.html.twig', [
                'data' => $this->businessStatsCalculator->getCustomerStats($customer),
            ]);
        }
    }

The ``#[AdminRoute]`` attribute generates admin routes for the given actions
following this logic:

* Take the route path and name of each EasyAdmin dashboard. For example, in the
  common case of using ``/admin`` and ``admin`` in your dashboard, those values are taken.
* If there's an ``#[AdminRoute]`` attribute at the class level, treat it as a
  prefix of the final route, just like Symfony's ``#[Route]`` attribute works.
* Use the route path and name of the ``#[AdminRoute]`` attribute of each action
  as the final segment in the generated route.

In this example:

* The first route path will be ``/admin/business-stats`` (``/admin`` + ``/business-stats`` + ``/``)
  and its name will be ``admin_business_stats_index`` (``admin`` + ``business_stats`` + ``index``)
* The second route path will be ``/admin/business-stats/{id}`` (``/admin`` + ``/business-stats`` + ``/{id}``)
  and its name will be ``admin_business_stats_customer`` (``admin`` + ``business_stats`` + ``customer``)

.. note::

    You might need to clear the cache of your Symfony application before the
    new routes become available.

This process is applied for each of the EasyAdmin dashboards defined in your
application. You can restrict in which dashboards each route is available using
the following options::

    use App\Controller\Admin\DashboardController;
    use App\Controller\Admin\GuestDashboardController;
    use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;

    // Use the 'allowedDashboards' option to NOT generate a route for ANY dashboards
    // except those listed explicitly:

    #[AdminRoute('...', name: '...', allowedDashboards: [DashboardController::class, '...'])]
    class BusinessStatsController extends AbstractController

    // Use the 'deniedDashboards' option to generate a route for ALL dashboards
    // except those listed explicitly:

    #[AdminRoute('...', name: '...', deniedDashboards: [GuestDashboardController::class, '...'])]
    class BusinessStatsController extends AbstractController

The options to allow or exclude dashboards can be applied at both the class and
action levels, and you can override them at the action level as follows:

* ``false`` (it's the default value): means "option not set" and tells EasyAdmin
  to inherit the value from the ``#[AdminRoute]`` attribute defined at the class
  level (if any);
* ``null``: explicitly allow/deny all dashboards; it's used to override the same
  option in the ``#[AdminRoute]`` attribute at class level;
* ``[]``: explicitly allow/deny no dashboards;
* ``[FooDashboard::class, BarDashboard::class, ...]``: allow/deny only these
  specific dashboards.

Now you can link to those admin routes from your main menu to render the actions
fully integrated into each dashboard::

    // src/Controller/Admin/DashboardController.php
    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

    #[AdminDashboard(routePath: '/admin', routeName: 'admin')]
    class DashboardController extends AbstractDashboardController
    {
        // ...

        public function configureMenuItems(): iterable
        {
            // ...

            yield MenuItem::linktoRoute('Stats', 'fa fa-chart-bar', 'admin_business_stats_index');
        }
    }

If you reload your backend and click on that new menu item, you'll see an error
because the templates used by the ``BusinessStatsController`` haven't been created yet.
Next, create the template used by the ``index()`` method, which shows a summary
of the stats of all customers and includes a link to the detailed stats of each one:

.. code-block:: twig

    {# templates/admin/business_stats/index.html.twig #}
    {% extends '@EasyAdmin/page/content.html.twig' %}

    {% block content_title 'Business Stats' %}
    {% block main %}
        <table>
            <thead> {# ... #} </thead>
            <tbody>
                {% for customer_data in data %}
                    <tr>
                        {# ... #}

                        <td>
                            <a href="{{ path('admin_business_stats_customer', { id: customer_data.id }) }}">
                                View Details
                            </a>
                        </td>
                    </tr>
                {% endfor %}
            </tbody>
        </table>
    {% endblock %}

The Twig template extends the :ref:`content page template <content_page_template>`
provided by EasyAdmin to reuse the backend design. The rest of the template
is standard Twig code, including the use of the Symfony's ``path()`` function t
 generate the URL for the ``admin_business_stats_customer`` admin route.

.. _generating-urls-to-symfony-actions-integrated-in-easyadmin:

Legacy URL Generation for Symfony Actions
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In EasyAdmin versions prior to 4.25.0, you couldn't define custom admin routes
for your actions. This meant that you couldn't use Symfony features related to
routing, such as the ``UrlGenerator`` service or the ``path()`` Twig function
to generate URLs.

In those cases, you had to use the EasyAdmin ``AdminUrlGenerator`` to generate
admin URLs pointing to your custom actions, as follows::

    // src/Controller/SomeController.php
    namespace App\Controller;

    use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

    class SomeController extends AbstractController
    {
        private $adminUrlGenerator;

        public function __construct(AdminUrlGenerator $adminUrlGenerator)
        {
            $this->adminUrlGenerator = $adminUrlGenerator;
        }

        public function someMethod()
        {
            $url = $this->adminUrlGenerator->setRoute('admin_business_stats_customer', [
                'id' => $this->getUser()->getId(),
            ])->generateUrl();

            // ...
        }
    }

This is no longer needed in modern EasyAdmin versions and is now a discouraged
practice that you should avoid in your applications. Instead, see the previous
section about :ref:`how to integrate custom Symfony controllers into EasyAdmin dashboards <actions-integrating-symfony>`.

.. _`FontAwesome`: https://fontawesome.com/
.. _`Symfony base controller class`: https://symfony.com/doc/current/controller.html#the-base-controller-class-services
.. _`Symfony controllers`: https://symfony.com/doc/current/controller.html
