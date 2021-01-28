<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\EventListener\AdminRouterSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class AdminRouterSubscriberTest.
 */
final class AdminRouterSubscriberTest extends TestCase
{
    /**
     * @var AdminRouterSubscriber
     */
    private $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriber = (new \ReflectionClass(AdminRouterSubscriber::class))->newInstanceWithoutConstructor();
    }

    /**
     * testFetchingControllerFromRequestAttributes.
     *
     * @param $controller
     *
     * @dataProvider controllerDataProvider
     *
     * @doesNotPerformAssertions
     */
    public function testFetchingControllerFromRequestAttributes($controller): void
    {
        $request = Request::create('/');
        $request->attributes->set('_controller', $controller);

        $this->subscriber->onKernelRequest(
            new RequestEvent(
                $this->getMockForAbstractClass(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MASTER_REQUEST
            )
        );
    }

    public static function controllerDataProvider(): iterable
    {
        yield ['Some\\Class::method'];
        yield ['Some\\Class'];
        yield [['Some\\Class', 'method']];
        yield [new class() {
            public function __invoke()
            {
            }
        }];
    }
}
