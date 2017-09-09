<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;

@trigger_error('The '.__NAMESPACE__.'\EasyAdminExtension class is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\Form\Extension\EasyAdminExtension class instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\Form\Extension\EasyAdminExtension');

if (\false) {
    class EasyAdminExtension extends AbstractTypeExtension
    {
    }
}
