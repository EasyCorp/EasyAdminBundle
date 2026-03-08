<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\AssetsController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\DashboardController;

class AssetsControllerTest extends AbstractCrudTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    protected function getControllerFqcn(): string
    {
        return AssetsController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testCssAssets()
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $headHtmlContents = $crawler->filter('head')->html();
        $bodyHtmlContents = $crawler->filter('body')->html();
        $linkResponseHeaderContents = $this->client->getResponse()->headers->get('Link');

        static::assertStringContainsString('<link rel="stylesheet" href="https://cdn.example.com/css1.css">', $headHtmlContents);
        static::assertStringContainsString('<link rel="stylesheet" href="/css2.css">', $headHtmlContents);
        static::assertStringContainsString('<link rel="stylesheet" href="/css3.css">', $headHtmlContents);
        static::assertStringContainsString('<link rel="stylesheet" href="/foo/bar/css4.css">', $headHtmlContents);
        static::assertStringContainsString('<link rel="stylesheet" href="/css5.css">', $headHtmlContents);
        static::assertStringContainsString('<link rel="stylesheet" href="/css6.css">', $headHtmlContents);
        static::assertStringContainsString('</css6.css>; rel="preload"; as="style",', $linkResponseHeaderContents);
        static::assertStringContainsString('<link rel="stylesheet" href="/css7.css">', $headHtmlContents);
        static::assertStringContainsString('</css7.css>; rel="preload"; as="style"; nopush,', $linkResponseHeaderContents);
        static::assertStringContainsString('<link rel="stylesheet" href="/css8.css" media="print">', $headHtmlContents);
        static::assertStringContainsString('<link rel="stylesheet" href="/css9.css" media="print" title="foo">', $headHtmlContents);
        static::assertStringContainsString('<link rel="stylesheet" href="/css10.css">', $headHtmlContents);
        static::assertStringContainsString('<link rel="stylesheet" href="/css11.css">', $headHtmlContents);
        static::assertStringContainsString('<link rel="stylesheet" href="/css12.css">', $headHtmlContents);

        static::assertStringContainsString('<script src="https://cdn.example.com/js1.js"></script>', $headHtmlContents);
        static::assertStringContainsString('<script src="/js2.js"></script>', $headHtmlContents);
        static::assertStringContainsString('<script src="/js3.js"></script>', $headHtmlContents);
        static::assertStringContainsString('<script src="/foo/bar/js4.js"></script>', $headHtmlContents);
        static::assertStringContainsString('<script src="/js5.js"></script>', $headHtmlContents);
        static::assertStringContainsString('<script src="/js6.js"></script>', $headHtmlContents);
        static::assertStringContainsString('</js6.js>; rel="preload"; as="script",', $linkResponseHeaderContents);
        static::assertStringContainsString('<script src="/js7.js"></script>', $headHtmlContents);
        static::assertStringContainsString('</js7.js>; rel="preload"; as="script"; nopush,', $linkResponseHeaderContents);
        static::assertStringContainsString('<script src="/js8.js" defer></script>', $headHtmlContents);
        static::assertMatchesRegularExpression('/<script src="\/js9\.js" async(="")?>/', $headHtmlContents);
        static::assertMatchesRegularExpression('/<script src="\/js10\.js" async(="")? defer>/', $headHtmlContents);
        static::assertMatchesRegularExpression('/<script src="\/js11\.js" async(="")? defer>/', $headHtmlContents);
        static::assertStringContainsString('</js11.js>; rel="preload"; as="script"; nopush', $linkResponseHeaderContents);
        static::assertStringContainsString('<script src="/js12.js" foo="bar"></script>', $headHtmlContents);
        static::assertStringContainsString('<script src="/js13.js" foo="bar" baz="qux"></script>', $headHtmlContents);

        static::assertResponseHeaderSame('Link', '</css6.css>; rel="preload"; as="style",</css7.css>; rel="preload"; as="style"; nopush,</js6.js>; rel="preload"; as="script",</js7.js>; rel="preload"; as="script"; nopush,</js11.js>; rel="preload"; as="script"; nopush');

        static::assertStringContainsString('<link rel="stylesheet" href="https://cdn.example.com/css11.css">', $headHtmlContents);
        static::assertStringContainsString('<script src="https://cdn.example.com/js14.js"></script>', $bodyHtmlContents);
    }
}
