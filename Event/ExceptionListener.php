<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Event;

use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\Configurator;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{

    /**
     * @var TwigEngine
     */
    protected $templating;

    /**
     * @var boolean
     */
    protected $enabled;

    public function __construct(Configurator $configurator, TwigEngine $templating)
    {
        $this->enabled = $configurator->get('exception_listener.enabled');
        $this->templating = $templating;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (false === $this->enabled) {
            return;
        }

        $controller = $event->getRequest()->attributes->get('_controller');

        $class = preg_replace('~\:.*$~', '', $controller);

        $reflection = new \ReflectionClass($class);

        do {
            $extendsAdminController = $reflection->getName() === 'JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController';
            $reflection = $reflection->getParentClass();
        } while ($reflection && !$extendsAdminController);

        if ($extendsAdminController) {
            $e = $event->getException();
            $response = new Response('', 500);

            $exceptions = array();

            $isHttpException = false;

            do {
                $e->class = get_class($e); // Hack to get the whole exception and its class name in twig
                $exceptions[] = $e;
                if ($e instanceof HttpExceptionInterface) {
                    $isHttpException = true;
                }
            } while ($e = $e->getPrevious());

            $template = $isHttpException
                ? '@EasyAdmin/error/http_exception.html.twig'
                : '@EasyAdmin/error/exception.html.twig'
            ;
            $response->setContent($this->templating->render($template, array('exceptions' => $exceptions, 'is_http_exception' => $isHttpException)));

            $event->setResponse($response);
        }
    }

}
