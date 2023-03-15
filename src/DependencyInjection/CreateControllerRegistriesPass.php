<?php

namespace EasyCorp\Bundle\EasyAdminBundle\DependencyInjection;

use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Registry\DashboardControllerRegistry;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Creates the services of the Dashboard and CRUD controller registries. They can't
 * be defined as normal services because they cause circular dependencies.
 * See https://github.com/EasyCorp/EasyAdminBundle/issues/3541.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class CreateControllerRegistriesPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        $this->createDashboardControllerRegistryService($container);
        $this->createCrudControllerRegistryService($container);
    }

    private function createDashboardControllerRegistryService(ContainerBuilder $container): void
    {
        $tag = new TaggedIteratorArgument(tag: EasyAdminExtension::TAG_DASHBOARD_CONTROLLER);
        $dashboardControllersFqcn = array_map(
            static fn (Reference $c) => (string) $c,
            $this->findAndSortTaggedServices($tag, $container)
        );

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
        $tag = new TaggedIteratorArgument(tag: EasyAdminExtension::TAG_CRUD_CONTROLLER);
        $crudControllersFqcn = array_map(
            static fn (Reference $c) => (string) $c,
            $this->findAndSortTaggedServices($tag, $container)
        );

        $container
            ->register(CrudControllerRegistry::class, CrudControllerRegistry::class)
            ->setPublic(false)
            ->setArguments([
                $container->getParameter('kernel.secret'),
                $crudControllersFqcn,
            ]);
    }
}
