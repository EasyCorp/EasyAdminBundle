<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\CommonFormatConfigTrait;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\CommonTemplateConfigTrait;
use EasyCorp\Bundle\EasyAdminBundle\Context\DashboardContext;

/**
 * Holds the configuration options of the dashboard.
 */
final class DashboardConfig
{
    use CommonFormatConfigTrait;
    use CommonTemplateConfigTrait;

    private $faviconPath = 'favicon.ico';
    private $siteName = 'EasyAdmin';
    private $translationDomain = 'messages';
    private $textDirection;
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

    public function setDisabledActions(array $disabledActions): self
    {
        $this->disabledActions = $disabledActions;

        return $this;
    }

    public function getAsValueObject(): DashboardContext
    {
        return new DashboardContext($this->faviconPath, $this->siteName, $this->translationDomain, $this->textDirection, $this->disabledActions, $this->dateFormat, $this->timeFormat, $this->dateTimeFormat, $this->dateIntervalFormat, $this->numberFormat, $this->customTemplates, $this->defaultTemplates);
    }
}
