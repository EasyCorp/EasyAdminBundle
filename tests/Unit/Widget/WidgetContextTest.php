<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget;

use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\InMemoryUser;

class WidgetContextTest extends TestCase
{
    public function testGetters(): void
    {
        $request = new Request();
        $user = new InMemoryUser('admin', null);
        $widget = new WidgetDto();

        $context = new WidgetContext($request, $user, 'App\Controller\Admin\DashboardController', $widget, ['range' => '30d']);

        $this->assertSame($request, $context->getRequest());
        $this->assertSame($user, $context->getUser());
        $this->assertSame('App\Controller\Admin\DashboardController', $context->getDashboardControllerFqcn());
        $this->assertSame($widget, $context->getWidget());
        $this->assertSame(['range' => '30d'], $context->getAttributes());
    }

    public function testAnonymousUser(): void
    {
        $context = new WidgetContext(new Request(), null, 'App\Controller\Admin\DashboardController', new WidgetDto());

        $this->assertNull($context->getUser());
        $this->assertSame([], $context->getAttributes());
    }

    public function testAttributes(): void
    {
        $context = new WidgetContext(new Request(), null, 'App\Controller\Admin\DashboardController', new WidgetDto(), ['range' => '30d', 'tenant' => null]);

        $this->assertTrue($context->hasAttribute('range'));
        $this->assertTrue($context->hasAttribute('tenant'));
        $this->assertFalse($context->hasAttribute('missing'));

        $this->assertSame('30d', $context->getAttribute('range'));
        $this->assertNull($context->getAttribute('tenant', 'default'), 'null values are returned instead of the default value');
        $this->assertNull($context->getAttribute('missing'));
        $this->assertSame('default', $context->getAttribute('missing', 'default'));
    }

    public function testResolveCallsClosuresWithTheContext(): void
    {
        $context = new WidgetContext(new Request(), null, 'App\Controller\Admin\DashboardController', new WidgetDto(), ['range' => '7d']);

        $this->assertSame('7d', $context->resolve(static fn (WidgetContext $context): string => $context->getAttribute('range')));
        $this->assertSame($context, $context->resolve(static fn (WidgetContext $context): WidgetContext => $context));
    }

    public function testResolveCallsInvokableObjectsWithTheContext(): void
    {
        $context = new WidgetContext(new Request(), null, 'App\Controller\Admin\DashboardController', new WidgetDto(), ['range' => '7d']);
        $invokable = new class {
            public function __invoke(WidgetContext $context): string
            {
                return 'range: '.$context->getAttribute('range');
            }
        };

        $this->assertSame('range: 7d', $context->resolve($invokable));
    }

    #[DataProvider('provideValuesReturnedUntouched')]
    public function testResolveReturnsOtherValuesUntouched(mixed $value): void
    {
        $context = new WidgetContext(new Request(), null, 'App\Controller\Admin\DashboardController', new WidgetDto());

        $this->assertSame($value, $context->resolve($value));
    }

    public static function provideValuesReturnedUntouched(): iterable
    {
        yield 'function name' => ['time'];
        yield 'dangerous function name' => ['phpinfo'];
        yield 'static method name' => ['DateTime::createFromFormat'];
        yield 'callable array' => [[\DateTimeImmutable::class, 'createFromFormat']];
        yield 'callable array with object' => [[new \ArrayObject(), 'count']];
        yield 'integer' => [42];
        yield 'float' => [3.14];
        yield 'null' => [null];
        yield 'array' => [['a' => 1]];
        yield 'not invokable object' => [new \ArrayObject()];
    }

    public function testFirstClassCallablesReceiveTheContextAsTheirFirstArgument(): void
    {
        $context = new WidgetContext(new Request(), null, 'App\Controller\Admin\DashboardController', new WidgetDto());
        $repository = new class {
            public function countAll(): int
            {
                return 3;
            }

            /**
             * @param array<string, mixed> $criteria
             */
            public function count(array $criteria = []): int
            {
                return \count($criteria);
            }
        };

        $this->assertSame(3, $context->resolve($repository->countAll(...)));
        // the context isn't passed to callables whose first argument can't receive it
        $this->assertSame(0, $context->resolve($repository->count(...)));
    }
}
