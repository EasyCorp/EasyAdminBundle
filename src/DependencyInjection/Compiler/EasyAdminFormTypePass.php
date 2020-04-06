<?php

namespace EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\Compiler;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator\TypeConfiguratorInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class EasyAdminFormTypePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTypeConfigurators($container);
    }

    private function registerTypeConfigurators(ContainerBuilder $container)
    {
        $configurators = new \SplPriorityQueue();
        foreach ($container->findTaggedServiceIds('easyadmin.form.type.configurator') as $id => $tags) {
            $configuratorClass = new \ReflectionClass($container->getDefinition($id)->getClass());
            if (!$configuratorClass->implementsInterface(TypeConfiguratorInterface::class)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, TypeConfiguratorInterface::class));
            }

            if ('easyadmin.form.type.configurator.ivory_ckeditor' === $id && $this->ivoryCkEditorHasDefaultConfiguration($container)) {
                $container->removeDefinition('easyadmin.form.type.configurator.ivory_ckeditor');
                continue;
            }

            if ('easyadmin.form.type.configurator.fos_ckeditor' === $id && $this->fosCkEditorHasDefaultConfiguration($container)) {
                $container->removeDefinition('easyadmin.form.type.configurator.fos_ckeditor');
                continue;
            }

            foreach ($tags as $tag) {
                $priority = $tag['priority'] ?? 0;
                $configurators->insert(new Reference($id), $priority);
            }
        }

        $configurators = iterator_to_array($configurators);
        $container->getDefinition('easyadmin.form.type')->replaceArgument(1, $configurators);
        $container->getDefinition('easyadmin.form.type.filters')->replaceArgument(1, $configurators);
    }

    private function ivoryCkEditorHasDefaultConfiguration(ContainerBuilder $container): bool
    {
        try {
            return null !== $container->get('ivory_ck_editor.config_manager')->getDefaultConfig();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function fosCkEditorHasDefaultConfiguration(ContainerBuilder $container): bool
    {
        try {
            return null !== $container->get('fos_ck_editor.configuration')->getDefaultConfig();
        } catch (\Exception $e) {
            return false;
        }
    }
}
