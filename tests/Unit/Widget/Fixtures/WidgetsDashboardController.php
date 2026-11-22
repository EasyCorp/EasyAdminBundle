<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget\Fixtures;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

class WidgetsDashboardController extends AbstractDashboardController
{
    // static because the controller resolver creates a new instance of the controller each time
    public static int $configureWidgetsCalls = 0;

    /**
     * @param iterable<mixed> $widgets
     */
    public function __construct(private readonly iterable $widgets = [])
    {
    }

    public function configureWidgets(): iterable
    {
        ++self::$configureWidgetsCalls;

        return $this->widgets;
    }
}
