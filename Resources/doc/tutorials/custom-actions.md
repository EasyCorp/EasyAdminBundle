How to Define Custom Actions
============================

One of the most powerful features of EasyAdmin is the possibility of defining
your own actions. In addition to the built-in `edit`, `list`, `new`, `search`,
`delete` and `show` actions, you can create any number of custom actions and
display them in any view (`edit`, `list`, `new`, `search`, `show`).

There are two types of custom actions:

 * **Method** based actions, which execute a method of the AdminController class;
 * **Route** based actions, which execute the controller associated with the
   given route (and which can be defined anywhere in your application).

Method Based Actions
--------------------

This is the most common type of action and it just executes a method of the
AdminController used by the backend. Suppose that in your backend, one of the
usual tasks is to restock a product adding 100 units to its current stock.
Instead of editing a product, manually adding those 100 units and saving
the changes, you can display a new `Restock` action in the `list` view.

First, define a new `restock` action using the `actions` option:

```yaml
easy_admin:
    entities:
        Product:
            list:
                actions: ['restock']
        # ...
```

If you reload the backend, you'll see a new `Restock` action displayed as a
link in the *Actions* column of the `Product` entity listings. However, if you
click on any of those links, you'll see an error because the `restockAction()`
method is not defined in the AdminController.

Therefore, the next step is to create a custom `AdminController` in your
Symfony application and to make it extend from the base AdminController
provided by EasyAdmin. This process will take you less than a minute and it's
explained in detail in the *Customization Based on Controller Methods* section
in the [Customizing AdminController tutorial] [customizing-admin-controller].

Now you can define the `restockAction()` method in your own controller:

```php
// src/AppBundle/Controller/AdminController.php
namespace AppBundle\Controller;

// ...

class AdminController extends BaseAdminController
{
    // ...

    public function restockAction()
    {
        // controllers extending the base AdminController can access to the
        // following variables:
        //   $this->request, stores the current request
        //   $this->em, stores the Entity Manager for this Doctrine entity

        // change the properties of the given entity and save the changes
        $id = $this->request->query->get('id');
        $entity = $this->em->getRepository('AppBundle:Product')->find($id);
        $entity->setStock(100 + $entity->getStock());
        $this->em->flush();

        // redirect to the 'list' view of the given entity
        return $this->redirectToRoute('easyadmin', array(
            'view' => 'list',
            'entity' => $this->request->query->get('entity'),
        ));

        // redirect to the 'edit' view of the given entity item
        return $this->redirectToRoute('easyadmin', array(
            'view' => 'edit',
            'id' => $id,
            'entity' => $this->request->query->get('entity'),
        ));
    }
}
```

And that's it! Click again on the `Restock` action and everything will work as
expected. Custom actions can define any of the properties available for the
built-in actions:

```yaml
easy_admin:
    entities:
        Product:
            list:
                actions:
                    - { name: 'restock', type: 'method', icon: 'plus-square' }
        # ...
```

The inheritance of actions is also applied to custom actions:

```yaml
easy_admin:
    list:
        # show the 'restock' action for all entities except those who remove it
        actions:
            - { name: 'restock', type: 'method', icon: 'plus-square' }

    entities:
        Product:
            # ...
        User:
            list:
                actions: ['-restock']
            # ...
```

Route Based Actions
-------------------

This type of actions allow you to execute any controller of your existing
application, without the need to define a custom AdminController. In this case,
the `name` of the action is treated as the route name and its type must be `route`:

```yaml
easy_admin:
    entities:
        Product:
            list:
                actions:
                    - { name: 'product_restock', type: 'route' }
        # ...
```

Route based actions are displayed as regular links or buttons, but they don't
point to the usual `easyadmin` route but to the route configured by the action.
In addition, the route is passed two parameters in the query string: `entity`
(the name of the Doctrine entity) and, when available, the `id` of the related
entity.

Following the same example as above, the controller of this route based action
would look as follows:

```php
// src/AppBundle/Controller/ProductController.php
namespace AppBundle\Controller;

// ...

class ProductController extends Controller
{
    // ...

    /**
     * @Route(path = "/admin/product/restock", name = "product_restock")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function restockAction()
    {
        // change the properties of the given entity and save the changes
        $em = $this->getDoctrine()->getManager();
        $repository = $this->getDoctrine()->getRepository('AppBundle:Product');

        $id = $this->request->query->get('id');
        $entity = $repository->find($id);
        $entity->setStock(100 + $entity->getStock());
        $em->flush();

        // redirect to the 'list' view of the given entity
        return $this->redirectToRoute('easyadmin', array(
            'view' => 'list',
            'entity' => $this->request->query->get('entity'),
        ));

        // redirect to the 'edit' view of the given entity item
        return $this->redirectToRoute('easyadmin', array(
            'view' => 'edit',
            'id' => $id,
            'entity' => $this->request->query->get('entity'),
        ));
    }
}
```

Similarly to method based actions, you can configure any option for the route
based actions and you can also leverage the action inheritance mechanism.

[customizing-admin-controller]: ./customizing-admin-controller.md
