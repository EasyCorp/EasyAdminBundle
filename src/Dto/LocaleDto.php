<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use Symfony\Component\Intl\Locales;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
final class LocaleDto
{
    public function __construct(
        private string $locale,
        private string $name,
        private ?string $icon = 'far fa-flag'
    ) {
    }

    public static function new(
        string $locale,
        string|null $name = null,
        ?string $icon = 'far fa-flag'
    ): self {
        $name ??= Locales::exists($locale) ? Locales::getName($locale, $locale) : $locale;

        return new self($locale, $name, $icon);
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }
}
