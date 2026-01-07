<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Router;

use EasyCorp\Bundle\EasyAdminBundle\Router\UrlSigner;
use PHPUnit\Framework\TestCase;

class UrlSignerTest extends TestCase
{
    private const KERNEL_SECRET = 'abc123';

    /**
     * @dataProvider provideSignData
     *
     * @group legacy
     */
    public function testSign(string $url, string $expectedResult): void
    {
        $urlSigner = new UrlSigner(self::KERNEL_SECRET);

        $this->assertSame($expectedResult, $urlSigner->sign($url));
    }

    /**
     * @dataProvider provideCheckData
     *
     * @group legacy
     */
    public function testCheck(string $url, bool $expectedResult): void
    {
        $urlSigner = new UrlSigner(self::KERNEL_SECRET);

        $this->assertSame($expectedResult, $urlSigner->check($url));
    }

    public static function provideSignData(): \Generator
    {
        // the host/user/pass/port URL parts don't affect the signature
        yield ['https://example.com/', 'https://example.com/?signature=bS2fxhAzf4E6G4WGnsIUEplAhgVDrQQwjYc1f2wBM_Y'];
        yield ['https://example.com:8080/', 'https://example.com:8080/?signature=bS2fxhAzf4E6G4WGnsIUEplAhgVDrQQwjYc1f2wBM_Y'];
        yield ['https://user@example.com/', 'https://user@example.com/?signature=bS2fxhAzf4E6G4WGnsIUEplAhgVDrQQwjYc1f2wBM_Y'];
        yield ['https://user:pass@example.com/', 'https://user:pass@example.com/?signature=bS2fxhAzf4E6G4WGnsIUEplAhgVDrQQwjYc1f2wBM_Y'];
        yield ['https://user:pass@example.com:8080/', 'https://user:pass@example.com:8080/?signature=bS2fxhAzf4E6G4WGnsIUEplAhgVDrQQwjYc1f2wBM_Y'];
        yield ['https://example.com/foo/bar', 'https://example.com/foo/bar?signature=bS2fxhAzf4E6G4WGnsIUEplAhgVDrQQwjYc1f2wBM_Y'];

        // changing the order of query params should produce the same signed URL
        yield ['https://example.com/foo/bar?crudAction=a&crudControllerFqcn=b', 'https://example.com/foo/bar?crudAction=a&crudControllerFqcn=b&signature=7h96f4SF2XMSviJatMSD7WYOIhYr6MGac2ATPWtrwng'];
        yield ['https://example.com/foo/bar?crudControllerFqcn=b&crudAction=a', 'https://example.com/foo/bar?crudAction=a&crudControllerFqcn=b&signature=7h96f4SF2XMSviJatMSD7WYOIhYr6MGac2ATPWtrwng'];

        // only certain query params are used for the signature
        yield ['https://example.com/foo/bar?crudAction=a', 'https://example.com/foo/bar?crudAction=a&signature=-2POpHMuFDWuaQAjqZEsVsQL062p5D9Pg7k6fSOitHA'];
        yield ['https://example.com/foo/bar?crudAction=a&page=2', 'https://example.com/foo/bar?crudAction=a&page=2&signature=-2POpHMuFDWuaQAjqZEsVsQL062p5D9Pg7k6fSOitHA'];
    }

    public static function provideCheckData(): \Generator
    {
        // if URL doesn't contain any query param, it's OK to not have a signature either
        yield ['https://example.com/', true];

        // if URL contains at least one query param, then it must have a signature too
        yield ['https://example.com/?foo=bar', false];
        yield ['https://example.com/?foo=bar&signature=bS2fxhAzf4E6G4WGnsIUEplAhgVDrQQwjYc1f2wBM_Y', true];

        // it's not enough to have a signature query param; it must have the right value
        yield ['https://example.com/?foo=bar&signature=', false];

        // the order of query params doesn't affect to the signature check
        yield ['https://example.com/foo/bar?crudAction=a&crudControllerFqcn=b&signature=7h96f4SF2XMSviJatMSD7WYOIhYr6MGac2ATPWtrwng', true];
        yield ['https://example.com/foo/bar?crudControllerFqcn=b&crudAction=a&signature=7h96f4SF2XMSviJatMSD7WYOIhYr6MGac2ATPWtrwng', true];
    }
}
