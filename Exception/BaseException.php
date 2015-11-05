<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Exception;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class BaseException extends \Exception
{
    protected $message;
    private $parameters;
    private $templatePath;
    private $httpStatusCode;

    public function __construct(array $parameters = array())
    {
        $this->parameters = $parameters;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getTemplatePath()
    {
        return $this->templatePath;
    }

    public function setTemplatePath($path)
    {
        $this->templatePath = $path;
    }

    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }

    public function setHttpStatusCode($code)
    {
        $this->httpStatusCode = $code;
    }
}
