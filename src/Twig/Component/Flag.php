<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig\Component;

use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\Radius;
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
    /**
     * The border radius of the flag: 'none', 'sm', 'md', 'lg' or 'full'; when null, it uses the value of the --flag-radius CSS variable (2px by default).
     */
    public ?Radius $radius = null;
    /**
     * Whether to display a subtle outline around the flag (useful for flags with white areas, which blend into light backgrounds).
     */
    public bool $bordered = true;

    private ?bool $flagExists = null;
    private ?string $flagFilePath = null;

    /** @var array<string, string> */
    private static array $svgCache = [];

    public function mount(string|Radius|null $radius = null): void
    {
        if ($radius instanceof Radius || null === $radius) {
            $this->radius = $radius;
        } else {
            $this->radius = Radius::tryFrom($radius);
        }
    }

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

        return str_replace(
            ['class="country-flag"', '__HEIGHT__', '__COUNTRY_NAME__'],
            [$this->getSvgClassAndStyleAttributes(), (string) $this->height, $escapedCountryName],
            $content
        );
    }

    public function getFallbackSvg(): string
    {
        $escapedCountryCode = htmlspecialchars($this->countryCode, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');

        return sprintf('<svg xmlns="http://www.w3.org/2000/svg" %s height="%d" viewBox="0 0 25 17"><title>You are not seeing a country flag here because the "%s.svg" file associated to given the "%s" country code does not exist in the assets/icons/flags/ directory of EasyAdmin.</title><rect width="100%%" height="%d" fill="#ff0000"/></svg>', $this->getSvgClassAndStyleAttributes(), $this->height, $escapedCountryCode, $escapedCountryCode, $this->height);
    }

    /**
     * The styles are inlined (instead of defined in a CSS file) so the flag looks
     * the same in any application, even outside EasyAdmin, without loading any CSS.
     * Each value reads a CSS variable first, so it can be customized from any
     * ancestor element; the fallback values work without any EasyAdmin CSS.
     */
    public function getSvgStyle(): string
    {
        $styles = [];

        // SVG elements have no border radius by default, so 'none' needs no style
        $borderRadius = match ($this->radius) {
            null => 'var(--flag-radius, 2px)',
            Radius::None => null,
            Radius::Small => 'var(--border-radius-sm, 2px)',
            Radius::Medium => 'var(--border-radius, 4px)',
            Radius::Large => 'var(--border-radius-lg, 8px)',
            Radius::Full => 'var(--border-radius-full, 50rem)',
        };

        if (null !== $borderRadius) {
            $styles[] = 'border-radius: '.$borderRadius;
        }

        if ($this->bordered) {
            // currentColor makes the default outline visible on both light and dark backgrounds
            $styles[] = 'outline: 1px solid var(--flag-outline-color, color-mix(in srgb, currentColor 20%, transparent))';
            $styles[] = 'outline-offset: var(--flag-outline-offset, -1px)';
        }

        return implode('; ', $styles);
    }

    private function getSvgClassAndStyleAttributes(): string
    {
        $style = $this->getSvgStyle();

        return '' === $style ? 'class="country-flag"' : sprintf('class="country-flag" style="%s"', $style);
    }

    private function getFlagFilePath(): string
    {
        return $this->flagFilePath ??= sprintf('%s/../../../assets/icons/flags/%s.svg', __DIR__, $this->countryCode);
    }
}
