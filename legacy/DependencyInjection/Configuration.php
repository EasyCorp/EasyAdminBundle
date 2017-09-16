<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;

@trigger_error('The '.__NAMESPACE__.'\Configuration class is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\Configuration class instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\Configuration');

if (\false) {
    class Configuration implements ConfigurationInterface
    {
    }
}
