<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\I18nKernel;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

/**
 * When the admin routes are localized, each locale has its own dashboard route and
 * its own route to serve the contents of the widgets. The widgets of a locale must
 * be protected like the dashboard page of that same locale.
 *
 * In this app, every admin path requires ROLE_USER, an 'access_control' rule requires
 * ROLE_ADMIN for the path of the Spanish "exact path" dashboard and another one requires
 * it for the route of the "route rule" dashboard.
 */
class I18nWidgetsSecurityTest extends WebTestCase
{
    private const DASHBOARD_PATH = '/admin/route-rule-widgets';

    private KernelBrowser $client;

    protected static function getKernelClass(): string
    {
        return I18nKernel::class;
    }

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    #[TestWith(['en'])]
    #[TestWith(['es'])]
    public function testDashboardLinksToTheWidgetUrlOfItsLocale(string $locale): void
    {
        $dashboardUrl = '/admin/'.$locale.self::DASHBOARD_PATH;

        $crawler = $this->request('admin', $dashboardUrl);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('[data-ea-widget="stat-1"] .ea-stat-value', 'Route rule value 1');
        $this->assertSame($dashboardUrl.'/_widget/stat-2', $crawler->filter('[data-ea-widget="stat-2"]')->attr('data-ea-widget-url'));

        $this->request('admin', $dashboardUrl.'/_widget/stat-2');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('X-EasyAdmin-Widget', 'stat-2');
        $this->assertSelectorTextSame('.ea-stat-value', 'Locale '.$locale);
    }

    #[TestWith(['en'])]
    #[TestWith(['es'])]
    public function testAnonymousUsersCantSeeTheWidgetsOfAnyLocale(string $locale): void
    {
        $this->client->request('GET', '/admin/'.$locale.self::DASHBOARD_PATH.'/_widget/stat-1');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertFalse($this->client->getResponse()->headers->has('X-EasyAdmin-Widget'));
    }

    public function testRuleOfALocalizedDashboardPathProtectsTheWidgetsOfThatLocale(): void
    {
        $this->request('user', '/admin/es/admin/exact-path-widgets');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $this->request('user', '/admin/es/admin/exact-path-widgets/_widget/stat-1');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertFalse($this->client->getResponse()->headers->has('X-EasyAdmin-Widget'));

        $this->request('admin', '/admin/es/admin/exact-path-widgets/_widget/stat-1');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('.ea-stat-value', 'Exact path value 1');
    }

    public function testRuleOfALocalizedDashboardPathDoesNotProtectTheWidgetsOfOtherLocales(): void
    {
        $this->request('user', '/admin/en/admin/exact-path-widgets');
        $this->assertResponseIsSuccessful();

        $this->request('user', '/admin/en/admin/exact-path-widgets/_widget/stat-1');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('.ea-stat-value', 'Exact path value 1');
    }

    /**
     * Symfony matches the 'route' option of the rules against the route name without the locale suffix.
     */
    #[TestWith(['en'])]
    #[TestWith(['es'])]
    public function testRuleOfTheDashboardRouteProtectsTheWidgetsOfEveryLocale(string $locale): void
    {
        $this->request('user', '/admin/'.$locale.self::DASHBOARD_PATH);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        foreach (['stat-1', 'stat-2'] as $widgetName) {
            $this->request('user', '/admin/'.$locale.self::DASHBOARD_PATH.'/_widget/'.$widgetName);
            $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
            $this->assertFalse($this->client->getResponse()->headers->has('X-EasyAdmin-Widget'));
        }
    }

    #[TestWith(['en'])]
    #[TestWith(['es'])]
    public function testIsGrantedAttributeProtectsTheWidgetsOfEveryLocale(string $locale): void
    {
        $this->request('user', '/admin/'.$locale.'/admin/granted-widgets/_widget/stat-1');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $this->request('admin', '/admin/'.$locale.'/admin/granted-widgets/_widget/stat-1');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('.ea-stat-value', 'Is granted value 1');
    }

    private function request(string $username, string $url): Crawler
    {
        return $this->client->request('GET', $url, [], [], ['PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => '1234']);
    }
}
