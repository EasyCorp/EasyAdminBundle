<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class BeforeEntityUpdatedEvent extends AbstractLifecycleEvent
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
