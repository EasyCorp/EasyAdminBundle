<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Widgets;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Checks that the assets of widgets are added only to the dashboard page that displays them.
 */
class WidgetsAssetsTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testAssetsOfWidgetsAreAddedToTheHeadOfTheDashboardPage(): void
    {
        $crawler = $this->client->request('GET', '/widgets-admin');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('head link[rel="stylesheet"][href="/widget-styles.css"]'));
        $this->assertCount(1, $crawler->filter('head script[src="/widget-scripts.js"]'));
        $this->assertSame('Meta 1', $crawler->filter('head meta[name="widget-meta"]')->attr('content'));
    }

    public function testAssetsOfHiddenWidgetsAreNotAdded(): void
    {
        $this->client->request('GET', '/widgets-admin');

        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('hidden-widget.css', (string) $this->client->getResponse()->getContent());
    }

    public function testAssetsOfWidgetsAreAddedWhenIndexRendersATemplateThatExtendsTheDashboardPage(): void
    {
        $crawler = $this->client->request('GET', '/widgets-extended-admin');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('head link[rel="stylesheet"][href="/extended-widget.css"]'));
        $this->assertCount(1, $crawler->filter('head script[src="/extended-widget.js"]'));
    }

    public function testAssetsOfWidgetsAreNotAddedToCrudPagesOfTheSameDashboard(): void
    {
        $this->client->request('GET', '/widgets-admin/category');

        $this->assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString('widget-styles.css', $content);
        $this->assertStringNotContainsString('widget-scripts.js', $content);
        $this->assertStringNotContainsString('widget-meta', $content);
    }

    public function testAssetsOfWidgetsAreNotAddedToTheFragments(): void
    {
        $this->client->request('GET', '/widgets-admin/_widget/assets-stat');

        $this->assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString('widget-styles.css', $content);
        $this->assertStringNotContainsString('widget-meta', $content);
    }

    public function testAssetsOfWidgetsAreNotAddedToCustomPagesOfTheSameDashboard(): void
    {
        $this->client->request('GET', '/widgets-admin?routeName=widgets_custom_page');

        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('widget-styles.css', (string) $this->client->getResponse()->getContent());
    }
}
