<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Collection;

use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetSectionDto;

/**
 * The widgets of a dashboard that the current user can see.
 *
 * @internal
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @implements \IteratorAggregate<string, WidgetDto>
 */
final class WidgetCollection implements \Countable, \IteratorAggregate
{
    /** @var array<string, WidgetDto> */
    private array $widgets = [];

    /**
     * @param WidgetSectionDto[] $sections       the sections with at least one widget, in the order they were configured
     * @param WidgetDto[]        $toolbarWidgets
     * @param string[]           $deniedNames    the names of the widgets hidden because of their permission
     */
    public function __construct(
        private readonly array $sections = [],
        private readonly array $toolbarWidgets = [],
        private readonly array $deniedNames = [],
    ) {
        foreach ($toolbarWidgets as $widget) {
            $this->widgets[(string) $widget->getName()] = $widget;
        }
        foreach ($sections as $section) {
            foreach ($section->getWidgets() as $widget) {
                $this->widgets[(string) $widget->getName()] = $widget;
            }
        }
    }

    /**
     * @return array<string, WidgetDto>
     */
    public function all(): array
    {
        return $this->widgets;
    }

    public function get(string $name): ?WidgetDto
    {
        return $this->widgets[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->widgets[$name]);
    }

    /**
     * Whether a widget with this name exists but the current user can't see it.
     */
    public function isDenied(string $name): bool
    {
        return \in_array($name, $this->deniedNames, true);
    }

    /**
     * @return WidgetSectionDto[]
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    public function getSection(string $name): ?WidgetSectionDto
    {
        foreach ($this->sections as $section) {
            if ($name === $section->getName()) {
                return $section;
            }
        }

        return null;
    }

    /**
     * @return WidgetDto[]
     */
    public function getToolbarWidgets(): array
    {
        return $this->toolbarWidgets;
    }

    public function getAssets(): AssetsDto
    {
        $assets = new AssetsDto();
        foreach ($this->widgets as $widget) {
            $assets->mergeWith($widget->getAssets());
        }

        return $assets;
    }

    public function isEmpty(): bool
    {
        return [] === $this->widgets;
    }

    public function count(): int
    {
        return \count($this->widgets);
    }

    /**
     * @return \ArrayIterator<string, WidgetDto>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->widgets);
    }
}
