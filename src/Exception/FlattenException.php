<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Exception;

use Symfony\Component\Debug\Exception\FlattenException as LegacyBaseFlattenException;
use Symfony\Component\ErrorHandler\Exception\FlattenException as BaseFlattenException;

if (class_exists(BaseFlattenException::class)) {
    class FlattenException extends BaseFlattenException
    {
        use FlattenExceptionTrait { FlattenExceptionTrait::create as createException; }

        public static function create(\Exception $exception, $statusCode = null, array $headers = []): BaseFlattenException
        {
            return self::createException($exception, $statusCode, $headers);
        }
    }
} else {
    class FlattenException extends LegacyBaseFlattenException
    {
        use FlattenExceptionTrait { FlattenExceptionTrait::create as createException; }

        public static function create(\Exception $exception, $statusCode = null, array $headers = []): LegacyBaseFlattenException
        {
            return self::createException($exception, $statusCode, $headers);
        }
    }
}
