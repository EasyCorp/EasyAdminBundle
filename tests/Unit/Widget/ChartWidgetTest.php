<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget;

use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;
use EasyCorp\Bundle\EasyAdminBundle\Widget\ChartWidget;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ChartWidgetTest extends TestCase
{
    public function testDefaults(): void
    {
        $dto = ChartWidget::new('Sales')->getAsDto();

        self::assertSame('Sales', $dto->getLabel());
        self::assertSame('widget/chart', $dto->getTemplateName());
        self::assertSame(6, $dto->getColumns());
        self::assertSame('chart', $dto->getType());
        self::assertSame([], $dto->getCustomOption(ChartWidget::OPTION_DATA));
        self::assertSame(['bars' => []], $this->resolveData($dto));
    }

    public function testArrayData(): void
    {
        $data = ['January' => 120, 'February' => 95.5, 2024 => -3];
        $dto = ChartWidget::new()->setData($data)->getAsDto();

        self::assertSame($data, $dto->getCustomOption(ChartWidget::OPTION_DATA));
        self::assertSame($data, $this->resolveData($dto)['bars']);
    }

    public function testNumericStringsAreConvertedToNumbers(): void
    {
        $dto = ChartWidget::new()->setData(static fn (): array => ['January' => '120', 'February' => '95.50'])->getAsDto();

        self::assertSame(['January' => 120, 'February' => 95.5], $this->resolveData($dto)['bars']);
    }

    public function testClosureDataReceivesTheContext(): void
    {
        $dto = ChartWidget::new()
            ->setData(static fn (WidgetContext $context): array => ['Visits' => $context->getAttribute('visits')])
            ->getAsDto();

        self::assertSame(['Visits' => 7], $this->resolveData($dto, ['visits' => 7])['bars']);
    }

    public function testInvokableObjectData(): void
    {
        $dto = ChartWidget::new()
            ->setData(new class {
                /**
                 * @return array<string, int>
                 */
                public function __invoke(WidgetContext $context): array
                {
                    return ['A' => 1];
                }
            })
            ->getAsDto();

        self::assertSame(['A' => 1], $this->resolveData($dto)['bars']);
    }

    /**
     * @return iterable<string, array{callable}>
     */
    public static function provideStringCallables(): iterable
    {
        yield 'function name' => ['time'];
        yield 'dangerous function name' => ['phpinfo'];
    }

    #[DataProvider('provideStringCallables')]
    public function testStringCallablesAreRejected(callable $callable): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('only accepts arrays, closures and invokable objects (string given)');

        ChartWidget::new()->setData($callable);
    }

    public function testArraysThatLookLikeCallablesAreNeverCalled(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The data of the chart widget must be an array of numbers indexed by their labels');

        ChartWidget::new()->setData([new \ArrayObject(), 'count']);
    }

    /**
     * @return iterable<string, array{array<mixed>}>
     */
    public static function provideInvalidArrays(): iterable
    {
        yield 'string value' => [['January' => '120 units']];
        yield 'null value' => [['January' => null]];
        yield 'nested series' => [['January' => [1, 2]]];
        yield 'infinite value' => [['January' => \INF]];
    }

    #[DataProvider('provideInvalidArrays')]
    public function testInvalidArrayDataIsRejectedWhenConfigured(array $data): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('the value of "January" is not a finite number');

        ChartWidget::new()->setData($data);
    }

    #[DataProvider('provideInvalidArrays')]
    public function testInvalidClosureDataIsRejectedWhenRendered(array $data): void
    {
        $dto = ChartWidget::new()->setName('sales')->setData(static fn (): array => $data)->getAsDto();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The data of the "sales" chart widget must be an array of numbers indexed by their labels, but the value of "January" is not a finite number');

        $this->resolveData($dto);
    }

    public function testClosureMustReturnAnArray(): void
    {
        $dto = ChartWidget::new()->setName('sales')->setData(static fn (): \ArrayIterator => new \ArrayIterator(['A' => 1]))->getAsDto();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The data of the "sales" chart widget must be an array of numbers indexed by their labels (ArrayIterator given).');

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
