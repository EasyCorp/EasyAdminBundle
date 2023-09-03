<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Dto\LocaleDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\LocaleDtoInterface;
use Symfony\Component\Intl\Locales;

final class Locale implements LocaleInterface
{
    private LocaleDto $dto;

    private function __construct(LocaleDtoInterface $localeDto)
    {
        $this->dto = $localeDto;
    }

    public function __toString()
    {
        return $this->dto->getName();
    }

    public static function new(string $locale, string|null $label = null, ?string $icon = null): self
    {
        if (!Locales::exists($locale)) {
            throw new \InvalidArgumentException(sprintf('The given value "%s" is not a valid locale code or it is not supported by the Symfony Intl component.', $locale));
        }

        $label ??= Locales::getName($locale, $locale);

        $dto = new LocaleDto($locale, $label, $icon);

        return new self($dto);
    }

    public function getAsDto(): LocaleDtoInterface
    {
        return $this->dto;
    }
}
