<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Event\EntityLifecycleEventInterface;

/**
 * @author: Benjamin Leibinger <mail@leibinger.io>
 *
 * @template TEntity of object
 *
 * @implements EntityLifecycleEventInterface<TEntity>
 */
abstract class AbstractLifecycleEvent implements EntityLifecycleEventInterface
{
    /**
     * @param TEntity $entityInstance
     */
    public function __construct(protected object $entityInstance)
    {
    }

    public function getEntityInstance(): object
    {
        return $this->entityInstance;
    }
}
