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
    protected $originalEntity;

    public function __construct(/* ?object */ $entityInstance, /* ?object */ $originalEntity)
    {
        parent::__construct($entityInstance);
        $this->originalEntity = $originalEntity;
    }

    /**
     * @return mixed
     */
    public function getOriginalEntity()/* : ?object */
    {
        return $this->originalEntity;
    }
}
