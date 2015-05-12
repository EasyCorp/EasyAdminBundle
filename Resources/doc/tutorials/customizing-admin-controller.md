Customizing AdminController
===========================

EasyAdmin includes one controller called `AdminController` which is responsible
for most of the backend operations (rendering views, looking for entities,
creating and persisting entities, etc.) This article explains how to customize
the behavior of the backend by overriding some parts of this `AdminController`.

Depending on your needs you can choose any of these two customization options:

  * Customization based on **controller methods**. This is the most common way
    to customize the backend because it's very easy to set up and offers great
    flexibility.
  * Customization based on **Symfony events**. This is the most advanced way to
    customize the backend. It allows your application to hook on any event to
    modify the behavior of the `AdminController` without having to override it.

In case your backend is very complex, you can even combine both methods.

Customization Based on Controller Methods
-----------------------------------------

This technique requires you to create a new controller in your Symfony
application and make it extend from the default `AdminController`. Then you
just add one or more methods in your controller to override the default ones.

The first step is to **create a new controller** in your Symfony application.
Its class name or namespace doesn't matter as long as it extends the default
`AdminController`:

```php
// src/AppBundle/Controller/AdminController.php
namespace AppBundle\Controller;

use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;

class AdminController extends BaseAdminController
{
    // ...
}
```

Extending from the default controller is not enough to override the entire
backend. That's why you must define an `indexAction()` method with the
following content:

```php
// src/AppBundle/Controller/AdminController.php
namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;

class AdminController extends BaseAdminController
{
    /**
     * @Route("/admin/", name="admin")
     */
    public function indexAction(Request $request)
    {
        return parent::indexAction($request);
    }

    // ...
}
```

This `indexAction()` method overrides the default `admin` route, which is
essential to make your controller override the default `AdminController`
behavior.

Keep reading the practical examples of the next sections to learn which
methods you can override in the backend.

### Customize the Instantiation of a Single Entity

By default, backend entities are instantiated just by executing
`new FullClassNamespace()`. The lack of constructor arguments can cause errors
for some of your entities.

Suppose that your application defines a `User` entity which requires some
constructor arguments. In order to tweak the instantiation of this entity,
define a new `AdminController` extending from the default one and add this
method:

```php
public function createNewUserEntity()
{
    return new User(array('ROLE_USER'), true);
}
```

The name of the method is constructed as `createNew<EntityName>Entity()` so
it's recommended to use CamelCase notation to set the entity names.

### Customize the Instantiation of All Entities

Similarly, you can define the `createNewEntity()` method in your
`AdminController` to override the instantiation of all the entities:

```php
public function createNewEntity()
{
    // ...
}
```

Inside this method, you can access the entire backend configuration via the
`$this->config` variable and the configuration of the current entity via the
`$this->entity` variable:

```php
public function createNewEntity()
{
    if ('User' === $this->entity['name']) {
        return new User(...);
    }

    // ...
}
```

### Customize the Form Used to Create New Entities

By default, the form used to create new entities is generated automatically
using the `fields` option of the `new` view configuration and the related
Doctrine entity properties. If you need to customize this form heavily, use
the `createNewForm()` method.

Suppose that your application defines a `Product` entity which defines a very
complex form. In order to tweak the form used to create new instances of this
entity, define a new `AdminController` extending from the default one and add
this method:

```php
public function createProductNewForm()
{
    // ...
}
```

This method allows you to create the form for this specific entity using the
form builder, a custom form type, a Symfony service, etc. The name of the
method is constructed as `create<EntityName>NewForm()` so it's recommended to
use CamelCase notation to set the entity names.

If you want to customize the form used to create new instances of all entities,
use instead the `createNewForm()` method:

```php
public function createNewForm()
{
    if ('Product' === $this->entity['name']) {
        // ...
    }

    // ...
}
```

### Customize the Form Used to Edit Existing Entities

The form used to edit any of the existing entities can be customized in a
similar fashion. In this case, the method name is constructed as
`create<EntityName>EditForm()`. A generic method called `createEditForm()` is
also available to customize the edit form for all the entities managed by the
backend.

### Tweak a Specific Entity Before Persisting/Updating/Removing It

The default `AdminController` defines three methods related to the usual
Doctrine operations:

  * `prePersist*Entity()` is called just before persisting the entity for the
    first time (i.e. just before creating the entity).
  * `preUpdate*Entity()` is called just before persisting the changes of a
    modified entity.
  * `preRemove*Entity()` is called just before an entity is deleted from the
    database.

Suppose your backend defines a `BlogPost` entity and you want to set the `slug`
of each blog post based on its `title` before persisting it in the database.
First, create a new `AdminController` extending from the default one and then,
define this method:

```php
public function prePersistBlogPostEntity($entity)
{
    $slug = $this->get('slugger')->slugify($entity->getTitle());
    $entity->setSlug($slug);
}
```

There is no need to return the entity because the `$entity` variable is passed
by reference and any modification made on it will propagate back to the
original `AdminController`.

Similarly, you may want to update the `slug` when editing a blog post, but only
if the `slug` field is empty:

```php
public function preUpdateBlogPostEntity($entity)
{
    if (empty($entity->getSlug())) {
        $slug = $this->get('slugger')->slugify($entity->getTitle());
        $entity->setSlug($slug);
    }
}
```

Lastly, you may want to send an email to the editor when a blog a post is
removed:

```php
public function preRemoveBlogPostEntity($entity)
{
    $subject = sprintf('Blog Post Removed: %s', $entity->getTitle());
    $this->get('mailer')->send(...);
}
```

The name of these methods is constructed as `pre*<EntityName>Entity()` so
it's recommended to use CamelCase notation to set the entity names.

### Tweak All Entities Before Persisting/Updating/Removing Them

`AdminController` also defines three similar but generic methods to allow you
tweaking all the entities of the backend in a single method:

```php
public function prePersistEntity($entity) { ... }
public function preUpdateEntity($entity)  { ... }
public function preRemoveEntity($entity)  { ... }
```

### Tweak the Default Actions for a Specific Entity

`AdminController` defines one method for each default action (`listAction()`,
`editAction()`, etc.) Before executing these generic methods, it checks whether
specific methods are defined for the given entity.

Suppose the listing of the `Product` entity in your backend is very complex and
requires executing some custom logic. The easiest way to achieve that is to
create a new `AdminController` extending from the default one and then, define
this method:

```php
public function listProductAction()
{
    // custom logic

    $this->render($this->entity['templates']['list'], array(...));
}
```

You can define any of the following methods for any or all of your entities:

```php
public function list<EntityName>Action()   { ... }
public function new<EntityName>Action()    { ... }
public function edit<EntityName>Action()   { ... }
public function show<EntityName>Action()   { ... }
public function delete<EntityName>Action() { ... }
```

Given the syntax of method names, it's recommended to use CamelCase notation
to set the entity names.

### Tweak the Default Actions for All Entities

This use case is only useful for extremely complex backends which need to
override the entire behavior of one or all the default actions. To do so,
create a new `AdminController` extending from the default one and then, define
any of these methods:

```php
public function listAction()   { ... }
public function newAction()    { ... }
public function editAction()   { ... }
public function showAction()   { ... }
public function deleteAction() { ... }
```

Customization Based on Symfony Events
-------------------------------------

`AdminController` dispatches lots of custom events during the execution of the
backend. Use Symfony's [event listeners] [event-listener] or event subscribers
to hook into the default controller and tweak its behavior.

The custom events are defined in the `EasyAdmin\Event\EasyAdminEvents` class
and are divided into three groups.

### Initialization related events

The `initialize()` method is executed before any other action method. It checks
for some common errors and initializes the variables used by the rest of the
methods (`$entity`, `$request`, `$config`, etc.)

The two events related to this `initialize()` method are:

  * `PRE_INITIALIZE`, executed just at the beginning of the method, before any
    variable has been initialized and any error checked.
  * `POST_INITIALIZE`, executed at the very end of the method, just before
    executing the method associated with the current action.

### Views related events

Each view defines two events which are dispatched respectively at the very
beginning of each method and at the very end of it, just before executing the
`$this->render()` instruction:

  * `PRE_DELETE`, `POST_DELETE`
  * `PRE_EDIT`, `POST_EDIT`
  * `PRE_LIST`, `POST_LIST`
  * `PRE_NEW`, `POST_NEW`
  * `PRE_SEARCH`, `POST_SEARCH`
  * `PRE_SHOW`, `POST_SHOW`

### Doctrine related events

Doctrine related logic is wrapped with a before and after event that allows you
to tweak the entity being created/updated/removed:

  * `PRE_PERSIST`, `POST_PERSIST`
  * `PRE_UPDATE`, `POST_UPDATE`
  * `PRE_REMOVE`, `POST_REMOVE`

### The Event Object

Event listeners and subscribers receive an event object based on the
[GenericEvent class] [generic-event] defined by Symfony. The subject of the
event depends on the current action:

  * `delete`, `edit` and `show` actions receive the current `$entity` object
    as the subject (this object is also available in the event arguments as
    `$event['entity']`).
  * `list` and `search` actions receive the `$paginator` object as the subject
    which contains the collection of entities that meet the criteria of the
    current listing of search query (this object is also available in the
    event arguments as `$event['paginator']`).

In addition, the event arguments contain all the action method variables. You
can access to them through the `getArgument()` method or via the array access
provided by the `GenericEvent` class.

The following example shows how to use an event subscriber to implement the
same example used in the previous section (set the `slug` property of the
`BlogPost` entity before persisting it):

```php
namespace AppBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use AppBundle\Entity\BlogPost;

class EasyAdminSubscriber implements EventSubscriberInterface
{
    private $slugger;

    public function __construct($slugger)
    {
        $this->slugger = $slugger;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'easy_admin.pre_persist' => array('setBlogPostSlug'),
        );
    }

    public function setBlogPostSlug(GenericEvent $event)
    {
        $entity = $event->getSubject();

        if (!($entity instanceof BlogPost)) {
            return;
        }

        $slug = $this->slugger->slugify($entity->getTitle());
        $entity->setSlug($slug);

        $event['entity'] = $entity;
    }
}
```

[event-listener]: http://symfony.com/doc/current/cookbook/service_container/event_listener.html
[generic-event]: http://symfony.com/doc/current/components/event_dispatcher/generic_event.html
