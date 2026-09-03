Events
======

EasyAdmin triggers several `Symfony events`_ during the execution of its
requests, so you can listen to those events and run your own logic.

Events are rarely needed today. In EasyAdmin 2 and earlier, backends were
defined with YAML files, so events were the only way to add custom logic. Since
EasyAdmin 3, everything is defined in PHP, so it's usually simpler to override
the PHP classes and methods of your backend. Events are still fully supported.

All events are dispatched as objects rather than string event names. They live
under the ``EasyCorp\Bundle\EasyAdminBundle\Event\`` namespace:

* Events related to Doctrine entities:

  * ``AfterEntityBuiltEvent``
  * ``AfterEntityDeletedEvent``
  * ``AfterEntityPersistedEvent``
  * ``AfterEntityUpdatedEvent``
  * ``BeforeEntityDeletedEvent``
  * ``BeforeEntityPersistedEvent``
  * ``BeforeEntityUpdatedEvent``

* Events related to CRUD controllers:

  * ``AfterCrudActionEvent``
  * ``AfterEntitySearchEvent`` (it receives the search ``QueryBuilder``, so you
    can modify the search results)
  * ``BeforeCrudActionEvent``

Event Subscriber Example
------------------------

The following example shows how to use an event subscriber to set the ``slug``
property of the ``BlogPost`` entity before persisting it::

    // src/EventSubscriber/EasyAdminSubscriber.php
    namespace App\EventSubscriber;

    use App\Entity\BlogPost;
    use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
    use Symfony\Component\EventDispatcher\EventSubscriberInterface;
    use Symfony\Component\String\Slugger\SluggerInterface;

    class EasyAdminSubscriber implements EventSubscriberInterface
    {
        public function __construct(
            private SluggerInterface $slugger,
        ) {
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

            $slug = $this->slugger->slug($entity->getTitle())->lower();
            $entity->setSlug($slug);
        }
    }

Returning a Response From an Event Listener
-------------------------------------------

Sometimes you need to stop the execution of the current action and return a
response immediately (e.g. to redirect to another URL instead of deleting an
entity). To do so, call the ``setResponse()`` method of the event. When a
listener sets a response, EasyAdmin stops the event propagation and returns
that response right away.

This method is only available on the ``BeforeCrudActionEvent``,
``AfterCrudActionEvent``, ``BeforeEntityDeletedEvent``,
``AfterEntityDeletedEvent``, ``AfterEntityPersistedEvent`` and
``AfterEntityUpdatedEvent`` events.

The following example prevents the deletion of blog posts that are published
and redirects to the index page instead::

    // src/EventSubscriber/EasyAdminSubscriber.php
    namespace App\EventSubscriber;

    use App\Entity\BlogPost;
    use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityDeletedEvent;
    use Symfony\Component\EventDispatcher\EventSubscriberInterface;
    use Symfony\Component\HttpFoundation\RedirectResponse;
    use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

    class EasyAdminSubscriber implements EventSubscriberInterface
    {
        public function __construct(
            private UrlGeneratorInterface $urlGenerator,
        ) {
        }

        public static function getSubscribedEvents()
        {
            return [
                BeforeEntityDeletedEvent::class => ['preventBlogPostDeletion'],
            ];
        }

        public function preventBlogPostDeletion(BeforeEntityDeletedEvent $event)
        {
            $entity = $event->getEntityInstance();

            if (!($entity instanceof BlogPost) || !$entity->isPublished()) {
                return;
            }

            // setting a response stops the event propagation and returns
            // that response immediately, so the entity is never deleted
            $url = $this->urlGenerator->generate('admin_blog_post_index');
            $event->setResponse(new RedirectResponse($url));
        }
    }

In this example, the redirect URL is generated with the route name created by
EasyAdmin for the CRUD action. If you don't know the route name beforehand, use
the ``AdminUrlGenerator`` service to :ref:`build the admin URL dynamically <generate-admin-urls>`.

JavaScript Events
-----------------

EasyAdmin triggers several `JavaScript events`_ during user interactions with the
entity forms displayed in the :ref:`form pages <crud-pages>`:

=================================  ==============================================  ================================  ==========
Event type                         Occurs when                                     Event detail                      Cancelable
=================================  ==============================================  ================================  ==========
``'ea.autocomplete.pre-connect'``  Before an autocomplete field is initialized     ``{config, prefix}``              false
``'ea.autocomplete.connect'``      After an autocomplete field is initialized      ``{tomSelect, config, prefix}``   false
``'ea.collection.item-added'``     Item added to collection                        ``{newElement, collection}``      false
``'ea.collection.item-removed'``   Item removed from collection                    ``{deletedElement, collection}``  false
``'ea.form.error'``                User submits a form that has validation errors  ``{page, form}``                  true
``'ea.form.submit'``               User submits a form                             ``{page, form}``                  true
=================================  ==============================================  ================================  ==========

All these events can be listened to on ``document``. The ``ea.autocomplete.*``
events are dispatched on the autocomplete form field element and bubble up,
so you can also listen to them on the field itself or any of its ancestors.

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
:ref:`Collection Field JavaScript Events <collection-field-javascript-events>` section.

TypeScript Support
~~~~~~~~~~~~~~~~~~

If your application frontend uses TypeScript, you can get type checking and
autocompletion for all these events. To do so, add the type declarations
provided by EasyAdmin to the ``include`` option of your ``tsconfig.json`` file:

.. code-block:: json

    {
        "include": [
            "src/**/*",
            "vendor/easycorp/easyadmin-bundle/assets/types.d.ts"
        ]
    }

.. caution::

    These type declarations import some types from the ``tom-select`` package
    (used by the ``ea.autocomplete.*`` events). Make sure that package is
    installed in your application (e.g. with ``npm install tom-select``);
    otherwise, the TypeScript compilation will fail.

.. _`Symfony events`: https://symfony.com/doc/current/event_dispatcher.html
.. _`JavaScript events`: https://developer.mozilla.org/en-US/docs/Learn/JavaScript/Building_blocks/Events
.. _`detail property`: https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent/detail
.. _`cancelable property`: https://developer.mozilla.org/en-US/docs/Web/API/Event/cancelable
