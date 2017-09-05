<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

@trigger_error('The '.__NAMESPACE__.'\EasyAdminBundle class is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle class instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle');

if (\false) {
    class EasyAdminBundle extends Bundle
    {
    }
}
