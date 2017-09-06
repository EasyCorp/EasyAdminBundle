<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Form\Type\Configurator;

@trigger_error('The '.__NAMESPACE__.'\CollectionTypeConfigurator class is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator\CollectionTypeConfigurator class instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator\CollectionTypeConfigurator');

if (\false) {
    class CollectionTypeConfigurator implements TypeConfiguratorInterface
    {
    }
}
