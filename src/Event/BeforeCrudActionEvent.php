<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContextInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class BeforeCrudActionEvent
{
    use StoppableEventTrait;

    private ?AdminContextInterface $adminContext;

    public function __construct(?AdminContextInterface $adminContext)
    {
        $this->adminContext = $adminContext;
    }

    public function getAdminContext(): ?AdminContextInterface
    {
        return $this->adminContext;
    }
}
