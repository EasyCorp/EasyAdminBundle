<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\EventListener;

@trigger_error('The '.__NAMESPACE__.'\ControllerListener class is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\EventListener\ControllerListener class instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\EventListener\ControllerListener');

if (\false) {
    class ControllerListener
    {
    }
}
