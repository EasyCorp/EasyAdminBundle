<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\DataCollector;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;

@trigger_error('The '.__NAMESPACE__.'\EasyAdminDataCollector class is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\DataCollector\EasyAdminDataCollector class instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\DataCollector\EasyAdminDataCollector');

if (\false) {
    class EasyAdminDataCollector extends DataCollector
    {
    }
}
