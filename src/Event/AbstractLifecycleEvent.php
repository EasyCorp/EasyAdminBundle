<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Event\EntityLifecycleEventInterface;
use Symfony\Component\Form\FormInterface;

/**
 * @author: Benjamin Leibinger <mail@leibinger.io>
 */
abstract class AbstractLifecycleEvent implements EntityLifecycleEventInterface
{
    protected /* ?object */ $entityInstance;

    protected /* ?FormInterface */ $form;

    public function __construct(/* ?object */ $entityInstance, /* ?FormInterface */ $form = null)
    {
        if (!\is_object($entityInstance)
            && null !== $entityInstance) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.0.5',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$entityInstance',
                __METHOD__,
                '"object" or "null"',
                \gettype($entityInstance)
            );
        }

        if (null !== $form && !$form instanceof FormInterface) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.0.5',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$form',
                __METHOD__,
                '"FormInterface" or "null"',
                \gettype($form)
            );
        }

        $this->entityInstance = $entityInstance;

        $this->form = $form;
    }

    public function getEntityInstance()/* : ?object */
    {
        return $this->entityInstance;
    }

    public function getForm()/* : ?FormInterface */
    {
        return $this->form;
    }
}
