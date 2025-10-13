<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;

interface FieldFactoryInterface
{
    /**
     * @param FieldCollection<FieldDto> $fields
     */
    public function processFields(EntityDto $entityDto, FieldCollection $fields): void;
}
