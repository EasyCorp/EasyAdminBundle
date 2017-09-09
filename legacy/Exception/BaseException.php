<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

@trigger_error('The '.__NAMESPACE__.'\BaseException class is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\Exception\BaseException class instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\Exception\BaseException');

if (\false) {
    class BaseException extends HttpException
    {
    }
}
