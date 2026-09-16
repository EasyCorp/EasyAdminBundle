Actions
=======

**Actions** are each of the tasks that you can perform on CRUD pages. In the
``index`` page for example, you have tasks to "edit" and "delete" each entity
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

.. _action-names:

Action Names and Constants
--------------------------

Some methods expect an action name as an argument. In addition to plain
strings with the action names (``'index'``, ``'detail'``, ``'edit'``, etc.) you
can also use constants for these values: ``Action::INDEX``, ``Action::DETAIL``,
``Action::EDIT``, etc. (they are defined in the ``EasyCorp\Bundle\EasyAdminBundle\Config\Action`` class).

.. _actions-built-in:

Built-in Actions
----------------

These are the built-in actions included by default in each page:

* Page ``Crud::PAGE_INDEX`` (``'index'``):

  * Added by default globally: ``Action::NEW``
  * Added by default per entry: ``Action::EDIT``, ``Action::DELETE``
  * Added by default as a :ref:`batch action <batch-actions>`: ``Action::BATCH_DELETE``
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

.. note::

    By default, clicking a row in the ``index`` page navigates to ``edit`` action
    (or ``detail`` if edit is unavailable). See :ref:`default row action <default-row-action>`
    to customize this.

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

The ``add()`` method throws an exception if the page already includes an action
with the same name. Use the ``set()`` method instead when you want to add the
action or replace the existing one, whichever applies::

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // ...
            // this replaces the built-in 'detail' action if it's already added
            ->set(Crud::PAGE_INDEX, $myCustomDetailAction)
        ;
    }

Removing Actions
----------------

Removing actions makes them unavailable in the interface, so the user can't
click on buttons/links to run those actions. However, users can modify the URL
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
            ->setLabel(static fn (Invoice $invoice): string => \count($invoice->getPayments()) . ' payments');

        return $actions
            // ...
            ->add(Crud::PAGE_DETAIL, $viewPayments);
    }

If the related entity object is not enough for computing the action label,
then you can compute the label with any other service, such as a Doctrine
repository. For example, a Doctrine repository service object can be used for
counting the related number of payments for the administered invoice::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function __construct(
        private InvoicePaymentRepository $invoicePaymentRepository,
    ) {
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewPayments = Action::new('payments')
            ->setLabel(function (Invoice $invoice) {
                return $this->invoicePaymentRepository->countByInvoice($invoice) . ' payments';
            });

        return $actions
            // ...
            ->add(Crud::PAGE_DETAIL, $viewPayments);
    }

Displaying Actions Conditionally
--------------------------------

Some actions should be displayed only when certain conditions are met. For
example, a "View Invoice" action may be displayed only when the order status is
"paid". Use the ``displayIf()`` method to configure when the action should be
visible to users::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function configureActions(Actions $actions): Actions
    {
        $viewInvoice = Action::new('invoice', 'View Invoice', 'fas fa-file-invoice')
            ->displayIf(static fn (Invoice $invoice): bool => $invoice->isPaid());

        return $actions
            // ...
            ->add(Crud::PAGE_INDEX, $viewInvoice);
    }

.. note::

    The ``displayIf()`` method also works for :ref:`global actions <global-actions>`.
    However, your closure won't receive the object that represents the current
    entity because global actions are not associated with any specific entity.

Action Confirmation
-------------------

By default, actions are executed immediately when clicked. The exceptions are the
built-in ``delete`` action and all :ref:`batch actions <batch-action-confirmation>`,
which show a confirmation message. For potentially destructive or important
actions, you can require user confirmation before execution.

To enable confirmation for any action, use the ``askConfirmation()`` method::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function configureActions(Actions $actions): Actions
    {
        $archiveAction = Action::new('archive', 'Archive')
            ->linkToCrudAction('archive')
            ->askConfirmation();

        return $actions
            ->add(Crud::PAGE_INDEX, $archiveAction);
    }

This will display a confirmation modal with a generic message before executing
the action. You can customize the confirmation message by passing a string::

    $archiveAction = Action::new('archive', 'Archive')
        ->linkToCrudAction('archive')
        ->askConfirmation('Are you sure you want to archive this item?');

The confirmation message supports placeholders that are replaced with actual
values: ``%action_name%`` (the action label), ``%entity_name%`` (the entity
label in singular), and ``%entity_id%`` (the entity ID)::

    $archiveAction = Action::new('archive', 'Archive')
        ->linkToCrudAction('archive')
        ->askConfirmation('Are you sure you want to %action_name% "%entity_name%" #%entity_id%?');

For translatable messages, pass a ``TranslatableInterface`` object::

    use function Symfony\Component\Translation\t;

    $archiveAction = Action::new('archive', 'Archive')
        ->linkToCrudAction('archive')
        ->askConfirmation(t('action.archive.confirm'));

You can also customize the confirmation button label by passing a second parameter::

    $publishAction = Action::new('publish', 'Publish')
        ->linkToCrudAction('publish')
        ->askConfirmation('Do you accept publishing this article?', 'Accept');

This is useful when the default "Confirm" label doesn't match the action context.
Both parameters support translatable messages::

    $publishAction = Action::new('publish', 'Publish')
        ->linkToCrudAction('publish')
        ->askConfirmation(t('action.publish.confirm'), t('action.publish.button'));

The ``delete`` action shows a confirmation message by default. Although it's
strongly recommended to keep this behavior, you can disable the confirmation dialog::

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->askConfirmation(false);
            });
    }

Disabling Actions
-----------------

Disabling an action means that it's not displayed in the interface and the user
can't run the action even if they modify the URL. If they try to do that, they
will see a "Forbidden Action" exception.

Actions are disabled globally, so you cannot disable them per page::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // ...
            // this prevents creating or deleting entities in the backend
            ->disable(Action::NEW, Action::DELETE)
        ;
    }

.. note::

    Disabling ``Action::DELETE`` also disables the ``Action::BATCH_DELETE``
    batch action automatically. The opposite doesn't happen: disabling
    ``Action::BATCH_DELETE`` keeps the regular ``delete`` action enabled.

Restricting Actions
-------------------

Instead of disabling actions, you can restrict their execution to certain users.
Use the ``setPermission()`` method to define the Symfony Security permission
needed to view and run some action. This is explained in more detail in the
article about :ref:`action permissions <security-permissions-actions>`.

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

Instead of a role name, you can pass a
``Symfony\Component\ExpressionLanguage\Expression`` object to define more
complex rules, as explained in :ref:`security expressions <security-expressions>`.

When restricting several actions, you can also use the ``setPermissions()``
method and pass all the permissions at once::

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // ...
            ->setPermissions([
                Action::NEW => 'ROLE_ADMIN',
                Action::DELETE => 'ROLE_SUPER_ADMIN',
            ])
        ;
    }

Reordering Actions
------------------

By default, actions are ordered by type: "primary" actions are displayed first,
followed by "default", "success", "info", "warning", and, lastly, "danger"
actions. Actions rendered as solid buttons are always displayed before actions
rendered as text links. This ordering also applies to your
:ref:`custom actions <actions-custom>`, as explained below.

This ordering usually produces the best visual result. However, you can disable
this behavior in your application by calling the following method::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // ...
            ->disableAutomaticOrdering();
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

    When using the ``reorder()`` method, the automatic ordering is disabled.

Dropdown and Inline Entity Actions
----------------------------------

In the ``index`` page, the entity actions (``edit``, ``delete``, etc.) are
displayed by default in a dropdown. This is done to better display the field
contents on each row. If you prefer to display all the actions *inline*
(that is, without a dropdown) use the ``showEntityActionsInlined()``
:ref:`design option <crud-design-options>`::

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

.. _actions-grouping:

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
with the entire page rather than any specific entity. It is rendered above the
listing, as shown in the image above.

When not using the ``createAsGlobalActionGroup()`` method on the index page, the
action group is displayed as a nested dropdown on each entity row (see the image
in the next section below).

.. _split-button-dropdowns:

Split Button Action Groups
~~~~~~~~~~~~~~~~~~~~~~~~~~

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

For better organization, especially with many actions in an action group, you can
add headers and dividers to create logical groups::

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

.. _conditional-dropdown-display:

Conditional Action Group Display
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Like regular actions, action groups can be displayed conditionally based on the
entity state or user permissions::

    $moderationGroup = ActionGroup::new('moderation', 'Moderation')
        // the callable receives the current entity instance or null for global action groups
        ->displayIf(static function ($entity) {
            return null !== $entity && 'pending' === $entity->getStatus();
        })
        ->addAction(Action::new('approve', 'Approve')->linkToCrudAction('approve'))
        ->addAction(Action::new('reject', 'Reject')->linkToCrudAction('reject'));

The action group will only appear when the condition is met. Individual actions
within the group can also have their own display conditions.

.. _customizing-dropdown-appearance:

Customizing Action Group Appearance
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Action groups support the same customization options as regular actions for
styling and HTML attributes::

    $customGroup = ActionGroup::new('custom', 'Options')
        // use only an icon, no label
        ->setLabel(false)
        ->setIcon('fa fa-ellipsis-v')

        // create different variants of action groups
        ->asPrimaryActionGroup()
        ->asDefaultActionGroup()
        ->asSuccessActionGroup()
        ->asInfoActionGroup()
        ->asWarningActionGroup()
        ->asDangerActionGroup()

        // add custom CSS classes
        ->addCssClass('my-custom-dropdown')

        // add HTML attributes
        ->setHtmlAttributes(['data-foo' => 'bar'])

        // render the action group with your own Twig template instead of the
        // built-in one (the template receives 'group' and 'entity' variables)
        ->setTemplatePath('admin/action_group/my_group.html.twig');

You can also customize individual actions within the action group using the
standard action configuration methods. Use the ``removeAction()`` method to
remove an action from a group, which is useful when you reuse a group created
somewhere else in your application::

    $publishActions->removeAction('publishDraft');

.. _actions-custom:

Adding Custom Actions
---------------------

.. tip::

    If you already have a controller action that implements the logic for your
    custom action, you can :ref:`integrate any Symfony controller into your EasyAdmin backend <actions-integrating-symfony>`
    without defining a new custom action.

In addition to the built-in actions provided by EasyAdmin, you can create your
own actions. First, define the basics of your action (name, label, icon) with
the ``Action::new()`` method::

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
        // actions created with Action::new() are rendered as `<a>` elements that
        // trigger GET requests; use this method to restore that rendering when
        // some other method changed it
        ->renderAsLink()

        // this method renders the action as a `<button>` element. By default it
        // uses `<button type="submit" ...>`; pass 'button' to render a
        // `<button type="button" ...>` element instead
        ->renderAsButton('button')
        // also available as EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonType
        ->renderAsButton(ButtonType::Button)

        // this method renders the action as a `<form method="post" ...>` element,
        // so the action sends a POST request to the action URL. Visually, it looks
        // exactly the same as a regular button
        ->renderAsForm()

        // a key-value array of attributes to add to the HTML element
        ->setHtmlAttributes(['data-foo' => 'bar', 'target' => '_blank'])

        // by default, actions are shown as `btn-secondary` elements; use the
        // following actions to change their style and priority accordingly
        ->asDefaultAction()
        ->asPrimaryAction()
        ->asSuccessAction()
        ->asInfoAction()
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

        // the parameters used when translating the action label
        ->setTranslationParameters(['%num_payments%' => 7])

        // render the action with your own Twig template instead of the built-in
        // one (the template receives 'action' and 'entity' variables)
        ->setTemplatePath('admin/action/my_action.html.twig')
    ;

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

The style and render methods accept an optional boolean argument so you can apply
them conditionally (e.g. ``asPrimaryAction(Crud::PAGE_DETAIL === $pageName)``,
which is how the built-in ``edit`` action is configured). Passing ``false``
leaves the current setting unchanged; it doesn't revert a style applied earlier.

Once you've configured the basics, use one of the following methods to define
which method runs when you click the action:

* ``linkToCrudAction()``: to execute some method of the current CRUD controller;
* ``linkToRoute()``: to execute some regular Symfony controller via its route;
* ``linkToUrl()``: to visit an external URL (useful when your action is not
  served by your application).

The following example shows all kinds of actions in practice::

    namespace App\Controller\Admin;

    use App\Entity\Order;
    use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
    use Symfony\Component\HttpFoundation\Response;

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

            // this action points to the invoice in the Stripe application
            $viewStripeInvoice = Action::new('viewStripeInvoice', 'Stripe Invoice', 'fab fa-stripe')
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

        #[AdminRoute('/{id}/invoice')]
        public function renderInvoice(Order $order): Response
        {
            // add your custom order logic here...
        }
    }

Apply the ``#[AdminRoute]`` attribute to turn CRUD controller methods into custom
CRUD actions with their own admin routes. In the above example, if the dashboard
uses ``admin`` as the main route name, EasyAdmin generates a route named
``admin_order_render_invoice`` with the path ``/admin/order/{id}/invoice``.
You can :ref:`customize the name, path, and methods <crud-routes>` of this route.

The placeholder that identifies the current entity in EasyAdmin routes is called
``{entityId}``, but you can also use ``{id}`` as an alias of it. Both work the
same way. Prefer ``{id}``: because the placeholder name matches the identifier
property of most entities, Symfony's ``EntityValueResolver`` can inject the
entity as a typed controller argument (the ``Order $order`` argument in the above
example) without extra configuration.

.. note::

    If your application doesn't enable the ``controller_resolver.auto_mapping``
    option of DoctrineBundle, add the ``#[MapEntity]`` attribute to the controller
    argument (``#[MapEntity] Order $order``) to inject the entity. When using the
    ``{entityId}`` placeholder instead of ``{id}``, you must always use the explicit
    `mapped route parameters`_ syntax: ``#[AdminRoute('/{entityId:order.id}/invoice')]``.

.. tip::

    CRUD controllers in EasyAdmin extend the `Symfony base controller class`_.
    When actions are defined as methods of CRUD controllers, they can use any
    of the shortcuts and utilities available in regular `Symfony controllers`_,
    such as ``$this->render()``, ``$this->redirect()``, and others.

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

.. _batch-actions:

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

The ``addBatchAction()`` method is a shortcut that adds the action to the
``index`` page and marks it as a batch action. If you prefer to add the action
with the regular ``add()`` method, call ``createAsBatchAction()`` on it first.

Batch actions support the same configuration options as the other actions and
they can link to a CRUD controller method, to a Symfony route or to some URL.
If there's at least one batch action, EasyAdmin adds a checkbox to each row of
the listing so users can select several entries.

When the user clicks on the batch action link/button, a form is submitted using
the ``POST`` method to the action or route configured in the action. The easiest
way to get the submitted data is to type-hint some argument of your batch action
method with the ``EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto`` class.
If you do that, EasyAdmin will inject a DTO with all the batch action data::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
    use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
    use Symfony\Component\HttpFoundation\Response;

    class UserCrudController extends AbstractCrudController
    {
        // ...

        #[AdminRoute('/batch-approve', 'batch_approve', options: ['methods' => ['POST']])]
        public function approveUsers(BatchActionDto $batchActionDto): Response
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

Instead of hardcoding the route name in ``redirectToRoute()``, you can build the
redirect URL with the :ref:`admin URL generator <generate-admin-urls>`, which
keeps the current dashboard, filters, and sorting.

The DTO also exposes ``getName()`` (the name of the batch action being executed,
useful when several batch actions share the same controller method) and
``getCsrfToken()`` (the CSRF token submitted with the form). Validate that token
before applying any change, using ``ea-batch-action-<action-name>-<entity-fqcn>``
as the token ID::

    if (!$this->isCsrfTokenValid('ea-batch-action-'.$batchActionDto->getName().'-'.$batchActionDto->getEntityFqcn(), $batchActionDto->getCsrfToken())) {
        throw $this->createAccessDeniedException();
    }

The entity FQCN of the DTO comes from the submitted form, so it's controlled by
the user. Compare it with your controller's ``static::getEntityFqcn()`` and
reject the request when they differ.

.. note::

    As an alternative, instead of injecting the ``BatchActionDto`` variable, you can
    also inject Symfony's ``Request`` object to get all the raw submitted batch data
    (e.g. ``$request->request->all('batchActionEntityIds')``).

.. _batch-action-confirmation:

Batch Action Confirmation
~~~~~~~~~~~~~~~~~~~~~~~~~

By default, batch actions display a confirmation modal before execution to prevent
accidental operations on multiple items. Configure this behavior with the
``askConfirmationOnBatchActions()`` :ref:`design option <crud-design-options>`, at
the dashboard level (for all CRUD controllers) or at the individual CRUD
controller level (to override the dashboard default).

To disable the confirmation modal entirely::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

    class ProductCrudController extends AbstractCrudController
    {
        // ...

        public function configureCrud(Crud $crud): Crud
        {
            return $crud
                // batch actions will be executed immediately without confirmation
                ->askConfirmationOnBatchActions(false)
            ;
        }
    }

You can also customize the confirmation message by passing a string instead of
a boolean. The message supports two placeholders: ``%action_name%`` (the name of
the batch action being executed) and ``%num_items%`` (the number of selected items)::

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->askConfirmationOnBatchActions(
                'Are you sure you want to apply "%action_name%" to %num_items% products?'
            )
        ;
    }

For translatable messages, you can pass a ``TranslatableInterface`` object::

    use function Symfony\Component\Translation\t;

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->askConfirmationOnBatchActions(t('batch.confirm.message'))
        ;
    }

.. _actions-integrating-symfony:

Integrating Symfony Actions
---------------------------

If the action logic is small and directly related to the backend, it is acceptable
to add it to the :doc:`CRUD controller </crud>` as a quick and simple way of
integrating it into your EasyAdmin backend. Sometimes the logic is too complex, or
it is also used elsewhere in your Symfony application. In those cases, you cannot
move it into the CRUD controller. This section explains how to integrate an
existing Symfony controller action in EasyAdmin so you can reuse the backend
layout, menu, and other features.

Imagine that your Symfony application has an action that calculates business
statistics about your clients (average order amount, yearly number of purchases, etc.).
All of this is calculated in a ``BusinessStatsCalculator`` service, so you can't
create a CRUD controller to display that information. Instead, create a standard
Symfony controller called ``BusinessStatsController``::

    // src/Controller/BusinessStatsController.php
    namespace App\Controller;

    use App\Entity\Customer;
    use App\Stats\BusinessStatsCalculator;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\Component\Security\Http\Attribute\IsGranted;

    #[IsGranted('ROLE_ADMIN')]
    class BusinessStatsController extends AbstractController
    {
        public function __construct(
            private BusinessStatsCalculator $businessStatsCalculator,
        ) {
        }

        #[Route('/admin/business-stats', name: 'business_stats_index')]
        public function index(): Response
        {
            return $this->render('admin/business_stats/index.html.twig', [
                'data' => $this->businessStatsCalculator->getStatsSummary(),
            ]);
        }

        #[Route('/admin/business-stats/{id}', name: 'business_stats_customer')]
        public function customer(Customer $customer): Response
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

    use App\Entity\Customer;
    use App\Stats\BusinessStatsCalculator;
    use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Security\Http\Attribute\IsGranted;

    #[IsGranted('ROLE_ADMIN')]
    #[AdminRoute('/business-stats', name: 'business_stats')]
    class BusinessStatsController extends AbstractController
    {
        public function __construct(
            private BusinessStatsCalculator $businessStatsCalculator,
        ) {
        }

        #[AdminRoute('/', name: 'index')]
        public function index(): Response
        {
            return $this->render('admin/business_stats/index.html.twig', [
                'data' => $this->businessStatsCalculator->getStatsSummary(),
            ]);
        }

        #[AdminRoute('/{id}', name: 'customer')]
        public function customer(Customer $customer): Response
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
    class BusinessStatsController extends AbstractController { /* ... */ }

    // Use the 'deniedDashboards' option to generate a route for ALL dashboards
    // except those listed explicitly:

    #[AdminRoute('...', name: '...', deniedDashboards: [GuestDashboardController::class, '...'])]
    class BusinessStatsController extends AbstractController { /* ... */ }

You can apply these options at the class level, at the action level, or both. An
action-level value overrides the class-level one:

* ``false`` (it's the default value): means "option not set" and tells EasyAdmin
  to inherit the value from the ``#[AdminRoute]`` attribute defined at the class
  level (if any);
* ``null`` in ``allowedDashboards``: explicitly allow all dashboards; it's used
  to override the same option in the ``#[AdminRoute]`` attribute at class level;
* ``[]`` in ``allowedDashboards``: explicitly allow no dashboards, so the route
  is not generated at all;
* ``null`` or ``[]`` in ``deniedDashboards``: no dashboard is excluded;
* ``[FooDashboard::class, BarDashboard::class, ...]``: allow/deny only these
  specific dashboards.

Now you can link to those admin routes from your :ref:`main menu <dashboard-menu>`
to render the actions fully integrated into each dashboard::

    // src/Controller/Admin/DashboardController.php
    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
    use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

    #[AdminDashboard(routePath: '/admin', routeName: 'admin')]
    class DashboardController extends AbstractDashboardController
    {
        // ...

        public function configureMenuItems(): iterable
        {
            // ...

            yield MenuItem::linkToRoute('Stats', 'fa fa-chart-bar', 'admin_business_stats_index');
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

The Twig template extends the :ref:`content page template <content-page-template>`
provided by EasyAdmin to reuse the backend design. The rest of the template
is standard Twig code, including the use of the Symfony's ``path()`` function to
generate the URL for the ``admin_business_stats_customer`` admin route.

.. _actions-extensions:

Actions Extensions
------------------

Applications using EasyAdmin define their actions in the ``configureActions()``
method of the :doc:`CRUD controllers </crud>`. You can enable, disable, or modify
:ref:`built-in actions <actions-built-in>`, and also create your own
:ref:`custom actions <actions-custom>`.

EasyAdmin provides an additional feature to add, remove, or change actions
(built-in or custom) dynamically at runtime: **action extensions**. They allow
your application (or third-party bundles installed in it) to modify the actions
defined for your controllers.

Action extensions are PHP classes that receive the full configuration of
actions in your backend so they can add, remove, or update any of them.

.. tip::

    Action extensions change how actions are *configured*. If instead you need to
    run some logic while an action is *executed*, listen to the
    :doc:`EasyAdmin events </events>`.

For example, imagine you need a **Duplicate** action across most of your
backends. Instead of defining it repeatedly, you can create a reusable package
(such as a `Symfony bundle`_) and add the following class::

    // <your-package>/src/DuplicateActionExtension.php
    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
    use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
    use EasyCorp\Bundle\EasyAdminBundle\Contracts\Action\ActionsExtensionInterface;

    final class DuplicateActionExtension implements ActionsExtensionInterface
    {
        // return true in this method to enable the extension for
        // the current backend request
        public function supports(AdminContext $context): bool
        {
            // enable the extension only on some pages
            return $context->getCrud()->getCurrentPage() === Crud::PAGE_DETAIL;

            // enable it on all except some entities
            $entityFqcn = $context->getCrud()->getEntityFqcn();
            return null !== $entityFqcn && !\in_array($entityFqcn, ['...'], true);

            // or use any other admin context data to make the decision
        }

        public function extend(Actions $actions, AdminContext $context): void
        {
            $duplicate = Action::new('duplicate', 'Duplicate', 'fa fa-clone')
                ->linkToCrudAction('duplicate')
                ->asSuccessAction();

            $actions->add(Crud::PAGE_DETAIL, $duplicate);

            // you can add single actions, groups of actions, etc.
            // you can also remove or update existing actions
        }
    }

Both methods receive the :ref:`admin context <admin-context>` of the current
request, so you can decide what to do based on the dashboard, the CRUD
controller, the current page, or the logged in user.

Extensions are registered automatically thanks to Symfony's autoconfiguration.
If autoconfiguration is disabled (which is common inside bundles), add the
``ea.actions_extension`` tag to the service:

.. code-block:: yaml

    # config/services.yaml
    services:
        App\Admin\DuplicateActionExtension:
            tags: ['ea.actions_extension']

Extensions run after the ``configureActions()`` method of the CRUD controller,
so they always have the last word and can override the configuration defined
there.

.. _`FontAwesome`: https://fontawesome.com/
.. _`mapped route parameters`: https://symfony.com/doc/current/doctrine.html#fetch-automatically
.. _`Symfony base controller class`: https://symfony.com/doc/current/controller.html#the-base-controller-class-services
.. _`Symfony controllers`: https://symfony.com/doc/current/controller.html
.. _`Symfony bundle`: https://symfony.com/doc/current/bundles.html
