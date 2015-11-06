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
    // this is the error message that can be safely displayed to end users
    protected $message;
    // this is the full error message displayed only in 'dev' environment
    private $errorMessageAndSolution;
    // the HTTP status code of the Response created for the exception
    private $statusCode;

    /**
     * @param string $errorMessage
     * @param string $proposedSolution
     * @param int    $statusCode
     */
    public function __construct($publicErrorMessage, $proposedSolution = '', $statusCode = 500)
    {
        $this->statusCode = $statusCode;
        $this->message = $publicErrorMessage;
        $this->errorMessageAndSolution = sprintf('Error: %s Solution: %s', $publicErrorMessage, $proposedSolution);
    }

    public function getErrorMessageAndSolution()
    {
        return $this->errorMessageAndSolution;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
