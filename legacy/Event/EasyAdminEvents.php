<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Event;

@trigger_error('The '.__NAMESPACE__.'\EasyAdminEvents class is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\Event\EasyAdminEvents class instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\Event\EasyAdminEvents');

if (\false) {
    final class EasyAdminEvents
    {
    }
}
