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

    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
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
  * Other available actions: ``Action::DELETE``, ``Action::INDEX``

* Page ``Crud::PAGE_NEW`` (``'new'``):

  * Added by default: ``Action::SAVE_AND_RETURN``, ``Action::SAVE_AND_ADD_ANOTHER``
  * Other available actions: ``Action::SAVE_AND_CONTINUE``, ``Action::INDEX``

Adding Actions
--------------

Use the ``add()`` method to add any built-in actions and your own custom actions
(which are explained later in this article)::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
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
                    return $entity->isPublished();
                });

                // in PHP 7.4 and newer you can use arrow functions
                // ->displayIf(fn ($entity) => $entity->isPublished())

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
            ->setPermission(Action::NEW, 'ROLE_SUPER_ADMIN')
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
            ->reorder(Crud::PAGE_INDEX, [Action::DELETE, Action::DETAIL, Action::EDIT])
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
    // to display an icon for the action; otherwise end users won't see it)
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
    use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

    class ProductCrudController extends AbstractCrudController
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
                ->linkToRoute('invoice_send', function (Invoice $entity) {
                    return [
                        'uuid' => $entity->getId(),
                        'method' => $entity->sendMethod(),
                    ];
                });

            // this action points to the invoice on Stripe application
            $viewStripeInvoice = Action::new('viewInvoice', 'Invoice', 'fa fa-file-invoice')
                ->linkToUrl(function (Invoice $entity) {
                    return 'https://www.stripe.com/invoice/'.$entity->getStripeReference();
                });

            return $actions
                // ...
                ->add(Crud::PAGE_DETAIL, $viewInvoice)
                ->add(Crud::PAGE_DETAIL, $sendInvoice)
                ->add(Crud::PAGE_DETAIL, $viewStripeInvoice)
            ;
        }
    }

Batch Actions
-------------

.. note::

    Batch actions are not ready yet, but we're working on adding support for them.

.. Batch actions are a special kind of action which is applied to multiple items at
.. the same time. They are only available in the ``index`` page. The only built-in
.. batch action is ``delete``. You can remove this action as follows::
..
..     ->removeBatchAction('delete');
..
.. You can change some of its options with the following method::
..
..     $batchDelete = Action::new('Delete', 'fa-trash')->cssClass('...')->method('batchDelete');
..     // ...
..     ->setBatchAction('delete', $batchDelete);
..
.. Custom Batch Actions
.. ~~~~~~~~~~~~~~~~~~~~
..
.. Imagine that you manage users with a ``User`` entity and a common task is to
.. approve their sign ups. Instead of creating a normal ``approve`` action as
.. explained in the previous sections, create a batch action to be more productive
.. and approve multiple users at once.
..
.. First, create a method in your resource admin to handle this batch action (the
.. method will receive an array with the IDs of the selected entities)::
..
..     namespace App\Controller\Admin;
..
..     use EasyCorp\Bundle\EasyAdminBundle\Config\ResourceConfig;
..     use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractResourceAdminController;
..
..     class UserAdminController extends AbstractResourceAdminController
..     {
..         // ...
..
..         public function approveUsers(array $ids)
..         {
..             $entityClass = $this->getConfig()->getEntityClass();
..             $em = $this->getDoctrine()->getManagerForClass($entityClass);
..
..             foreach ($ids as $id) {
..                 $user = $em->find($id);
..                 $user->approve();
..             }
..
..             $this->em->flush();
..
..             // don't return anything or redirect to any URL because it will be ignored
..             // when a batch action finishes, user is redirected to the original page
..         }
..     }
..
.. Now use the ``addBatchAction()`` method to add it to your resource admin::
..
..     namespace App\Controller\Admin;
..
..     use EasyCorp\Bundle\EasyAdminBundle\Config\ResourceConfig;
..     use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractResourceAdminController;
..
..     class UserAdminController extends AbstractResourceAdminController
..     {
..         // ...
..
..         public function getIndexPageConfig(): IndexPageConfig
..         {
..             return IndexPageConfig::new()
..                 // ...
..                 ->addBatchAction('approve', Action::new('Approve', 'fa-user-check')->method('approveUsers'));
..         }
..     }
