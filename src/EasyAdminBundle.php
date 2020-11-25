<?php

namespace EasyCorp\Bundle\EasyAdminBundle;

use EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\CreateControllerRegistriesPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class EasyAdminBundle extends Bundle
{
    public const VERSION = '3.1.9';
    public const CONTEXT_ATTRIBUTE_NAME = 'easyadmin_context';

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new CreateControllerRegistriesPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION);
    }
}
