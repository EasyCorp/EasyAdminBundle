<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets a test run code before a CRUD action without calling addListener() on the
 * compiled event dispatcher, which is deprecated since Symfony 8.2.
 */
class BeforeCrudActionListener implements EventSubscriberInterface
{
    public ?\Closure $callback = null;

    public static function getSubscribedEvents(): array
    {
        return [BeforeCrudActionEvent::class => 'onBeforeCrudAction'];
    }

    public function onBeforeCrudAction(BeforeCrudActionEvent $event): void
    {
        if (null !== $this->callback) {
            ($this->callback)($event);
        }
    }
}
