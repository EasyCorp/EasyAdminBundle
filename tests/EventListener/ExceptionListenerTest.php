<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\EventListener\ExceptionListener;
use EasyCorp\Bundle\EasyAdminBundle\Exception\EntityNotFoundException as EasyEntityNotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ExceptionListenerTest extends TestCase
{
    private function getTwig()
    {
        $twig = $this->createMock('Twig\Environment');
        $twig->method('render')->willReturn('template content');

        return $twig;
    }

    public function testCatchBaseExceptions()
    {
        $exception = new EasyEntityNotFoundException([
            'entity_name' => 'Test',
            'entity_id_name' => 'Test key',
            'entity_id_value' => 2,
        ]);
        $event = class_exists(ExceptionEvent::class) ? ExceptionEvent::class : GetResponseForExceptionEvent::class;
        $event = new $event(new TestKernel(), new Request(), TestKernel::MASTER_REQUEST, $exception);
        $twig = $this->getTwig();

        $listener = new ExceptionListener($twig, []);
        $listener->onKernelException($event);

        $this->assertInstanceOf(Response::class, $event->getResponse());
    }

    public function testShouldNotCatchExceptionsWithSameName()
    {
        $exception = new EntityNotFoundException();
        $event = class_exists(ExceptionEvent::class) ? ExceptionEvent::class : GetResponseForExceptionEvent::class;
        $event = new $event(new TestKernel(), new Request(), TestKernel::MASTER_REQUEST, $exception);
        $twig = $this->getTwig();

        $listener = new ExceptionListener($twig, []);
        $listener->onKernelException($event);

        $this->assertNull($event->getResponse());
    }
}

class EntityNotFoundException extends \Exception
{
}

class TestKernel implements HttpKernelInterface
{
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        return new Response('foo');
    }
}
