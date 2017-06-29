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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
final class EasyAdminConfigPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $configPasses = $container->findTaggedServiceIds('easyadmin.config_pass');
        $definition = $container->getDefinition('easyadmin.config.manager');

        foreach ($configPasses as $id => $tags) {
            $definition->addMethodCall('addConfigPass', array(new Reference($id)));
        }
    }
}
