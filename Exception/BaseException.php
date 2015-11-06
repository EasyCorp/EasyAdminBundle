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
    // this is the full error message displayed only in 'dev' environment
    protected $message;
    // this is the error message that can be safely displayed to end users
    private $publicErrorMessage;
    private $httpStatusCode;

    /**
     * @param string $errorMessage
     * @param string $proposedSolution
     * @param int    $httpStatusCode
     */
    public function __construct($publicErrorMessage, $proposedSolution = '', $httpStatusCode = 500)
    {
        $this->httpStatusCode = $httpStatusCode;
        $this->publicErrorMessage = $publicErrorMessage;
        $this->message = sprintf('Error: %s Solution: %s', $publicErrorMessage, $proposedSolution);
    }

    public function getPublicErrorMessage()
    {
        return $this->publicErrorMessage;
    }

    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }
}
