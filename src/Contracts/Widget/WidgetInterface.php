<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget;

use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;

/**
 * Implemented by all the widgets displayed in dashboards. Custom widgets
 * implement this interface and use WidgetTrait, which provides the options
 * shared by all widgets.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface WidgetInterface
{
    public function getAsDto(): WidgetDto;
}
