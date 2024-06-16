<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Test\Exception;

final class InvalidClassPropertyTypeException extends \Exception
{
    public function __construct(string $propertyName, string $propertyType, int $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf('The test class should have a property named %s of type %s', $propertyName, $propertyType);

        parent::__construct($message, $code, $previous);
    }
}
