<?php

namespace EasyCorp\Bundle\EasyAdminBundle\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Exception\BaseException;
use EasyCorp\Bundle\EasyAdminBundle\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Twig\Environment;

/**
 * This listener allows to display customized error pages in the production
 * environment.
 *
 * @internal
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
final class ExceptionListener
{
    private $applicationContextProvider;
    private $twig;

    public function __construct(ApplicationContextProvider $applicationContextProvider, Environment $twig)
    {
        $this->applicationContextProvider = $applicationContextProvider;
        $this->twig = $twig;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        if (!$exception instanceof BaseException) {
            return;
        }

        $event->setResponse($this->createExceptionResponse(FlattenException::create($exception)));
    }

    public function createExceptionResponse(FlattenException $exception): Response
    {
        $context = $this->applicationContextProvider->getContext();
        $exceptionTemplatePath = $context->getTemplatePath('exception');
        $layoutTemplatePath = $context->getTemplatePath('layout');

        return Response::create($this->twig->render($exceptionTemplatePath, [
            'exception' => $exception,
            'layout_template_path' => $layoutTemplatePath,
        ]), $exception->getStatusCode());
    }
}
