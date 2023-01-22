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
}
