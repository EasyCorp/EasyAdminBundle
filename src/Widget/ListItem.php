<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Widget;

/**
 * Each of the items displayed by a ListWidget.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ListItem
{
    // these URL schemes can run code when the link is clicked (e.g. 'javascript:alert(1)')
    private const DANGEROUS_URL_SCHEMES = ['javascript', 'data', 'vbscript', 'file'];

    private ?string $description = null;
    private string|int|float|null $value = null;
    private ?string $url = null;
    private ?string $icon = null;

    private function __construct(
        private readonly string $title,
    ) {
    }

    public static function new(string $title): self
    {
        return new self($title);
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @param string|int|float|null $value Displayed at the end of the item (e.g. the amount of an order)
     */
    public function setValue(string|int|float|null $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @param string|null $url Makes the title of the item a link to this URL
     */
    public function setUrl(?string $url): self
    {
        if (null !== $url && self::hasDangerousScheme($url)) {
            throw new \InvalidArgumentException(sprintf('The URL of the "%s" list item uses a scheme that is not allowed (%s); use a relative URL or a URL with a scheme like "https:".', $this->title, implode(', ', self::DANGEROUS_URL_SCHEMES)));
        }

        $this->url = $url;

        return $this;
    }

    /**
     * @param string|null $icon The name of the icon displayed before the title (e.g. 'fa fa-user')
     */
    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getValue(): string|int|float|null
    {
        return $this->value;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    private static function hasDangerousScheme(string $url): bool
    {
        // browsers ignore leading whitespace and control bytes, and tabs and new lines
        // anywhere in the URL (e.g. "java\tscript:"), before parsing the scheme
        $normalizedUrl = str_replace(["\t", "\n", "\r"], '', ltrim($url, "\0..\x20"));

        return 1 === preg_match('#^([a-z][a-z0-9+.\-]*):#i', $normalizedUrl, $matches)
            && \in_array(strtolower($matches[1]), self::DANGEROUS_URL_SCHEMES, true);
    }
}
