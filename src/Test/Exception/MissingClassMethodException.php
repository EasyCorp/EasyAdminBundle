<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Test\Exception;

final class MissingClassMethodException extends \Exception
{
    /**
     * @param array<array-key, string> $methodsName
     */
    public function __construct(array $methodsName, int $code = 0, ?\Throwable $previous = null)
    {
        $message = 'The class should implement the following methods :';

        foreach ($methodsName as $key => $methodName) {
            $message .= sprintf(' %s', $methodName);
            if ($key !== array_key_last($methodsName)) {
                $message .= ' and';
            }
        }

        parent::__construct($message, $code, $previous);
    }
}
