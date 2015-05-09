Customizing Admin Controller
============================

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
