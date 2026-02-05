<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Icon;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Icon\EntityIconGeneratorInterface;

final class EntityIconGenerator implements EntityIconGeneratorInterface
{
    public function generate(string $entity): ?string
    {
        return null;
    }
}
