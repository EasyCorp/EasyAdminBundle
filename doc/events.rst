Events
======

Backends trigger several `Symfony events`_ during the execution of their
requests, so you can listen to those events and run your own logic.

Starting from EasyAdmin 3.0, the entire backend configuration is made with PHP
instead of YAML files, so you can customize backend behavior more easily
overloading PHP classes and methods and calling to your own services. However,
the events still remain in case you want to use them.

All events are triggered using objects instead of string names (as recommended
since Symfony 4.3). They are defined under the
``EasyCorp\Bundle\EasyAdminBundle\Events\`` namespace:

* Events related to Doctrine entities:

  * ``BeforeCreatingEntity``
  * ``AfterCreatingEntity``
  * ``BeforeUpdatingEntity``
  * ``AfterUpdatingEntity``
  * ``BeforeRemovingEntity``
  * ``AfterRemovingEntity``

* Events related to resource admins:

  * ``BeforeAdminAction``
  * ``AfterAdminAction``

Event Subscriber Example
------------------------

.. TODO: explain how to redirect to another URL from the listener (e.g. to avoid
..       deleting an entity in some cases when listening to BeforeRemovingEntity

The following example shows how to use an event subscriber to set the ``slug``
property of the ``BlogPost`` entity before persisting it:

.. code-block:: php

    # src/EventSubscriber/EasyAdminSubscriber.php
    namespace App\EventSubscriber;

    use App\Entity\BlogPost;
    use EasyCorp\Bundle\EasyAdminBundle\Events\BeforeCreatingEntity;
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
            return array(
                BeforeCreatingEntity::class => ['setBlogPostSlug'],
            );
        }

        public function setBlogPostSlug(BeforeCreatingEntity $event)
        {
            $entity = $event->getEntity();

            if (!($entity instanceof BlogPost)) {
                return;
            }

            $slug = $this->slugger->slugify($entity->getTitle());
            $entity->setSlug($slug);

            $event->setEntity($entity);
        }
    }

.. _`Symfony events`: https://symfony.com/doc/current/event_dispatcher.html
