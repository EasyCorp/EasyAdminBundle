<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class DashboardDto
{
    use PropertyModifierTrait;

    private $routeName;
    private $faviconPath;
    private $siteName;
    private $translationDomain;
    private $textDirection;
    private $disabledActions;
    private $dateFormat;
    private $timeFormat;
    private $dateTimeFormat;
    private $dateIntervalFormat;
    private $numberFormat;
    private $formThemes;

    public function __construct(string $faviconPath, string $siteName, string $translationDomain, ?string $textDirection, array $disabledActions, string $dateFormat, string $timeFormat, string $dateTimeFormat, string $dateIntervalFormat, ?string $numberFormat, array $formThemes)
    {
        $this->faviconPath = $faviconPath;
        $this->siteName = $siteName;
        $this->translationDomain = $translationDomain;
        $this->textDirection = $textDirection;
        $this->disabledActions = $disabledActions;
        $this->dateFormat = $dateFormat;
        $this->timeFormat = $timeFormat;
        $this->dateTimeFormat = $dateTimeFormat;
        $this->dateIntervalFormat = $dateIntervalFormat;
        $this->numberFormat = $numberFormat;
        $this->formThemes = $formThemes;
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function getFaviconPath(): string
    {
        return $this->faviconPath;
    }

    public function getSiteName(): string
    {
        return $this->siteName;
    }

    public function getTranslationDomain(): string
    {
        return $this->translationDomain;
    }

    public function getTextDirection(): ?string
    {
        return $this->textDirection;
    }

    public function getDisabledActions(): array
    {
        return $this->disabledActions;
    }

    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    public function getTimeFormat(): string
    {
        return $this->timeFormat;
    }

    public function getDateTimeFormat(): string
    {
        return $this->dateTimeFormat;
    }

    public function getDateIntervalFormat(): string
    {
        return $this->dateIntervalFormat;
    }

    public function getNumberFormat(): ?string
    {
        return $this->numberFormat;
    }

    public function getFormThemes(): array
    {
        return $this->formThemes;
    }
}
