<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Config;


use EasyCorp\Bundle\EasyAdminBundle\Dto\DashboardDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\DashboardDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface DashboardInterface
{
    public function setFaviconPath(string $path): DashboardInterface;

    public function setTitle(string $title): DashboardInterface;

    public function setTranslationDomain(string $translationDomain): DashboardInterface;

    public function setTextDirection(string $direction): DashboardInterface;

    public function renderContentMaximized(bool $maximized = true): DashboardInterface;

    public function renderSidebarMinimized(bool $minimized = true): DashboardInterface;

    public function disableUrlSignatures(bool $disableSignatures = true
    ): DashboardInterface;

    public function generateRelativeUrls(bool $relativeUrls = true): DashboardInterface;

    public function disableDarkMode(bool $disableDarkMode = true): DashboardInterface;

    public function setLocales(array $locales): DashboardInterface;

    public function getAsDto(): DashboardDtoInterface;
}
