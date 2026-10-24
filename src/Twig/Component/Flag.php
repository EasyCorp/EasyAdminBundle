<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig\Component;

use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Exception\MissingResourceException;

class Flag
{
    /**
     * The two-letter ISO 3166-1 alpha-2 code of the country (e.g. JP).
     */
    public string $countryCode;
    /**
     * The height of the flag in pixels (the width is adjusted automatically).
     */
    public int $height = 17;
    /**
     * Whether to display the name of the country next to the flag.
     */
    public bool $showName = false;
    /**
     * A custom name to display instead of the country name provided by the Intl component.
     */
    public ?string $countryName = null;

    private ?bool $flagExists = null;
    private ?string $flagFilePath = null;

    /** @var array<string, string> */
    private static array $svgCache = [];

    public function getCountryName(): string
    {
        if (null !== $this->countryName) {
            return $this->countryName;
        }

        try {
            return Countries::getName($this->countryCode);
        } catch (MissingResourceException) {
            return '';
        }
    }

    public function flagExists(): bool
    {
        // checking the country code first is essential to prevent path traversal attacks
        return $this->flagExists ??= Countries::exists($this->countryCode) && is_file($this->getFlagFilePath());
    }

    public function getFlagAsSvg(): string
    {
        if (!$this->flagExists()) {
            return $this->getFallbackSvg();
        }

        // the same flag can be rendered many times per page (e.g. once per row on
        // CRUD index pages), so cache the SVG file contents to avoid reading the
        // same files from disk repeatedly; the raw contents keep the placeholders,
        // which are replaced per render because they vary per component instance
        $content = self::$svgCache[$this->countryCode] ??= file_get_contents($this->getFlagFilePath());
        $escapedCountryName = htmlspecialchars($this->getCountryName(), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');

        return str_replace(['__HEIGHT__', '__COUNTRY_NAME__'], [(string) $this->height, $escapedCountryName], $content);
    }

    public function getFallbackSvg(): string
    {
        $escapedCountryCode = htmlspecialchars($this->countryCode, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');

        return sprintf('<svg xmlns="http://www.w3.org/2000/svg" class="country-flag" height="%d" viewBox="0 0 25 17"><title>You are not seeing a country flag here because the "%s.svg" file associated to given the "%s" country code does not exist in the assets/icons/flags/ directory of EasyAdmin.</title><rect width="100%%" height="%d" fill="#ff0000"/></svg>', $this->height, $escapedCountryCode, $escapedCountryCode, $this->height);
    }

    private function getFlagFilePath(): string
    {
        return $this->flagFilePath ??= sprintf('%s/../../../assets/icons/flags/%s.svg', __DIR__, $this->countryCode);
    }
}
