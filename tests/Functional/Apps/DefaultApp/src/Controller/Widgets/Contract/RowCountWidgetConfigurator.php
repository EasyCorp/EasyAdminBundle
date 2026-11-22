<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets\Contract;

use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget\WidgetConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetContext;

final class RowCountWidgetConfigurator implements WidgetConfiguratorInterface
{
    // the test app doesn't run in debug mode, so this tells which widgets were configured and how many times
    /** @var array<string, int> */
    public static array $callsPerWidget = [];

    public function __construct(
        private readonly ManagerRegistry $doctrine,
    ) {
    }

    public function supports(WidgetDto $widget): bool
    {
        return RowCountWidget::class === $widget->getWidgetFqcn();
    }

    public function configure(WidgetDto $widget, WidgetContext $context): void
    {
        $name = (string) $widget->getName();
        self::$callsPerWidget[$name] = (self::$callsPerWidget[$name] ?? 0) + 1;

        $widget->setData(RowCountWidget::DATA_COUNT, $this->doctrine->getRepository($widget->getCustomOption(RowCountWidget::OPTION_ENTITY))->count([]));
        $widget->setCustomOption('range', $context->getAttribute('range'));
        // the options used outside the contents of the widget are already applied, so this change is ignored
        $widget->setLabel('Label changed by the configurator');
    }
}
