<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class CrudAutocompleteParentSubscriber implements EventSubscriberInterface
{
    private ?array $newValues = null;
    private ?\Closure $newValueHandler = null;

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SUBMIT => 'postSubmit',
        ];
    }

    public function setNewValuesAndHandler(array $newValues, callable $newValueHandler): void
    {
        $this->newValues = $newValues;
        $this->newValueHandler = $newValueHandler instanceof \Closure ? $newValueHandler : \Closure::fromCallable($newValueHandler);
    }

    public function postSubmit(FormEvent $event): void
    {
        if (null !== $this->newValues) {
            $handler = $this->newValueHandler;
            $handler($this->newValues, $event->getData());
        }
    }
}
