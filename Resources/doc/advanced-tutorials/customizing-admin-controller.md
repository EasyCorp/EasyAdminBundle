Customizing Admin Controller
============================

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

In case you backend is very complex, you can even combine both methods in the
same application.

Customization Based on Controller Methods
-----------------------------------------

This technique requires you to create a new controller in your Symfony
application and make it extend from the default `AdminController`. Then you
just add one or more methods in your controller to override the default ones.

The first step is to create a new controller in your Symfony application. Its
class name or namespace deson't matter as long as it extends the default
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
    ...
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

`AdminController` also defines three similar but generic methods to allow
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



Hook into any of the new events defined by the bundle:

```php
// Initialization related events
const PRE_INITIALIZE = 'easy_admin.pre_initialize';
const POST_INITIALIZE = 'easy_admin.post_initialize';

// Backend views related events
const PRE_DELETE = 'easy_admin.pre_delete';
const POST_DELETE = 'easy_admin.post_delete';
const PRE_EDIT = 'easy_admin.pre_edit';
const POST_EDIT = 'easy_admin.post_edit';
const PRE_LIST = 'easy_admin.pre_list';
const POST_LIST = 'easy_admin.post_list';
const PRE_NEW = 'easy_admin.pre_new';
const POST_NEW = 'easy_admin.post_new';
const PRE_SEARCH = 'easy_admin.pre_search';
const POST_SEARCH = 'easy_admin.post_search';
const PRE_SHOW = 'easy_admin.pre_show';
const POST_SHOW = 'easy_admin.post_show';

// Doctrine related events
const PRE_PERSIST = 'easy_admin.pre_persist';
const POST_PERSIST = 'easy_admin.post_persist';
const PRE_UPDATE = 'easy_admin.pre_update';
const POST_UPDATE = 'easy_admin.post_update';
const PRE_REMOVE = 'easy_admin.pre_remove';
const POST_REMOVE = 'easy_admin.post_remove';
```

Single entity actions (edit, new, delete, etc.) receive the `$entity` as the event subject. The actions related to multiple entities (list, search) receive the `$paginator` object as the subject of the event. In addition, the event receives all the controller variables as arguments. You can access them via `getArgument()` or via the array access provided by Symfony for the GenericEvent:

```php
<?php

namespace AppBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class EasyAdminSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            'easy_admin.pre_update' => array('preUpdate'),
        );
    }

    public function preUpdate(GenericEvent $event)
    {
        $entity = $event->getSubject();
        $view = $event['view'];
        $entity = $event['entity'];

        // ...
    }
}
```
