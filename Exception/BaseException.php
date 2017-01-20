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
    /** @var ExceptionContext */
    private $context;

    /**
     * @param ExceptionContext $context
     */
    public function __construct(ExceptionContext $context)
    {
        $this->message = $context->getDebugMessage();
        $this->context = $context;
    }

    /**
     * @return ExceptionContext
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return string The message that can safely be dispalyed to end-users because it doesn't contain sensitive data
     */
    public function getPublicMessage()
    {
        return $this->context->getPublicMessage();
    }

    /**
     * @return string The full exception message that is logged and it can contain sensitive data
     */
    public function getDebugMessage()
    {
        return $this->context->getDebugMessage();
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->context->getParameters();
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->context->getStatusCode();
    }
}
