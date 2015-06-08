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

    public function __construct($templating)
    {
        $this->templating = $templating;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exceptionTemplates = array(
            'ForbiddenActionException' => '@EasyAdmin/error/forbidden_action.html.twig',
            'NoEntitiesConfigurationException' => '@EasyAdmin/error/no_entities.html.twig',
            'UndefinedEntityException' => '@EasyAdmin/error/undefined_entity.html.twig',
        );

        $exception = $event->getException();
        $exceptionClassName = basename(str_replace('\\', '/', get_class($exception)));

        if (!array_key_exists($exceptionClassName, $exceptionTemplates)) {
            return;
        }

        $templatePath = $exceptionTemplates[$exceptionClassName];
        $response = $this->templating->renderResponse($templatePath, $exception->getParameters());
        $event->setResponse($response);
    }
}
