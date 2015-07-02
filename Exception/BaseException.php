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

class BaseException extends \Exception
{
    protected $message;
    private $parameters;
    private $htmlMessage;

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
        $this->htmlMessage = $message;
        $this->message = strip_tags($message);
    }

    public function getMessageAsHtml()
    {
        return $this->htmlMessage;
    }
}
