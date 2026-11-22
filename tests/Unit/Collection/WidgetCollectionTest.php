<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Collection;

use EasyCorp\Bundle\EasyAdminBundle\Collection\WidgetCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetSectionDto;
use PHPUnit\Framework\TestCase;

class WidgetCollectionTest extends TestCase
{
    public function testEmptyCollection(): void
    {
        $collection = new WidgetCollection();

        $this->assertTrue($collection->isEmpty());
        $this->assertCount(0, $collection);
        $this->assertSame([], $collection->all());
        $this->assertSame([], $collection->getSections());
        $this->assertSame([], $collection->getToolbarWidgets());
        $this->assertSame([], iterator_to_array($collection));
        $this->assertNull($collection->get('users'));
        $this->assertFalse($collection->has('users'));
        $this->assertFalse($collection->isDenied('users'));
        $this->assertSame([], $collection->getAssets()->getCssAssets());
    }

    public function testLookup(): void
    {
        $users = $this->createWidget('users');
        $orders = $this->createWidget('orders');
        $range = $this->createWidget('range');
        $sales = $this->createSection('sales', $users, $orders);

        $collection = new WidgetCollection([$sales], [$range], ['revenue']);

        $this->assertFalse($collection->isEmpty());
        $this->assertCount(3, $collection);
        $this->assertSame($users, $collection->get('users'));
        $this->assertSame($range, $collection->get('range'));
        $this->assertTrue($collection->has('orders'));
        $this->assertFalse($collection->has('revenue'));
        $this->assertNull($collection->get('revenue'));
        $this->assertSame(['range' => $range, 'users' => $users, 'orders' => $orders], $collection->all());
        $this->assertSame(['range' => $range, 'users' => $users, 'orders' => $orders], iterator_to_array($collection));
    }

    public function testDeniedNames(): void
    {
        $collection = new WidgetCollection([$this->createSection(null, $this->createWidget('users'))], [], ['revenue']);

        $this->assertTrue($collection->isDenied('revenue'));
        $this->assertFalse($collection->isDenied('users'));
        $this->assertFalse($collection->isDenied('unknown'));
    }

    public function testSections(): void
    {
        $unnamed = $this->createSection(null, $this->createWidget('users'));
        $sales = $this->createSection('sales', $this->createWidget('orders'));
        $toolbarWidget = $this->createWidget('range');

        $collection = new WidgetCollection([$unnamed, $sales], [$toolbarWidget]);

        $this->assertSame([$unnamed, $sales], $collection->getSections());
        $this->assertSame($sales, $collection->getSection('sales'));
        $this->assertNull($collection->getSection('marketing'));
        $this->assertSame([$toolbarWidget], $collection->getToolbarWidgets());
    }

    public function testToolbarWidgetsOnly(): void
    {
        $collection = new WidgetCollection([], [$this->createWidget('range')]);

        $this->assertFalse($collection->isEmpty());
        $this->assertSame([], $collection->getSections());
    }

    public function testMergedAssets(): void
    {
        $users = $this->createWidget('users');
        $users->addCssAsset(new AssetDto('users.css'));
        $users->addCssAsset(new AssetDto('shared.css'));
        $users->addJsAsset(new AssetDto('users.js'));

        $orders = $this->createWidget('orders');
        $orders->addCssAsset(new AssetDto('shared.css'));
        $orders->addJsAsset(new AssetDto('orders.js'));

        $range = $this->createWidget('range');
        $range->addJsAsset(new AssetDto('range.js'));

        $collection = new WidgetCollection([$this->createSection(null, $users, $orders)], [$range]);
        $assets = $collection->getAssets();

        $this->assertSame(['users.css', 'shared.css'], array_keys($assets->getCssAssets()));
        $this->assertSame(['range.js', 'users.js', 'orders.js'], array_keys($assets->getJsAssets()));
        $this->assertSame(['users.js'], array_keys($users->getAssets()->getJsAssets()), 'the assets of the widgets are not modified');
    }

    private function createWidget(string $name): WidgetDto
    {
        $widget = new WidgetDto();
        $widget->setName($name);

        return $widget;
    }

    private function createSection(?string $name, WidgetDto ...$widgets): WidgetSectionDto
    {
        $section = new WidgetSectionDto();
        $section->setName($name);
        foreach ($widgets as $widget) {
            $section->addWidget($widget);
        }

        return $section;
    }
}
