<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget;

use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetContext;

/**
 * Prepares the contents of widgets using services (e.g. to get their data from
 * a repository). Configurators run right before rendering the contents of a
 * widget, so they don't run for widgets that the user can't see, for cached
 * contents or for the placeholders of lazy widgets.
 *
 * They can change what the template of the widget displays (data entries,
 * custom options and the template itself). The rest of the options (name,
 * label, columns, permission, assets, etc.) are already applied when they run.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface WidgetConfiguratorInterface
{
    public function supports(WidgetDto $widget): bool;

    public function configure(WidgetDto $widget, WidgetContext $context): void;
}
