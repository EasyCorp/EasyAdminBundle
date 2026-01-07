<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Context\ExceptionContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\EventListener\ExceptionListener;
use EasyCorp\Bundle\EasyAdminBundle\Exception\BaseException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Loader\ArrayLoader;

class ExceptionListenerTest extends TestCase
{
    /**
     * @dataProvider unhandledException
     */
    public function testUnhandledException(bool $kernelDebug, bool $contextIsNull, \Exception $exception): void
    {
        $contextProvider = $this->createMock(AdminContextProviderInterface::class);
        if (!$contextIsNull) {
            $context = $this->createMock(AdminContextInterface::class);
            $context->method('getTemplatePath')->willReturn('foo');
            $contextProvider->method('getContext')->willReturn($context);
        }

        $listener = new ExceptionListener(
            $kernelDebug,
            $contextProvider,
            $this->createMock(Environment::class),
        );

        $expectedMessage = $exception->getMessage();

        $listener->onKernelException($exceptionEvent = $this->createExceptionEvent($exception));

        $this->assertSame($expectedMessage, $exceptionEvent->getThrowable()->getMessage());
        $this->assertNull($exceptionEvent->getResponse());
    }

    public static function unhandledException(): \Generator
    {
        yield [true, true, new \Exception()];
        yield [true, false, new \Exception()];
        yield [false, true, new \Exception()];
        yield [false, false, new \Exception()];
        yield [true, true, new RuntimeError('foo')];
        yield [true, false, new RuntimeError('foo')];
        yield [false, true, new RuntimeError('foo')];
        yield [false, false, new RuntimeError('foo')];
        yield [true, true, new class(new ExceptionContext('foo')) extends BaseException {}];
        yield [true, false, new class(new ExceptionContext('foo')) extends BaseException {}];
        yield [false, true, new class(new ExceptionContext('foo')) extends BaseException {}];
    }

    public function testAppendMessage(): void
    {
        $listener = new ExceptionListener(
            true,
            $this->createMock(AdminContextProviderInterface::class),
            $this->createMock(Environment::class),
        );

        $exception = new RuntimeError('Variable "ea" does not exist.');
        $listener->onKernelException($exceptionEvent = $this->createExceptionEvent($exception));

        $expectedMessage = <<<MESSAGE
Variable "ea" does not exist.

The "ea" variable stores the admin context (menu items, actions, fields, etc.) and it's created automatically for requests served by EasyAdmin.

If you are seeing this error, you are trying to use some EasyAdmin features in a request not served by EasyAdmin. For example, some of your custom actions may be trying to render or extend from one of the templates provided EasyAdmin.

Your request must meet one of these conditions to be served by EasyAdmin (and to have the "ea" variable defined):

1) It must be run by a controller that implements DashboardControllerInterface. This is done automatically for all actions and CRUD controllers associated to your dashboard.

2) It must contain an "eaContext" query string parameter that identifies the Dashboard associated to this request (this parameter is automatically added by EasyAdmin when creating menu items that link to custom Symfony routes).
MESSAGE;

        $this->assertSame($expectedMessage, $exceptionEvent->getThrowable()->getMessage());
        $this->assertNull($exceptionEvent->getResponse());
    }

    public function testResponse(): void
    {
        $contextProvider = $this->createMock(AdminContextProviderInterface::class);
        $context = $this->createMock(AdminContextInterface::class);
        $context->method('getTemplatePath')->willReturn('@EasyAdmin/exception.html.twig');
        $contextProvider->method('getContext')->willReturn($context);
        $listener = new ExceptionListener(
            false,
            $contextProvider,
            new Environment(new ArrayLoader(['@EasyAdmin/exception.html.twig' => '{{ exception.publicMessage }}'])),
        );

        $exception = new class(new ExceptionContext('foo')) extends BaseException {};

        $expectedStatusCode = $exception->getStatusCode();

        $listener->onKernelException($exceptionEvent = $this->createExceptionEvent($exception));

        $this->assertSame($expectedStatusCode, $exceptionEvent->getResponse()->getStatusCode());
        $this->assertSame('foo', $exceptionEvent->getResponse()->getContent());
    }

    private function createExceptionEvent(\Exception $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('/'),
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );
    }
}
