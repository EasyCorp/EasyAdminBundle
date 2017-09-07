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

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class BaseException extends HttpException
{
    /** @var ExceptionContext */
    private $context;

    /**
     * @param ExceptionContext $context
     */
    public function __construct(ExceptionContext $context)
    {
        $this->context = $context;
        parent::__construct($this->context->getStatusCode(), $this->context->getDebugMessage());
    }

    /**
     * @return ExceptionContext
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return string The message that can safely be displayed to end-users because it doesn't contain sensitive data
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

class_alias('EasyCorp\Bundle\EasyAdminBundle\Exception\BaseException', 'JavierEguiluz\Bundle\EasyAdminBundle\Exception\BaseException', false);
