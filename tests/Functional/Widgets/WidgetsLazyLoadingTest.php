<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Widgets;

use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checks the widgets whose contents are loaded (or reloaded) in a separate request
 * and the route of the dashboard that serves those contents.
 */
class WidgetsLazyLoadingTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testLazyWidgetDisplaysAPlaceholder(): void
    {
        $crawler = $this->client->request('GET', '/widgets-admin');

        $widget = $crawler->filter('.ea-widget[data-ea-widget="lazy-stat"]');
        $this->assertSame('/widgets-admin/_widget/lazy-stat', $widget->attr('data-ea-widget-url'));
        $this->assertSame('true', $widget->attr('data-ea-widget-lazy'));
        $this->assertNull($widget->attr('data-ea-widget-refresh'));

        $body = $widget->filter('[data-ea-widget-body]');
        $this->assertSame('true', $body->attr('aria-busy'));
        $this->assertCount(1, $body->filter('.ea-skeleton.ea-skeleton-stat[role="status"]'));
        $this->assertCount(0, $body->filter('.ea-stat'));
        $this->assertStringNotContainsString('Lazy value', (string) $this->client->getResponse()->getContent());

        $this->assertCount(1, $widget->filter('template[data-ea-widget-error-template]'));
    }

    public function testRefreshingWidgetRendersItsContentsAndItsRefreshInterval(): void
    {
        $crawler = $this->client->request('GET', '/widgets-admin');

        $widget = $crawler->filter('.ea-widget[data-ea-widget="refreshing-stat"]');
        $this->assertSame('/widgets-admin/_widget/refreshing-stat', $widget->attr('data-ea-widget-url'));
        $this->assertSame('30', $widget->attr('data-ea-widget-refresh'));
        $this->assertNull($widget->attr('data-ea-widget-lazy'));
        $this->assertSame('false', $widget->filter('[data-ea-widget-body]')->attr('aria-busy'));
        $this->assertSame('77', $widget->filter('[data-ea-widget-body] .ea-stat-value')->text());
    }

    public function testRegularWidgetsHaveNoUrl(): void
    {
        $crawler = $this->client->request('GET', '/widgets-admin');

        $this->assertSame(['lazy-stat', 'refreshing-stat'], $crawler->filter('[data-ea-widget-url]')->each(static fn ($node): string => (string) $node->attr('data-ea-widget')));
        $this->assertCount(2, $crawler->filter('template[data-ea-widget-error-template]'));
    }

    public function testWidgetsScriptIsIncludedOncePerPage(): void
    {
        $crawler = $this->client->request('GET', '/widgets-admin');

        $scripts = $crawler->filter('script[src*="/widgets."]');
        $this->assertCount(1, $scripts);
        $this->assertNotNull($scripts->attr('defer'));
    }

    public function testWidgetsScriptIsNotIncludedWithoutLazyOrRefreshingWidgets(): void
    {
        $crawler = $this->client->request('GET', '/widgets-extended-admin');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('.ea-widget'));
        $this->assertCount(0, $crawler->filter('script[src*="/widgets."]'));
    }

    public function testFragmentUrlKeepsTheQueryStringOfThePage(): void
    {
        $crawler = $this->client->request('GET', '/widgets-admin?range=30d&page=2&routeParams[foo]=bar');

        $this->assertResponseIsSuccessful();
        $this->assertSame('/widgets-admin/_widget/lazy-stat?range=30d&page=2', $crawler->filter('[data-ea-widget="lazy-stat"]')->attr('data-ea-widget-url'));
    }

    public function testFragmentUrlIsEscaped(): void
    {
        $crawler = $this->client->request('GET', '/widgets-admin?range='.rawurlencode('"><b>Range</b>'));

        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('"><b>Range</b>', (string) $this->client->getResponse()->getContent());
        $this->assertSame('/widgets-admin/_widget/lazy-stat?range=%22%3E%3Cb%3ERange%3C/b%3E', $crawler->filter('[data-ea-widget="lazy-stat"]')->attr('data-ea-widget-url'));
    }

    public function testFragmentUrlDropsTheRouteNameOfCustomPages(): void
    {
        $crawler = $this->client->request('GET', '/widgets-admin?routeName=widgets_custom_page&range=30d');

        $this->assertResponseIsSuccessful();
        $this->assertSame('/widgets-admin/_widget/lazy-stat?range=30d', $crawler->filter('[data-ea-widget="lazy-stat"]')->attr('data-ea-widget-url'));
    }

    public function testFragmentResponse(): void
    {
        $this->client->request('GET', '/widgets-admin/_widget/lazy-stat?range=30d');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('X-EasyAdmin-Widget', 'lazy-stat');
        $this->assertResponseHeaderSame('X-Robots-Tag', 'noindex');
        $response = $this->client->getResponse();
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertFalse($response->headers->hasCacheControlDirective('public'));

        $content = (string) $response->getContent();
        $this->assertSame(['ea-stat'], $this->client->getCrawler()->filter('body > *')->each(static fn ($node): string => (string) $node->attr('class')));
        $this->assertStringNotContainsString('<html', $content);
        $this->assertStringNotContainsString('<body', $content);
        $this->assertStringNotContainsString('ea-card', $content);
        $this->assertStringNotContainsString('data-ea-widget', $content);
        $this->assertSelectorTextSame('.ea-stat-value', 'Lazy value for range 30d');
    }

    public function testContextAttributesReachTheLazyWidget(): void
    {
        $this->client->request('GET', '/widgets-admin/_widget/lazy-stat');
        $this->assertSelectorTextSame('.ea-stat-value', 'Lazy value for range 7d');

        $this->client->request('GET', '/widgets-admin/_widget/lazy-stat?range=90d');
        $this->assertSelectorTextSame('.ea-stat-value', 'Lazy value for range 90d');
    }

    public function testFragmentOfNonLazyWidgets(): void
    {
        $this->client->request('GET', '/widgets-admin/_widget/stat-1');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('.ea-stat-value', '1,234,567');

        $this->client->request('GET', '/widgets-admin/_widget/range?range=30d');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('a.range-link', 'Range: 30d');
    }

    public function testFragmentOfUnknownWidget(): void
    {
        $this->client->request('GET', '/widgets-admin/_widget/unknown-widget');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertFalse($this->client->getResponse()->headers->has('X-EasyAdmin-Widget'));
    }

    /**
     * The visitors of this app are anonymous, so Symfony turns the access denied
     * error into a 401 response (the 403 response for logged in users is checked in SecuredApp).
     */
    public function testFragmentOfDeniedWidget(): void
    {
        $this->client->request('GET', '/widgets-admin/_widget/hidden-stat');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $this->client->request('GET', '/widgets-admin/_widget/stat-in-hidden-section');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertFalse($this->client->getResponse()->headers->has('X-EasyAdmin-Widget'));
    }

    #[TestWith(['/widgets-admin/_widget/lazy.stat'])]
    #[TestWith(['/widgets-admin/_widget/lazy%20stat'])]
    #[TestWith(['/widgets-admin/_widget/lazy-stat/extra'])]
    #[TestWith(['/widgets-admin/_widget/'])]
    public function testInvalidWidgetNamesDontMatchTheRoute(string $url): void
    {
        $this->client->request('GET', $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    #[TestWith(['POST'])]
    #[TestWith(['PUT'])]
    #[TestWith(['DELETE'])]
    #[TestWith(['PATCH'])]
    public function testFragmentOnlyAcceptsGetRequests(string $method): void
    {
        $this->client->request($method, '/widgets-admin/_widget/lazy-stat');

        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testFragmentAcceptsHeadRequests(): void
    {
        $this->client->request('HEAD', '/widgets-admin/_widget/lazy-stat');

        $this->assertResponseIsSuccessful();
    }
}
