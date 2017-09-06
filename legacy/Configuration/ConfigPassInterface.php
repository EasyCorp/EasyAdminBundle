<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Configuration;

@trigger_error('The '.__NAMESPACE__.'\ConfigPassInterface interface is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigPassInterface interface instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigPassInterface');

if (\false) {
    interface ConfigPassInterface
    {
    }
}
