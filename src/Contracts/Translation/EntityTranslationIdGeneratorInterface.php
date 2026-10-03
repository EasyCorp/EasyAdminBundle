<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Translation;

/**
 * Generates the translation ids of entity and property names.
 */
interface EntityTranslationIdGeneratorInterface
{
    /**
     * @param class-string $entity
     */
    public function generateForEntity(string $entity, bool $singular): string;

    /**
     * @param class-string $entity
     */
    public function generateForProperty(string $entity, string $property): string;
}
