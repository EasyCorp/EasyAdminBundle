<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use JavierEguiluz\Bundle\EasyAdminBundle\Exception\NoEntitiesConfigurationException;
use JavierEguiluz\Bundle\EasyAdminBundle\Exception\UndefinedEntityException;
use JavierEguiluz\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;

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
        // in 'dev' environment, don't override Symfony's exception pages
        if (true === $this->debug) {
            return $event->getException()->getMessage();
        }

        $exceptionTemplates = array(
            'ForbiddenActionException' => '@EasyAdmin/error/forbidden_action.html.twig',
            'NoEntitiesConfigurationException' => '@EasyAdmin/error/no_entities.html.twig',
            'UndefinedEntityException' => '@EasyAdmin/error/undefined_entity.html.twig',
            'EntityNotFoundException' => '@EasyAdmin/error/entity_not_found.html.twig',
        );

        /** @var \JavierEguiluz\Bundle\EasyAdminBundle\Exception\BaseException */
        $exception = $event->getException();
        $exceptionClassName = basename(str_replace('\\', '/', get_class($exception)));

        if (!array_key_exists($exceptionClassName, $exceptionTemplates)) {
            return;
        }

        $templatePath = $exceptionTemplates[$exceptionClassName];
        $parameters = array_merge($exception->getParameters(), array('message' => $exception->getMessageAsHtml()));
        $response = $this->templating->renderResponse($templatePath, $parameters);

        $event->setResponse($response);
    }
}
