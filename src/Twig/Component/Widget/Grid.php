<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Widget;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\WidgetPlacement;
use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetSectionDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\WidgetFactory;

/**
 * Renders the widgets returned by the configureWidgets() method of the current
 * dashboard: all of them (grouped in their sections), the widgets of a single
 * section or the widgets placed in the toolbar of the page.
 */
class Grid
{
    /**
     * The name of the only section to render (the one set with its setName() method or, by default, the slug of its label); when null, it renders all sections.
     */
    public ?string $section = null;
    /**
     * Which widgets to render: 'grid' (the widgets of the main content of the page) or 'toolbar' (the widgets displayed next to the page title).
     */
    public WidgetPlacement $placement = WidgetPlacement::Grid;

    public function __construct(
        private readonly WidgetFactory $widgetFactory,
    ) {
    }

    public function mount(?string $section = null, string|WidgetPlacement $placement = WidgetPlacement::Grid): void
    {
        $this->section = $section;
        $this->placement = \is_string($placement) ? (WidgetPlacement::tryFrom($placement) ?? WidgetPlacement::Grid) : $placement;
    }

    public function isToolbar(): bool
    {
        return WidgetPlacement::Toolbar === $this->placement;
    }

    /**
     * @return WidgetSectionDto[]
     */
    public function getSections(): array
    {
        $widgets = $this->widgetFactory->getWidgets();

        if (null === $this->section) {
            return $widgets->getSections();
        }

        // a section that doesn't exist is the same as a section hidden to the
        // current user: sections depend on permissions, so it can't be an error
        return null === ($section = $widgets->getSection($this->section)) ? [] : [$section];
    }

    /**
     * @return WidgetDto[]
     */
    public function getToolbarWidgets(): array
    {
        return $this->widgetFactory->getWidgets()->getToolbarWidgets();
    }

    /**
     * Whether the dashboard has no widgets at all for the current user.
     */
    public function isEmpty(): bool
    {
        return null === $this->section && $this->widgetFactory->getWidgets()->isEmpty();
    }
}
