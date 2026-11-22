<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget;

use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;
use EasyCorp\Bundle\EasyAdminBundle\Widget\ListItem;
use EasyCorp\Bundle\EasyAdminBundle\Widget\ListWidget;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatableMessage;

class ListWidgetTest extends TestCase
{
    public function testDefaults(): void
    {
        $dto = ListWidget::new('Latest orders')->getAsDto();

        self::assertSame('Latest orders', $dto->getLabel());
        self::assertSame('widget/list', $dto->getTemplateName());
        self::assertSame(6, $dto->getColumns());
        self::assertSame('list', $dto->getType());
        self::assertSame([], $dto->getCustomOption(ListWidget::OPTION_ITEMS));
        self::assertNull($dto->getCustomOption(ListWidget::OPTION_EMPTY_MESSAGE));
        self::assertSame(['items' => []], $this->resolveData($dto));
    }

    public function testEmptyMessage(): void
    {
        $message = new TranslatableMessage('orders.empty');

        self::assertSame('No orders yet', ListWidget::new()->setEmptyMessage('No orders yet')->getAsDto()->getCustomOption(ListWidget::OPTION_EMPTY_MESSAGE));
        self::assertSame($message, ListWidget::new()->setEmptyMessage($message)->getAsDto()->getCustomOption(ListWidget::OPTION_EMPTY_MESSAGE));
    }

    public function testArrayItemsAreConvertedToAList(): void
    {
        $item1 = ListItem::new('Item 1');
        $item2 = ListItem::new('Item 2');
        $dto = ListWidget::new()->setItems(['a' => $item1, 'b' => $item2])->getAsDto();

        self::assertSame([$item1, $item2], $this->resolveData($dto)['items']);
    }

    public function testTraversableItems(): void
    {
        $item1 = ListItem::new('Item 1');
        $item2 = ListItem::new('Item 2');
        $dto = ListWidget::new()->setItems(new \ArrayIterator([$item1, $item2]))->getAsDto();

        self::assertSame([$item1, $item2], $this->resolveData($dto)['items']);
    }

    public function testClosureReturningAGeneratorReceivesTheContext(): void
    {
        $dto = ListWidget::new()
            ->setItems(static function (WidgetContext $context): \Generator {
                yield from [ListItem::new('Item 1')];
                // 'yield from' repeats the key 0, which must not overwrite the first item
                yield from [ListItem::new((string) $context->getAttribute('title'))];
            })
            ->getAsDto();

        $items = $this->resolveData($dto, ['title' => 'Item 2'])['items'];

        self::assertCount(2, $items);
        self::assertSame('Item 1', $items[0]->getTitle());
        self::assertSame('Item 2', $items[1]->getTitle());
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
        $this->expectExceptionMessage('only accepts iterables, closures and invokable objects (string given)');

        ListWidget::new()->setItems($callable);
    }

    public function testArraysThatLookLikeCallablesAreNeverCalled(): void
    {
        $dto = ListWidget::new()->setName('orders')->setItems([new \ArrayObject(), 'count'])->getAsDto();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The items of the "orders" list widget must be "%s" objects (ArrayObject given).', ListItem::class));

        $this->resolveData($dto);
    }

    public function testItemsMustBeListItems(): void
    {
        $dto = ListWidget::new()->setName('orders')->setItems(static fn (): array => [ListItem::new('Item 1'), 'Item 2'])->getAsDto();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The items of the "orders" list widget must be "%s" objects (string given).', ListItem::class));

        $this->resolveData($dto);
    }

    public function testClosureMustReturnAnIterable(): void
    {
        $dto = ListWidget::new()->setName('orders')->setItems(static fn (): ListItem => ListItem::new('Item 1'))->getAsDto();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The items of the "orders" list widget must be an iterable of "%s" objects (%s given).', ListItem::class, ListItem::class));

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
