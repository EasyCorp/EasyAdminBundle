<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use Symfony\Component\Form\FormInterface;

/**
 * Event dispatched after form submission but before validation.
 * This allows to perform custom validation, modify data, or return
 * a custom response before the standard validation runs.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @template TEntity of object
 *
 * @extends AbstractLifecycleEvent<TEntity>
 */
final class BeforeFormValidateEvent extends AbstractLifecycleEvent
{
    use StoppableEventTrait;

    /**
     * @param TEntity $entityInstance
     */
    public function __construct(
        protected object $entityInstance,
        private readonly FormInterface $form,
    ) {
    }

    public function getForm(): FormInterface
    {
        return $this->form;
    }
}
