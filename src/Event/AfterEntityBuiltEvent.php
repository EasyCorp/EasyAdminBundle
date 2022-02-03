<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AfterEntityBuiltEvent
{
    private EntityDto $entityDto;

    public function __construct(EntityDto $entityDto)
    {
        $this->entityDto = $entityDto;
    }

    public function getEntity(): EntityDto
    {
        return $this->entityDto;
    }
}
