<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\EventListener;

use JavierEguiluz\Bundle\EasyAdminBundle\Exception\BaseException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;

/**
 * This listener allows to display customized error pages in the production
 * environment.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class ExceptionListener
{
    private $templating;
    private $debug;

    public function __construct($templating, $debug)
    {
        $this->templating = $templating;
        $this->debug = $debug;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        /** @var BaseException $exception */
        $exception = $event->getException();
        if (!$exception instanceof BaseException) {
            return;
        }

        // in 'dev' environment, don't override Symfony's exception pages
        if (true === $this->debug) {
            return $exception->getMessage();
        }

        $response = $this->templating->renderResponse(
            $exception->getTemplatePath(),
            array_merge($exception->getParameters(), array('message' => $exception->getMessage())),
            new Response('', $exception->getHttpStatusCode())
        );

        $event->setResponse($response);
    }
}
