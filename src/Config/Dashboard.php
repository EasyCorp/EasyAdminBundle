<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Dto\DashboardDto;

/**
 * Holds the configuration options of the dashboard.
 */
final class Dashboard
{
    private $faviconPath = 'favicon.ico';
    private $title = 'EasyAdmin';
    private $translationDomain = 'messages';
    private $textDirection;

    public static function new(): self
    {
        return new self();
    }

    public function setFaviconPath(string $path): self
    {
        $this->faviconPath = $path;

        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function setTranslationDomain(string $translationDomain): self
    {
        $this->translationDomain = $translationDomain;

        return $this;
    }

    public function setTextDirection(string $direction): self
    {
        if (\in_array($direction, ['ltr', 'rtl'], true)) {
            throw new \InvalidArgumentException(sprintf('The "%s" value given to the textDirection option is not valid. It can only be "ltr" or "rtl"', $direction));
        }

        $this->textDirection = $direction;

        return $this;
    }

    public function getAsDto(): DashboardDto
    {
        return new DashboardDto($this->faviconPath, $this->title, $this->translationDomain, $this->textDirection);
    }
}
