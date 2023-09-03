<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Config\CrudInterface;

final class DashboardDto implements DashboardDtoInterface
{
    private $routeName;
    private string $faviconPath = 'data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 128 128%22><text y=%221.2em%22 font-size=%2296%22>⬛</text></svg>';
    private string $title = 'EasyAdmin';
    private string $translationDomain = 'messages';
    private $textDirection;
    private string $contentWidth = CrudInterface::LAYOUT_CONTENT_DEFAULT;
    private string $sidebarWidth = CrudInterface::LAYOUT_SIDEBAR_DEFAULT;
    private bool $signedUrls = false;
    private bool $absoluteUrls = true;
    private bool $enableDarkMode = true;
    /** @var LocaleDtoInterface[] */
    private array $locales = [];

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function setRouteName(/* string */ $routeName): void
    {
        if (!\is_string($routeName)) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.0.5',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$routeName',
                __METHOD__,
                '"string"',
                \gettype($routeName)
            );
        }

        $this->routeName = $routeName;
    }

    public function getFaviconPath(): string
    {
        return $this->faviconPath;
    }

    public function setFaviconPath(string $faviconPath): void
    {
        $this->faviconPath = $faviconPath;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTranslationDomain(): string
    {
        return $this->translationDomain;
    }

    public function setTranslationDomain(string $translationDomain): void
    {
        $this->translationDomain = $translationDomain;
    }

    public function getTextDirection(): ?string
    {
        return $this->textDirection;
    }

    public function setTextDirection(/* ?string */ $textDirection): void
    {
        if (!\is_string($textDirection)
            && null !== $textDirection) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.0.5',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$textDirection',
                __METHOD__,
                '"string" or "null"',
                \gettype($textDirection)
            );
        }

        $this->textDirection = $textDirection;
    }

    public function getContentWidth(): string
    {
        return $this->contentWidth;
    }

    public function setContentWidth(string $contentWidth): void
    {
        $this->contentWidth = $contentWidth;
    }

    public function getSidebarWidth(): string
    {
        return $this->sidebarWidth;
    }

    public function setSidebarWidth(string $sidebarWidth): void
    {
        $this->sidebarWidth = $sidebarWidth;
    }

    public function getSignedUrls(): bool
    {
        return $this->signedUrls;
    }

    public function setSignedUrls(bool $signedUrls): DashboardDtoInterface
    {
        $this->signedUrls = $signedUrls;

        return $this;
    }

    public function getAbsoluteUrls(): bool
    {
        return $this->absoluteUrls;
    }

    public function setAbsoluteUrls(bool $absoluteUrls): DashboardDtoInterface
    {
        $this->absoluteUrls = $absoluteUrls;

        return $this;
    }

    public function setEnableDarkMode(bool $enableDarkMode): DashboardDtoInterface
    {
        $this->enableDarkMode = $enableDarkMode;

        return $this;
    }

    public function isDarkModeEnabled(): bool
    {
        return $this->enableDarkMode;
    }

    public function getLocales(): array
    {
        return $this->locales;
    }

    public function setLocales(array $locales): void
    {
        $this->locales = $locales;
    }
}
