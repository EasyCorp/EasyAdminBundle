<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Dto\LocaleDto;
use Symfony\Component\Intl\Locales;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class Locale
{
    private LocaleDto $dto;

    private function __construct(LocaleDto $localeDto)
    {
        $this->dto = $localeDto;
    }

    public function __toString()
    {
        return $this->dto->getName();
    }

    public static function new(string $locale, ?string $label = null, ?string $icon = null): self
    {
        if (!Locales::exists($locale)) {
            throw new \InvalidArgumentException(sprintf('The given value "%s" is not a valid locale code or it is not supported by the Symfony Intl component.', $locale));
        }

        $label ??= Locales::getName($locale, $locale);

        $dto = new LocaleDto($locale, $label, $icon);

        return new self($dto);
    }

    public function getAsDto(): LocaleDto
    {
        return $this->dto;
    }
}
