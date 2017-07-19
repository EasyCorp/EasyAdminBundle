<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\EventListener;

use JavierEguiluz\Bundle\EasyAdminBundle\EventListener\ExceptionListener;
use JavierEguiluz\Bundle\EasyAdminBundle\Exception\EntityNotFoundException as EasyEntityNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ExceptionListenerTest extends \PHPUnit_Framework_TestCase
{
    private function getTwig()
    {
        $templating = $this->getMockBuilder('\Twig_Environment')->disableOriginalConstructor()->getMock();
        $templating->method('render')->willReturn('template content');

        return $templating;
    }

    private function getEventExceptionThatShouldBeCalledOnce($exception)
    {
        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->method('getException')->willReturn($exception);
        $event->method('getRequest')->willReturn(new Request());
        $event->method('getKernel')->willReturn(new TestKernel());
        $event->expects($this->once())->method('setResponse');

        return $event;
    }

    public function testCatchBaseExceptions()
    {
        $exception = new EasyEntityNotFoundException(array(
            'entity_name' => 'Test',
            'entity_id_name' => 'Test key',
            'entity_id_value' => 2,
        ));
        $event = $this->getEventExceptionThatShouldBeCalledOnce($exception);
        $twig = $this->getTwig();

        $listener = new ExceptionListener($twig, array(), 'easyadmin.listener.exception:showExceptionPageAction');
        $listener->onKernelException($event);
    }

    private function getEventExceptionThatShouldNotBeCalled($exception)
    {
        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->method('getException')->willReturn($exception);
        $event->method('getRequest')->willReturn(new Request());
        $event->expects($this->never())->method('setResponse');

        return $event;
    }

    public function testShouldNotCatchExceptionsWithSameName()
    {
        $exception = new EntityNotFoundException();
        $event = $this->getEventExceptionThatShouldNotBeCalled($exception);
        $twig = $this->getTwig();

        $listener = new ExceptionListener($twig, array(), 'easyadmin.listener.exception:showExceptionPageAction');
        $listener->onKernelException($event);
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
