<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\DependencyInjection\Compiler;

use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\ActionConfigPass;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\DefaultConfigPass;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\DesignConfigPass;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\MenuConfigPass;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\MetadataConfigPass;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\NormalizerConfigPass;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\PropertyConfigPass;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\TemplateConfigPass;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\ViewConfigPass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class EasyAdminConfigurationPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $backendConfig = $this->getBackendConfig($container);

        $configPasses = array(
            new NormalizerConfigPass(),
            new DesignConfigPass($container->getParameter('kernel.debug')),
            new MenuConfigPass(),
            new ActionConfigPass(),
            new MetadataConfigPass($container->get('doctrine')),
            new PropertyConfigPass(),
            new ViewConfigPass(),
            new TemplateConfigPass($container->getParameter('kernel.root_dir').'/Resources/views'),
            new DefaultConfigPass(),
        );

        foreach ($configPasses as $configPass) {
            $backendConfig = $configPass->process($backendConfig);
        }

        $container->setParameter('easyadmin.config', $backendConfig);
        $container->getDefinition('easyadmin.configurator')->replaceArgument(0, $backendConfig);
    }

    /**
     * Returns the current backend configuration defined in the given container.
     *
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function getBackendConfig(ContainerBuilder $container)
    {
        $backendConfig = $container->getParameter('easyadmin.config');

        // The parameter returned by the container has its values resolved.
        //   %value% -> is turned into the parameter value
        //   %%value%% -> is turned into %value% (we use this for EasyAdmin translations)
        // We need to escape again the % character to prevent Symfony interpreting
        // them as a container parameter when setting back the easyadmin.config
        // parameter in the container
        array_walk_recursive($backendConfig, function (&$value) {
            if (is_string($value)) {
                $value = str_replace('%', '%%', $value);
            }
        });

        return $backendConfig;
    }
}
