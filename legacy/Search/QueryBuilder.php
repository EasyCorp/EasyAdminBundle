<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Search;

@trigger_error('The '.__NAMESPACE__.'\QueryBuilder class is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\Search\QueryBuilder class instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\Search\QueryBuilder');

if (\false) {
    class QueryBuilder
    {
    }
}
