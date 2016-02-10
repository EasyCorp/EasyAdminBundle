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
 * @author unexge <unexge@yandex.com>
 */
class EasyAdminBatchActionPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ( ! $container->hasDefinition('easyadmin.batch.manager')) {
            return;
        }

        $definition = $container->findDefinition('easyadmin.batch.manager');

        foreach ($container->findTaggedServiceIds('easyadmin.batch.action') as $id => $tags) {
            $definition->addMethodCall('addAction', array(new Reference($id)));
        }
    }
}
