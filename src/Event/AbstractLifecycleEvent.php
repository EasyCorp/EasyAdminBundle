<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Event\EntityLifecycleEventInterface;

/**
 * @author: Benjamin Leibinger <mail@leibinger.io>
 */
abstract class AbstractLifecycleEvent implements EntityLifecycleEventInterface
{
    protected $entityInstance;

    public function __construct($entityInstance)
    {
        $this->entityInstance = $entityInstance;
    }

    public function getEntityInstance()
    {
        return $this->entityInstance;
    }
}
