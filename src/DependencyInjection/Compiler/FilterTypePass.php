<?php

namespace EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
final class FilterTypePass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        // type guessers
        $guessers = $this->findAndSortTaggedServices('easyadmin.filter.type_guesser', $container);
        // the filter type guesser created by the user (in the app side) becomes
        // a form type guesser too due to autoconfiguration, and that can cause
        // issues in new/edit forms, so we need to exclude the filter type guesser
        // from the form type guessers group
        foreach ($guessers as $guesser) {
            $container->getDefinition((string) $guesser)
                ->clearTag('form.type_guesser');
        }
        // types Map
        $typesMap = [];
        $servicesMap = [];
        foreach ($container->findTaggedServiceIds('easyadmin.filter.type', true) as $serviceId => $attributes) {
            $class = $container->getDefinition($serviceId)->getClass();
            $name = $attributes[0]['alias'] ?? $class;
            $priority = $attributes[0]['priority'] ?? 0;
            $typesMap[$priority][$name] = $serviceId;
            $servicesMap[$priority][$serviceId] = new Reference($serviceId);
        }
        if ($typesMap) {
            krsort($typesMap);
            $typesMap = array_merge(...$typesMap);
        }
        $container->getDefinition('easyadmin.filter.registry')
            ->replaceArgument(0, $typesMap)
            ->replaceArgument(1, new IteratorArgument($guessers));

        // add extension to the 'form.registry' service to resolve filter form types by service id
        if ($servicesMap) {
            krsort($servicesMap);
            $servicesMap = array_merge(...$servicesMap);
        }
        $container->getDefinition('easyadmin.filter.extension')
            ->replaceArgument(0, ServiceLocatorTagPass::register($container, $servicesMap));
        $formRegistry = $container->getDefinition('form.registry');
        $formRegistry->replaceArgument(0, array_merge([new Reference('easyadmin.filter.extension')], $formRegistry->getArgument(0)));
    }
}
