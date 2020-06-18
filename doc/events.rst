Events
======

.. raw:: html

    <div class="box box--small box--warning">
        <strong class="title">WARNING:</strong>

        You are browsing the documentation for <strong>EasyAdmin 3.x</strong>,
        which has just been released. Switch to
        <a href="https://symfony.com/doc/2.x/bundles/EasyAdminBundle/index.html">EasyAdmin 2.x docs</a>
        if your application has not been upgraded to EasyAdmin 3 yet.
    </div>

EasyAdmin triggers several `Symfony events`_ during the execution of its
requests, so you can listen to those events and run your own logic.

Events were useful in EasyAdmin versions previous to 3.0, because backends were
defined with YAML config files instead of PHP code. Starting from EasyAdmin 3.0
everything is defined with PHP. That's why it's easier to customize backend
behavior overloading PHP classes and methods and calling to your own services.
However, the events still remain in case you want to use them.

All events are triggered using objects instead of event names defined as strings
(as recommended since Symfony 4.3). They are defined under the
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

  * ``BeforeCrudActionEvent``
  * ``AfterCrudActionEvent``

Event Subscriber Example
------------------------

.. TODO: explain how to redirect to another URL from the listener (e.g. to avoid
..       deleting an entity in some cases when listening to BeforeRemovingEntity
..       Show the CRUD URL builder

The following example shows how to use an event subscriber to set the ``slug``
property of the ``BlogPost`` entity before persisting it:

.. code-block:: php

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

.. _`Symfony events`: https://symfony.com/doc/current/event_dispatcher.html
