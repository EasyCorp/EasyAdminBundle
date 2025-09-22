Events
======

EasyAdmin triggers several `Symfony events`_ during the execution of its
requests, so you can listen to those events and run your own logic.

Events were useful in EasyAdmin versions prior to 3.0, because backends were
defined with YAML config files instead of PHP code. Since EasyAdmin 3.0,
everything is defined in PHP. It is usually easier to customize the backend by
overriding PHP classes and methods and by calling your own services. Events are
still available if you want to use them.

All events are dispatched as objects rather than string event names (as
recommended since Symfony 4.3). They live under the
``EasyCorp\Bundle\EasyAdminBundle\Event\`` namespace:

* Events related to Doctrine entities:

  * ``AfterEntityBuiltEvent``
  * ``AfterEntityDeletedEvent``
  * ``AfterEntityPersistedEvent``
  * ``AfterEntityUpdatedEvent``
  * ``BeforeEntityDeletedEvent``
  * ``BeforeEntityPersistedEvent``
  * ``BeforeEntityUpdatedEvent``

* Events related to resource admins:

  * ``AfterCrudActionEvent``
  * ``BeforeCrudActionEvent``

Event Subscriber Example
------------------------

.. TODO: explain how to redirect to another URL from the listener (e.g. to avoid
..       deleting an entity in some cases when listening to BeforeRemovingEntity
..       Show the CRUD URL builder

The following example shows how to use an event subscriber to set the ``slug``
property of the ``BlogPost`` entity before persisting it::

    # src/EventSubscriber/EasyAdminSubscriber.php
    namespace App\EventSubscriber;

    use App\Entity\BlogPost;
    use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
    use Symfony\Component\EventDispatcher\EventSubscriberInterface;

    class EasyAdminSubscriber implements EventSubscriberInterface
    {
        private $slugger;

        public function __construct($slugger)
        {
            $this->slugger = $slugger;
        }

        public static function getSubscribedEvents()
        {
            return [
                BeforeEntityPersistedEvent::class => ['setBlogPostSlug'],
            ];
        }

        public function setBlogPostSlug(BeforeEntityPersistedEvent $event)
        {
            $entity = $event->getEntityInstance();

            if (!($entity instanceof BlogPost)) {
                return;
            }

            $slug = $this->slugger->slugify($entity->getTitle());
            $entity->setSlug($slug);
        }
    }

JavaScript Events
-----------------

EasyAdmin triggers several `JavaScript events`_ during user interactions with entity forms:

================================  ==============================================  ================================  ==========
Event type                        Occurs when                                     Event detail                      Cancelable
================================  ==============================================  ================================  ==========
``'ea.form.error'``               User submits a form that has validation errors  ``{page: pageName, form: form}``  true
--------------------------------  ----------------------------------------------  --------------------------        ----------
``'ea.form.submit'``              User submits a form                             ``{page: pageName, form: form}``  true
--------------------------------  ----------------------------------------------  --------------------------------  ----------
``'ea.collection.item-added'``    Item added to collection                        ``{newElement: element}``         false
--------------------------------  ----------------------------------------------  --------------------------------  ----------
``'ea.collection.item-removed'``  Item removed from collection                                                      false
================================  ==============================================  ================================  ==========

.. tip::

    Read more about the `detail property`_ and the `cancelable property`_
    of JavaScript events.

Here's how you can listen for these events in JavaScript:

.. code-block:: javascript

    document.addEventListener('ea.form.error', (event) => {
        const {page, form} = event.detail
        alert(`The ${page} form contains errors. Please resolve these before submitting again.`)
    });

    document.addEventListener('ea.form.submit', (event) => {
        const {page, form} = event.detail
        console.debug(`${page} form submitted`, form)
    });

For more details and examples of the ``ea.collection.*`` events, see the
:doc:`Collection Field JavaScript Events </fields/CollectionField#javascript-events>` section.

.. _`Symfony events`: https://symfony.com/doc/current/event_dispatcher.html
.. _`JavaScript events`: https://developer.mozilla.org/en-US/docs/Learn/JavaScript/Building_blocks/Events
.. _`detail property`: https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent/detail
.. _`cancelable property`: https://developer.mozilla.org/en-US/docs/Web/API/Event/cancelable
