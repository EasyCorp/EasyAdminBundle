<?php

namespace EasyCorp\Bundle\EasyAdminBundle\DependencyInjection;

use EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @deprecated since 5.5.1, use \EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle instead
 */
class EasyAdminExtension
{
    public const TAG_CRUD_CONTROLLER = EasyAdminBundle::TAG_CRUD_CONTROLLER;
    public const TAG_DASHBOARD_CONTROLLER = EasyAdminBundle::TAG_DASHBOARD_CONTROLLER;
    public const TAG_ADMIN_ROUTE_CONTROLLER = EasyAdminBundle::TAG_ADMIN_ROUTE_CONTROLLER;
    public const TAG_FIELD_CONFIGURATOR = EasyAdminBundle::TAG_FIELD_CONFIGURATOR;
    public const TAG_FILTER_CONFIGURATOR = EasyAdminBundle::TAG_FILTER_CONFIGURATOR;
    public const TAG_ACTIONS_EXTENSION = EasyAdminBundle::TAG_ACTIONS_EXTENSION;
}
