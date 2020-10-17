<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * This form event subscriber helps to provide the tab functionality.
 *
 * @author naitsirch <naitsirch@e.mail.de>
 */
class EaFormTabSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SUBMIT => ['handleViolations', -1],
        ];
    }

    /**
     * Deal with form constraint violations. This method has to be executed with
     * a negative priority to make sure that the validation process is done.
     */
    public function handleViolations(FormEvent $event)
    {
        foreach ($event->getForm() as $child) {
            $errors = $child->getErrors(true);

            if (\count($errors) > 0) {
                $field = $child->getConfig()->getAttribute('ea_field');
                $panel = $field->getDecorator('panel');
                $tab   = $field->getDecorator('tab');

                $tab->setCustomOption('errors', (int) $tab->getCustomOption('errors') + \count($errors));

                if (!$panel->getCustomOption('activeTab')) {
                    $panel->setCustomOption('activeTab', $tab);
                }
            }
        }
    }
}
