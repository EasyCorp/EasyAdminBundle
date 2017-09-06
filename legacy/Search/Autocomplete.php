<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Search;

@trigger_error('The '.__NAMESPACE__.'\Autocomplete class is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\Search\Autocomplete class instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\Search\Autocomplete');

if (\false) {
    class Autocomplete
    {
    }
}
