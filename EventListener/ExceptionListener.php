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
    private $exceptionTemplates = array(
        'ForbiddenActionException' => array('template' => '@EasyAdmin/error/forbidden_action.html.twig', 'status' => 403),
        'NoEntitiesConfigurationException' => array('template' => '@EasyAdmin/error/no_entities.html.twig', 'status' => 500),
        'UndefinedEntityException' => array('template' => '@EasyAdmin/error/undefined_entity.html.twig', 'status' => 500),
        'EntityNotFoundException' => array('template' => '@EasyAdmin/error/entity_not_found.html.twig', 'status' => 404),
    );

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

        /** @var BaseException $exception */
        $exception = $event->getException();
        $exceptionClassName = basename(str_replace('\\', '/', get_class($exception)));

        if (!$exception instanceof BaseException || !array_key_exists($exceptionClassName, $this->exceptionTemplates)) {
            return;
        }

        $templatePath = $this->exceptionTemplates[$exceptionClassName]['template'];
        $reponseStatusCode = $this->exceptionTemplates[$exceptionClassName]['status'];
        $parameters = array_merge($exception->getParameters(), array('message' => $exception->getMessage()));

        $response = $this->templating->renderResponse($templatePath, $parameters, new Response('', $reponseStatusCode));

        $event->setResponse($response);
    }
}
