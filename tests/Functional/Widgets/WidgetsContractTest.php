<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Widgets;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets\Contract\EntityCount;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets\Contract\RowCountWidgetConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use function Zenstruck\Foundry\Persistence\delete;

/**
 * Checks that a custom widget built only with the public API (as a third-party
 * package would build it) works like the built-in ones: services through a widget
 * configurator and through a Twig component, the "extra" block of the Stat component, context attributes, cache,
 * lazy loading, assets of a named asset package, permissions and cloning.
 */
class WidgetsContractTest extends WebTestCase
{
    private KernelBrowser $client;
    // the cache of the test app is stored in files shared by all tests (and by all ParaTest
    // workers), so the tests of the cache use their own ranges to get unused cache entries
    private string $range;
    private string $otherRange;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->range = 'range-'.bin2hex(random_bytes(8));
        $this->otherRange = 'range-'.bin2hex(random_bytes(8));
        EntityCount::$countsPerWidget = [];
        RowCountWidgetConfigurator::$callsPerWidget = [];
    }

    public function testCustomWidgetCountsTheRowsWithAService(): void
    {
        CategoryFactory::createMany(3);

        $crawler = $this->client->request('GET', '/widgets-contract-admin');

        $this->assertResponseIsSuccessful();
        $widget = $crawler->filter('.ea-widget[data-ea-widget="plain-count"]');
        $this->assertSame('ea-widget ea-widget-entity-count ea-widget-cols-3', $widget->attr('class'));
        $this->assertSame('Plain count', $widget->filter('.ea-card-title')->text());
        $this->assertSame('3', $widget->filter('.ea-stat-value')->text());
        $this->assertSame(1, EntityCount::$countsPerWidget['plain-count']);
    }

    public function testCustomWidgetFillsTheChartBlockOfTheStatComponent(): void
    {
        $crawler = $this->client->request('GET', '/widgets-contract-admin?range=30d');

        $stat = $crawler->filter('[data-ea-widget="plain-count"] .ea-stat');
        $this->assertSame(['ea-stat-main', 'ea-stat-extra', 'ea-stat-description'], $stat->children()->each(static fn (Crawler $node): string => (string) $node->attr('class')));
        $this->assertSame('Chart for 30d', $stat->filter('.ea-stat-extra > span.contract-chart')->text());
        $this->assertSame('Range 30d', $stat->filter('.ea-stat-description')->text());
    }

    public function testToolbarWidgetSwitchesTheRangeOfTheCustomWidget(): void
    {
        $crawler = $this->client->request('GET', '/widgets-contract-admin');

        $links = $crawler->filter('.page-actions [data-ea-widget="contract-range"] a.contract-range');
        $this->assertSame(['7d', '30d'], $links->each(static fn (Crawler $node): string => $node->text()));
        $this->assertSame('7d', $links->filter('.active')->text());
        $this->assertSame('Range 7d', $crawler->filter('[data-ea-widget="plain-count"] .ea-stat-description')->text());

        $crawler = $this->client->click($links->selectLink('30d')->link());

        $this->assertResponseIsSuccessful();
        $this->assertSame('/widgets-contract-admin?range=30d', $this->client->getRequest()->getRequestUri());
        $this->assertSame('30d', $crawler->filter('a.contract-range.active')->text());
        $this->assertSame('Range 30d', $crawler->filter('[data-ea-widget="plain-count"] .ea-stat-description')->text());
    }

    public function testCacheOfTheCustomWidgetFollowsTheRange(): void
    {
        $categories = CategoryFactory::createMany(2);

        $crawler = $this->client->request('GET', '/widgets-contract-admin?range='.$this->range);
        $cachedBody = $crawler->filter('[data-ea-widget="cached-count"] [data-ea-widget-body]')->html();
        $this->assertSame('2', $crawler->filter('[data-ea-widget="cached-count"] .ea-stat-value')->text());

        // a cache hit displays the old number of rows and doesn't count them again
        CategoryFactory::createOne();
        $crawler = $this->client->request('GET', '/widgets-contract-admin?range='.$this->range);
        $this->assertSame($cachedBody, $crawler->filter('[data-ea-widget="cached-count"] [data-ea-widget-body]')->html());
        $this->assertSame('3', $crawler->filter('[data-ea-widget="plain-count"] .ea-stat-value')->text());
        $this->assertSame(1, EntityCount::$countsPerWidget['cached-count']);

        // other range, other cache entry
        delete($categories[0]);
        $crawler = $this->client->request('GET', '/widgets-contract-admin?range='.$this->otherRange);
        $this->assertSame('2', $crawler->filter('[data-ea-widget="cached-count"] .ea-stat-value')->text());
        $this->assertSame('Range '.$this->otherRange, $crawler->filter('[data-ea-widget="cached-count"] .ea-stat-description')->text());
        $this->assertSame(2, EntityCount::$countsPerWidget['cached-count']);

        $crawler = $this->client->request('GET', '/widgets-contract-admin?range='.$this->range);
        $this->assertSame($cachedBody, $crawler->filter('[data-ea-widget="cached-count"] [data-ea-widget-body]')->html());
        $this->assertSame(2, EntityCount::$countsPerWidget['cached-count']);
    }

    public function testLazyCustomWidgetIsCountedInItsOwnRequestWithTheRangeOfThePage(): void
    {
        CategoryFactory::createMany(2);

        $crawler = $this->client->request('GET', '/widgets-contract-admin?range=30d');

        $widget = $crawler->filter('.ea-widget[data-ea-widget="lazy-count"]');
        $this->assertSame('/widgets-contract-admin/_widget/lazy-count?range=30d', $widget->attr('data-ea-widget-url'));
        $this->assertCount(1, $widget->filter('[data-ea-widget-body] .ea-skeleton'));
        $this->assertCount(0, $widget->filter('.ea-stat'));
        $this->assertArrayNotHasKey('lazy-count', EntityCount::$countsPerWidget);

        CategoryFactory::createOne();
        $this->client->request('GET', (string) $widget->attr('data-ea-widget-url'));

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('X-EasyAdmin-Widget', 'lazy-count');
        $this->assertSelectorTextSame('.ea-stat-value', '3');
        $this->assertSelectorTextSame('.ea-stat-description', 'Range 30d');
        $this->assertSelectorTextSame('.ea-stat-extra .contract-chart', 'Chart for 30d');
        $this->assertSame(1, EntityCount::$countsPerWidget['lazy-count']);
    }

    public function testAssetsOfTheCustomWidgetUseTheirAssetPackage(): void
    {
        $crawler = $this->client->request('GET', '/widgets-contract-admin');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('head link[rel="stylesheet"][href="/bundles/contractwidgets/entity-count.css"]'));
        $this->assertCount(1, $crawler->filter('head script[src="/bundles/contractwidgets/entity-count.js"]'));
    }

    public function testDeniedCustomWidgetIsHiddenAndNeverCounts(): void
    {
        $crawler = $this->client->request('GET', '/widgets-contract-admin');

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('[data-ea-widget="denied-count"]'));
        $this->assertArrayNotHasKey('denied-count', EntityCount::$countsPerWidget);

        $this->client->request('GET', '/widgets-contract-admin/_widget/denied-count');

        // visitors of this app are anonymous, so the access denied error is a 401 response
        $this->assertResponseStatusCodeSame(401);
        $this->assertArrayNotHasKey('denied-count', EntityCount::$countsPerWidget);
    }

    public function testClonedCustomWidgetIsIndependentOfTheOriginal(): void
    {
        CategoryFactory::createMany(2);
        BlogPostFactory::createMany(3);

        $crawler = $this->client->request('GET', '/widgets-contract-admin');

        $original = $crawler->filter('[data-ea-widget="plain-count"]');
        $this->assertSame('ea-widget ea-widget-entity-count ea-widget-cols-3', $original->attr('class'));
        $this->assertSame('2', $original->filter('.ea-stat-value')->text());

        $clone = $crawler->filter('[data-ea-widget="cloned-count"]');
        $this->assertSame('ea-widget ea-widget-entity-count ea-widget-cols-6 cloned-class', $clone->attr('class'));
        $this->assertSame('Plain count', $clone->filter('.ea-card-title')->text());
        $this->assertSame('3', $clone->filter('.ea-stat-value')->text());

        // the page merges the assets of all widgets, so this only checks that both are loaded once
        $this->assertCount(1, $crawler->filter('head link[href="/bundles/contractwidgets/cloned-only.css"]'));
        $this->assertCount(1, $crawler->filter('head link[href="/bundles/contractwidgets/entity-count.css"]'));
    }

    public function testConfiguratorGetsTheDataOfTheWidgetFromAService(): void
    {
        CategoryFactory::createMany(3);

        $crawler = $this->client->request('GET', '/widgets-contract-admin?range=30d');

        $this->assertResponseIsSuccessful();
        $widget = $crawler->filter('.ea-widget[data-ea-widget="configured-count"]');
        $this->assertSame('ea-widget ea-widget-row-count ea-widget-cols-3', $widget->attr('class'));
        $this->assertSame('3', $widget->filter('.ea-stat-value')->text());
        $this->assertSame('Range 30d', $widget->filter('.ea-stat-description')->text());
        $this->assertSame(1, RowCountWidgetConfigurator::$callsPerWidget['configured-count']);
        $this->assertArrayNotHasKey('plain-count', RowCountWidgetConfigurator::$callsPerWidget, 'Configurators only run for the widgets they support.');
    }

    /**
     * Configurators run when the contents are computed, which doesn't happen in every
     * request, so what they change must never be visible outside those contents.
     */
    public function testConfiguratorCantChangeTheOptionsUsedOutsideTheContents(): void
    {
        $crawler = $this->client->request('GET', '/widgets-contract-admin?range='.$this->range);

        $this->assertSame('Configured count', $crawler->filter('[data-ea-widget="configured-count"] .ea-card-title')->text());
        $this->assertSame('Configured cached count', $crawler->filter('[data-ea-widget="configured-cached-count"] .ea-card-title')->text());
        $this->assertSame('Configured lazy count', $crawler->filter('[data-ea-widget="configured-lazy-count"] .ea-card-title')->text());

        $crawler = $this->client->request('GET', '/widgets-contract-admin?range='.$this->range);

        $this->assertSame('Configured cached count', $crawler->filter('[data-ea-widget="configured-cached-count"] .ea-card-title')->text());
    }

    public function testConfiguratorDoesNotRunForCachedContents(): void
    {
        CategoryFactory::createMany(2);
        $this->client->request('GET', '/widgets-contract-admin?range='.$this->range);
        $this->assertSelectorTextSame('[data-ea-widget="configured-cached-count"] .ea-stat-value', '2');

        CategoryFactory::createOne();
        $this->client->request('GET', '/widgets-contract-admin?range='.$this->range);
        $this->assertSelectorTextSame('[data-ea-widget="configured-cached-count"] .ea-stat-value', '2');
        $this->assertSelectorTextSame('[data-ea-widget="configured-count"] .ea-stat-value', '3');

        $this->assertSame(1, RowCountWidgetConfigurator::$callsPerWidget['configured-cached-count']);
        $this->assertSame(2, RowCountWidgetConfigurator::$callsPerWidget['configured-count']);
    }

    public function testConfiguratorOfLazyWidgetRunsInItsOwnRequest(): void
    {
        CategoryFactory::createMany(2);

        $crawler = $this->client->request('GET', '/widgets-contract-admin?range=30d');

        $widget = $crawler->filter('.ea-widget[data-ea-widget="configured-lazy-count"]');
        $this->assertCount(1, $widget->filter('[data-ea-widget-body] .ea-skeleton'));
        $this->assertArrayNotHasKey('configured-lazy-count', RowCountWidgetConfigurator::$callsPerWidget);

        $this->client->request('GET', (string) $widget->attr('data-ea-widget-url'));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('.ea-stat-value', '2');
        $this->assertSelectorTextSame('.ea-stat-description', 'Range 30d');
        $this->assertSame(1, RowCountWidgetConfigurator::$callsPerWidget['configured-lazy-count']);
    }

    public function testConfiguratorNeverRunsForDeniedWidgets(): void
    {
        $crawler = $this->client->request('GET', '/widgets-contract-admin');

        $this->assertCount(0, $crawler->filter('[data-ea-widget="configured-denied-count"]'));

        $this->client->request('GET', '/widgets-contract-admin/_widget/configured-denied-count');

        $this->assertResponseStatusCodeSame(401);
        $this->assertArrayNotHasKey('configured-denied-count', RowCountWidgetConfigurator::$callsPerWidget);
    }
}
