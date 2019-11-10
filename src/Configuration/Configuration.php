<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Context\UserMenuContext;
use EasyCorp\Bundle\EasyAdminBundle\Dashboard\DashboardConfig;
use EasyCorp\Bundle\EasyAdminBundle\Menu\MenuBuilderInterface;

final class Configuration
{
    private $dashboardConfig;
    private $assets;
    private $userMenuConfig;
    private $crudConfig;
    private $pageConfig;
    private $menuBuilder;
    private $locale;

    public function __construct(DashboardConfig $dashboardConfig, AssetCollection $assets, UserMenuContext $userMenuConfig, ?CrudConfig $crudConfig, ?CrudPageConfigInterface $pageConfig, MenuBuilderInterface $menuBuilder, string $locale)
    {
        $this->dashboardConfig = $dashboardConfig;
        $this->assets = $assets;
        $this->userMenuConfig = $userMenuConfig;
        $this->crudConfig = $crudConfig;
        $this->pageConfig = $pageConfig;
        $this->menuBuilder = $menuBuilder;
        $this->locale = $locale;
    }

    public function getFaviconPath(): string
    {
        return $this->dashboardConfig->getFaviconPath();
    }

    public function getCssAssets(): array
    {
        return $this->assets->getCssAssets();
    }

    public function getJsAssets(): array
    {
        return $this->assets->getJsAssets();
    }

    public function getHeadHtmlContents(): array
    {
        return $this->assets->getHeadContents();
    }

    public function getBodyHtmlContents(): array
    {
        return $this->assets->getBodyContents();
    }

    public function getSiteName(): string
    {
        return $this->dashboardConfig->getSiteName();
    }

    public function getTranslationDomain(): string
    {
        return $this->dashboardConfig->getTranslationDomain();
    }

    public function getTextDirection(): string
    {
        if (null !== $textDirection = $this->dashboardConfig->getTextDirection()) {
            return $textDirection;
        }

        $localePrefix = strtolower(substr($this->locale, 0, 2));

        return \in_array($localePrefix, ['ar', 'fa', 'he']) ? 'rtl' : 'ltr';
    }

    public function getUserMenu(): UserMenuContext
    {
        return $this->userMenuConfig;
    }

    public function getPageTitle(): ?string
    {
        if (null === $this->pageConfig) {
            return null;
        }

        return $this->pageConfig->getTitle();
    }

    public function getPageHelp(): ?string
    {
        if (null === $this->pageConfig) {
            return null;
        }

        return $this->pageConfig->getHelp();
    }

    public function getTemplate(string $name): string
    {
        if (null !== $this->crudConfig && null !== $templatePath = $this->crudConfig->getCustomTemplate($name)) {
            return $templatePath;
        }

        return $this->dashboardConfig->getCustomTemplate($name)
            ?? $this->dashboardConfig->getDefaultTemplate($name);
    }
}
