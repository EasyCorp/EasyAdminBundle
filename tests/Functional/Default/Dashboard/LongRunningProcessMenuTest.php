<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Dashboard;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests that the main menu links to the dashboard of the current request when
 * the same kernel serves many requests (FrankenPHP worker mode, RoadRunner, Swoole).
 */
class LongRunningProcessMenuTest extends WebTestCase
{
    #[DataProvider('provideDashboardsWithDashboardLink')]
    public function testDashboardLinkPointsToTheCurrentDashboard(string $firstDashboardPath, string $secondDashboardPath): void
    {
        $client = $this->createLongRunningClient();

        $client->request('GET', $firstDashboardPath.'/category');
        static::assertSame($firstDashboardPath, $this->getMenuLinkPath($client, 'Dashboard'));

        $client->request('GET', $secondDashboardPath.'/category');
        static::assertSame($secondDashboardPath, $this->getMenuLinkPath($client, 'Dashboard'));
    }

    #[DataProvider('provideDashboardsWithRouteLinks')]
    public function testRouteLinksPointToTheCurrentDashboard(string $firstDashboardPath, string $secondDashboardPath): void
    {
        $client = $this->createLongRunningClient();

        $client->request('GET', $firstDashboardPath.'/category');
        static::assertSame($firstDashboardPath.'/custom-page-with-form', $this->getMenuLinkPath($client, 'Admin route'));
        static::assertSame($firstDashboardPath, $this->getMenuLinkPath($client, 'App route'));

        $client->request('GET', $secondDashboardPath.'/category');
        static::assertSame($secondDashboardPath.'/custom-page-with-form', $this->getMenuLinkPath($client, 'Admin route'));
        static::assertSame($secondDashboardPath, $this->getMenuLinkPath($client, 'App route'));
    }

    public static function provideDashboardsWithDashboardLink(): iterable
    {
        yield ['/custom_icon_set_admin', '/custom_icon_set_with_prefix_admin'];
        yield ['/custom_icon_set_with_prefix_admin', '/custom_icon_set_admin'];
    }

    public static function provideDashboardsWithRouteLinks(): iterable
    {
        yield ['/route_menu_first_admin', '/route_menu_second_admin'];
        yield ['/route_menu_second_admin', '/route_menu_first_admin'];
    }

    private function createLongRunningClient(): KernelBrowser
    {
        $client = static::createClient();
        // keep the same kernel (and its services) for all requests, as a worker does
        $client->disableReboot();

        return $client;
    }

    private function getMenuLinkPath(KernelBrowser $client, string $linkLabel): string
    {
        static::assertResponseIsSuccessful();

        $link = $client->getCrawler()->filter('#main-menu')->selectLink($linkLabel);
        static::assertCount(1, $link);

        return parse_url($link->attr('href'), \PHP_URL_PATH);
    }
}
