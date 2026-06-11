<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Translation;

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
