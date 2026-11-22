<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Widgets;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets\WidgetsDashboardController;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Checks the cache of widget contents (the per-user and shared cache is checked in SecuredApp).
 */
class WidgetsCacheTest extends WebTestCase
{
    private KernelBrowser $client;
    // the cache of the test app is stored in files shared by all tests (and by all ParaTest
    // workers), so each test uses its own range to get cache entries that no one else uses
    private string $range;
    private string $otherRange;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->range = 'range-'.bin2hex(random_bytes(8));
        $this->otherRange = 'range-'.bin2hex(random_bytes(8));
        WidgetsDashboardController::$cachedWidgetComputations = 0;
    }

    public function testCachedWidgetIsComputedOnce(): void
    {
        $this->client->request('GET', '/widgets-admin?range='.$this->range);
        $this->assertCachedStatValue('Cached 1 for range '.$this->range);

        $this->client->request('GET', '/widgets-admin?range='.$this->range);
        $this->assertCachedStatValue('Cached 1 for range '.$this->range);

        $this->assertSame(1, WidgetsDashboardController::$cachedWidgetComputations);
    }

    public function testPageAndFragmentShareTheCachedContents(): void
    {
        $this->client->request('GET', '/widgets-admin?range='.$this->range);
        $this->assertCachedStatValue('Cached 1 for range '.$this->range);

        $this->client->request('GET', '/widgets-admin/_widget/cached-stat?range='.$this->range);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('.ea-stat-value', 'Cached 1 for range '.$this->range);
        $this->assertSame(1, WidgetsDashboardController::$cachedWidgetComputations);
    }

    public function testContextAttributesArePartOfTheCacheKey(): void
    {
        $this->client->request('GET', '/widgets-admin?range='.$this->range);
        $this->assertCachedStatValue('Cached 1 for range '.$this->range);

        $this->client->request('GET', '/widgets-admin?range='.$this->otherRange);
        $this->assertCachedStatValue('Cached 2 for range '.$this->otherRange);

        $this->client->request('GET', '/widgets-admin?range='.$this->range);
        $this->assertCachedStatValue('Cached 1 for range '.$this->range);

        // query parameters that are not context attributes don't change the cache key
        $this->client->request('GET', '/widgets-admin?page=3&range='.$this->otherRange);
        $this->assertCachedStatValue('Cached 2 for range '.$this->otherRange);

        $this->assertSame(2, WidgetsDashboardController::$cachedWidgetComputations);
    }

    private function assertCachedStatValue(string $expectedValue): void
    {
        $this->assertSelectorTextSame('[data-ea-widget="cached-stat"] .ea-stat-value', $expectedValue);
    }
}
