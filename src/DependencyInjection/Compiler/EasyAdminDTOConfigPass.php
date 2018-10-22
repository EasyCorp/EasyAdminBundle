<?php

namespace EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class EasyAdminDTOConfigPass implements CompilerPassInterface
{
    public const OBJECT_FACTORY_TAG = 'easyadmin.dto_factory_storage';
    public const OBJECT_CALLABLE_TAG = 'easyadmin.dto_entity_callable';

    public function process(ContainerBuilder $container)
    {
        $dtoFactoryDefinition = $container->getDefinition('easyadmin.dto_factory_storage');

        foreach ($container->findTaggedServiceIds(self::OBJECT_FACTORY_TAG) as $id => $tags) {
            $dtoFactoryDefinition->addMethodCall('addFactory', [$id, new Reference($id)]);
        }

        $callableStorageDefinition = $container->getDefinition('easyadmin.dto_entity_callable_storage');

        foreach ($container->findTaggedServiceIds(self::OBJECT_CALLABLE_TAG) as $id => $tags) {
            $callableStorageDefinition->addMethodCall('addCallable', [$id, new Reference($id)]);
        }
    }
}
