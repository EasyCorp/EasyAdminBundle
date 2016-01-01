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
use Symfony\Component\DependencyInjection\Reference;

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
        $configPasses = array(
            new NormalizerConfigPass(),
            new FormViewConfigPass(),
            new MetadataConfigPass($container->get('doctrine')),
            new ViewConfigPass(),
            new PropertyConfigPass(),
            new ActionConfigPass(),
            new TemplateConfigPass($container->getParameter('kernel.root_dir')),
            new DefaultConfigPass(),
        );

        foreach ($configPasses as $configPass) {
            $backendConfiguration = $configPass->process($backendConfiguration);
        }

        $container->setParameter('easyadmin.config', $backendConfiguration);
    }
}
