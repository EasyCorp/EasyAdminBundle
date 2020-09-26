<?php

namespace EasyCorp\Bundle\EasyAdminBundle\DependencyInjection;

use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Registry\DashboardControllerRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Creates the services of the Dashboard and CRUD controller registries. They can't
 * be defined as normal services because they cause circular dependencies.
 * See https://github.com/EasyCorp/EasyAdminBundle/issues/3541.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class CreateControllerRegistriesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $this->createDashboardControllerRegistryService($container);
        $this->createCrudControllerRegistryService($container);
    }

    private function createDashboardControllerRegistryService(ContainerBuilder $container): void
    {
        $dashboardControllersFqcn = array_keys($container->findTaggedServiceIds(EasyAdminExtension::TAG_DASHBOARD_CONTROLLER, true));

        $container
            ->register(DashboardControllerRegistry::class, DashboardControllerRegistry::class)
            ->setPublic(false)
            ->setArguments([
                $container->getParameter('kernel.secret'),
                $container->getParameter('kernel.cache_dir'),
                $dashboardControllersFqcn,
            ]);
    }

    private function createCrudControllerRegistryService(ContainerBuilder $container): void
    {
        $crudControllersFqcn = array_keys($container->findTaggedServiceIds(EasyAdminExtension::TAG_CRUD_CONTROLLER, true));

        $container
            ->register(CrudControllerRegistry::class, CrudControllerRegistry::class)
            ->setPublic(false)
            ->setArguments([
                $container->getParameter('kernel.secret'),
                $crudControllersFqcn,
            ]);
    }
}
