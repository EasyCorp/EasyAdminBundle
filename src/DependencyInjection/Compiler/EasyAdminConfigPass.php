<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
final class EasyAdminConfigPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $configPasses = $this->findAndSortTaggedServices('easyadmin.config_pass', $container);
        $definition = $container->getDefinition('easyadmin.config.manager');

        foreach ($configPasses as $service) {
            $definition->addMethodCall('addConfigPass', array($service));
        }
    }
}

class_alias('EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\Compiler\EasyAdminConfigPass', 'JavierEguiluz\Bundle\EasyAdminBundle\DependencyInjection\Compiler\EasyAdminConfigPass', false);
