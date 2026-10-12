<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\UiKit;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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
}
