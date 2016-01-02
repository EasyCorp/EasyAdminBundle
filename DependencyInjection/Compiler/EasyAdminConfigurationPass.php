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
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\FormViewConfigPass;
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
        $backendConfiguration = $container->getParameter('easyadmin.config');

        // The parameter returned by the container has its values resolved.
        // %value% -> is turned into the parameter value
        // %%value%% -> is turned into %value% (we use this for EasyAdmin translations)
        // Before further processing the configuration we need to escape again
        // the % character to prevent Symfony interpreting them as a container
        // parameter when setting the easyadmin.config value at the end of this method
        array_walk_recursive($backendConfiguration, function (&$value) {
            $value = str_replace('%', '%%', $value);
        });

        $configPasses = array(
            new NormalizerConfigPass(),
            new FormViewConfigPass(),
            new PropertyConfigPass(),
            new MetadataConfigPass($container->get('doctrine')),
            new ActionConfigPass(),
            new ViewConfigPass(),
            new TemplateConfigPass($container->getParameter('kernel.root_dir').'/Resources/views'),
            new DefaultConfigPass(),
        );

        foreach ($configPasses as $configPass) {
            $backendConfiguration = $configPass->process($backendConfiguration);
        }

        $container->setParameter('easyadmin.config', $backendConfiguration);
        $container->getDefinition('easyadmin.configurator')->replaceArgument(0, $backendConfiguration);
    }
}
