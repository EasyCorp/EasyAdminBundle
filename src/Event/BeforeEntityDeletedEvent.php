<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

final class BeforeEntityDeletedEvent
{
    private $entityInstance;

    public function __construct($entityInstance)
    {
        $this->entityInstance = $entityInstance;
    }

    public function getEntityInstance()
    {
        return $this->entityInstance;
    }
}
