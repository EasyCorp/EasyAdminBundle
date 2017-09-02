Chapter 9. Creating Complex and Dynamic Backends
================================================

In the previous chapters you've learned how to configure your backend using YAML
configuration options and overriding Twig templates. This mechanism is enough
for simple and medium-sized backends.

However, for more complex and dynamic backends you need to use the PHP-based
customization mechanism provided by EasyAdmin. Depending on your needs you can
choose any of these three customization options (or combine them, if your
backend is very complex):

1. **Override the default AdminController**, which is easy to set up and best
   suited for simple backends.
2. **Define a different controller for some or all entities**, which is also
   easy to set up and scales well for medium-sized backends.
3. **Define event listeners or subscribers** that listen to the events
   triggered by EasyAdmin. This is harder to set up but allows you to define
   the customization code anywhere in your application.

.. _overriding-the-default-controller:

Customization Based on Overriding the Default AdminController
-------------------------------------------------------------

This technique requires you to create a new controller in your Symfony
application and make it extend from the default ``AdminController`` provided by
EasyAdmin. Then you just add some methods in your controller to override the
default ones.

**Step 1.** Create a new controller class anywhere in your Symfony application
and make it extend from the default ``AdminController`` class:

.. code-block:: php

    // src/AppBundle/Controller/AdminController.php
    namespace AppBundle\Controller;

    use EasyCorp\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;

    class AdminController extends BaseAdminController
    {
        // ...
    }

**Step 2.** Open the ``app/config/routing.yml`` file and change the ``resource``
option of the ``easy_admin_bundle`` route to point to your new controller:

.. code-block:: yaml

    # app/config/routing.yml
    easy_admin_bundle:
        # this is just an example; update the value of 'resource' accordingly
        resource: "@AppBundle/Controller/AdminController.php"
        type:     annotation
        prefix:   /admin

Save the changes and the backend will start using your own controller.

**Step 3.** You can now override in your own controller any of the methods
executed in the default ``AdminController``. The next sections explain all the
available methods and show some practical examples.

AdminController Properties and Methods
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

First, the default ``AdminController`` extends from `the base Symfony controller`_,
so you have access to all its shortcuts and utility methods:

.. code-block:: php

    use Symfony\Bundle\FrameworkBundle\Controller\Controller;
    // ...

    class AdminController extends Controller
    {
        // ...
    }

In addition, the default ``AdminController`` defines some **properties** which
are commonly used in the rest of the methods:

.. code-block:: php

    class AdminController extends Controller
    {
        /** @var array The full configuration of the entire backend */
        protected $config;
        /** @var array The full configuration of the current entity */
        protected $entity;
        /** @var Request The instance of the current Symfony request */
        protected $request;
        /** @var EntityManager The Doctrine entity manager for the current entity */
        protected $em;
    }

Finally, the default ``AdminController`` defines lots of **methods** which you
can override in your own backends.

The ``indexAction()`` method is the only "real controller" because it's the only
method associated with a route (all the pages created with EasyAdmin use a
single route called ``easyadmin``). It makes some checks and then it redirects to
the actual executed method, such as ``listAction()``, ``showAction()``, etc.:

.. code-block:: php

    class AdminController extends Controller
    {
        /** @Route("/", name="easyadmin") */
        public function indexAction(Request $request)
        {
            // you can override this method to perform additional checks and to
            // perform more complex logic before redirecting to the other methods
        }
    }

The ``initialize()`` method is called by ``indexAction()`` and it initializes
the values of the ``$config``, ``$entity``, ``$request`` and ``$em`` properties
shown above:

.. code-block:: php

    class AdminController extends Controller
    {
        // override this method to initialize your custom properties
        protected function initialize(Request $request);
    }

Then, the ``AdminController`` defines a method to handle each view. These
methods are complex because they need to perform lots of checks:

.. code-block:: php

    class AdminController extends Controller
    {
        protected function listAction();
        protected function showAction();
        protected function editAction();
        protected function newAction();
        protected function searchAction();
        protected function deleteAction();
        // special Ajax-based action used to get the results for the autocomplete form field
        protected function autocompleteAction();
    }

The rest of the available methods are specific for each action:

**List** action:

.. code-block:: php

    class AdminController extends Controller
    {
        // Creates the Doctrine query builder used to get all the items. Override it
        // to filter the elements displayed in the listing
        protected function createListQueryBuilder($entityClass, $sortDirection, $sortField = null, $dqlFilter = null);

        // Performs the actual database query to get all the items (using the query
        // builder created with the previous method). You can override this method
        // to filter the results before sending them to the template
        protected function findAll($entityClass, $page = 1, $maxPerPage = 15, $sortField = null, $sortDirection = null, $dqlFilter = null);
    }

**Search** action:

.. code-block:: php

    class AdminController extends Controller
    {
        // Creates the Doctrine query builder used to look for items according to the
        // user's query. Override it to filter the elements displayed in the search listing
        protected function createSearchQueryBuilder($entityClass, $searchQuery, array $searchableFields, $sortField = null, $sortDirection = null);

        // Performs the actual database query to look for the items according to the
        // user's query (using the query builder created with the previous method).
        // You can override this method to filter the results before sending them to
        // the template
        protected function findBy($entityClass, $searchQuery, array $searchableFields, $page = 1, $maxPerPage = 15, $sortField = null, $sortDirection = null);
    }

**Delete** action:

.. code-block:: php

    class AdminController extends Controller
    {
        // Creates the form used to delete an entity item (a form is required because
        // items are deleted using the 'DELETE' HTTP method)
        protected function createDeleteForm($entityName, $entityId);

        // It's executed just before removing the item with Doctrine and it allows
        // to modify the item being deleted before removing it
        protected function preRemoveEntity($entity);
    }

**Edit** action:

.. code-block:: php

    class AdminController extends Controller
    {
        // Creates the form used to edit an entity item
        protected function createEditForm($entity, array $entityProperties);

        // It's executed just before saving the changes of a modified entity. It
        // allows you to modify the entity even further before it's saved
        protected function preUpdateEntity($entity)
    }

**New** action:

.. code-block:: php

    class AdminController extends Controller
    {
        // Creates a new instance of the entity being created. This instance is passed
        // to the form created with the 'createNewForm()' method. Override this method
        // if your entity has a constructor that expects some arguments to be passed
        protected function createNewEntity()

        // Creates the form used to create a new entity item
        protected function createNewForm($entity, array $entityProperties)

        // It's executed just before saving the item for the first time. It allows
        // you to modify the entity before it's saved
        protected function prePersistEntity($entity)
    }

**Edit** and **New** actions:

These methods are useful to make the same customizations for the ``edit`` and
``new`` actions at the same time:

.. code-block:: php

    class AdminController extends Controller
    {
        // Creates the form builder used to create the form rendered in the
        // create and edit actions
        protected function createEntityFormBuilder($entity, $view);

        // Returns the list of form options used by 'createEntityFormBuilder()'
        protected function getEntityFormOptions($entity, $view);

        // Creates the form object passed to the 'edit' and 'new' templates (using the
        // form builder created by 'createEntityFormBuilder()')
        protected function createEntityForm($entity, array $entityProperties, $view);
    }

Overriding the Default AdminController in Practice
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Update Some Properties for All Entities
.......................................

Imagine that some or all of your entities define a property called ``updatedAt``.
Instead of editing this value using the backend interface or relying on Doctrine
extensions, you can make use of the ``preUpdateEntity()`` method, which is called
just before saving the changes made on an existing entity:

.. code-block:: php

    // src/AppBundle/Controller/AdminController.php
    namespace AppBundle\Controller;

    use EasyCorp\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;

    class AdminController extends BaseAdminController
    {
        // ...

        public function preUpdateEntity($entity)
        {
            if (method_exists($entity, 'setUpdatedAt')) {
                $entity->setUpdatedAt(new \DateTime());
            }
        }
    }

This other example shows how to automatically set the slug of the entities when
creating (``prePersistEntity()``) or editing (``preUpdateEntity()``) them:

.. code-block:: php

    // src/AppBundle/Controller/AdminController.php
    namespace AppBundle\Controller;

    use EasyCorp\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;

    class AdminController extends BaseAdminController
    {
        // ...

        public function prePersistEntity($entity)
        {
            $this->updateSlug($entity);
        }

        public function preUpdateEntity($entity)
        {
            $this->updateSlug($entity);
        }

        private function updateSlug($entity)
        {
            if (method_exists($entity, 'setSlug') and method_exists($entity, 'getTitle')) {
                $entity->setSlug($this->get('app.slugger')->slugify($entity->getTitle()));
            }
        }
    }

Override the AdminController Methods per Entity
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Before executing the methods showed above (``listAction()``, ``showAction()``,
etc.), the controller looks for the existence of methods created specifically
for the current entity. These specific methods are called like the regular
methods, but they include the entity name as part of their names:

.. code-block:: php

    protected function list<EntityName>Action();
    protected function search<EntityName>Action();
    protected function show<EntityName>Action();
    // ...
    protected function createNew<EntityName>Entity();
    // ...
    protected function prePersist<EntityName>Entity();
    protected function preUpdate<EntityName>Entity();
    // ...

.. tip::

    Given the syntax of method names, it's recommended to use CamelCase notation
    to set the entity names.

Suppose that you have a ``User`` entity which requires to pass the roles of the
new user to its constructor. If you try to create new users with EasyAdmin,
you'll see an error because the entity constructor is missing a required
argument.

Instead of overriding the ``createNewEntity()`` method and check for the
``User`` entity, you can just define the following method:

.. code-block:: php

    // src/AppBundle/Controller/AdminController.php
    namespace AppBundle\Controller;

    use EasyCorp\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;

    class AdminController extends BaseAdminController
    {
        // Customizes the instantiation of entities only for the 'User' entity
        public function createNewUserEntity()
        {
            return new User(array('ROLE_USER'));
        }
    }

Customization Based on Entity Controllers
-----------------------------------------

If your backend is medium-sized, the previous overriding mechanism doesn't scale
well because it requires you to put all the custom code in the same AdminController.
In those cases, you can make each entity to use a different controller.

**Step 1.** Create a new controller class (for example ``ProductController``)
anywhere in your Symfony application and make it extend from the default
``AdminController`` class:

.. code-block:: php

    // src/AppBundle/Controller/AdminController.php
    namespace AppBundle\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;

    class ProductController extends BaseAdminController
    {
        // ...
    }

**Step 2.** Define the ``controller`` configuration option for the entity that
will use that controller and set the fully qualified class name as its value:

.. code-block:: yaml

    easy_admin:
        entities:
            # ...
            Product:
                controller: AppBundle\Admin\ProductController
                # ...

**Step 3.** You can now override any of the default ``AdminController`` methods
and they will be executed only for the ``Product`` entity. Repeat these steps for
the other backend entities that you want to customize.

.. note::

    It's not mandatory that your custom controllers extend from the default
    ``AdminController`` class, but doing that will simplify the code of your
    controllers.

.. note::

    In addition to the custom controller fully qualified class name, the
    ``controller`` option also works for controllers defined as services. Just
    set the name of the service as the value of the ``controller`` option.

Customization Based on Symfony Events
-------------------------------------

During the execution of the backend actions, lots of events are triggered. Using
Symfony's event listeners or event subscribers you can hook to these events and
modify the behavior of your backend.

EasyAdmin events are defined in the ``EasyAdmin\Event\EasyAdminEvents`` class.
They are triggered before and after important operations and their names follow
the ``PRE_*`` and ``POST_*`` pattern:

.. code-block:: php

    final class EasyAdminEvents
    {
        // Events related to initialize()
        const PRE_INITIALIZE;
        const POST_INITIALIZE;

        // Events related to the main actions
        const PRE_DELETE;
        const POST_DELETE;
        const PRE_EDIT;
        const POST_EDIT;
        const PRE_LIST;
        const POST_LIST;
        const PRE_NEW;
        const POST_NEW;
        const PRE_SEARCH;
        const POST_SEARCH;
        const PRE_SHOW;
        const POST_SHOW;

        // Events related to Doctrine entities
        const PRE_PERSIST;
        const POST_PERSIST;
        const PRE_UPDATE;
        const POST_UPDATE;
        const PRE_REMOVE;
        const POST_REMOVE;

        // Events related to the Doctrine Query builders
        const POST_LIST_QUERY_BUILDER;
        const POST_SEARCH_QUERY_BUILDER;
    }

The Event Object
~~~~~~~~~~~~~~~~

Event listeners and subscribers receive an event object based on the
`GenericEvent class`_ defined by Symfony. The subject of the event depends
on the current action:

* ``show``, ``edit`` and ``new`` actions receive the current ``$entity`` object
  (this object is also available in the event arguments as ``$event['entity']``).
* ``list`` and ``search`` actions receive the ``$paginator`` object which contains
  the collection of entities that meet the criteria of the current listing
  (this object is also available in the event arguments as
  ``$event['paginator']``).

In addition, the event arguments contain all the AdminController properties
(``$config``, ``$entity``, ``$request`` and ``$em``). You can access to them
through the ``getArgument()`` method or via the array access provided by the
``GenericEvent`` class.

Event Subscriber Example
~~~~~~~~~~~~~~~~~~~~~~~~

The following example shows how to use an event subscriber to set the ``slug``
property of the ``BlogPost`` entity before persisting it:

.. code-block:: php

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

.. _`the base Symfony controller`: https://symfony.com/doc/current/book/controller.html#the-base-controller-class
.. _`GenericEvent class`: https://symfony.com/doc/current/components/event_dispatcher/generic_event.html
