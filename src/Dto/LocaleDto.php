<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
final class LocaleDto
{
    public function __construct(
        private readonly string $locale,
        private readonly string $name,
        private readonly ?string $icon = null,
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
