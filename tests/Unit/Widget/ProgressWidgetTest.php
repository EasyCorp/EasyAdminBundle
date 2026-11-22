<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget;

use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;
use EasyCorp\Bundle\EasyAdminBundle\Widget\ProgressWidget;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ProgressWidgetTest extends TestCase
{
    public function testDefaults(): void
    {
        $dto = ProgressWidget::new('Monthly goal')->getAsDto();

        self::assertSame('Monthly goal', $dto->getLabel());
        self::assertSame('widget/progress', $dto->getTemplateName());
        self::assertSame(3, $dto->getColumns());
        self::assertSame('progress', $dto->getType());
        self::assertSame(0, $dto->getCustomOption(ProgressWidget::OPTION_VALUE));
        self::assertSame(100, $dto->getCustomOption(ProgressWidget::OPTION_TARGET));
        self::assertSame(['value' => 0, 'target' => 100], $this->resolveData($dto));
    }

    public function testNumbers(): void
    {
        $dto = ProgressWidget::new()->setValue(37.5)->setTarget(50)->getAsDto();

        self::assertSame(37.5, $dto->getCustomOption(ProgressWidget::OPTION_VALUE));
        self::assertSame(50, $dto->getCustomOption(ProgressWidget::OPTION_TARGET));
        self::assertSame(['value' => 37.5, 'target' => 50], $this->resolveData($dto));
    }

    public function testClosuresReceiveTheContext(): void
    {
        $dto = ProgressWidget::new()
            ->setValue(static fn (WidgetContext $context): int => $context->getAttribute('sales'))
            ->setTarget(static fn (WidgetContext $context): int => $context->getAttribute('goal'))
            ->getAsDto();

        self::assertSame(['value' => 80, 'target' => 200], $this->resolveData($dto, ['sales' => 80, 'goal' => 200]));
    }

    public function testNumericStringsAreConvertedToNumbers(): void
    {
        $dto = ProgressWidget::new()->setValue(static fn (): string => '37.50')->setTarget(static fn (): string => '50')->getAsDto();

        self::assertSame(['value' => 37.5, 'target' => 50], $this->resolveData($dto));
    }

    /**
     * @return iterable<string, array{string, callable}>
     */
    public static function provideCallablesThatAreNotAccepted(): iterable
    {
        foreach (['setValue', 'setTarget'] as $method) {
            yield $method.' with function name' => [$method, 'time'];
            yield $method.' with dangerous function name' => [$method, 'phpinfo'];
            yield $method.' with array callable' => [$method, [new \ArrayObject(), 'count']];
        }
    }

    #[DataProvider('provideCallablesThatAreNotAccepted')]
    public function testStringAndArrayCallablesAreRejected(string $method, callable $callable): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('only accepts numbers, closures and invokable objects');

        ProgressWidget::new()->{$method}($callable);
    }

    public function testInfiniteNumbersAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('only accepts finite numbers');

        ProgressWidget::new()->setTarget(\INF);
    }

    /**
     * @return iterable<string, array{string, string, mixed}>
     */
    public static function provideInvalidResolvedNumbers(): iterable
    {
        foreach (['setValue' => 'value', 'setTarget' => 'target'] as $method => $name) {
            yield $method.' with string' => [$method, $name, '50%'];
            yield $method.' with null' => [$method, $name, null];
            yield $method.' with not a number' => [$method, $name, \NAN];
        }
    }

    #[DataProvider('provideInvalidResolvedNumbers')]
    public function testInvalidResolvedNumbersThrow(string $method, string $name, mixed $number): void
    {
        $dto = ProgressWidget::new()->setName('goal')->{$method}(static fn (): mixed => $number)->getAsDto();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The %s of the "goal" progress widget must be a finite number', $name));

        $this->resolveData($dto);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>
     */
    private function resolveData(WidgetDto $dto, array $attributes = []): array
    {
        $context = new WidgetContext(new Request(), null, 'App\Controller\Admin\DashboardController', $dto, $attributes);

        return array_map(static fn (mixed $valueOrCallable): mixed => $context->resolve($valueOrCallable), $dto->getData());
    }
}
