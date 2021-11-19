<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Event;

/**
 * @author: Benjamin Leibinger <mail@leibinger.io>
 */
interface EntityLifecycleEventInterface
{
    public function getEntityInstance();
}
