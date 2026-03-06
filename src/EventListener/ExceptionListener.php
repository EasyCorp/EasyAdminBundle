<?php

namespace EasyCorp\Bundle\EasyAdminBundle\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Exception\BaseException;
use EasyCorp\Bundle\EasyAdminBundle\Exception\FlattenException;
use Psr\Log\LoggerInterface;
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
final readonly class ExceptionListener
{
    public function __construct(
        private bool $kernelDebug,
        private AdminContextProviderInterface $adminContextProvider,
        private Environment $twig,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

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
}
