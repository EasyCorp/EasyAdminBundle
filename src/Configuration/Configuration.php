<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Context\AssetContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\CrudContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\CrudPageContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\DashboardContext;

final class Configuration
{
    private $dashboardContext;
    private $assets;
    private $crudContext;
    private $pageContext;
    private $locale;

    public function __construct(DashboardContext $dashboardContext, AssetContext $assets, ?CrudContext $crudContext, ?CrudPageContext $pageContext, string $locale)
    {
        $this->dashboardContext = $dashboardContext;
        $this->assets = $assets;
        $this->crudContext = $crudContext;
        $this->pageContext = $pageContext;
        $this->locale = $locale;
    }

    public function getFaviconPath(): string
    {
        return $this->dashboardContext->getFaviconPath();
    }

    public function getAssets(): AssetContext
    {
        return $this->assets;
    }

    public function getSiteName(): string
    {
        return $this->dashboardContext->getSiteName();
    }

    public function getTranslationDomain(): string
    {
        return $this->dashboardContext->getTranslationDomain();
    }

    public function getTextDirection(): string
    {
        if (null !== $textDirection = $this->dashboardContext->getTextDirection()) {
            return $textDirection;
        }

        $localePrefix = strtolower(substr($this->locale, 0, 2));

        return \in_array($localePrefix, ['ar', 'fa', 'he']) ? 'rtl' : 'ltr';
    }

    public function getPageTitle(): ?string
    {
        if (null === $this->pageContext) {
            return null;
        }

        return $this->pageContext->getTitle();
    }

    public function getPageHelp(): ?string
    {
        if (null === $this->pageContext) {
            return null;
        }

        return $this->pageContext->getHelp();
    }

    public function getTemplate(string $name): string
    {
        if (null !== $this->crudContext && null !== $templatePath = $this->crudContext->getCustomTemplate($name)) {
            return $templatePath;
        }

        return $this->dashboardContext->getCustomTemplate($name)
            ?? $this->dashboardContext->getDefaultTemplate($name);
    }
}
