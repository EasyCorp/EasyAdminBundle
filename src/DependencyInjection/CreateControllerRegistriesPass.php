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

        $controllerFqcnToContextIdMap = [];
        foreach ($dashboardControllersFqcn as $controllerFqcn) {
            $controllerFqcnToContextIdMap[$controllerFqcn] = substr(sha1($container->getParameter('kernel.secret').$controllerFqcn), 0, 7);
        }

        $container
            ->register(DashboardControllerRegistry::class, DashboardControllerRegistry::class)
            ->setPublic(false)
            ->setArguments([
                $container->getParameter('kernel.cache_dir'),
                $controllerFqcnToContextIdMap,
                array_flip($controllerFqcnToContextIdMap),
            ]);
    }

    private function createCrudControllerRegistryService(ContainerBuilder $container): void
    {
        $tag = new TaggedIteratorArgument(tag: EasyAdminExtension::TAG_CRUD_CONTROLLER);
        $crudControllersFqcn = array_map(
            static fn (Reference $c) => (string) $c,
            $this->findAndSortTaggedServices($tag, $container)
        );

        $crudFqcnToEntityFqcnMap = $crudFqcnToCrudIdMap = [];

        foreach ($crudControllersFqcn as $controllerFqcn) {
            $crudFqcnToEntityFqcnMap[$controllerFqcn] = $controllerFqcn::getEntityFqcn();
            $crudFqcnToCrudIdMap[$controllerFqcn] = substr(sha1($container->getParameter('kernel.secret').$controllerFqcn), 0, 7);
        }

        $container
            ->register(CrudControllerRegistry::class, CrudControllerRegistry::class)
            ->setPublic(false)
            ->setArguments([
                $crudFqcnToEntityFqcnMap,
                $crudFqcnToCrudIdMap,
                // more than one controller can manage the same entity, so this map will
                // only contain the last controller associated to that repeated entity. That's why
                // several methods in other classes allow to define the CRUD controller explicitly
                array_flip($crudFqcnToEntityFqcnMap),
                array_flip($crudFqcnToCrudIdMap),
            ]);
    }
}
