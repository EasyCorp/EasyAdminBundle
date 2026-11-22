<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Widgets;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets\WidgetsDashboardController;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Checks the HTML of the built-in widgets and of the grid, sections and toolbar that contain them.
 */
class WidgetsMarkupTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        WidgetsDashboardController::$hiddenWidgetComputations = 0;
    }

    public function testNoWidgetFails(): void
    {
        $crawler = $this->requestDashboard();

        $this->assertCount(0, $crawler->filter('[data-ea-widget-body] .ea-empty-state-error'));
    }

    public function testStatWidget(): void
    {
        $crawler = $this->requestDashboard();

        $stat1 = $this->widget($crawler, 'stat-1');
        $this->assertSame('ea-widget ea-widget-stat ea-widget-cols-3', $stat1->attr('class'));
        $this->assertSame('group', $stat1->attr('role'));
        $this->assertSame('ea-widget-stat-1-title', $stat1->attr('aria-labelledby'));
        $this->assertSame('Stat 1', $stat1->filter('#ea-widget-stat-1-title')->text());
        $this->assertSame('1,234,567', $stat1->filter('.ea-stat-value')->text());
        $this->assertSame('+8.2%', $stat1->filter('.ea-stat-delta-value')->text());
        $this->assertStringContainsString('ea-stat-delta-good', (string) $stat1->filter('.ea-stat-delta')->attr('class'));
        $this->assertSame('Increase', trim($stat1->filter('.ea-stat-delta .visually-hidden')->text()));
        $this->assertSame('(good)', trim($stat1->filter('.ea-stat-delta .visually-hidden')->last()->text()));
        $this->assertSame('Description 1', $stat1->filter('.ea-stat-description')->text());

        // a decrease is good when 'increaseIsGood' is false
        $stat2 = $this->widget($crawler, 'stat-2');
        $this->assertSame('-3%', $stat2->filter('.ea-stat-delta-value')->text());
        $this->assertStringContainsString('ea-stat-delta-good', (string) $stat2->filter('.ea-stat-delta')->attr('class'));
        $this->assertSame('Decrease', trim($stat2->filter('.ea-stat-delta .visually-hidden')->text()));
        $this->assertCount(0, $stat2->filter('.ea-stat-description'));

        $stat3 = $this->widget($crawler, 'stat-3');
        $this->assertSame('5 units', $stat3->filter('.ea-stat-value')->text());
        $this->assertCount(0, $stat3->filter('.ea-stat-delta'));
    }

    public function testChartWidget(): void
    {
        $crawler = $this->requestDashboard();

        $chart = $this->widget($crawler, 'chart-1');
        $this->assertSame('ea-widget ea-widget-chart ea-widget-cols-6', $chart->attr('class'));
        $this->assertCount(3, $chart->filter('svg rect.ea-bar-chart-bar'));
        $this->assertSame(['Label 1: 10', 'Label 2: 25', '<b>Label 3</b>: 0'], $chart->filter('svg rect title')->each(static fn (Crawler $node): string => $node->text()));
        // screen readers read the data table instead of the plot
        $this->assertSame('true', $chart->filter('.ea-bar-chart-plot')->attr('aria-hidden'));
        $this->assertCount(0, $chart->filter('[role="img"]'));
        $this->assertSame('Chart 1', $chart->filter('table.visually-hidden caption')->text());
        $this->assertSame('Chart 1', $chart->filter('figure.ea-bar-chart')->attr('aria-label'));
        $this->assertSame([['Label 1', '10'], ['Label 2', '25'], ['<b>Label 3</b>', '0']], $chart->filter('table.visually-hidden tbody tr')->each(static fn (Crawler $row): array => [$row->filter('th')->text(), $row->filter('td')->text()]));

        $emptyChart = $this->widget($crawler, 'empty-chart');
        $this->assertCount(0, $emptyChart->filter('.ea-bar-chart'));
        $this->assertSame('No data to display', $emptyChart->filter('[data-ea-widget-body] .ea-empty-state:not(.ea-empty-state-error) .ea-empty-state-message')->text());
    }

    public function testProgressWidget(): void
    {
        $crawler = $this->requestDashboard();

        $progress = $this->widget($crawler, 'progress-1');
        $this->assertSame('ea-widget ea-widget-progress ea-widget-cols-3', $progress->attr('class'));
        $bar = $progress->filter('progress.ea-progress-bar');
        $this->assertSame('30', $bar->attr('value'));
        $this->assertSame('120', $bar->attr('max'));
        $this->assertSame('Progress 1', $bar->attr('aria-label'));
        $this->assertSame('25%', $bar->attr('aria-valuetext'));
        $this->assertSame('25%', $progress->filter('.ea-progress-value')->text());
        $this->assertSame('true', $progress->filter('.ea-progress-value')->attr('aria-hidden'));
        // the title of the card names the widget, so the bar has no visible label
        $this->assertCount(0, $progress->filter('.ea-progress-label, .ea-progress-labelled'));
    }

    public function testListWidget(): void
    {
        $crawler = $this->requestDashboard();

        $list = $this->widget($crawler, 'list-1');
        $this->assertSame('ea-widget ea-widget-list ea-widget-cols-6', $list->attr('class'));
        $items = $list->filter('li.ea-widget-list-item');
        $this->assertCount(2, $items);

        $firstItem = $items->eq(0);
        $this->assertSame('https://example.com/item-1', $firstItem->filter('a.ea-widget-list-item-title')->attr('href'));
        $this->assertSame('Item 1', $firstItem->filter('a.ea-widget-list-item-title')->text());
        $this->assertSame('Item description 1', $firstItem->filter('.ea-widget-list-item-description')->text());
        $this->assertSame('11', $firstItem->filter('.ea-widget-list-item-value')->text());
        $this->assertCount(1, $firstItem->filter('.ea-widget-list-item-icon i.fa-file'));
        $this->assertSame('true', $firstItem->filter('.ea-widget-list-item-icon')->attr('aria-hidden'));

        $secondItem = $items->eq(1);
        $this->assertCount(0, $secondItem->filter('a'));
        $this->assertSame('<i>Item 2</i>', $secondItem->filter('span.ea-widget-list-item-title')->text());
        $this->assertCount(0, $secondItem->filter('.ea-widget-list-item-description, .ea-widget-list-item-value, .ea-widget-list-item-icon'));

        $emptyList = $this->widget($crawler, 'empty-list');
        $this->assertCount(0, $emptyList->filter('li'));
        $this->assertSame('Custom empty message', $emptyList->filter('[data-ea-widget-body] .ea-empty-state-message')->text());
    }

    public function testTwigWidget(): void
    {
        $crawler = $this->requestDashboard('?range=30d');

        $twig = $this->widget($crawler, 'twig-1');
        $this->assertSame('ea-widget ea-widget-twig ea-widget-cols-12', $twig->attr('class'));
        $this->assertSame('Var 1', $twig->filter('.twig-var1')->text());
        $this->assertSame('twig-1', $twig->filter('.twig-widget-name')->text());
        $this->assertSame('30d', $twig->filter('.twig-context-range')->text());
        $this->assertSame(WidgetsDashboardController::class, $twig->filter('.twig-context-dashboard')->text());
    }

    public function testSpanClassesAndCustomCssClass(): void
    {
        $crawler = $this->requestDashboard();

        $stat3 = $this->widget($crawler, 'stat-3');
        $this->assertSame('ea-widget ea-widget-stat ea-widget-cols-4 ea-widget-rows-2 custom-widget-class', $stat3->attr('class'));
        $this->assertCount(0, $crawler->filter('.ea-widget[class*="ea-widget-rows-"]:not([data-ea-widget="stat-3"])'));

        // the body of widgets with rows scrolls, so it can be focused with the keyboard
        $stat3Body = $stat3->filter('[data-ea-widget-body]');
        $this->assertSame('0', $stat3Body->attr('tabindex'));
        $this->assertSame('region', $stat3Body->attr('role'));
        $this->assertSame('ea-widget-stat-3-title', $stat3Body->attr('aria-labelledby'));
        $this->assertCount(1, $crawler->filter('[data-ea-widget-body][tabindex]'));
    }

    public function testCardHeader(): void
    {
        $crawler = $this->requestDashboard();

        $header = $this->widget($crawler, 'stat-2')->filter('.ea-card.ea-widget-card > .ea-card-header');
        $this->assertCount(1, $header->filter('.ea-card-icon i.fa-users'));
        $this->assertSame('Help 2', $header->filter('.ea-card-help')->text());
        $this->assertSame('https://example.com/stat-2', $header->filter('a.ea-card-link')->attr('href'));
        $this->assertSame('Link 2', $header->filter('a.ea-card-link .ea-card-link-label')->text());

        $stat1Header = $this->widget($crawler, 'stat-1')->filter('.ea-card-header');
        $this->assertCount(0, $stat1Header->filter('.ea-card-icon, .ea-card-help, .ea-card-link'));
    }

    public function testCardlessWidget(): void
    {
        $crawler = $this->requestDashboard();

        $notes = $this->widget($crawler, 'notes');
        $this->assertSame('ea-widget ea-widget-twig ea-widget-cols-12 ea-widget-cardless', $notes->attr('class'));
        $this->assertNull($notes->attr('role'));
        $this->assertCount(0, $notes->filter('.ea-card'));
        $this->assertSame('Note 1 (notes)', $notes->filter('.ea-widget > [data-ea-widget-body] p.dashboard-note')->text());
    }

    public function testToolbarWidget(): void
    {
        $crawler = $this->requestDashboard('?range=30d');

        $toolbarWidget = $crawler->filter('.content-header .page-actions > .ea-widget');
        $this->assertCount(1, $toolbarWidget);
        $this->assertSame('ea-widget ea-widget-twig ea-widget-toolbar ea-widget-cardless', $toolbarWidget->attr('class'));
        $this->assertSame('range', $toolbarWidget->attr('data-ea-widget'));
        $this->assertSame('Range: 30d', $toolbarWidget->filter('[data-ea-widget-body] a.range-link')->text());
        $this->assertCount(0, $crawler->filter('.ea-widget-grid [data-ea-widget="range"]'));
    }

    public function testSections(): void
    {
        $crawler = $this->requestDashboard();

        $sections = $crawler->filter('.ea-widget-grid > section.ea-widget-section');
        $this->assertCount(3, $sections);

        // the widgets defined before the first section are in a section without a name or a header
        $this->assertNull($sections->eq(0)->attr('data-ea-widget-section'));
        $this->assertCount(0, $sections->eq(0)->filter('.ea-widget-section-header'));
        $this->assertSame(['stat-1', 'stat-2', 'stat-3', 'assets-stat'], $sections->eq(0)->filter('.ea-widgets > .ea-widget')->each(static fn (Crawler $node): string => (string) $node->attr('data-ea-widget')));

        $section1 = $sections->eq(1);
        $this->assertSame('section-1', $section1->attr('data-ea-widget-section'));
        $this->assertSame('ea-widget-section custom-section-class', $section1->attr('class'));
        $this->assertSame('ea-widgets-section-section-1-title', $section1->attr('aria-labelledby'));
        $this->assertSame('Section 1', $section1->filter('h2.ea-widget-section-title#ea-widgets-section-section-1-title')->text());
        $this->assertCount(1, $section1->filter('h2 .ea-widget-section-icon i.fa-chart-line'));
        $this->assertSame('true', $section1->filter('h2 .ea-widget-section-icon')->attr('aria-hidden'));
        $this->assertSame('Section help 1', $section1->filter('p.ea-widget-section-help')->text());
        $this->assertSame(['chart-1', 'empty-chart', 'progress-1', 'list-1', 'empty-list'], $section1->filter('.ea-widgets > .ea-widget')->each(static fn (Crawler $node): string => (string) $node->attr('data-ea-widget')));

        // the titles of widgets follow the heading of their section, or the page title when the section has none
        $this->assertCount(4, $sections->eq(0)->filter('.ea-widget h2.ea-card-title'));
        $this->assertCount(0, $sections->eq(0)->filter('.ea-widget h3'));
        $this->assertCount(0, $section1->filter('.ea-widget h2'));
        $this->assertGreaterThan(0, $section1->filter('.ea-widget h3.ea-card-title')->count());
        $this->assertCount(0, $crawler->filter('[headingLevel], [headinglevel]'));

        $section2 = $sections->eq(2);
        $this->assertSame('section-two', $section2->attr('data-ea-widget-section'));
        $this->assertCount(0, $section2->filter('p.ea-widget-section-help'));
    }

    public function testHiddenWidgetsAndSectionsAreNotRenderedNorComputed(): void
    {
        $crawler = $this->requestDashboard();

        $this->assertCount(0, $crawler->filter('[data-ea-widget="hidden-stat"]'));
        $this->assertCount(0, $crawler->filter('[data-ea-widget="stat-in-hidden-section"]'));
        $this->assertCount(0, $crawler->filter('[data-ea-widget-section="hidden-section"]'));
        $this->assertStringNotContainsString('Hidden section', (string) $this->client->getResponse()->getContent());
        $this->assertSame(0, WidgetsDashboardController::$hiddenWidgetComputations);
    }

    public function testDataIsEscapedAndDeveloperLabelsAreNot(): void
    {
        $crawler = $this->requestDashboard();
        $content = (string) $this->client->getResponse()->getContent();

        // values computed by widgets are escaped
        $this->assertSame('<b>Value 2</b>', $this->widget($crawler, 'stat-2')->filter('.ea-stat-value')->text());
        $this->assertStringContainsString('&lt;b&gt;Value 2&lt;/b&gt;', $content);
        $this->assertStringContainsString('&lt;i&gt;Item 2&lt;/i&gt;', $content);
        $this->assertStringContainsString('<title>&lt;b&gt;Label 3&lt;/b&gt;: 0</title>', $content);
        $this->assertSame('<b>Var 2</b>', $this->widget($crawler, 'twig-1')->filter('.twig-html-var')->text());

        // labels, help messages and link labels are written by the developer and can contain HTML
        $stat2 = $this->widget($crawler, 'stat-2');
        $this->assertSame('2', $stat2->filter('.ea-card-title em')->text());
        $this->assertSame('2', $stat2->filter('.ea-card-help em')->text());
        $this->assertSame('2', $stat2->filter('.ea-card-link-label em')->text());
        $this->assertSame('1', $crawler->filter('[data-ea-widget-section="section-1"] .ea-widget-section-help em')->text());
    }

    private function requestDashboard(string $queryString = ''): Crawler
    {
        $crawler = $this->client->request('GET', '/widgets-admin'.$queryString);
        $this->assertResponseIsSuccessful();

        return $crawler;
    }

    private function widget(Crawler $crawler, string $name): Crawler
    {
        $widget = $crawler->filter(sprintf('.ea-widget[data-ea-widget="%s"]', $name));
        $this->assertCount(1, $widget, sprintf('The page must contain exactly one "%s" widget.', $name));

        return $widget;
    }
}
