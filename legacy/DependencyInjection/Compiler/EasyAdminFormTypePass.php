<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

@trigger_error('The '.__NAMESPACE__.'\EasyAdminFormTypePass class is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\Compiler\EasyAdminFormTypePass class instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\Compiler\EasyAdminFormTypePass');

if (\false) {
    class EasyAdminFormTypePass implements CompilerPassInterface
    {
    }
}
