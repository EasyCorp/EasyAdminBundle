<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

final class AfterEntityBuiltEvent
{
    private $entityDto;

    public function __construct(EntityDto $entityDto)
    {
        $this->entityDto = $entityDto;
    }

    public function getEntity(): EntityDto
    {
        return $this->entityDto;
    }
}
