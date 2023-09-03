<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;


/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface I18nDtoInterface
{
    /**
     * Returns the full locale formatted as ICU/Java/Symfony locales (e.g. 'es_ES', 'en_US').
     *
     * @see self::getHtmlLocale() if you need to format locale for HTML 'lang' attribute
     */
    public function getLocale(): string;

    /**
     * Returns the locale formatted as an IETF BCP 47 language tag, as required
     * by HTML 'lang' attribute (in practice, it replaces underscores by dashes).
     * Example: Symfony locale = 'es_ES'   HTML locale = 'es-ES'.
     *
     * @see self::getLocale() if you need to format locale for Symfony code
     */
    public function getHtmlLocale(): string;

    /**
     * Returns the language part of the locale (e.g. returns 'es' for 'es_ES' and 'zh' for 'zh_Hans_MO').
     */
    public function getLanguage(): string;

    public function getTextDirection(): string;

    public function getTranslationDomain(): string;

    public function getTranslationParameters(): array;
}
