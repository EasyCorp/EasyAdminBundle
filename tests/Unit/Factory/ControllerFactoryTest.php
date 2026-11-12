<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
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
            CategoryCrudController::class,
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
        $this->expectExceptionMessage(sprintf('Unable to find the controller "%s::index".', CategoryCrudController::class));

        $this->controllerFactory->getCrudControllerInstance(
            CategoryCrudController::class,
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
            CategoryCrudController::class,
            'index',
            $request
        );

        $this->assertNull($result);
    }

    public function testGetCrudControllerInstanceReturnsNullWhenControllerDoesNotImplementInterface(): void
    {
        $request = new Request();
        // create a simple controller object that is not a CrudControllerInterface
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
            CategoryCrudController::class,
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
            DashboardController::class,
            $request
        );

        $this->assertSame($dashboardController, $result);
    }

    public function testGetDashboardControllerInstanceReturnsNullWhenControllerDoesNotImplementInterface(): void
    {
        $request = new Request();
        // create a simple controller object that is not a DashboardControllerInterface
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
            DashboardController::class,
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
        $this->expectExceptionMessage(sprintf('Unable to find the controller "%s::index".', DashboardController::class));

        $this->controllerFactory->getDashboardControllerInstance(
            DashboardController::class,
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
                // verify the backslashes were decoded
                $this->assertSame([CategoryCrudController::class, 'index'], $controller);

                return [$crudController, 'index'];
            });

        $result = $this->controllerFactory->getCrudControllerInstance(
            str_replace('\\', '%5C', CategoryCrudController::class),
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
            CategoryCrudController::class,
            'index',
            $request
        );
    }

    public function testClassesThatAreNotControllersOfTheRequestedTypeAreNotResolved(): void
    {
        $this->controllerResolver->expects($this->never())->method('getController');

        $this->assertNull($this->controllerFactory->getCrudControllerInstance(\ArrayObject::class, 'index', new Request()));
        $this->assertNull($this->controllerFactory->getCrudControllerInstance(DashboardController::class, 'index', new Request()));
        $this->assertNull($this->controllerFactory->getDashboardControllerInstance(CategoryCrudController::class, new Request()));
    }

    public function testStringsThatAreNotClassesAreNotResolved(): void
    {
        $this->controllerResolver->expects($this->never())->method('getController');

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unable to find the controller "router::index".');

        $this->controllerFactory->getCrudControllerInstance('router', 'index', new Request());
    }
}
