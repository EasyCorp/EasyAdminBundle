<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    public function __construct($publicMessage, $debugMessage = '', $parameters = array(), $statusCode = 500)
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
        $placeholders = array();
        foreach ($parameters as $key => $value) {
            if ('%' !== $key[0]) {
                $key = '%'.$key;
            }
            if ('%' !== substr($key, -1)) {
                $key = $key.'%';
            }

            $placeholders[$key] = $value;
        }

        return $placeholders;
    }
}

class_alias('EasyCorp\Bundle\EasyAdminBundle\Exception\ExceptionContext', 'JavierEguiluz\Bundle\EasyAdminBundle\Exception\ExceptionContext', false);
