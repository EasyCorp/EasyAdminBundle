<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle;

use EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\Compiler\AnnotatedRouteControllerLoaderPass;
use EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\Compiler\EasyAdminConfigPass;
use EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\Compiler\EasyAdminFormTypePass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class EasyAdminBundle extends Bundle
{
    const VERSION = '1.17.20-DEV';

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new EasyAdminFormTypePass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new EasyAdminConfigPass());

        if (trait_exists('Symfony\Component\DependencyInjection\PriorityTaggedServiceTrait')) { // DI >= 3.2, we can use priority rules
            $container->addCompilerPass(new AnnotatedRouteControllerLoaderPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 10);
        } else {
            $passConfig = $container->getCompilerPassConfig();
            $passes = $passConfig->getBeforeOptimizationPasses();
            array_unshift($passes, new AnnotatedRouteControllerLoaderPass()); // Make sure our pass is executed before the RoutingResolverPass
            $passConfig->setBeforeOptimizationPasses($passes);
        }
    }
}

class_alias('EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle', 'JavierEguiluz\Bundle\EasyAdminBundle\EasyAdminBundle', false);
