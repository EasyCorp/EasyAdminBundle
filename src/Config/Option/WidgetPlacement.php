<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config\Option;

enum WidgetPlacement: string
{
    /** In the grid of widgets displayed in the main content of the dashboard */
    case Grid = 'grid';
    /** Next to the title of the dashboard page (e.g. a selector that changes what all widgets display) */
    case Toolbar = 'toolbar';
}
