<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Listener;

use JavierEguiluz\Bundle\EasyAdminBundle\Listener\ExceptionListener;
use JavierEguiluz\Bundle\EasyAdminBundle\Exception\EntityNotFoundException as EasyEntityNotFoundException;

class ExceptionListenerTest extends \PHPUnit_Framework_TestCase
{
    private function getTemplating()
    {
        $response = $this->getMockBuilder('Symfony\Component\HttpFoundation\Response')
                         ->disableOriginalConstructor()
                         ->getMock();
        $templating = $this->getMockBuilder('\stdClass')
                           ->disableOriginalConstructor()
                           ->setMethods(array('renderResponse'))
                           ->getMock();
        $templating->method('renderResponse')
                   ->willReturn($response);

        return $templating;
    }

    private function getEventExceptionThatShouldBeCalledOnce($exception)
    {
        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')
                           ->disableOriginalConstructor()
                           ->getMock();
        $event->method('getException')
                ->willReturn($exception);
        $event->expects($this->once())
              ->method('setResponse');

        return $event;
    }

    public function testCatchBaseExceptions()
    {
        $exception = new EasyEntityNotFoundException(
            array(
                'entity' => array(
                    'name' => 'Test',
                    'primary_key_field_name' => 'Test key'
                ),
                'entity_id' => 2
            )
        );
        $event = $this->getEventExceptionThatShouldBeCalledOnce($exception);
        $templating = $this->getTemplating();
        $debug = false;

        $listener = new ExceptionListener($templating, $debug);
        $listener->onKernelException($event);
    }

    private function getEventExceptionThatShouldNotBeCalled($exception)
    {
        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')
                           ->disableOriginalConstructor()
                           ->getMock();
        $event->method('getException')
                ->willReturn($exception);
        $event->expects($this->never())
              ->method('setResponse');

        return $event;
    }

    public function testShouldNotCatchExceptionsWithSameName()
    {
        $exception = new EntityNotFoundException;
        $event = $this->getEventExceptionThatShouldNotBeCalled($exception);
        $templating = $this->getTemplating();
        $debug = false;

        $listener = new ExceptionListener($templating, $debug);
        $listener->onKernelException($event);
    }
}
