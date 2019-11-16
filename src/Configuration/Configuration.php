<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudPageDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\DashboardDto;

final class Configuration
{
    private $dashboardDto;
    private $assetDto;
    private $crudDto;
    private $crudPageDto;
    private $locale;

    public function __construct(DashboardDto $dashboardDto, AssetDto $assetDto, ?CrudDto $crudDto, ?CrudPageDto $crudPageDto, string $locale)
    {
        $this->dashboardDto = $dashboardDto;
        $this->assetDto = $assetDto;
        $this->crudDto = $crudDto;
        $this->crudPageDto = $crudPageDto;
        $this->locale = $locale;
    }

    public function getFaviconPath(): string
    {
        return $this->dashboardDto->getFaviconPath();
    }

    public function getAssets(): AssetDto
    {
        return $this->assetDto;
    }

    public function getSiteName(): string
    {
        return $this->dashboardDto->getSiteName();
    }

    public function getTranslationDomain(): string
    {
        return $this->dashboardDto->getTranslationDomain();
    }

    public function getTextDirection(): string
    {
        if (null !== $textDirection = $this->dashboardDto->getTextDirection()) {
            return $textDirection;
        }

        $localePrefix = strtolower(substr($this->locale, 0, 2));

        return \in_array($localePrefix, ['ar', 'fa', 'he']) ? 'rtl' : 'ltr';
    }

    public function getPageTitle(): ?string
    {
        if (null === $this->crudPageDto) {
            return null;
        }

        return $this->crudPageDto->getTitle();
    }

    public function getPageHelp(): ?string
    {
        if (null === $this->crudPageDto) {
            return null;
        }

        return $this->crudPageDto->getHelp();
    }

    public function getTemplate(string $name): string
    {
        if (null !== $this->crudDto && null !== $templatePath = $this->crudDto->getCustomTemplate($name)) {
            return $templatePath;
        }

        return $this->dashboardDto->getCustomTemplate($name)
            ?? $this->dashboardDto->getDefaultTemplate($name);
    }
}
