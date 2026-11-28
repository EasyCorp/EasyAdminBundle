<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\EventSubscriber;

use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Overrides the layout template for the requests that include the X-Override-Layout
 * header, to test AdminContext::overrideTemplate().
 */
class OverrideLayoutSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [BeforeCrudActionEvent::class => 'overrideLayout'];
    }

    public function overrideLayout(BeforeCrudActionEvent $event): void
    {
        $context = $event->getAdminContext();
        if (null === $context || !$context->getRequest()->headers->has('X-Override-Layout')) {
            return;
        }

        $context->overrideTemplate('layout', 'admin/override_layout.html.twig');
    }
}
