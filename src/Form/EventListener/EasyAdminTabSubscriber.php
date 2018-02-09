<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * This form event subscriber helps to provide the tab functionality.
 *
 * @author naitsirch <naitsirch@e.mail.de>
 */
class EasyAdminTabSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::POST_SUBMIT => array('handleViolations', -1),
        );
    }

    /**
     * Deal with form constraint violations. This method has to be executed with
     * a negative priority to make sure that the validation process is done.
     *
     * @param FormEvent $event
     */
    public function handleViolations(FormEvent $event)
    {
        $formTabs = $event->getForm()->getConfig()->getAttribute('easyadmin_form_tabs');

        $activeTab = null;
        foreach ($event->getForm() as $child) {
            $errors = $child->getErrors(true);

            if (count($errors) > 0) {
                $formTab = $child->getConfig()->getAttribute('easyadmin_form_tab');
                $formTabs[$formTab]['errors'] += count($errors);

                if (null === $activeTab) {
                    $activeTab = $formTab;
                }
            }
        }

        $firstTab = key($formTabs);
        if ($firstTab !== $activeTab) {
            // We have to deactivate the first tab, so that the first tab with
            // eroneous data is shown
            $formTabs[$firstTab]['active'] = false;
            $formTabs[$activeTab]['active'] = true;
        }
    }
}
