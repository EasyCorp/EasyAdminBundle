<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;


use EasyCorp\Bundle\EasyAdminBundle\Collection\ActionCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface ActionFactoryInterface
{
    public function processEntityActions(EntityDto $entityDto, ActionConfigDto $actionsDto): void;

    public function processGlobalActions(?ActionConfigDto $actionsDto = null): ActionCollection;
}
