<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;


/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface DashboardDtoInterface
{
    public function getRouteName(): string;

    public function setRouteName($routeName): void;

    public function getFaviconPath(): string;

    public function setFaviconPath(string $faviconPath): void;

    public function getTitle(): string;

    public function setTitle(string $title): void;

    public function getTranslationDomain(): string;

    public function setTranslationDomain(string $translationDomain): void;

    public function getTextDirection(): ?string;

    public function setTextDirection($textDirection): void;

    public function getContentWidth(): string;

    public function setContentWidth(string $contentWidth): void;

    public function getSidebarWidth(): string;

    public function setSidebarWidth(string $sidebarWidth): void;

    public function getSignedUrls(): bool;

    public function setSignedUrls(bool $signedUrls): DashboardDtoInterface;

    public function getAbsoluteUrls(): bool;

    public function setAbsoluteUrls(bool $absoluteUrls): DashboardDtoInterface;

    public function setEnableDarkMode(bool $enableDarkMode): DashboardDtoInterface;

    public function isDarkModeEnabled(): bool;

    public function getLocales(): array;

    /**
     * @param LocaleDtoInterface[] $locales
     */
    public function setLocales(array $locales): void;
}
