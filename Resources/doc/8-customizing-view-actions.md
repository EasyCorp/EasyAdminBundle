Chapter 8. Customizing the View Actions
=======================================

Configure the Actions Displayed in Each View
--------------------------------------------

The actions displayed in each view can be configured globally for the entire
backend or on a per entity basis.

### Adding or Removing Actions Globally

Define the actions to display using the `actions` option of each view:

```yaml
easy_admin:
    edit:
        actions: ['show']
    # ...
```

The value of the `actions` option is added to the default actions. This means
that in the example above, the `edit` view of all the backend entities will
include the `list`, `delete` and `show` actions (the first two are the default
actions and the last one is explicitly configured).

Instead of adding new actions, sometimes you want to remove some actions from
the views. To do so, use the same `actions` option but prefix each action to
remove with a dash (`-`):

```yaml
easy_admin:
    edit:
        actions: ['show', '-delete']
    # ...
```

In the example above, the `edit` view will now include the `list` and `show`
because of the following:

  * Default actions for `edit` view: `list` and `delete`
  * Actions added by configuration: `show`
  * Actions removed by configuration: `delete`
  * Resulting actions: `list` and `show`

Using this action removal convention, you can easily turn your backend into a
read-only backend, where users can access to information but they cannot
modify or remove anything:

```yaml
easy_admin:
    list:
        actions: ['-edit']
    show:
        actions: ['-delete', '-edit']
```

### Adding or Removing Actions Per Entity

In addition to adding or removing actions for the entire backend, you can also
define the `actions` option for the view of each entity:

```yaml
easy_admin:
    list:
        actions: ['-edit']
    entities:
        Customer:
            list:
                actions: ['-show']
            # ...
        Invoice:
            list:
                actions: ['edit']
            # ...
```

Entities inherit all the action configuration from the global backend. This
means that each entity starts with the same actions as the backend and then you
can add or remove any action.

In the example above, the actions of the `list` view for the `Customer` entity
will be the following:

  * Default actions for `list` view: `edit`, `list`, `new`, `search`, `show`
  * Actions added by the backend: none
  * Actions removed by the backend: `edit`
  * Resulting actions for the backend (and inherited by the entity): `list`, `new`, `search`, `show`
  * Actions added by the entity: none
  * Actions removed by the entity: `show`
  * Resulting actions for this entity: `list`, `new`, `search`

Customizing the Actions Displayed in Each View
----------------------------------------------

In addition to adding or removing actions, you can also configure their
properties. To do so, you must use the expanded configuration format for the
customized action:

```yaml
easy_admin:
    list:
        actions: [ { name: 'edit', label: 'Modify' }]
```

When customizing lots of actions, consider using the alternative YAML syntax to
improve the readability of your backend configuration:

```yaml
easy_admin:
    list:
        actions:
            - { name: 'edit', label: 'Modify' }
            - { name: 'show', label: 'View details' }
```

The following properties can be configured for each action:

  * `name`, this is the only mandatory option. Later in this chapter you'll
    fully understand the importance of this option when defining your own
    custom actions.
  * `type`, (default value: `method`), this option defines the type of action,
    which can be `method` or `route`. Later in this chapter you'll fully
    understand the importance of this option when defining your own custom
    actions.
  * `label`, is the text displayed in the button or link associated with the
    action. If not defined, the action label is the *humanized* version of its
    `name` option.
  * `class`, is the CSS class or classes applied to the link or button used to
    render the action.
  * `icon`, is the name of the FontAwesome icon displayed next to the link or
    inside the button used to render the action. You don't have to include the
    `fa-` prefix of the icon name (e.g. to display the icon of a user, don't
    use the `fa fa-user` or `fa-user` names; just use `icon`).

Defining Custom Actions in the Backend
--------------------------------------

So far you've learned how to add/remove built-in actions and how to configure
them. However, one of the most powerful features of EasyAdmin is the
possibility of defining your own actions and displaying them in any of the
views.

### Method Based Actions

This is the most common type of action and it just executes a method of the
AdminController used by the backend. Suppose that in your backend, one of the
most common tasks is to restock a product adding 100 units to its current
stock. Instead of editing a product, manually add those 100 units and saving
the changes, you can define a new `Restock` action in the product listing.

First, define a new `restock` action using the `actions` option of the `list`
view:

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

Therefore, the next step is to create your own AdminController in your Symfony
application and to make it extend from the base AdminController provided by
EasyAdmin. This process will take you less than a minute and it's explained in
detail in the *Customize the Actions Used to Create and Edit Entities* section
in the [Chapter 6](6-customizing-new-edit-views.md).

Now you can define the `restockAction()` method in your own controller:

```php
// src/AppBundle/Controller/AdminController.php
namespace AppBundle\Controller;

// ...

class AdminController extends EasyAdminController
{
    // ...

    public function restockAction()
    {
        // controllers extending the base AdminController can access to the
        // following variables:
        // $this->request, stores the current request
        // $this->em, stores the Doctrine Entity Manager associated with this entity

        // change the properties of the given entity and save the changes
        $entity = $this->em->getRepository('AppBundle:Product')->find($this->request->query->get('id'));
        $entity->setStock(100 + $entity->getStock());
        $this->em->flush();

        // redirect to the 'list' view of the given entity
        return $this->redirectToRoute('admin', array(
            'view' => 'list',
            'entity' => $this->request->query->get('entity'),
        ));

        // redirect to the 'edit' view of the given entity item
        return $this->redirectToRoute('admin', array(
            'view' => 'edit',
            'entity' => $this->request->query->get('entity'),
            'id' => $this->request->query->get('id'),
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

### Route Based Actions

This type of actions allow you to execute any controller of your existing
application, without the need to define a custom AdminController extending from
the default one. In this case, the `name` of the action is considered the name
of the route to link to. These actions must also define the `type` option as
`route` because actions by default are considered of `method` type:

```yaml
easy_admin:
    entities:
        Product:
            list:
                actions: [ { name: 'product_restock', type: 'route' } ]
        # ...
```

Route based actions are displayed as regular links or buttons, but they don't
point to the usual `admin` route but to the route configured by the action.
In addition, the route is passed two parameters in the query string: `entity`
(with the name of the Doctrine entity) and, when possible, the `id` of the
current entity.

Following the same example as above, the controller of this new route based
action would look as follows:

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

        $entity = $repository->find($this->request->query->get('id'));
        $entity->setStock(100 + $entity->getStock());
        $em->flush();

        // redirect to the 'list' view of the given entity
        return $this->redirectToRoute('admin', array(
            'view' => 'list',
            'entity' => $this->request->query->get('entity'),
        ));

        // redirect to the 'edit' view of the given entity item
        return $this->redirectToRoute('admin', array(
            'view' => 'edit',
            'entity' => $this->request->query->get('entity'),
            'id' => $this->request->query->get('id'),
        ));
    }
}
```

Similarly to method based actions, you can configure any option of the route
based actions and you can also leverage the action inheritance mechanism.




Customize the Actions Used to Create and Edit Entities
------------------------------------------------------

By default, new and edited entities are persisted without any further
modification. In case you want to manipulate the entity before persisting it,
you can override the methods used by EasyAdmin.

Similarly to customizing templates, you need to use the Symfony bundle
[inheritance mechanism](http://symfony.com/doc/current/cookbook/bundles/inheritance.html#overriding-controllers)
to override the controller used to generate the backend. Among many other
methods, this controller contains two methods which are called just before the
entity is persisted:

```php
protected function prepareEditEntityForPersist($entity)
{
    return $entity;
}

protected function prepareNewEntityForPersist($entity)
{
    return $entity;
}
```

Suppose you want to automatically set the slug of some entity called `Article`
whenever the entity is persisted. First, create a new controller inside any of
your own bundles. Make this controller extend the `AdminController` provided by
EasyAdmin and include, at least, the following contents:

```php
// src/AppBundle/Controller/AdminController.php
namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as EasyAdminController;

class AdminController extends EasyAdminController
{
    /**
     * @Route("/admin/", name="admin")
     */
    public function indexAction(Request $request)
    {
        return parent::indexAction($request);
    }
}
```

Now you can add in this new controller any of the original controller's
methods to override them. Let's add `prepareEditEntityForPersist()` and
`prepareNewEntityForPersist()` to set the `slug` of the `Article` entity:

```php
// src/AppBundle/Controller/AdminController.php
namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as EasyAdminController;
use AppBundle\Entity\Article;

class AdminController extends EasyAdminController
{
    /**
     * @Route("/admin/", name="admin")
     */
    public function indexAction(Request $request)
    {
        return parent::indexAction($request);
    }

    protected function prepareEditEntityForPersist($entity)
    {
        if ($entity instanceof Article) {
            return $this->updateSlug($entity);
        }
    }

    protected function prepareNewEntityForPersist($entity)
    {
        if ($entity instanceof Article) {
            return $this->updateSlug($entity);
        }
    }

    private function updateSlug($entity)
    {
        $slug = $this->get('app.slugger')->slugify($entity->getTitle());
        $entity->setSlug($slug);

        return $entity;
    }
}
```

The example above is trivial, but your custom admin controller can be as
complex as needed. In fact, you can override any of the original controller's
methods to customize the backend as much as you need.

Advanced Customization of the Fields Displayed in Forms
-------------------------------------------------------

The previous sections showed how to tweak the fields displayed in the `edit`
and `new` forms using some simple options. When the field customization is
more advanced, you should override the `configureEditForm()` method in your own
admin controller.

In this example, the form of the `Event` entity is tweaked to change the
regular `city` field by a `choice` form field with custom and limited choices:

```php
// src/AppBundle/Controller/AdminController.php
namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as EasyAdminController;
use AppBundle\Entity\Event;

class AdminController extends EasyAdminController
{
    /**
     * @Route("/admin/", name="admin")
     */
    public function indexAction(Request $request)
    {
        return parent::indexAction($request);
    }

    public function createEditForm($entity, array $entityProperties)
    {
        $editForm = parent::createEditForm($entity, $entityProperties);

        if ($entity instanceof Event) {
            // the trick is to remove the default field and then
            // add the customized field
            $editForm->remove('city');
            $editForm->add('city', 'choice', array('choices' => array(
                'London', 'New York', 'Paris', 'Tokyo'
            )));
        }

        return $editForm;
    }
}
```
