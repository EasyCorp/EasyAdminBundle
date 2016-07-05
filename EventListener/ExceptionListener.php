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
use JavierEguiluz\Bundle\EasyAdminBundle\Exception\FlattenException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\EventListener\ExceptionListener as BaseExceptionListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

/**
 * This listener allows to display customized error pages in the production
 * environment.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class ExceptionListener extends BaseExceptionListener
{
    /** @var EngineInterface */
    private $templating;

    /** @var array */
    private $easyAdminConfig;

    private $currentEntityName;

    public function __construct(EngineInterface $templating, array $easyAdminConfig, $controller, LoggerInterface $logger = null)
    {
        $this->templating = $templating;
        $this->easyAdminConfig = $easyAdminConfig;

        parent::__construct($controller, $logger);
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $this->currentEntityName = $event->getRequest()->query->get('entity', null);

        if (!$exception instanceof BaseException) {
            return;
        }

        if (!$this->isLegacySymfony()) {
            parent::onKernelException($event);
        } else {
            $response = $this->legacyOnKernelException($event);
            $event->setResponse($response);
        }
    }

    public function showExceptionPageAction(FlattenException $exception)
    {
        $entityConfig = isset($this->easyAdminConfig['entities'][$this->currentEntityName])
            ? $this->easyAdminConfig['entities'][$this->currentEntityName] : null;
        $exceptionTemplatePath = isset($entityConfig['templates']['exception'])
            ? $entityConfig['templates']['exception']
            : isset($this->easyAdminConfig['design']['templates']['exception'])
                ? $this->easyAdminConfig['design']['templates']['exception']
                : '@EasyAdmin/default/exception.html.twig';

        return $this->templating->renderResponse(
            $exceptionTemplatePath,
            array('exception' => $exception),
            Response::create()->setStatusCode($exception->getStatusCode())
        );
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
        if (!$this->isLegacySymfony()) {
            $request = parent::duplicateRequest($exception, $request);
        } else {
            $request = $this->legacyDuplicateRequest($request);
        }

        $request->attributes->set('exception', FlattenException::create($exception));

        return $request;
    }

    /**
     * Utility method needed for BC reasons with Symfony 2.3
     * Code copied from Symfony\Component\HttpKernel\EventListener\ExceptionListener
     *
     * @param GetResponseForExceptionEvent $event
     *
     * @return Response
     *
     * @throws \Exception
     */
    private function legacyOnKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        $this->logException($exception, sprintf('Uncaught PHP Exception %s: "%s" at %s line %s', get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine()));

        $request = $this->duplicateRequest($exception, $event->getRequest());

        try {
            return $event->getKernel()->handle($request, HttpKernelInterface::SUB_REQUEST, false);
        } catch (\Exception $e) {
            $this->logException($e, sprintf('Exception thrown when handling an exception (%s: %s at %s line %s)', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));

            $wrapper = $e;

            while ($prev = $wrapper->getPrevious()) {
                if ($exception === $wrapper = $prev) {
                    throw $e;
                }
            }

            $prev = new \ReflectionProperty('Exception', 'previous');
            $prev->setAccessible(true);
            $prev->setValue($wrapper, $exception);

            throw $e;
        }
    }

    /**
     * Utility method needed for BC reasons with Symfony 2.3
     * Code copied from Symfony\Component\HttpKernel\EventListener\ExceptionListener.
     *
     * @param Request $request
     *
     * @return Request
     */
    private function legacyDuplicateRequest(Request $request)
    {
        $attributes = array(
            '_controller' => $this->controller,
            'logger' => $this->logger instanceof DebugLoggerInterface ? $this->logger : null,
            'format' => $request->getRequestFormat(),
        );
        $request = $request->duplicate(null, null, $attributes);
        $request->setMethod('GET');

        return $request;
    }

    /**
     * Returns true if Symfony version is considered legacy (e.g. 2.3)
     *
     * @return bool
     */
    private function isLegacySymfony()
    {
        return 2 === Kernel::MAJOR_VERSION && 3 === Kernel::MINOR_VERSION;
    }
}
