<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Twig;

@trigger_error('The '.__NAMESPACE__.'\EasyAdminTwigExtension class is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\Twig\EasyAdminTwigExtension class instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\Twig\EasyAdminTwigExtension');

if (\false) {
    class EasyAdminTwigExtension extends \Twig_Extension
    {
    }
}
