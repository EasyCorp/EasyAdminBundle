<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class I18nDto
{
    private readonly string $language;

    /**
     * @param array<string, mixed>      $translationParameters
     * @param array<string, mixed>|null $plainTextTranslationParameters
     */
    public function __construct(
        private readonly string $locale,
        private readonly string $textDirection,
        private readonly string $translationDomain,
        private readonly array $translationParameters,
        private readonly ?array $plainTextTranslationParameters = null,
    ) {
        // returns 'en' for 'en', 'en-US', 'en_US', 'en-US.UTF-8', 'en_US.UTF-8', etc.
        $this->language = explode('-', str_replace('_', '-', $this->locale))[0];
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

    /**
     * @return array<string, mixed>
     */
    public function getTranslationParameters(): array
    {
        return $this->translationParameters;
    }

    /**
     * The values of the entity placeholders (%entity_as_string%, %entity_id%, etc.) in
     * getTranslationParameters() are HTML-escaped because page titles and labels are
     * rendered as HTML. These parameters contain the original values instead, for the
     * contents that are displayed as plain text (or escaped later by Twig).
     *
     * @internal
     *
     * @return array<string, mixed>
     */
    public function getPlainTextTranslationParameters(): array
    {
        return $this->plainTextTranslationParameters ?? $this->translationParameters;
    }
}
