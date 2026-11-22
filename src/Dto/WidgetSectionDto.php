<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class WidgetSectionDto
{
    private ?string $name = null;
    private TranslatableInterface|string|null $label = null;
    private ?string $icon = null;
    private TranslatableInterface|string|null $help = null;
    private string|Expression|null $permission = null;
    private string $cssClass = '';
    /** @var WidgetDto[] */
    private array $widgets = [];

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getLabel(): TranslatableInterface|string|null
    {
        return $this->label;
    }

    public function setLabel(TranslatableInterface|string|null $label): void
    {
        $this->label = $label;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): void
    {
        $this->icon = $icon;
    }

    public function getHelp(): TranslatableInterface|string|null
    {
        return $this->help;
    }

    public function setHelp(TranslatableInterface|string|null $help): void
    {
        $this->help = $help;
    }

    public function getPermission(): string|Expression|null
    {
        return $this->permission;
    }

    public function setPermission(string|Expression|null $permission): void
    {
        $this->permission = $permission;
    }

    public function getCssClass(): string
    {
        return $this->cssClass;
    }

    public function setCssClass(string $cssClass): void
    {
        $this->cssClass = trim($cssClass);
    }

    /**
     * @return WidgetDto[]
     */
    public function getWidgets(): array
    {
        return $this->widgets;
    }

    public function addWidget(WidgetDto $widgetDto): void
    {
        $this->widgets[] = $widgetDto;
    }
}
