How to Define Custom Actions
============================

One of the most powerful features of EasyAdmin is the possibility of defining
your own actions. In addition to the built-in ``edit``, ``list``, ``new``,
``search``, ``delete`` and ``show`` actions, you can create any number of custom
actions and display them in any view (``edit``, ``list``, ``new``, ``search``,
``show``).

There are two types of custom actions:

* **Method** based actions, which execute a method of the AdminController
  class. This is the default type in EasyAdmin;
* **Route** based actions, which execute the controller associated with the
  given route (and which can be defined anywhere in your application).

Method Based Actions
--------------------

This is the most simple type of action and it just executes a method of the
AdminController associated with the backend. Suppose that in your backend, one
of the most common tasks is to restock a product adding 100 units to its current
stock. Instead of editing a product, manually adding those 100 units and saving
the changes, you can display a new ``Restock`` action in the ``list`` view.

First, define a new ``restock`` action using the ``actions`` option:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        entities:
            Product:
                list:
                    actions: ['restock']
            # ...

If you reload the backend, you'll see a new ``Restock`` action displayed as a
link in the *Actions* column of the ``Product`` entity listing. However, if you
click on any of the ``Restock`` links, you'll see an error because the
``restockAction()`` method is not defined in the AdminController.

Therefore, the next step is to create a custom ``AdminController`` in your
Symfony application and to make it extend from the base AdminController
provided by EasyAdmin. This will take you less than a minute and it's explained
in detail in the :ref:`Customization Based on Controller Methods <overriding-the-default-controller>`
section. Make sure to read it before continuing.

Now you can define the ``restockAction()`` method in your own controller:

.. code-block:: php

    // src/Controller/AdminController.php
    namespace App\Controller;

    use EasyCorp\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
    // ...

    class AdminController extends BaseAdminController
    {
        // ...

        public function restockAction()
        {
            // controllers extending the base AdminController get access to the
            // following variables:
            //   $this->request, stores the current request
            //   $this->em, stores the Entity Manager for this Doctrine entity

            // change the properties of the given entity and save the changes
            $id = $this->request->query->get('id');
            $entity = $this->em->getRepository('Product::class')->find($id);
            $entity->setStock(100 + $entity->getStock());
            $this->em->flush();

            // redirect to the 'list' view of the given entity ...
            return $this->redirectToRoute('easyadmin', array(
                'action' => 'list',
                'entity' => $this->request->query->get('entity'),
            ));

            // ... or redirect to the 'edit' view of the given entity item
            return $this->redirectToRoute('easyadmin', array(
                'action' => 'edit',
                'id' => $id,
                'entity' => $this->request->query->get('entity'),
            ));
        }
    }

And that's it! Click again on the ``Restock`` action and everything will work as
expected. Custom actions can define any of the properties available for the
built-in actions:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        entities:
            Product:
                list:
                    actions:
                        - { name: 'restock', icon: 'plus-square' }
            # ...

The inheritance of actions is also applied to custom actions:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        list:
            # show the 'restock' action for all entities except those which remove it
            actions:
                - { name: 'restock', icon: 'plus-square' }

        entities:
            Product:
                # ...
            User:
                list:
                    actions: ['-restock']
                # ...

Route Based Actions
-------------------

This type of actions allows you to execute any controller defined in your
existing application, without the need to define a custom AdminController. In
this case, the ``name`` of the action is treated as the route name and you must
add a ``type`` option with the ``route`` value:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        entities:
            Product:
                list:
                    actions:
                        - { name: 'product_restock', type: 'route' }
            # ...

Route based actions are displayed as regular links or buttons, but they don't
link to the usual ``easyadmin`` route but to the route configured by the action.
In addition, the route is passed two parameters in the query string: ``entity``
(the name of the entity) and, when available, the ``id`` of the related entity.

Following the same example as above, the controller of this route based action
would look as follows:

.. code-block:: php

    // src/Controller/ProductController.php
    namespace App\Controller;

    // ...
    use Symfony\Component\HttpFoundation\Request;

    class ProductController extends Controller
    {
        // ...

        /**
         * @Route(path = "/admin/product/restock", name = "product_restock")
         * @Security("has_role('ROLE_ADMIN')")
         */
        public function restockAction(Request $request)
        {
            // change the properties of the given entity and save the changes
            $em = $this->getDoctrine()->getManager();
            $repository = $this->getDoctrine()->getRepository('Product::class');

            $id = $request->query->get('id');
            $entity = $repository->find($id);
            $entity->setStock(100 + $entity->getStock());
            $em->flush();

            // redirect to the 'list' view of the given entity ...
            return $this->redirectToRoute('easyadmin', array(
                'action' => 'list',
                'entity' => $this->request->query->get('entity'),
            ));

            // ... or redirect to the 'edit' view of the given entity item
            return $this->redirectToRoute('easyadmin', array(
                'action' => 'edit',
                'id' => $id,
                'entity' => $this->request->query->get('entity'),
            ));
        }
    }

Similarly to method based actions, you can configure any option for these
actions (icons, labels, etc.) and you can also leverage the action inheritance
mechanism.
