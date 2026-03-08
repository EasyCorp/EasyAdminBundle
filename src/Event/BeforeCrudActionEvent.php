<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class BeforeCrudActionEvent
{
    use StoppableEventTrait;

    public function __construct(private readonly ?AdminContext $adminContext)
    {
    }

    public function getAdminContext(): ?AdminContext
    {
        return $this->adminContext;
    }
}
