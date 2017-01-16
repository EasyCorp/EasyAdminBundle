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
    private $context;

    /**
     * @param ExceptionContext $exceptionContext
     */
    public function __construct(ExceptionContext $context)
    {
        $this->message = $context->getDebugMessage();
        $this->context = $context;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function getPublicMessage()
    {
        return $this->context->getPublicMessage();
    }

    public function getDebugMessage()
    {
        return $this->context->getDebugMessage();
    }

    public function getParameters()
    {
        return $this->context->getParameters();
    }

    public function getStatusCode()
    {
        return $this->context->getStatusCode();
    }
}
