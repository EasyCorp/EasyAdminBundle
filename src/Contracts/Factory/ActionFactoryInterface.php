<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Collection\ActionCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

interface ActionFactoryInterface
{
    public function processEntityActions(EntityDto $entityDto, ActionConfigDto $actionsDto): void;

    public function processGlobalActions(?ActionConfigDto $actionsDto = null): ActionCollection;
}
