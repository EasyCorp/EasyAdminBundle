<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\UiKit;

use EasyCorp\Bundle\EasyAdminBundle\UiKit\ComponentDocumentationReader;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class UiKitTest extends WebTestCase
{
    private const COMPONENTS_DIR = __DIR__.'/../../../templates/ui_kit/components';

    protected function tearDown(): void
    {
        parent::tearDown();
        self::ensureKernelShutdown();
    }

    public function testIndexListsTheComponents(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/_ea_ui_kit');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'EasyAdmin UI Kit');
        $this->assertSame('/admin/_ea_ui_kit/components/badge', $crawler->filter('#main ul a:contains("Badge")')->attr('href'));
    }

    public function testBreadcrumbLinksToTheDashboardAndToTheIndex(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/_ea_ui_kit/components/badge');

        $items = $crawler->filter('.ea-ui-kit-breadcrumb li');
        $this->assertCount(3, $items);
        $this->assertSame('/admin', $items->eq(0)->filter('a')->attr('href'));
        $this->assertSame('/admin/_ea_ui_kit', $items->eq(1)->filter('a')->attr('href'));
        $this->assertSame('Badge', $items->eq(2)->text());
        $this->assertSame('page', $items->eq(2)->attr('aria-current'));
    }

    public function testDetailPageRendersEachExampleNextToItsSource(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/_ea_ui_kit/components/badge');

        $this->assertResponseIsSuccessful();
        // the UI Kit renders inside the dashboard layout
        $this->assertSelectorExists('body.ea.ea-ui-kit #main-menu');
        $this->assertSelectorTextContains('h1', 'Badge');

        $variants = $crawler->filter('section#variants');
        $this->assertSame('Variants', $variants->filter('h2')->text());
        $this->assertStringContainsString('Use the variant to tell what the label means', $variants->filter('p')->text());
        $this->assertSame('Paid', $variants->filter('.ea-ui-kit-preview .badge.badge-success')->text());

        $source = $variants->filter('.ea-ui-kit-source code');
        $this->assertStringStartsWith('<twig:ea:Badge variant="success">Paid</twig:ea:Badge>', $source->text());
        $this->assertStringNotContainsString('{#', $source->text());
        $this->assertSame('twig:ea:Badge', $source->filter('.hl-tag .hl-name')->first()->text());
    }

    public function testAnonymousComponentPageShowsItsPropsAndBlocks(): void
    {
        if (!ComponentDocumentationReader::isSupported()) {
            $this->markTestSkipped('Documentation comments require Twig 3.29 and TwigComponent 3.5 or higher.');
        }

        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/_ea_ui_kit/components/modal');

        $this->assertResponseIsSuccessful();
        $sizeCells = $crawler->filter('#props tbody tr')->reduce(static fn ($row) => 'size' === $row->filter('td')->first()->text())->filter('td');
        $this->assertSame("'sm'|'lg'|'xl'|null", $sizeCells->eq(1)->text());
        $this->assertSame('null', $sizeCells->eq(2)->text());
        $this->assertStringStartsWith('The window width', $sizeCells->eq(3)->text());
        $propNames = $crawler->filter('#props tbody tr td:first-child')->each(static fn ($cell) => $cell->text());
        $this->assertSame(['bodyClass', 'description', 'footerClass', 'headerClass', 'icon', 'size', 'title', 'topIcon'], $propNames);

        $this->assertSame('API Reference', $crawler->filter('section#api-reference h2')->text());
        $this->assertSame('Prop Name', $crawler->filter('#props thead th')->first()->text());
        $this->assertSame('Block Name', $crawler->filter('#blocks thead th')->first()->text());

        $blockNames = $crawler->filter('#blocks tbody tr td:first-child')->each(static fn ($cell) => $cell->text());
        $this->assertSame(['header', 'body', 'footer'], $blockNames);
    }

    public function testComponentPageShowsThePropsAndBlocksOfItsSubComponents(): void
    {
        if (!ComponentDocumentationReader::isSupported()) {
            $this->markTestSkipped('Documentation comments require Twig 3.29 and TwigComponent 3.5 or higher.');
        }

        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/_ea_ui_kit/components/modal');

        $this->assertResponseIsSuccessful();
        $trigger = $crawler->filter('section#sub-component-modal-trigger');
        $this->assertSame('<twig:ea:Modal:Trigger>', $trigger->filter('h3')->text());
        $this->assertSame(['target', 'type'], $trigger->filter('tbody tr td:first-child')->each(static fn ($cell) => $cell->text()));
        $this->assertSame('required', $trigger->filter('tbody tr')->first()->filter('td')->eq(2)->text());

        // nested sub-components are included too, sorted by their path
        $crawler = $client->request('GET', '/admin/_ea_ui_kit/components/action_menu');
        $sectionIds = $crawler->filter('section[id^="sub-component-"]')->each(static fn ($section) => $section->attr('id'));
        $this->assertContains('sub-component-actionmenu-actionlist-item', $sectionIds);
        $this->assertLessThan(array_search('sub-component-actionmenu-button', $sectionIds, true), array_search('sub-component-actionmenu-actionlist', $sectionIds, true));
        // sub-components without props or blocks (e.g. the divider) are not listed
        $this->assertNotContains('sub-component-actionmenu-actionlist-divider', $sectionIds);
    }

    public function testClassBackedComponentPageShowsThePropsOfItsClass(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/_ea_ui_kit/components/badge');

        $this->assertResponseIsSuccessful();
        $sizeCells = $crawler->filter('#props tbody tr')->reduce(static fn ($row) => 'size' === $row->filter('td')->first()->text())->filter('td');
        $this->assertSame('Size|string', $sizeCells->eq(1)->text());
        $this->assertSame("'md'", $sizeCells->eq(2)->text());
        $this->assertStringStartsWith('The size of the badge', $sizeCells->eq(3)->text());
        // default values use the colors of the Symfony Docs syntax highlighting
        $this->assertSame('hl-string', $sizeCells->eq(2)->filter('code')->attr('class'));
        $iconDefault = $crawler->filter('#props tbody tr')->reduce(static fn ($row) => 'icon' === $row->filter('td')->first()->text())->filter('td')->eq(2);
        $this->assertSame('hl-literal', $iconDefault->filter('code')->attr('class'));
    }

    public function testExamplesAreSortedByTheirFileName(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/_ea_ui_kit/components/badge');

        $sectionIds = $crawler->filter('section.ea-ui-kit-example')->each(static fn ($section) => $section->attr('id'));
        $this->assertSame(['variants', 'default', 'sizes', 'icons', 'radius', 'custom_variants'], $sectionIds);
    }

    /**
     * @dataProvider provideComponentDirNames
     */
    public function testEveryComponentPageRendersAllItsExamples(string $componentDirName): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/_ea_ui_kit/components/'.$componentDirName);

        $this->assertResponseIsSuccessful();
        $exampleFiles = glob(sprintf('%s/%s/*.html.twig', self::COMPONENTS_DIR, $componentDirName));
        $this->assertCount(\count($exampleFiles), $crawler->filter('section.ea-ui-kit-example'));
        $this->assertCount(0, $crawler->filter('.ea-ui-kit-preview:empty'));
        $this->assertCount(0, $this->findEmptyIcons($crawler));
        $this->assertSame([], $this->findExamplesShowingInternalIcons($crawler));
    }

    /**
     * @dataProvider provideComponentDirNames
     */
    public function testEveryComponentPageRendersWithCustomIconSet(string $componentDirName): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/custom_icon_set_admin/_ea_ui_kit/components/'.$componentDirName);

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('.ea-ui-kit-preview:empty'));
        $this->assertCount(0, $this->findEmptyIcons($crawler));
        $this->assertSame([], $this->findExamplesShowingInternalIcons($crawler));
    }

    public function testExampleSourceShowsTheIconNamesOfTheDashboardIconSet(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/admin/_ea_ui_kit/components/badge');
        $icons = $crawler->filter('section#icons');
        $this->assertStringContainsString('icon="fa-solid fa-check"', $icons->filter('.ea-ui-kit-source')->text());
        $this->assertCount(3, $icons->filter('.ea-ui-kit-preview .icon svg'));
        $this->assertCount(0, $crawler->filter('.ea-ui-kit-source-note'));

        $crawler = $client->request('GET', '/custom_icon_set_admin/_ea_ui_kit/components/badge');
        $icons = $crawler->filter('section#icons');
        $this->assertStringContainsString('icon="lucide:check"', $icons->filter('.ea-ui-kit-source')->text());
        $this->assertCount(1, $icons->filter('.ea-ui-kit-source-note'));
        $this->assertCount(0, $crawler->filter('section#variants .ea-ui-kit-source-note'));

        $crawler = $client->request('GET', '/custom_icon_set_with_prefix_admin/_ea_ui_kit/components/badge');
        $icons = $crawler->filter('section#icons');
        $this->assertStringContainsString('icon="check"', $icons->filter('.ea-ui-kit-source')->text());
        $this->assertCount(1, $icons->filter('.ea-ui-kit-source-note'));
    }

    public function testFontAwesomeExamplesAreOnlyShownWithFontAwesomeIconSet(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/admin/_ea_ui_kit/components/icon');
        $this->assertCount(1, $crawler->filter('section#fontawesome'));

        $crawler = $client->request('GET', '/custom_icon_set_admin/_ea_ui_kit/components/icon');
        $this->assertCount(0, $crawler->filter('section#fontawesome'));
        $this->assertCount(1, $crawler->filter('section#sizes'));
    }

    public static function provideComponentDirNames(): iterable
    {
        foreach (glob(self::COMPONENTS_DIR.'/*', \GLOB_ONLYDIR) as $componentDir) {
            yield basename($componentDir) => [basename($componentDir)];
        }
    }

    public function testUnknownComponentReturnsNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/_ea_ui_kit/components/this_component_does_not_exist');

        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * @return list<string> The ids of the examples whose source shows internal icon names
     */
    private function findExamplesShowingInternalIcons(Crawler $crawler): array
    {
        return $crawler->filter('section.ea-ui-kit-example')
            ->reduce(static fn (Crawler $example) => str_contains($example->filter('.ea-ui-kit-source')->text(), 'internal:'))
            ->each(static fn (Crawler $example) => $example->attr('id'));
    }

    private function findEmptyIcons(Crawler $crawler): Crawler
    {
        return $crawler->filter('.ea-ui-kit-preview .icon')->reduce(static fn (Crawler $icon) => 0 === $icon->children()->count());
    }
}
