<?php

namespace EasyCorp\Bundle\EasyAdminBundle\DependencyInjection;

use EasyCorp\Bundle\EasyAdminBundle\Router\AdminRouteGenerator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Indra Gunawan <hello@indra.my.id>
 */
class AdminRoutePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $dashboardControllersFqcn = array_map(
            static function ($serviceId) use ($container) {
                return $container->getDefinition($serviceId)->getClass();
            },
            array_keys($container->findTaggedServiceIds(EasyAdminExtension::TAG_DASHBOARD_CONTROLLER, true))
        );

        $crudControllersFqcn = array_map(
            static function ($serviceId) use ($container) {
                return $container->getDefinition($serviceId)->getClass();
            },
            array_keys($container->findTaggedServiceIds(EasyAdminExtension::TAG_CRUD_CONTROLLER, true))
        );

        $container->getDefinition(AdminRouteGenerator::class)
            ->setArgument(0, $dashboardControllersFqcn)
            ->setArgument(1, $crudControllersFqcn)
        ;
    }
}
