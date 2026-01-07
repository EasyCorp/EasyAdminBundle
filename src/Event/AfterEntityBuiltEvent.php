<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AfterEntityBuiltEvent
{
    public function __construct(private readonly EntityDto $entityDto)
    {
    }

    public function getEntity(): EntityDto
    {
        return $this->entityDto;
    }
}
