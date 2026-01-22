<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Translation;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatableEntity implements TranslatableInterface
{
    public function __construct(
        /** @var class-string */
        private readonly string $entity,
        private readonly bool $singular,
    ) {
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        $translation = $translator->trans(
            $id = sprintf('entities.%s.%s', $this->entity, $this->singular ? 'singular' : 'plural'),
            [],
            'EasyAdminBundle',
            $locale,
        );

        return $id === $translation
            ? (new \ReflectionClass($this->entity))->getShortName() // Fallback if no translation is found
            : $translation;
    }
}
