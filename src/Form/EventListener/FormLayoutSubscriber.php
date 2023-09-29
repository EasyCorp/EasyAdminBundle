<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormTabListType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Handles some logic related to the form layout, like error counters in tabs.
 *
 * @author naitsirch <naitsirch@e.mail.de>
 */
class FormLayoutSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SUBMIT => ['handleTabErrors', -1],
        ];
    }

    /**
     * Deal with the errors of fields inside form tabs. This method has to be executed with
     * a negative priority to make sure that the validation process is done.
     */
    public function handleTabErrors(FormEvent $event)
    {
        $formTabs = [];
        /** @var Form $child */
        foreach ($event->getForm() as $child) {
            /** @var FieldDto $fieldDto */
            if (null === $fieldDto = $child->getConfig()->getAttribute('ea_field')) {
                continue;
            }

            if (EaFormTabListType::class === $fieldDto->getFormType()) {
                $formTabs = $fieldDto->getCustomOption('tabs');

                break;
            }
        }

        if (0 === \count($formTabs)) {
            return;
        }

        // find the number of errors per tab
        $currentTabId = null;
        $errorCountPerTab = [];
        foreach ($event->getForm() as $child) {
            /** @var FieldDto $fieldDto */
            if (null === $fieldDto = $child->getConfig()->getAttribute('ea_field')) {
                continue;
            }

            if ($fieldDto->isFormTab()) {
                $currentTabId = $fieldDto->getCustomOption(FormField::OPTION_TAB_ID);

                continue;
            }

            $errors = $child->getErrors(true);
            $numErrorsInTab = \count($errors);
            if ($numErrorsInTab > 0 && '' !== $currentTabId) {
                $errorCountPerTab[$currentTabId] = ($errorCountPerTab[$currentTabId] ?? 0) + $numErrorsInTab;
            }
        }

        // store the number of errors in the config of each tab
        foreach ($event->getForm() as $child) {
            /** @var FieldDto $fieldDto */
            if (null === $fieldDto = $child->getConfig()->getAttribute('ea_field')) {
                continue;
            }

            if ($fieldDto->isFormTab()) {
                $formTabId = $fieldDto->getCustomOption(FormField::OPTION_TAB_ID);

                if (isset($errorCountPerTab[$formTabId])) {
                    $fieldDto->setCustomOption(FormField::OPTION_TAB_ERROR_COUNT, $errorCountPerTab[$formTabId]);
                }
            }
        }

        // set as active the first tab that contains errors
        $firstTabIdWithErrors = array_key_first($formTabs);
        if (\count($errorCountPerTab) > 0) {
            $firstTabIdWithErrors = array_key_first($errorCountPerTab);
        }
        foreach ($formTabs as $tabId => $formTab) {
            $formTab->setCustomOption(FormField::OPTION_TAB_IS_ACTIVE, $tabId === $firstTabIdWithErrors);
        }
    }
}
