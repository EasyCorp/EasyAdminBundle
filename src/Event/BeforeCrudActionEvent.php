<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;

final class BeforeCrudActionEvent
{
    use StoppableEventTrait;

    private $adminContext;

    public function __construct(?AdminContext $adminContext)
    {
        $this->adminContext = $adminContext;
    }

    public function getAdminContext(): ?AdminContext
    {
        return $this->adminContext;
    }
}
