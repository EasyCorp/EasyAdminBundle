<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget\Fixtures;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget\WidgetConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetContext;

final class CallbackWidgetConfigurator implements WidgetConfiguratorInterface
{
    public int $calls = 0;

    /**
     * @param \Closure(WidgetDto, WidgetContext): void $callback
     */
    public function __construct(
        private readonly \Closure $callback,
        private readonly ?string $supportedWidgetName = null,
    ) {
    }

    public function supports(WidgetDto $widget): bool
    {
        return null === $this->supportedWidgetName || $this->supportedWidgetName === $widget->getName();
    }

    public function configure(WidgetDto $widget, WidgetContext $context): void
    {
        ++$this->calls;
        ($this->callback)($widget, $context);
    }
}
