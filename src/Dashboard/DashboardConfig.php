<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dashboard;

/**
 * Holds the configuration options of the dashboard.
 */
final class DashboardConfig
{
    private $faviconPath = 'favicon.ico';
    private $siteName = 'EasyAdmin';
    private $dateFormat = 'Y-m-d';
    private $timeFormat = 'H:i:s';
    private $dateTimeFormat = 'F j, Y H:i';
    private $dateIntervalFormat = '%%y Year(s) %%m Month(s) %%d Day(s)';
    private $numberFormat = '';
    private $translationDomain = 'messages';
    private $disabledActions = [];

    public static function new(): self
    {
        return new self();
    }

    public function setFaviconPath(string $path): self
    {
        $this->faviconPath = $path;

        return $this;
    }

    public function setSiteName(string $name): self
    {
        $this->siteName = $name;

        return $this;
    }

    public function setDateFormat(string $dateFormat): self
    {
        $this->dateFormat = $dateFormat;

        return $this;
    }

    public function setTimeFormat(string $timeFormat): self
    {
        $this->timeFormat = $timeFormat;

        return $this;
    }

    public function setDateTimeFormat(string $dateTimeFormat): self
    {
        $this->dateTimeFormat = $dateTimeFormat;

        return $this;
    }

    public function setDateIntervalFormat(string $dateIntervalFormat): self
    {
        $this->dateIntervalFormat = $dateIntervalFormat;

        return $this;
    }

    public function setNumberFormat(string $numberFormat): self
    {
        $this->numberFormat = $numberFormat;

        return $this;
    }

    public function setTranslationDomain(string $translationDomain): self
    {
        $this->translationDomain = $translationDomain;

        return $this;
    }

    public function setDisabledActions(array $disabledActions): self
    {
        $this->disabledActions = $disabledActions;

        return $this;
    }

    public function getFaviconPath(): string
    {
        return $this->faviconPath;
    }

    public function getSiteName(): string
    {
        return $this->siteName;
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

    public function getNumberFormat(): string
    {
        return $this->numberFormat;
    }

    public function getTranslationDomain(): string
    {
        return $this->translationDomain;
    }

    public function getDisabledActions(): array
    {
        return $this->disabledActions;
    }
}
