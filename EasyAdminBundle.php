<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle;

use JavierEguiluz\Bundle\EasyAdminBundle\DependencyInjection\Compiler\EasyAdminFormTypePass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class EasyAdminBundle extends Bundle
{
    const VERSION = '1.15.2-DEV';

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new EasyAdminFormTypePass(), PassConfig::TYPE_BEFORE_REMOVING);
    }
}
