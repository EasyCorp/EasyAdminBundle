<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Configuration;

@trigger_error('The '.__NAMESPACE__.'\ViewConfigPass class is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\Configuration\ViewConfigPass class instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\Configuration\ViewConfigPass');

if (\false) {
    class ViewConfigPass implements ConfigPassInterface
    {
    }
}
