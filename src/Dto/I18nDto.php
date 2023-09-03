<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class I18nDto implements I18nDtoInterface
{
    private string $locale;
    private $language;
    private string $textDirection;
    private string $translationDomain;
    private array $translationParameters;

    public function __construct(
        string $locale,
        string $textDirection,
        string $translationDomain,
        array $translationParameters
    ) {
        $this->locale = $locale;
        $this->language = strtok($locale, '-_');
        $this->textDirection = $textDirection;
        $this->translationDomain = $translationDomain;
        $this->translationParameters = $translationParameters;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getHtmlLocale(): string
    {
        return str_replace('_', '-', $this->locale);
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getTextDirection(): string
    {
        return $this->textDirection;
    }

    public function getTranslationDomain(): string
    {
        return $this->translationDomain;
    }

    public function getTranslationParameters(): array
    {
        return $this->translationParameters;
    }
}
