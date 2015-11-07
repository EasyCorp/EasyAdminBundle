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
    // the HTTP status code of the Response created for the exception
    protected $message;
    // this is the error message that can be safely displayed to end users
    private $safeMessage;
    // this is the full error message displayed only in 'dev' environment and logs
    private $statusCode;

    /**
     * @param string $errorMessage
     * @param string $proposedSolution
     * @param int    $statusCode
     */
    public function __construct($errorMessage, $proposedSolution = '', $statusCode = 500)
    {
        $this->safeMessage = $errorMessage;
        $this->message = sprintf('Error: %s Solution: %s', $errorMessage, $proposedSolution);
        $this->statusCode = $statusCode;
    }

    public function getSafeMessage()
    {
        return $this->safeMessage;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
