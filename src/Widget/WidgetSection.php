<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Widget;

use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetSectionDto;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Starts a new group of widgets in the dashboard. All the widgets yielded
 * after it (and before the next section) are displayed in their own grid,
 * below the optional title of the section.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class WidgetSection
{
    private WidgetSectionDto $dto;

    private function __construct()
    {
        $this->dto = new WidgetSectionDto();
    }

    public function __clone(): void
    {
        $this->dto = clone $this->dto;
    }

    /**
     * @param string|null $icon The name of the icon displayed before the label (e.g. 'fa fa-chart-line')
     */
    public static function new(TranslatableInterface|string|null $label = null, ?string $icon = null): self
    {
        $section = new self();
        $section->dto->setLabel($label);
        $section->dto->setIcon($icon);

        return $section;
    }

    /**
     * The name identifies the section in templates (<twig:ea:Widget:Grid section="..." />).
     * If not set, it's the slug of the label (e.g. 'sales' for 'Sales').
     */
    public function setName(string $name): self
    {
        if (1 !== preg_match('/^[a-zA-Z0-9_-]+$/D', $name)) {
            throw new \InvalidArgumentException(sprintf('The name of a widget section can only contain letters, numbers, dashes and underscores ("%s" given).', $name));
        }

        $this->dto->setName($name);

        return $this;
    }

    public function setHelp(TranslatableInterface|string $help): self
    {
        $this->dto->setHelp($help);

        return $this;
    }

    /**
     * The section and all its widgets are hidden, and the data of those widgets
     * is not computed, for users without this permission.
     */
    public function setPermission(string|Expression $permission): self
    {
        $this->dto->setPermission($permission);

        return $this;
    }

    public function addCssClass(string $cssClass): self
    {
        $this->dto->setCssClass($this->dto->getCssClass().' '.$cssClass);

        return $this;
    }

    public function setCssClass(string $cssClass): self
    {
        $this->dto->setCssClass($cssClass);

        return $this;
    }

    public function getAsDto(): WidgetSectionDto
    {
        return $this->dto;
    }
}
