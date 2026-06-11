<?php

namespace EasyCorp\Bundle\EasyAdminBundle\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Exception\BaseException;
use EasyCorp\Bundle\EasyAdminBundle\Exception\FlattenException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Twig\Environment;
use Twig\Error\RuntimeError;

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
    public function __construct(
        private readonly bool $kernelDebug,
        private readonly AdminContextProviderInterface $adminContextProvider,
        private readonly Environment $twig,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($this->kernelDebug && $exception instanceof RuntimeError && 'Variable "ea" does not exist.' === $exception->getRawMessage()) {
            $exception->appendMessage($this->getEaVariableExceptionMessage());

            return;
        }

        if ($this->kernelDebug) {
            return;
        }

        if (!$exception instanceof BaseException && null === $this->adminContextProvider->getContext()) {
            return;
        }

        try {
            $event->setResponse($this->createExceptionResponse(FlattenException::createFromThrowable($exception)));
        } catch (\Throwable $renderingError) {
            $this->logger?->warning('EasyAdmin error page rendering failed, falling back to default error handling.', [
                'rendering_error' => $renderingError->getMessage(),
                'original_exception' => $exception::class,
            ]);
        }
    }

    public function createExceptionResponse(FlattenException $exception): Response
    {
        $context = $this->adminContextProvider->getContext();

        if (null === $context) {
            return new Response($this->twig->render('@EasyAdmin/exception_standalone.html.twig', [
                'exception' => $exception,
            ]), $exception->getStatusCode());
        }

        return new Response($this->twig->render($context->getTemplatePath('exception'), [
            'exception' => $exception,
            'layout_template_path' => $context->getTemplatePath('layout'),
        ]), $exception->getStatusCode());
    }

    private function getEaVariableExceptionMessage(): string
    {
        return <<<MESSAGE


            The "ea" variable stores the admin context (menu items, actions, fields, etc.) and it's created automatically for requests served by EasyAdmin.

            If you are seeing this error, you are trying to use some EasyAdmin features in a request not served by EasyAdmin. For example, some of your custom actions may be trying to render or extend from one of the templates provided EasyAdmin.

            Your request must meet one of these conditions to be served by EasyAdmin (and to have the "ea" variable defined):

            1) It must be run by a controller that implements DashboardControllerInterface. This is done automatically for all actions and CRUD controllers associated to your dashboard.

            2) It must contain an "eaContext" query string parameter that identifies the Dashboard associated to this request (this parameter is automatically added by EasyAdmin when creating menu items that link to custom Symfony routes).
            MESSAGE;
    }
}
