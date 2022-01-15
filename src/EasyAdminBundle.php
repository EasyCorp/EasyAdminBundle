<?php

namespace EasyCorp\Bundle\EasyAdminBundle;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\CreateControllerRegistriesPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class EasyAdminBundle extends Bundle
{
    public const VERSION = '3.5.19';
    /** @deprecated use EA::CONTEXT_REQUEST_ATTRIBUTE */
    public const CONTEXT_ATTRIBUTE_NAME = EA::CONTEXT_REQUEST_ATTRIBUTE;

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new CreateControllerRegistriesPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION);
    }
}
