<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    /** @var \Twig_Environment */
    private $twig;

    /** @var array */
    private $easyAdminConfig;

    private $currentEntityName;

    public function __construct(\Twig_Environment $twig, array $easyAdminConfig, $controller, LoggerInterface $logger = null)
    {
        $this->twig = $twig;
        $this->easyAdminConfig = $easyAdminConfig;

        parent::__construct($controller, $logger);
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $this->currentEntityName = $event->getRequest()->query->get('entity', null);

        if (!$exception instanceof BaseException) {
            return;
        }

        parent::onKernelException($event);
    }

    /**
     * @param FlattenException $exception
     *
     * @return Response
     */
    public function showExceptionPageAction(FlattenException $exception)
    {
        $entityConfig = isset($this->easyAdminConfig['entities'][$this->currentEntityName])
            ? $this->easyAdminConfig['entities'][$this->currentEntityName] : null;
        $exceptionTemplatePath = isset($entityConfig['templates']['exception'])
            ? $entityConfig['templates']['exception']
            : isset($this->easyAdminConfig['design']['templates']['exception'])
                ? $this->easyAdminConfig['design']['templates']['exception']
                : '@EasyAdmin/default/exception.html.twig';

        return Response::create($this->twig->render(
            $exceptionTemplatePath,
            array('exception' => $exception)
        ), $exception->getStatusCode());
    }

    /**
     * {@inheritdoc}
     */
    protected function logException(\Exception $exception, $message, $original = true)
    {
        if (!$exception instanceof BaseException) {
            parent::logException($exception, $message, $original);

            return;
        }

        if (null !== $this->logger) {
            if ($exception->getStatusCode() >= 500) {
                $this->logger->critical($message, array('exception' => $exception));
            } else {
                $this->logger->error($message, array('exception' => $exception));
            }
        }
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

class_alias('EasyCorp\Bundle\EasyAdminBundle\EventListener\ExceptionListener', 'JavierEguiluz\Bundle\EasyAdminBundle\EventListener\ExceptionListener', false);
