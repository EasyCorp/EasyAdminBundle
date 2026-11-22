<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget\Fixtures;

/**
 * A dashboard that defines widgets but not the renderWidget() action of AbstractDashboardController.
 */
class WidgetsWithoutRenderDashboardController extends InterfaceOnlyDashboardController
{
    /**
     * @param iterable<mixed> $widgets
     */
    public function __construct(private readonly iterable $widgets = [])
    {
    }

    /**
     * @return iterable<mixed>
     */
    public function configureWidgets(): iterable
    {
        return $this->widgets;
    }
}
