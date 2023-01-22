<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

/**
 * This event is triggered after the updateEntity() call in the
 * new() or edit() flows.
 *
 * @see AbstractCrudController::edit
 * @see AbstractCrudController::new
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AfterEntityUpdatedEvent extends AbstractLifecycleEvent
{
    private /* ?object */ $form;

    public function __construct(/* ?object */ $entityInstance, /* ?object */ $form = null)
    {
        parent::__construct($entityInstance);

        if (null !== $form && !\is_object($form)) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.0.5',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$form',
                __METHOD__,
                '"object" or "null"',
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
