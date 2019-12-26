<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Property;

use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

interface PropertyConfiguratorInterface
{
    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool;

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void;
}
