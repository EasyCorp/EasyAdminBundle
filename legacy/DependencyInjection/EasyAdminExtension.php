<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;

@trigger_error('The '.__NAMESPACE__.'\EasyAdminExtension class is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\EasyAdminExtension class instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\EasyAdminExtension');

if (\false) {
    class EasyAdminExtension extends Extension
    {
    }
}
