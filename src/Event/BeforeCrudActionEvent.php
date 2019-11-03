<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;

final class BeforeCrudActionEvent extends StoppableEvent
{
    private $applicationContext;

    public function __construct(?ApplicationContext $applicationContext)
    {
        $this->applicationContext = $applicationContext;
    }

    public function getApplicationContext(): ?ApplicationContext
    {
        return $this->applicationContext;
    }
}
