<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Property;

use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;

interface PropertyConfiguratorInterface
{
    public function supports(PropertyConfigInterface $property, EntityDto $entityDto): bool;

    public function configure(PropertyDto $propertyDto, EntityDto $entityDto): void;
}
