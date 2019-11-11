<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Context\ActionContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\AssetContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\CrudContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\CrudPageContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\DashboardContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\UserMenuContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\MenuBuilderInterface;

final class Configuration
{
    private $dashboardContext;
    private $assets;
    private $userMenuContext;
    private $crudContext;
    private $pageContext;
    private $menuBuilder;
    private $locale;

    public function __construct(DashboardContext $dashboardContext, AssetContext $assets, UserMenuContext $userMenuContext, ?CrudContext $crudContext, ?CrudPageContext $pageContext, MenuBuilderInterface $menuBuilder, string $locale)
    {
        $this->dashboardContext = $dashboardContext;
        $this->assets = $assets;
        $this->userMenuContext = $userMenuContext;
        $this->crudContext = $crudContext;
        $this->pageContext = $pageContext;
        $this->menuBuilder = $menuBuilder;
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

    public function getUserMenu(): UserMenuContext
    {
        return $this->userMenuContext;
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

    /**
     * @return ActionContext[]
     */
    public function getPageActions(): array
    {
        if (null === $this->pageContext) {
            return [];
        }

        $actions = [];
        $actionConfigs = $this->pageContext->getActions();
        foreach ($actionConfigs as $actionConfig) {
            $actions[] = $actionConfig->getAsValueObject();
        }

        return $actions;
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
