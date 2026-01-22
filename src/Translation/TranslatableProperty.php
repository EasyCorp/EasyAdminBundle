<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Translation;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use function Symfony\Component\String\u;

class TranslatableProperty implements TranslatableInterface
{
    public function __construct(
        /** @var class-string */
        private readonly string $entity,
        private readonly string $property,
    ) {
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        $translation = $translator->trans(
            $id = sprintf('entities.%s.properties.%s', $this->entity, $this->property),
            [],
            'EasyAdminBundle',
            $locale,
        );

        if ($id !== $translation) {
            return $translation;
        }

        // If no translation is found generate a label
        $property = u($this->property);
        $propertyUpper = u($this->property)->upper();

        // Some labels look better in uppercase
        if (\in_array($propertyUpper->toString(), ['ID', 'URL'], true)) {
            return $propertyUpper->toString();
        }

        // Prevent changing all-uppercase labels (e.g. 'UUID' -> 'U u i d')
        if ($propertyUpper->equalsTo($property)) {
            return $propertyUpper->toString();
        }

        return $property
            ->replaceMatches('/([A-Z])/', '_$1')
            ->replaceMatches('/[_\s]+/', ' ')
            ->trim()
            ->lower()
            ->title(true)
            ->toString()
        ;
    }
}
