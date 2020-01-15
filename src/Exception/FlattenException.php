<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Exception;

use Symfony\Component\Debug\Exception\FlattenException as LegacyBaseFlattenException;
use Symfony\Component\ErrorHandler\Exception\FlattenException as BaseFlattenException;

if (class_exists(BaseFlattenException::class)) {
    class FlattenException extends BaseFlattenException
    {
        use FlattenExceptionTrait;
    }
} else {
    class FlattenException extends LegacyBaseFlattenException
    {
        use FlattenExceptionTrait;
    }
}
