<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider;

use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;

interface FieldProviderInterface
{
    /**
     * @param iterable<FieldInterface|string> $fields
     *
     * @return FieldCollection<FieldDto>
     */
    public function createCollection(iterable $fields): FieldCollection;

    /**
     * @return array<FieldInterface>
     */
    public function getDefaultFields(string $pageName): array;
}
