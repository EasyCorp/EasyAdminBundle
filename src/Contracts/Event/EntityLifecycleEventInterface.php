<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Event;

/**
 * @author: Benjamin Leibinger <mail@leibinger.io>
 *
 * @template TEntity of object
 */
interface EntityLifecycleEventInterface
{
    /**
     * @return object
     *
     * @phpstan-return TEntity
     */
    public function getEntityInstance();
}
