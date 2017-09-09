<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Form\Type\Configurator;

@trigger_error('The '.__NAMESPACE__.'\AutocompleteTypeConfigurator class is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator\AutocompleteTypeConfigurator class instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator\AutocompleteTypeConfigurator');

if (\false) {
    class AutocompleteTypeConfigurator implements TypeConfiguratorInterface
    {
    }
}
