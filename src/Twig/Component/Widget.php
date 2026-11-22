<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig\Component;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\WidgetPlacement;
use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\WidgetFactory;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetRenderer;

/**
 * Renders one of the widgets returned by the configureWidgets() method of the
 * current dashboard. It renders nothing when the current user can't see the widget.
 */
class Widget
{
    // 'ea-widget-<type>' must not be one of the CSS classes that EasyAdmin uses for the layout of widgets
    private const RESERVED_TYPES_PATTERN = '/^(body|card|cardless|lazy|loading|toolbar|list-items?|((cols|rows|section)(-.*)?))$/';

    /**
     * The name of the widget: the one set with its setName() method or, by default, the slug of its label.
     */
    public string $name;
    /**
     * The level of the heading used for the title of the widget, from 2 to 6.
     */
    public int $headingLevel = 3;
    private ?WidgetDto $widget = null;
    private ?string $body = null;
    private ?string $fragmentUrl = null;
    private bool $includesScript = false;

    public function __construct(
        private readonly WidgetFactory $widgetFactory,
        private readonly WidgetRenderer $widgetRenderer,
    ) {
    }

    public function mount(string $name, int|string $headingLevel = 3): void
    {
        $this->name = $name;
        $this->headingLevel = max(2, min(6, (int) $headingLevel));

        $widgets = $this->widgetFactory->getWidgets();
        if (null === $this->widget = $widgets->get($name)) {
            if ($widgets->isDenied($name)) {
                return;
            }

            throw new \InvalidArgumentException(sprintf('The current dashboard has no widget called "%s". The names of its widgets are: "%s". The name of a widget is the one set with its "setName()" method or, by default, the slug of its label.', $name, implode('", "', array_keys($widgets->all()))));
        }

        $isLazy = $this->widget->isLazy();
        if ($isLazy || null !== $this->widget->getRefreshInterval()) {
            $this->fragmentUrl = $this->widgetRenderer->getFragmentUrl($this->widget);
        }

        // lazy widgets display a placeholder and load their contents later in another request;
        // without a URL to load them, the placeholder would be displayed forever
        if (!$isLazy || null === $this->fragmentUrl) {
            $this->body = $this->widgetRenderer->renderBody($this->widget);

            // failed widgets display a button to load their contents again
            if ($this->widgetRenderer->hasFailed($this->widget)) {
                $this->fragmentUrl ??= $this->widgetRenderer->getFragmentUrl($this->widget);
            }
        }

        $this->includesScript = null !== $this->fragmentUrl && $this->widgetRenderer->shouldIncludeScript();
    }

    public function getWidget(): ?WidgetDto
    {
        return $this->widget;
    }

    /**
     * The HTML contents of the widget, or null when they are loaded later.
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    public function getErrorBody(): string
    {
        return null === $this->widget ? '' : $this->widgetRenderer->renderError($this->widget);
    }

    public function getFragmentUrl(): ?string
    {
        return $this->fragmentUrl;
    }

    /**
     * Whether this widget must print the tag of the script that loads widgets
     * (only the first widget of the page that needs the script prints it).
     */
    public function includesScript(): bool
    {
        return $this->includesScript;
    }

    public function isInToolbar(): bool
    {
        return WidgetPlacement::Toolbar === $this->widget?->getPlacement();
    }

    public function hasCard(): bool
    {
        return null !== $this->widget && !$this->widget->isRenderedWithoutCard() && !$this->isInToolbar();
    }

    public function getDefaultCssClass(): string
    {
        if (null === $this->widget) {
            return '';
        }

        $type = $this->widget->getType();
        if (1 === preg_match(self::RESERVED_TYPES_PATTERN, $type)) {
            $type .= '-widget';
        }

        $cssClasses = ['ea-widget', 'ea-widget-'.$type];

        if ($this->isInToolbar()) {
            $cssClasses[] = 'ea-widget-toolbar';
        } else {
            $cssClasses[] = 'ea-widget-cols-'.$this->widget->getColumns();
            if (null !== $rows = $this->widget->getRows()) {
                $cssClasses[] = 'ea-widget-rows-'.$rows;
            }
        }

        if (!$this->hasCard()) {
            $cssClasses[] = 'ea-widget-cardless';
        }

        if ('' !== $customCssClass = $this->widget->getCssClass()) {
            $cssClasses[] = $customCssClass;
        }

        return implode(' ', $cssClasses);
    }

    /**
     * @return array<string, string>
     */
    public function getDefaultAttributes(): array
    {
        if (null === $this->widget) {
            return [];
        }

        $attributes = [
            'class' => $this->getDefaultCssClass(),
            'data-ea-widget' => $this->name,
        ];

        if (null !== $this->fragmentUrl) {
            $attributes['data-ea-widget-url'] = $this->fragmentUrl;

            if (null === $this->body) {
                $attributes['data-ea-widget-lazy'] = 'true';
            }
            if (null !== $refreshInterval = $this->widget->getRefreshInterval()) {
                $attributes['data-ea-widget-refresh'] = (string) $refreshInterval;
            }
        }

        return $attributes;
    }
}
