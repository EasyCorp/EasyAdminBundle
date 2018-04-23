<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Exception;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class ExceptionContext
{
    private $publicMessage;
    private $debugMessage;
    private $parameters;
    private $statusCode;

    public function __construct($publicMessage, $debugMessage = '', $parameters = [], $statusCode = 500)
    {
        $this->publicMessage = $publicMessage;
        $this->debugMessage = $debugMessage;
        $this->parameters = $parameters;
        $this->statusCode = $statusCode;
    }

    public function getPublicMessage()
    {
        return $this->publicMessage;
    }

    public function getDebugMessage()
    {
        return $this->debugMessage;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getTranslationParameters()
    {
        return $this->transformIntoTranslationPlaceholders($this->parameters);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    private function transformIntoTranslationPlaceholders(array $parameters)
    {
        $placeholders = [];
        foreach ($parameters as $key => $value) {
            if ('%' !== $key[0]) {
                $key = '%'.$key;
            }
            if ('%' !== $key[-1]) {
                $key .= '%';
            }

            $placeholders[$key] = $value;
        }

        return $placeholders;
    }
}
