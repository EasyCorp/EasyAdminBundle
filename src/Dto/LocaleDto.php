<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class LocaleDto implements LocaleDtoInterface
{
    public function __construct(
        private string $locale,
        private string $name,
        private ?string $icon = null
    ) {
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
