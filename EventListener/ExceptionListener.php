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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\EventListener\ExceptionListener as BaseExceptionListener;
use Symfony\Component\HttpKernel\Exception\HttpException;
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

    /** @var bool */
    private $debug;

    public function __construct($templating, $debug, $controller, LoggerInterface $logger = null)
    {
        $this->templating = $templating;
        $this->debug = $debug;

        parent::__construct($controller, $logger);
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if (!$exception instanceof BaseException || true === $this->debug) {
            return;
        }

        if (3 !== Kernel::RELEASE_VERSION) {
            parent::onKernelException($event);
        } else {
            /* For BC reasons with 2.3, we need to duplicate this entirely
            from Symfony\Component\HttpKernel\EventListener\ExceptionListener.
            Once sf 2.3 support is dropped, we can remove this else block and condition */
            $request = $event->getRequest();

            $this->logException($exception, sprintf('Uncaught PHP Exception %s: "%s" at %s line %s', get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine()));

            $request = $this->duplicateRequest($exception, $request);

            try {
                $response = $event->getKernel()->handle($request, HttpKernelInterface::SUB_REQUEST, false);
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

            $event->setResponse($response);
        }
    }

    public function showExceptionPageAction(FlattenException $exception)
    {
        return $this->templating->renderResponse(
            $exception->getTemplatePath(),
            array_merge($exception->getParameters(), array('message' => $exception->getMessage())),
            Response::create()->setStatusCode($exception->getStatusCode())
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function logException(\Exception $exception, $message, $original = true)
    {
        if (null !== $this->logger) {
            /** @var BaseException $exception */
            if ($exception->getHttpStatusCode() >= 500) {
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
        if (3 !== Kernel::RELEASE_VERSION) {
            $request = parent::duplicateRequest($exception, $request);
        } else {
            /* For BC reasons with 2.3, we need to duplicate this entirely
            from Symfony\Component\HttpKernel\EventListener\ExceptionListener.
            Once sf 2.3 support is dropped, we can remove this else block and condition */
            $attributes = array(
                '_controller' => $this->controller,
                'logger' => $this->logger instanceof DebugLoggerInterface ? $this->logger : null,
                'format' => $request->getRequestFormat(),
            );
            $request = $request->duplicate(null, null, $attributes);
            $request->setMethod('GET');
        }

        $request->attributes->set('exception', FlattenException::create($exception));

        return $request;
    }
}
