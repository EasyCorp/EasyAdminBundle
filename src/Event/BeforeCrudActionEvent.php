<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class BeforeCrudActionEvent
{
    use StoppableEventTrait;

    private ?AdminContext $adminContext;

    public function __construct(?AdminContext $adminContext)
    {
        $this->adminContext = $adminContext;
    }

    public function getAdminContext(): ?AdminContext
    {
        return $this->adminContext;
    }
}
