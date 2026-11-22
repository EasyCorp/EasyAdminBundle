<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget;

use EasyCorp\Bundle\EasyAdminBundle\Widget\ListItem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ListItemTest extends TestCase
{
    public function testDefaults(): void
    {
        $item = ListItem::new('Order 1');

        self::assertSame('Order 1', $item->getTitle());
        self::assertNull($item->getDescription());
        self::assertNull($item->getValue());
        self::assertNull($item->getUrl());
        self::assertNull($item->getIcon());
    }

    public function testSetters(): void
    {
        $item = ListItem::new('Order 1')
            ->setDescription('Description 1')
            ->setValue(19.99)
            ->setUrl('/admin/order/1')
            ->setIcon('fa fa-cart-shopping');

        self::assertSame('Description 1', $item->getDescription());
        self::assertSame(19.99, $item->getValue());
        self::assertSame('/admin/order/1', $item->getUrl());
        self::assertSame('fa fa-cart-shopping', $item->getIcon());
    }

    public function testOptionsCanBeRemoved(): void
    {
        $item = ListItem::new('Order 1')
            ->setDescription('Description 1')->setDescription(null)
            ->setValue(3)->setValue(null)
            ->setUrl('/admin')->setUrl(null)
            ->setIcon('fa fa-user')->setIcon(null);

        self::assertNull($item->getDescription());
        self::assertNull($item->getValue());
        self::assertNull($item->getUrl());
        self::assertNull($item->getIcon());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideSafeUrls(): iterable
    {
        yield 'relative' => ['/admin/order/1?a=b:c'];
        yield 'relative without slash' => ['order/1'];
        yield 'fragment' => ['#top'];
        yield 'https' => ['https://example.com'];
        yield 'mailto' => ['mailto:user@example.com'];
    }

    #[DataProvider('provideSafeUrls')]
    public function testSafeUrls(string $url): void
    {
        self::assertSame($url, ListItem::new('Item 1')->setUrl($url)->getUrl());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideDangerousUrls(): iterable
    {
        yield 'javascript' => ['javascript:alert(1)'];
        yield 'uppercase javascript' => ['JavaScript:alert(1)'];
        yield 'leading whitespace' => [" \x01javascript:alert(1)"];
        yield 'tab inside the scheme' => ["java\tscript:alert(1)"];
        yield 'data' => ['data:text/html,<script>alert(1)</script>'];
        yield 'vbscript' => ['vbscript:msgbox(1)'];
    }

    #[DataProvider('provideDangerousUrls')]
    public function testDangerousUrlsAreRejected(string $url): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The URL of the "Item 1" list item uses a scheme that is not allowed');

        ListItem::new('Item 1')->setUrl($url);
    }
}
