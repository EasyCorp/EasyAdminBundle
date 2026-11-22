<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WidgetDtoTest extends TestCase
{
    #[DataProvider('provideWidgetFqcns')]
    public function testGetType(?string $widgetFqcn, string $expectedType): void
    {
        $dto = new WidgetDto();
        if (null !== $widgetFqcn) {
            $dto->setWidgetFqcn($widgetFqcn);
        }

        $this->assertSame($expectedType, $dto->getType());
    }

    public static function provideWidgetFqcns(): iterable
    {
        yield 'no class' => [null, 'custom'];
        yield 'built-in widget' => ['EasyCorp\Bundle\EasyAdminBundle\Widget\StatWidget', 'stat'];
        yield 'several words' => ['App\Admin\Widget\LatestOrdersWidget', 'latest-orders'];
        yield 'without the suffix' => ['App\Admin\SalesChart', 'sales-chart'];
        yield 'not namespaced' => ['RevenueWidget', 'revenue'];
        yield 'not namespaced without the suffix' => ['Revenue', 'revenue'];
        yield 'only the suffix' => ['App\Widget', 'custom'];
        yield 'suffix in the middle' => ['App\WidgetCounter', 'widget-counter'];
        yield 'with numbers' => ['App\Top10Widget', 'top10'];
    }

    public function testGetTypeOfAnonymousClassesDoesNotIncludeTheirFilePath(): void
    {
        $dto = new WidgetDto();
        $dto->setWidgetFqcn((new class {})::class);
        $this->assertSame('custom', $dto->getType());

        $dto->setWidgetFqcn((new class extends \ArrayObject {})::class);
        $this->assertSame('custom', $dto->getType());
    }

    public function testColumns(): void
    {
        $dto = new WidgetDto();
        $this->assertSame(12, $dto->getColumns());

        $dto->setDefaultColumns(3);
        $this->assertSame(3, $dto->getColumns());

        $dto->setColumns(8);
        $this->assertSame(8, $dto->getColumns());
        $this->assertSame(3, $dto->getDefaultColumns());

        $dto->setColumns(null);
        $this->assertSame(3, $dto->getColumns());
    }

    public function testData(): void
    {
        $closure = static fn (): int => 42;

        $dto = new WidgetDto();
        $dto->setData('value', 10);
        $dto->setData('total', $closure);
        $dto->setData('value', 20);

        $this->assertSame(['value' => 20, 'total' => $closure], $dto->getData());
    }

    public function testCssClassIsTrimmed(): void
    {
        $dto = new WidgetDto();
        $dto->setCssClass('  foo bar ');

        $this->assertSame('foo bar', $dto->getCssClass());
    }

    public function testAssetsAreStoredInTheAssetsDto(): void
    {
        $dto = new WidgetDto();
        $dto->addCssAsset(new AssetDto('widget.css'));
        $dto->addJsAsset(new AssetDto('widget.js'));
        $dto->addHtmlContentToHead('<meta>');
        $dto->addHtmlContentToBody('<div></div>');

        $this->assertSame(['widget.css'], array_keys($dto->getAssets()->getCssAssets()));
        $this->assertSame(['widget.js'], array_keys($dto->getAssets()->getJsAssets()));
        $this->assertSame(['<meta>'], $dto->getAssets()->getHeadContents());
        $this->assertSame(['<div></div>'], $dto->getAssets()->getBodyContents());
    }
}
