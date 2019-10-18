Actions
=======

**Actions** are each of the tasks you can perform on your entities. For example,
the ``index`` action displays a paginated list of entities and the ``detail``
action displays all the data of a given entity.

**Pages** are the result of performing an action. For example, when you run the
``index`` action on some entity, your browser displays the ``index`` page of
that entity. Some actions don't produce any result and they redirect to other
pages. For example, when you execute the ``delete`` action on some entities,
after removing them you are redirected to the ``index`` page.

Actions are configured on the :doc:`resource admin </resources>` controllers.
These are the actions included by default in each page:

==========  ===================================================
Page        Default Actions
==========  ===================================================
``detail``  ``delete``, ``form``, ``index``
``form``    ``index``, ``delete`` (only when editing)
``index``   ``delete``, ``detail``, ``form``
==========  ===================================================

Removing Actions
----------------

Removing actions is a quick way to prevent users to execute some tasks on some
entity. When an action is removed, the backend no longer displays it in any
page. Moreover, if some user tries to *hack* the URL to access to a removed
action, they'll see a *Forbidden Action* error page.

You can remove actions with the ``removeActions()`` method, which is available
both in :doc:`dashboards </dashboards>` and :doc:`resource admins </resources>`
(the last one overrides any global option). For example, this dashboard prevents
deleting any entity in the backend::

    use EasyCorp\Bundle\EasyAdminBundle\Config\DashboardConfig;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboard;

    class DashboardController extends AbstractDashboardController
    {
        // ...

        public static function getConfig(): DashboardConfig
        {
            return DashboardConfig::new()
                // ...
                ->removeActions('delete');
        }
    }

However, the following resource admin overrides that option, so you can delete
entities of this type but you can't edit or create them. You can't either run the
``invoice`` custom action on this entity (:ref:`custom actions <actions-custom>`
are explained later)::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Config\ResourceConfig;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractResourceAdminController;

    class ProductAdminController extends AbstractResourceAdminController
    {
        // ...

        public function getResourceConfig(): ResourceConfig
        {
            return ResourceConfig::new()
                // ...
                ->removeActions('form', 'invoice');
        }
    }

Modifying the Default Actions
-----------------------------

Use the ``setAction()`` method (either in a dashboard controller or in a
resource admin controller) to replace an existing action with a new one that
modifies some of its features.

For example, to change the label and icon of the default ``detail`` action::

    public static function getConfig(): DashboardConfig
    {
        return DashboardConfig::new()
            // ...
            // the first argument is a string that identifies the action uniquely (choose it freely)
            // the second argument is an Action::new($actionLabel, $iconName) object
            ->setAction('detail', Action::new('See details', 'fa-file-alt')->method('detail'));
    }

Actions can define the following options::

    // constructor arguments:
    //   $label: the visible text displayed in the action link/button. Set it to
    //           NULL to not display any label (in that case, display the icon at least)
    //   $icon: the name of the FontAwesome icon. Set it to NULL to not display it
    //          (in that case, display the label at least)
    $action = Action::new('See details', 'fa-file-alt')
        // the value of the 'title' HTML attribute of the action's link/button
        ->title('Click here to see the details of this item')

        // the CSS class or classes applied to the action's link/button
        ->cssClass('...')

        // the value of the 'target' HTML attribute of the action's link/button
        // (e.g. use '_blank' to open the action in a new browser tab)
        ->target('...')

        // the path of the Twig template used to render the action's link/button
        ->template('admin/actions/my_custom_action.html.twig');

Displaying Actions Conditionally
--------------------------------

Some actions must be enabled for all entities but displayed only when some
conditions met. For example, a "View Invoice" action may be displayed only when
the order status is "paid". Use the ``displayIf()`` method to configure when the
action should be visible to users::

    class ProductAdminController extends AbstractResourceAdminController
    {
        // ...

        public function getResourceConfig(): ResourceConfig
        {
            $detailAction = Action::new('Show', 'fa-file-alt')->displayIf(static function ($entity) {
                return $entity->isPublished();
            });

            return ResourceConfig::new()
                // ...
                ->setAction('detail', $detailAction);
        }
    }

Dropdown Actions
----------------

If you display lots of fields on each row of the ``index`` page, there won't be
enough room for the item actions. In those cases, you can display the actions in
a dropdown menu instead of the expanded design used by default.

To do so, set the ``collapseActions()`` option to ``true`` in the
``IndexPageConfig`` of the related resource admin::

    class ProductAdminController extends AbstractResourceAdminController
    {
        // ...

        public function getResourceConfig(): ResourceConfig
        {
            return ResourceConfig::new()
                // ...
                ->collapseActions(true);
        }
    }

.. _actions-custom:

Adding Custom Actions
---------------------

Pages can also include links/buttons to your own custom actions. These actions
execute either a method of the current resource admin controller or link to an
URL (usually from the same Symfony application). Use the ``addAction()`` method
to add them::

    class ProductAdminController extends AbstractResourceAdminController
    {
        // ...

        public function getIndexPageConfig(): IndexPageConfig
        {
            // this action executes the 'invoice()' method of the current controller
            $viewInvoice = Action::new('See invoice', 'fa-file-invoice')->method('invoice');

            // when linking to an external URL, pass any needed argument because the method
            // associated with the URL won't have access to the ApplicationContext variable
            $sendInvoice = Action::new('Send invoice', 'fa-envelope')->urlFor(static function ($entity) {
                return $this->generateUrl('invoice_send', ['id' => $entity->getId()]);
            });

            return IndexPageConfig::new()
                // ...
                ->addAction('viewInvoice', $viewInvoice)
                ->addAction('sendIinvoice', $sendInvoice);
        }
    }

.. TODO: show a full example of creating a custom action

Batch Actions
-------------

Batch actions are a special kind of action which is applied to multiple items at
the same time. They are only available in the ``index`` page. The only built-in
batch action is ``delete``. You can remove this action as follows::

    ->removeBatchAction('delete');

You can change some of its options with the following method::

    $batchDelete = Action::new('Delete', 'fa-trash')->cssClass('...')->method('batchDelete');
    // ...
    ->setBatchAction('delete', $batchDelete);

Custom Batch Actions
~~~~~~~~~~~~~~~~~~~~

Imagine that you manage users with a ``User`` entity and a common task is to
approve their sign ups. Instead of creating a normal ``approve`` action as
explained in the previous sections, create a batch action to be more productive
and approve multiple users at once.

First, create a method in your resource admin to handle this batch action (the
method will receive an array with the IDs of the selected entities)::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Config\ResourceConfig;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractResourceAdminController;

    class UserAdminController extends AbstractResourceAdminController
    {
        // ...

        public function approveUsers(array $ids)
        {
            $entityClass = $this->getConfig()->getEntityClass();
            $em = $this->getDoctrine()->getManagerForClass($entityClass);

            foreach ($ids as $id) {
                $user = $em->find($id);
                $user->approve();
            }

            $this->em->flush();

            // don't return anything or redirect to any URL because it will be ignored
            // when a batch action finishes, user is redirected to the original page
        }
    }

Now use the ``addBatchAction()`` method to add it to your resource admin::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Config\ResourceConfig;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractResourceAdminController;

    class UserAdminController extends AbstractResourceAdminController
    {
        // ...

        public function getIndexPageConfig(): IndexPageConfig
        {
            return IndexPageConfig::new()
                // ...
                ->addBatchAction('approve', Action::new('Approve', 'fa-user-check')->method('approveUsers'));
        }
    }
