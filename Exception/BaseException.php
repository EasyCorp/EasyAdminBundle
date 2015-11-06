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
class BaseException extends \RuntimeException
{
    protected $message;
    private $templatePath;
    private $httpStatusCode;

    /**
     * @param string $errorMessage
     * @param string $templatePath
     * @param int    $httpStatusCode
     */
    public function __construct($errorMessage, $templatePath, $httpStatusCode = 500)
    {
        $this->message = $errorMessage;
        $this->templatePath = $templatePath;
        $this->httpStatusCode = $httpStatusCode;
    }

    public function getTemplatePath()
    {
        return $this->templatePath;
    }

    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }
}
