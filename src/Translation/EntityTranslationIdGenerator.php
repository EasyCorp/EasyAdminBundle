<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Translation;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Translation\EntityTranslationIdGeneratorInterface;

class EntityTranslationIdGenerator implements EntityTranslationIdGeneratorInterface
{
    public function generateForEntity(string $entity, bool $singular): string
    {
        return sprintf('entities.%s.%s', $entity, $singular ? 'singular' : 'plural');
    }

    public function generateForProperty(string $entity, string $property): string
    {
        return sprintf('entities.%s.properties.%s', $entity, $property);
    }
}
