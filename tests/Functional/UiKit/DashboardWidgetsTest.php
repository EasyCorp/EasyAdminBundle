<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\UiKit;

use EasyCorp\Bundle\EasyAdminBundle\UiKit\ComponentDocumentationReader;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class DashboardWidgetsTest extends WebTestCase
{
    private const URL = '/admin/_ea_ui_kit/dashboard';
    private const WIDGET_TYPES = ['stat', 'chart', 'progress', 'list', 'twig'];

    protected function tearDown(): void
    {
        parent::tearDown();
        self::ensureKernelShutdown();
    }

    public function testIndexLinksToTheDashboardWidgets(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/_ea_ui_kit');

        $this->assertSame(self::URL, $crawler->filter('#main ul a:contains("Dashboard widgets")')->attr('href'));
    }

    public function testLoadedStateRendersEveryWidgetTypeInTheGrid(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::URL);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Dashboard widgets');
        $this->assertSame(['Loaded', 'Loading', 'Empty', 'Errors', 'No widgets'], $crawler->filter('nav[aria-label="Widget state"] a')->each(static fn (Crawler $link) => $link->text()));
        $this->assertSame('Loaded', $crawler->filter('nav[aria-label="Widget state"] a[aria-current="page"]')->text());

        foreach (self::WIDGET_TYPES as $type) {
            $this->assertGreaterThan(0, $crawler->filter(sprintf('.ea-widget-grid > .ea-widget-section > .ea-widgets > .ea-widget.ea-widget-%s', $type))->count(), sprintf('The dashboard includes a "%s" widget.', $type));
        }

        $this->assertCount(2, $crawler->filter('.ea-widget-section-header .ea-widget-section-title'));
        $this->assertCount(1, $crawler->filter('.ea-widget-section-title .ea-widget-section-icon svg'));
        $this->assertCount(1, $crawler->filter('.ea-widget.ea-widget-rows-2'));
        $this->assertCount(1, $crawler->filter('.ea-widgets > .ea-widget.ea-widget-cardless > .ea-widget-body .alert'));
        $this->assertCount(1, $crawler->filter('.page-actions > .ea-widget.ea-widget-toolbar.ea-widget-cardless > .ea-widget-body select'));
        $this->assertCount(6, $crawler->filter('.ea-widget-stat .ea-stat-delta'));
        $this->assertCount(2, $crawler->filter('.ea-widget-chart figure.ea-bar-chart'));
        $this->assertCount(2, $crawler->filter('.ea-widget-progress progress.ea-progress-bar'));
        $this->assertCount(2, $crawler->filter('.ea-widget-list .ea-widget-list-items'));
        $this->assertCount(0, $crawler->filter('.ea-skeleton, .ea-empty-state'));
    }

    public function testLoadingStateRendersSkeletons(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::URL.'?state=loading');

        $this->assertResponseIsSuccessful();
        $widgets = $crawler->filter('.ea-widget');
        $this->assertGreaterThan(0, $widgets->count());
        $this->assertCount($widgets->count(), $crawler->filter('.ea-widget[data-ea-widget-lazy] .ea-widget-body[aria-busy="true"] > .ea-skeleton[role="status"]'));
    }

    public function testEmptyStateRendersTheEmptyContentsOfEachWidget(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::URL.'?state=empty');

        $this->assertResponseIsSuccessful();
        $this->assertCount(2, $crawler->filter('.ea-widget-chart .ea-empty-state'));
        $this->assertCount(2, $crawler->filter('.ea-widget-list .ea-empty-state'));
        $this->assertSame('There are no orders in the selected period.', $crawler->filter('[data-ea-widget="latest-orders"] .ea-empty-state-message')->text());
        // like the stat widget template, stats without a value render an empty state
        $this->assertCount(6, $crawler->filter('.ea-widget-stat .ea-empty-state'));
        $this->assertCount(0, $crawler->filter('.ea-widget-stat .ea-stat'));
        $this->assertSame(['0%', '0%'], $crawler->filter('.ea-widget-progress .ea-progress-value')->each(static fn (Crawler $value) => $value->text()));
    }

    public function testErrorsStateRendersTheErrorOfEachWidget(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::URL.'?state=errors');

        $this->assertResponseIsSuccessful();
        $widgets = $crawler->filter('.ea-widget');
        $this->assertCount($widgets->count(), $crawler->filter('.ea-widget-body .ea-empty-state.ea-empty-state-error'));
        $this->assertCount($widgets->count(), $crawler->filter('.ea-widget-body button[data-ea-widget-retry]'));
    }

    public function testNoWidgetsStateRendersTheEmptyDashboard(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::URL.'?state=no_widgets');

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('.ea-widget'));
        $this->assertCount(1, $crawler->filter('.ea-widget-grid > .ea-empty-state'));
    }

    #[DataProvider('provideStates')]
    public function testWidgetsAreNeverLoadedWithJavaScript(string $state): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::URL.'?state='.$state);

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('[data-ea-widget-url], [data-ea-widget-refresh], script[src*="widgets"]'));

        $ids = $crawler->filter('[id]')->each(static fn (Crawler $element) => $element->attr('id'));
        $this->assertSame(array_unique($ids), $ids, 'The ids of the page are unique.');
        foreach ($crawler->filter('.ea-widget-grid [aria-labelledby], .page-actions [aria-labelledby]') as $element) {
            $this->assertContains($element->getAttribute('aria-labelledby'), $ids);
        }
    }

    public static function provideStates(): iterable
    {
        foreach (['loaded', 'loading', 'empty', 'errors', 'no_widgets'] as $state) {
            yield $state => [$state];
        }
    }

    public function testDashboardRendersWithCustomIconSet(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/custom_icon_set_admin/_ea_ui_kit/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('.ea-widget-grid .icon, .page-actions .icon')->reduce(static fn (Crawler $icon) => 0 === $icon->children()->count()));
    }

    public function testPageShowsTheUsageAndThePropsOfTheComponents(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::URL);

        $this->assertStringContainsString('<twig:ea:Widget:Grid section="sales" />', $crawler->filter('#api-reference .ea-ui-kit-source')->text());
        $this->assertSame('<twig:ea:Widget>', $crawler->filter('#component-widget h3')->text());
        $this->assertSame(['headingLevel', 'name'], $crawler->filter('#component-widget tbody tr td:first-child')->each(static fn (Crawler $cell) => $cell->text()));
        $this->assertSame(['placement', 'section'], $crawler->filter('#component-widget-grid table')->first()->filter('tbody tr td:first-child')->each(static fn (Crawler $cell) => $cell->text()));

        if (ComponentDocumentationReader::isSupported()) {
            $this->assertSame(['empty'], $crawler->filter('#component-widget-grid table')->eq(1)->filter('tbody tr td:first-child')->each(static fn (Crawler $cell) => $cell->text()));
        }
    }

    public function testUnknownStateReturnsNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', self::URL.'?state=foo');

        $this->assertResponseStatusCodeSame(404);
    }
}
