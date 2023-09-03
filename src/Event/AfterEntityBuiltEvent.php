<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AfterEntityBuiltEvent
{
    private EntityDto $entityDto;

    public function __construct(EntityDtoInterface $entityDto)
    {
        $this->entityDto = $entityDto;
    }

    public function getEntity(): EntityDtoInterface
    {
        return $this->entityDto;
    }
}
