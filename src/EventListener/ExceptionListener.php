<?php

namespace EasyCorp\Bundle\EasyAdminBundle\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Exception\BaseException;
use EasyCorp\Bundle\EasyAdminBundle\Exception\FlattenException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\EventListener\ExceptionListener as BaseExceptionListener;

/**
 * This listener allows to display customized error pages in the production
 * environment.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class ExceptionListener extends BaseExceptionListener
{
    private $twig;
    private $easyAdminConfig;
    private $currentEntityName;

    public function __construct(\Twig_Environment $twig, array $easyAdminConfig, $controller, LoggerInterface $logger = null)
    {
        $this->twig = $twig;
        $this->easyAdminConfig = $easyAdminConfig;

        parent::__construct($controller, $logger);
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        if (!$exception instanceof BaseException) {
            return;
        }

        $this->currentEntityName = $event->getRequest()->query->get('entity');

        parent::onKernelException($event);
    }

    public function showExceptionPageAction(FlattenException $exception)
    {
        $entityConfig = $this->easyAdminConfig['entities'][$this->currentEntityName] ?? null;
        $exceptionTemplatePath = $entityConfig['templates']['exception']
            ?? $this->easyAdminConfig['design']['templates']['exception']
            ?? '@EasyAdmin/default/exception.html.twig';
        $exceptionLayoutTemplatePath = $entityConfig['templates']['layout']
            ?? $this->easyAdminConfig['design']['templates']['layout']
            ?? '@EasyAdmin/default/layout.html.twig';

        return Response::create($this->twig->render($exceptionTemplatePath, [
            'exception' => $exception,
            'layout_template_path' => $exceptionLayoutTemplatePath,
        ]), $exception->getStatusCode());
    }

    /**
     * {@inheritdoc}
     */
    protected function duplicateRequest(\Exception $exception, Request $request)
    {
        $request = parent::duplicateRequest($exception, $request);
        $request->attributes->set('exception', FlattenException::create($exception));

        return $request;
    }
}
