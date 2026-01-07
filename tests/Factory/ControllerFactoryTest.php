<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ControllerFactoryTest extends TestCase
{
    private ControllerResolverInterface $controllerResolver;
    private ControllerFactory $controllerFactory;

    protected function setUp(): void
    {
        $this->controllerResolver = $this->createMock(ControllerResolverInterface::class);
        $this->controllerFactory = new ControllerFactory($this->controllerResolver);
    }

    public function testGetCrudControllerInstanceReturnsNullWhenFqcnIsNull(): void
    {
        $request = new Request();

        $result = $this->controllerFactory->getCrudControllerInstance(null, 'index', $request);

        $this->assertNull($result);
    }

    public function testGetCrudControllerInstanceReturnsControllerWhenValid(): void
    {
        $request = new Request();
        $crudController = $this->createMock(CrudControllerInterface::class);

        $this->controllerResolver
            ->expects($this->once())
            ->method('getController')
            ->willReturnCallback(static fn (): array => [$crudController, 'index']);

        $result = $this->controllerFactory->getCrudControllerInstance(
            'App\Controller\ProductCrudController',
            'index',
            $request
        );

        $this->assertSame($crudController, $result);
    }

    public function testGetCrudControllerInstanceThrowsNotFoundExceptionWhenControllerNotFound(): void
    {
        $request = new Request();

        $this->controllerResolver
            ->expects($this->once())
            ->method('getController')
            ->willReturn(false);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unable to find the controller "App\Controller\InvalidController::index".');

        $this->controllerFactory->getCrudControllerInstance(
            'App\Controller\InvalidController',
            'index',
            $request
        );
    }

    public function testGetCrudControllerInstanceReturnsNullWhenCallableIsNotArray(): void
    {
        $request = new Request();

        $this->controllerResolver
            ->expects($this->once())
            ->method('getController')
            ->willReturnCallback(static fn (): \Closure => static fn (): string => 'response');

        $result = $this->controllerFactory->getCrudControllerInstance(
            'App\Controller\ProductCrudController',
            'index',
            $request
        );

        $this->assertNull($result);
    }

    public function testGetCrudControllerInstanceReturnsNullWhenControllerDoesNotImplementInterface(): void
    {
        $request = new Request();
        // Create a simple controller object that is not a CrudControllerInterface
        $nonCrudController = new class {
            public function index(): void
            {
            }
        };

        $this->controllerResolver
            ->expects($this->once())
            ->method('getController')
            ->willReturnCallback(static fn (): array => [$nonCrudController, 'index']);

        $result = $this->controllerFactory->getCrudControllerInstance(
            'App\Controller\ProductCrudController',
            'index',
            $request
        );

        $this->assertNull($result);
    }

    public function testGetDashboardControllerInstanceReturnsControllerWhenValid(): void
    {
        $request = new Request();
        $dashboardController = $this->createMock(DashboardControllerInterface::class);

        $this->controllerResolver
            ->expects($this->once())
            ->method('getController')
            ->willReturnCallback(static fn (): array => [$dashboardController, 'index']);

        $result = $this->controllerFactory->getDashboardControllerInstance(
            'App\Controller\DashboardController',
            $request
        );

        $this->assertSame($dashboardController, $result);
    }

    public function testGetDashboardControllerInstanceReturnsNullWhenControllerDoesNotImplementInterface(): void
    {
        $request = new Request();
        // Create a simple controller object that is not a DashboardControllerInterface
        $nonDashboardController = new class {
            public function index(): void
            {
            }
        };

        $this->controllerResolver
            ->expects($this->once())
            ->method('getController')
            ->willReturnCallback(static fn (): array => [$nonDashboardController, 'index']);

        $result = $this->controllerFactory->getDashboardControllerInstance(
            'App\Controller\DashboardController',
            $request
        );

        $this->assertNull($result);
    }

    public function testGetDashboardControllerInstanceThrowsNotFoundExceptionWhenControllerNotFound(): void
    {
        $request = new Request();

        $this->controllerResolver
            ->expects($this->once())
            ->method('getController')
            ->willReturn(false);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unable to find the controller "App\Controller\InvalidDashboard::index".');

        $this->controllerFactory->getDashboardControllerInstance(
            'App\Controller\InvalidDashboard',
            $request
        );
    }

    public function testGetCrudControllerInstanceHandlesDoubleEncodedBackslashes(): void
    {
        $request = new Request();
        $crudController = $this->createMock(CrudControllerInterface::class);

        $this->controllerResolver
            ->expects($this->once())
            ->method('getController')
            ->willReturnCallback(function (Request $req) use ($crudController) {
                $controller = $req->attributes->get('_controller');
                // Verify the backslashes were decoded
                $this->assertSame(['App\Controller\ProductCrudController', 'index'], $controller);

                return [$crudController, 'index'];
            });

        $result = $this->controllerFactory->getCrudControllerInstance(
            'App%5CController%5CProductCrudController',
            'index',
            $request
        );

        $this->assertSame($crudController, $result);
    }

    public function testGetCrudControllerInstanceHandlesControllerResolverException(): void
    {
        $request = new Request();

        $this->controllerResolver
            ->expects($this->once())
            ->method('getController')
            ->willThrowException(new \InvalidArgumentException('Controller not found'));

        $this->expectException(NotFoundHttpException::class);

        $this->controllerFactory->getCrudControllerInstance(
            'App\Controller\BrokenController',
            'index',
            $request
        );
    }
}
