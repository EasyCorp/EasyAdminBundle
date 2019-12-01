<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class I18nDto
{
    private $locale;
    private $language;
    private $textDirection;
    private $translationParameters;

    public function __construct(string $locale, string $textDirection, array $translationParameters)
    {
        $this->locale = $locale;
        $this->language = strtok($locale, '-_');
        $this->textDirection = $textDirection;
        $this->translationParameters = $translationParameters;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getTextDirection(): string
    {
        return $this->textDirection;
    }

    public function getTransParameters(): array
    {
        return $this->translationParameters;
    }
}
