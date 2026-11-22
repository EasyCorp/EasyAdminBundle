<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget;

use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;
use EasyCorp\Bundle\EasyAdminBundle\Widget\StatWidget;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatableMessage;
use Twig\Markup;

class StatWidgetTest extends TestCase
{
    public function testDefaults(): void
    {
        $dto = StatWidget::new('Users')->getAsDto();

        self::assertSame('Users', $dto->getLabel());
        self::assertSame('widget/stat', $dto->getTemplateName());
        self::assertNull($dto->getTemplatePath());
        self::assertSame(3, $dto->getColumns());
        self::assertSame('stat', $dto->getType());
        self::assertSame(StatWidget::class, $dto->getWidgetFqcn());
        self::assertNull($dto->getCustomOption(StatWidget::OPTION_VALUE));
        self::assertNull($dto->getCustomOption(StatWidget::OPTION_VALUE_FORMATTER));
        self::assertNull($dto->getCustomOption(StatWidget::OPTION_DESCRIPTION));
        self::assertNull($dto->getCustomOption(StatWidget::OPTION_DELTA));
        self::assertTrue($dto->getCustomOption(StatWidget::OPTION_INCREASE_IS_GOOD));
        self::assertSame(['value' => null, 'delta' => null], $this->resolveData($dto));
    }

    public function testLabelIsOptional(): void
    {
        self::assertNull(StatWidget::new()->getAsDto()->getLabel());
    }

    public function testSetters(): void
    {
        $description = new TranslatableMessage('stat.description');
        $dto = StatWidget::new('Users')
            ->setValue(42)
            ->setDescription($description)
            ->setDelta(-3.5, false)
            ->getAsDto();

        self::assertSame(42, $dto->getCustomOption(StatWidget::OPTION_VALUE));
        self::assertSame($description, $dto->getCustomOption(StatWidget::OPTION_DESCRIPTION));
        self::assertSame(-3.5, $dto->getCustomOption(StatWidget::OPTION_DELTA));
        self::assertFalse($dto->getCustomOption(StatWidget::OPTION_INCREASE_IS_GOOD));
        self::assertSame(['value' => 42, 'delta' => -3.5], $this->resolveData($dto));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function provideValidValues(): iterable
    {
        yield 'integer' => [42];
        yield 'float' => [3.14];
        yield 'string' => ['1.2K'];
        yield 'null' => [null];
        // strings are values, never callables
        yield 'name of a function' => ['time'];
        yield 'name of a dangerous function' => ['phpinfo'];
    }

    #[DataProvider('provideValidValues')]
    public function testScalarValuesAreDisplayedAsTheyAre(mixed $value): void
    {
        $dto = StatWidget::new()->setValue($value)->getAsDto();

        self::assertSame($value, $this->resolveData($dto)['value']);
    }

    public function testStringableValue(): void
    {
        $value = new Markup('<strong>42</strong>', 'UTF-8');
        $dto = StatWidget::new()->setValue($value)->getAsDto();

        self::assertSame($value, $this->resolveData($dto)['value']);
    }

    public function testClosureValueReceivesTheContext(): void
    {
        $dto = StatWidget::new()
            ->setValue(static fn (WidgetContext $context): int => $context->getAttribute('multiplier') * 10)
            ->getAsDto();

        self::assertSame(30, $this->resolveData($dto, ['multiplier' => 3])['value']);
    }

    public function testInvokableObjectValue(): void
    {
        $dto = StatWidget::new()
            ->setValue(new class {
                public function __invoke(WidgetContext $context): string
                {
                    return 'invoked';
                }
            })
            ->getAsDto();

        self::assertSame('invoked', $this->resolveData($dto)['value']);
    }

    public function testClosuresAreNotCalledUntilTheWidgetIsRendered(): void
    {
        $called = false;
        StatWidget::new()->setValue(static function () use (&$called): int {
            $called = true;

            return 1;
        });

        self::assertFalse($called);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function provideInvalidResolvedValues(): iterable
    {
        yield 'array' => [[1, 2]];
        yield 'boolean' => [true];
        yield 'object' => [new \stdClass()];
    }

    #[DataProvider('provideInvalidResolvedValues')]
    public function testInvalidValueThrows(mixed $value): void
    {
        $dto = StatWidget::new()->setName('users')->setValue(static fn (): mixed => $value)->getAsDto();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The value of the "users" stat widget must be');

        $this->resolveData($dto);
    }

    public function testFormatValue(): void
    {
        $dto = StatWidget::new()
            ->setValue(static fn (): int => 1234)
            ->formatValue(static fn (mixed $value, WidgetContext $context): string => sprintf('%s %d', $context->getAttribute('currency'), $value))
            ->getAsDto();

        self::assertSame('EUR 1234', $this->resolveData($dto, ['currency' => 'EUR'])['value']);
    }

    public function testFormatValueKeepsMarkupObjects(): void
    {
        $dto = StatWidget::new()
            ->setValue(5)
            ->formatValue(static fn (mixed $value): Markup => new Markup(sprintf('<em>%d</em>', $value), 'UTF-8'))
            ->getAsDto();

        $value = $this->resolveData($dto)['value'];
        self::assertInstanceOf(Markup::class, $value);
        self::assertSame('<em>5</em>', (string) $value);
    }

    public function testFormatValueReceivesValuesOfAnyType(): void
    {
        $dto = StatWidget::new()
            ->setValue(static fn (): \DateTimeImmutable => new \DateTimeImmutable('2026-01-15'))
            ->formatValue(static fn (\DateTimeImmutable $value): string => $value->format('M j'))
            ->getAsDto();

        self::assertSame('Jan 15', $this->resolveData($dto)['value']);
    }

    public function testFormatValueCanBeRemoved(): void
    {
        $dto = StatWidget::new()
            ->setValue(5)
            ->formatValue(static fn (mixed $value): string => 'formatted')
            ->formatValue(null)
            ->getAsDto();

        self::assertNull($dto->getCustomOption(StatWidget::OPTION_VALUE_FORMATTER));
        self::assertSame(5, $this->resolveData($dto)['value']);
    }

    public function testFormatValueMustReturnAString(): void
    {
        $dto = StatWidget::new()->setName('users')->setValue(5)->formatValue(static fn (mixed $value): int => 5)->getAsDto();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The callable passed to the "formatValue()" method of the "users" stat widget must return a string or a Stringable object (int returned).');

        $this->resolveData($dto);
    }

    /**
     * @return iterable<string, array{callable}>
     */
    public static function provideCallablesThatAreNotAccepted(): iterable
    {
        yield 'function name' => ['time'];
        yield 'dangerous function name' => ['phpinfo'];
        yield 'static method string' => ['DateTime::createFromFormat'];
        yield 'array callable' => [[new \ArrayObject(), 'count']];
    }

    #[DataProvider('provideCallablesThatAreNotAccepted')]
    public function testFormatValueRejectsStringAndArrayCallables(callable $callable): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('only accepts closures and invokable objects');

        StatWidget::new()->formatValue($callable);
    }

    #[DataProvider('provideCallablesThatAreNotAccepted')]
    public function testSetDeltaRejectsStringAndArrayCallables(callable $callable): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('only accepts closures and invokable objects');

        StatWidget::new()->setDelta($callable);
    }

    public function testClosureDeltaReceivesTheContext(): void
    {
        $dto = StatWidget::new()
            ->setDelta(static fn (WidgetContext $context): float => $context->getAttribute('delta'))
            ->getAsDto();

        self::assertSame(8.2, $this->resolveData($dto, ['delta' => 8.2])['delta']);
    }

    public function testNumericStringDeltaIsConvertedToANumber(): void
    {
        $dto = StatWidget::new()->setDelta(static fn (): string => '-2.50')->getAsDto();

        self::assertSame(-2.5, $this->resolveData($dto)['delta']);
    }

    public function testClosureDeltaCanReturnNull(): void
    {
        $dto = StatWidget::new()->setDelta(static fn (): ?float => null)->getAsDto();

        self::assertNull($this->resolveData($dto)['delta']);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function provideInvalidResolvedDeltas(): iterable
    {
        yield 'string' => ['8%'];
        yield 'infinite' => [\INF];
        yield 'not a number' => [\NAN];
    }

    #[DataProvider('provideInvalidResolvedDeltas')]
    public function testInvalidDeltaThrows(mixed $delta): void
    {
        $dto = StatWidget::new()->setName('users')->setDelta(static fn (): mixed => $delta)->getAsDto();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The delta of the "users" stat widget must be');

        $this->resolveData($dto);
    }

    public function testDataOfClonedWidgetsIsIndependent(): void
    {
        $widget = StatWidget::new()->setValue(1);
        $clone = (clone $widget)->setValue(2);

        self::assertSame(1, $this->resolveData($widget->getAsDto())['value']);
        self::assertSame(2, $this->resolveData($clone->getAsDto())['value']);
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
