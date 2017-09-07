<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Exception;

use Symfony\Component\Debug\Exception\FlattenException as BaseFlattenException;

@trigger_error('The '.__NAMESPACE__.'\FlattenException class is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\Exception\FlattenException class instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\Exception\FlattenException');

if (\false) {
    class FlattenException extends BaseFlattenException
    {
    }
}
