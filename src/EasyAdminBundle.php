<?php

namespace EasyCorp\Bundle\EasyAdminBundle;

use EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\Compiler\EasyAdminConfigPass;
use EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\Compiler\EasyAdminDTOConfigPass;
use EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\Compiler\EasyAdminFormTypePass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class EasyAdminBundle extends Bundle
{
    public const VERSION = '2.1.2-DEV';

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new EasyAdminFormTypePass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new EasyAdminConfigPass());
        $container->addCompilerPass(new EasyAdminDTOConfigPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }
}
