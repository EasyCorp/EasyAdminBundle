<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AfterEntityPersistedEvent extends AbstractLifecycleEvent
{
    private /* ?object */ $form;

    public function __construct(/* ?object */ $entityInstance, /* object */ $form)
    {
        parent::__construct($entityInstance);

        if (!\is_object($form)) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.0.5',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$form',
                __METHOD__,
                '"object"',
                \gettype($form)
            );
        }

        $this->form = $form;
    }

    public function getForm()/* : ?object */
    {
        return $this->form;
    }
}
