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
 *
 * @template TEntity of object
 *
 * @extends AbstractLifecycleEvent<TEntity>
 */
final class AfterEntityUpdatedEvent extends AbstractLifecycleEvent
{
}
