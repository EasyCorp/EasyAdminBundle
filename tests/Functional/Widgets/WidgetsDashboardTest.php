<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Widgets;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets\WidgetsDeniedDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets\WidgetsWelcomeDashboardController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Checks which page the index() action of a dashboard renders depending on its widgets.
 */
class WidgetsDashboardTest extends WebTestCase
{
    public function testDashboardWithoutWidgetsDisplaysTheWelcomePage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/widgets-welcome-admin');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString((string) (new \ReflectionClass(WidgetsWelcomeDashboardController::class))->getFileName(), $crawler->filter('pre')->text());
        $this->assertCount(0, $crawler->filter('.ea-widget-grid'));
    }

    public function testDashboardWithWidgetsDisplaysTheDashboardPage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/widgets-admin');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('title', 'Widgets Dashboard');
        $this->assertSelectorTextSame('.content-header-title h1.title', 'Widgets Dashboard');
        $this->assertSame('ea page-dashboard', $crawler->filter('body')->attr('class'));
        $this->assertCount(1, $crawler->filter('aside.ea-sidebar #main-menu'));
        $this->assertCount(1, $crawler->filter('#main .ea-widget-grid'));
        $this->assertGreaterThan(0, $crawler->filter('#main .ea-widget-grid .ea-widget')->count());
        $this->assertCount(0, $crawler->filter('#main .ea-widget-grid > .ea-empty-state'));
    }

    public function testDashboardWhoseWidgetsAreAllDeniedDisplaysTheEmptyState(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/widgets-denied-admin');

        $this->assertResponseIsSuccessful();
        $this->assertSame('ea page-dashboard', $crawler->filter('body')->attr('class'));
        $this->assertCount(0, $crawler->filter('.ea-widget'));
        $this->assertCount(0, $crawler->filter('.ea-widget-section'));
        $this->assertSelectorTextSame('#main .ea-widget-grid > .ea-empty-state .ea-empty-state-message', 'There is nothing to show here yet.');
        $this->assertStringNotContainsString((string) (new \ReflectionClass(WidgetsDeniedDashboardController::class))->getFileName(), (string) $client->getResponse()->getContent());
    }

    public function testDashboardThatOverridesIndexRendersItsOwnTemplate(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/widgets-custom-index-admin');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('p.custom-index', 'Custom index 1');
        $this->assertCount(0, $crawler->filter('.ea-widget-grid'));
        $this->assertCount(0, $crawler->filter('.ea-widget'));
    }

    public function testTemplateThatExtendsTheDashboardPageAddsContentsAroundTheWidgets(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/widgets-extended-admin');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('#main > p.before-widgets', 'Before widgets 1');
        $this->assertSelectorTextSame('#main > p.after-widgets', 'After widgets 1');

        $mainChildrenClasses = $crawler->filter('#main > *')->each(static fn ($node): string => (string) $node->attr('class'));
        $this->assertSame(['before-widgets', 'ea-widget-grid', 'after-widgets'], $mainChildrenClasses);
        $this->assertCount(1, $crawler->filter('#main .ea-widget-grid .ea-widget[data-ea-widget="stat-1"]'));
    }

    public function testCustomTemplateRendersTheDashboardAndWidgetComponents(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/widgets-components-admin');

        $this->assertResponseIsSuccessful();

        $toolbarWidgets = $crawler->filter('.custom-toolbar > .ea-widget');
        $this->assertCount(1, $toolbarWidgets);
        $this->assertSame('toolbar-stat', $toolbarWidgets->attr('data-ea-widget'));
        $this->assertSelectorTextSame('.custom-toolbar .ea-stat-value', 'Toolbar value');

        $this->assertCount(1, $crawler->filter('.custom-section .ea-widget-section'));
        $this->assertSame('section-a', $crawler->filter('.custom-section .ea-widget-section')->attr('data-ea-widget-section'));
        $this->assertSame(['stat-a'], $crawler->filter('.custom-section .ea-widget')->each(static fn ($node): string => (string) $node->attr('data-ea-widget')));

        // a missing section is the same as a section hidden to the user, so it renders nothing (not even the empty state)
        $this->assertCount(0, $crawler->filter('.custom-missing-section .ea-widget-section'));
        $this->assertCount(0, $crawler->filter('.custom-missing-section .ea-empty-state'));

        $this->assertCount(1, $crawler->filter('.custom-single > .ea-widget[data-ea-widget="single-stat"]'));
        $this->assertSelectorTextSame('.custom-single .ea-stat-value', 'Single value');

        $this->assertSame('', trim($crawler->filter('.custom-denied')->html()));
        $this->assertStringNotContainsString('Denied value', (string) $client->getResponse()->getContent());
    }

    public function testWidgetComponentWithUnknownNameThrowsAnException(): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);

        $this->expectExceptionMessage('The current dashboard has no widget called "unknown-widget". The names of its widgets are: "toolbar-stat", "single-stat", "stat-a", "stat-b".');

        $client->request('GET', '/widgets-components-admin?template=unknown-widget');
    }
}
