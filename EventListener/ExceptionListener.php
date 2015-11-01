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
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ExceptionListener
{
    /** @var  EngineInterface */
    private $templating;
    private $debug;
    private $exceptionTemplates = array(
        'ForbiddenActionException' => '@EasyAdmin/error/forbidden_action.html.twig',
        'NoEntitiesConfigurationException' => '@EasyAdmin/error/no_entities.html.twig',
        'UndefinedEntityException' => '@EasyAdmin/error/undefined_entity.html.twig',
        'EntityNotFoundException' => '@EasyAdmin/error/entity_not_found.html.twig',
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

        $templatePath = $this->exceptionTemplates[$exceptionClassName];
        $parameters = array_merge($exception->getParameters(), array('message' => $exception->getMessage()));
        $response = $this->templating->renderResponse($templatePath, $parameters);

        $event->setResponse($response);
    }
}
