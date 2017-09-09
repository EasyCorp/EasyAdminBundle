<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Search;

@trigger_error('The '.__NAMESPACE__.'\Paginator class is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\Search\Paginator class instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\Search\Paginator');

if (\false) {
    class Paginator
    {
    }
}
