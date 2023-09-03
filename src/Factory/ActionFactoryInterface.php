<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;


use EasyCorp\Bundle\EasyAdminBundle\Collection\ActionCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface ActionFactoryInterface
{
    public function processEntityActions(EntityDtoInterface $entityDto, ActionConfigDtoInterface $actionsDto): void;

    public function processGlobalActions(?ActionConfigDtoInterface $actionsDto = null): ActionCollection;
}
