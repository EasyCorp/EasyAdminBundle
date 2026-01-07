<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @template TEntity of object
 *
 * @extends AbstractLifecycleEvent<TEntity>
 */
final class AfterEntityDeletedEvent extends AbstractLifecycleEvent
{
}
