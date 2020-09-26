<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class I18nDto
{
    private $locale;
    private $language;
    private $textDirection;
    private $translationDomain;
    private $translationParameters;

    public function __construct(string $locale, string $textDirection, string $translationDomain, array $translationParameters)
    {
        $this->locale = $locale;
        $this->language = strtok($locale, '-_');
        $this->textDirection = $textDirection;
        $this->translationDomain = $translationDomain;
        $this->translationParameters = $translationParameters;
    }

    /**
     * Returns the full locale formatted as ICU/Java/Symfony locales (e.g. 'es_ES', 'en_US').
     *
     * @see self::getHtmlLocale() if you need to format locale for HTML 'lang' attribute
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Returns the locale formatted as an IETF BCP 47 language tag, as required
     * by HTML 'lang' attribute (in practice, it replaces underscores by dashes).
     * Example: Symfony locale = 'es_ES'   HTML locale = 'es-ES'.
     *
     * @see self::getLocale() if you need to format locale for Symfony code
     */
    public function getHtmlLocale(): string
    {
        return str_replace('_', '-', $this->locale);
    }

    /**
     * Returns the language part of the locale (e.g. returns 'es' for 'es_ES' and 'zh' for 'zh_Hans_MO').
     */
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
