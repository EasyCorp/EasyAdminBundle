<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecureRouteController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\WidgetsSecuredDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The contents of widgets are served by an action of the dashboard controller, so
 * they must be protected exactly like the dashboard page that displays them.
 *
 * SecuredApp restricts '^/admin/secure' to ROLE_ADMIN and '^/admin' to ROLE_USER.
 */
class WidgetsSecurityTest extends WebTestCase
{
    private KernelBrowser $client;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // the cache keys of these widgets only depend on the user (this app has no context providers),
        // so the cache is cleared to not reuse the contents stored by the previous tests
        static::getContainer()->get('cache.app')->clear();
        WidgetsSecuredDashboardController::$userCacheComputations = 0;
        WidgetsSecuredDashboardController::$sharedCacheComputations = 0;
    }

    #[TestWith(['/admin/widgets'])]
    #[TestWith(['/admin/secure/widgets'])]
    #[TestWith(['/admin/granted-widgets'])]
    #[TestWith(['/admin/index-granted-widgets'])]
    #[TestWith(['/admin/exact-path-widgets'])]
    #[TestWith(['/admin/route-rule-widgets'])]
    #[TestWith(['/admin/request-subject-widgets'])]
    public function testAnonymousUsersCantSeeTheDashboardNorItsWidgets(string $dashboardPath): void
    {
        $this->client->request('GET', $dashboardPath);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $this->client->request('GET', $dashboardPath.'/_widget/stat-1');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertFalse($this->client->getResponse()->headers->has('X-EasyAdmin-Widget'));
    }

    #[TestWith(['/admin/secure/widgets', 'Access control value 1'])]
    #[TestWith(['/admin/granted-widgets', 'Is granted value 1'])]
    #[TestWith(['/admin/index-granted-widgets', 'Index granted value 1'])]
    #[TestWith(['/admin/exact-path-widgets', 'Exact path value 1'])]
    #[TestWith(['/admin/route-rule-widgets', 'Route rule value 1'])]
    #[TestWith(['/admin/request-subject-widgets', 'Request subject value 1'])]
    public function testUsersWithoutTheRoleOfTheDashboardCantSeeItsWidgets(string $dashboardPath, string $widgetValue): void
    {
        $this->request('user', $dashboardPath);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertStringNotContainsString($widgetValue, (string) $this->client->getResponse()->getContent());

        $this->request('user', $dashboardPath.'/_widget/stat-1');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertStringNotContainsString($widgetValue, (string) $this->client->getResponse()->getContent());
        $this->assertFalse($this->client->getResponse()->headers->has('X-EasyAdmin-Widget'));
    }

    #[TestWith(['/admin/secure/widgets', 'Access control value 1'])]
    #[TestWith(['/admin/granted-widgets', 'Is granted value 1'])]
    #[TestWith(['/admin/index-granted-widgets', 'Index granted value 1'])]
    #[TestWith(['/admin/exact-path-widgets', 'Exact path value 1'])]
    #[TestWith(['/admin/route-rule-widgets', 'Route rule value 1'])]
    #[TestWith(['/admin/request-subject-widgets', 'Request subject value 1'])]
    public function testUsersWithTheRoleOfTheDashboardCanSeeItsWidgets(string $dashboardPath, string $widgetValue): void
    {
        $this->request('admin', $dashboardPath);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('[data-ea-widget="stat-1"] .ea-stat-value', $widgetValue);

        $this->request('admin', $dashboardPath.'/_widget/stat-1');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('X-EasyAdmin-Widget', 'stat-1');
        $this->assertSelectorTextSame('.ea-stat-value', $widgetValue);
    }

    /**
     * Symfony also applies the attributes that extend #[IsGranted] to the index() method.
     */
    public function testCustomIsGrantedAttributeOfIndexAlsoProtectsTheWidgets(): void
    {
        $this->request('user', '/admin/custom-granted-widgets');
        if (Response::HTTP_FORBIDDEN !== $this->client->getResponse()->getStatusCode()) {
            $this->markTestSkipped('This Symfony version does not apply the attributes that extend #[IsGranted].');
        }

        $this->request('user', '/admin/custom-granted-widgets/_widget/stat-1');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertStringNotContainsString('Custom granted value 1', (string) $this->client->getResponse()->getContent());

        $this->request('admin', '/admin/custom-granted-widgets/_widget/stat-1');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('.ea-stat-value', 'Custom granted value 1');
    }

    /**
     * Widgets are requested with GET, like the dashboard page, so the attributes
     * restricted to other HTTP methods don't apply to them.
     */
    public function testIsGrantedAttributeOfOtherHttpMethodsDoesNotProtectTheWidgets(): void
    {
        if (!property_exists(IsGranted::class, 'methods')) {
            $this->markTestSkipped('This Symfony version does not support the "methods" option of #[IsGranted].');
        }

        $this->request('user', '/admin/post-granted-widgets');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('[data-ea-widget="stat-1"] .ea-stat-value', 'Post granted value 1');

        $this->request('user', '/admin/post-granted-widgets/_widget/stat-1');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('.ea-stat-value', 'Post granted value 1');
    }

    public function testWidgetWithPermissionIsHiddenForUsersWithoutIt(): void
    {
        $crawler = $this->request('user', '/admin/widgets');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('[data-ea-widget="public-stat"] .ea-stat-value', 'Public value');
        $this->assertCount(0, $crawler->filter('[data-ea-widget="admin-stat"]'));
        $this->assertCount(0, $crawler->filter('[data-ea-widget="lazy-admin-stat"]'));
        $this->assertCount(0, $crawler->filter('script[src*="/widgets."]'));
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString('Admin value', $content);
        $this->assertStringNotContainsString('admin-widget.css', $content);

        $this->request('user', '/admin/widgets/_widget/admin-stat');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertStringNotContainsString('Admin value', (string) $this->client->getResponse()->getContent());

        $this->request('user', '/admin/widgets/_widget/lazy-admin-stat');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertStringNotContainsString('Lazy admin value', (string) $this->client->getResponse()->getContent());
    }

    public function testWidgetWithPermissionIsDisplayedForUsersWithIt(): void
    {
        $crawler = $this->request('admin', '/admin/widgets');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('[data-ea-widget="admin-stat"] .ea-stat-value', 'Admin value');
        $this->assertSame('/admin/widgets/_widget/lazy-admin-stat', $crawler->filter('[data-ea-widget="lazy-admin-stat"]')->attr('data-ea-widget-url'));
        $this->assertCount(1, $crawler->filter('head link[href="/admin-widget.css"]'));

        $this->request('admin', '/admin/widgets/_widget/admin-stat');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('.ea-stat-value', 'Admin value');

        $this->request('super_admin', '/admin/widgets/_widget/lazy-admin-stat');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('.ea-stat-value', 'Lazy admin value');
    }

    public function testEachUserHasTheirOwnCachedContents(): void
    {
        $this->request('user', '/admin/widgets');
        $this->assertSelectorTextSame('[data-ea-widget="user-cached-stat"] .ea-stat-value', 'User cached 1 for user');

        $this->request('admin', '/admin/widgets');
        $this->assertSelectorTextSame('[data-ea-widget="user-cached-stat"] .ea-stat-value', 'User cached 2 for admin');

        $this->request('user', '/admin/widgets/_widget/user-cached-stat');
        $this->assertSelectorTextSame('.ea-stat-value', 'User cached 1 for user');

        $this->request('admin', '/admin/widgets');
        $this->assertSelectorTextSame('[data-ea-widget="user-cached-stat"] .ea-stat-value', 'User cached 2 for admin');

        $this->assertSame(2, WidgetsSecuredDashboardController::$userCacheComputations);
    }

    public function testSharedCachedContentsAreTheSameForAllUsers(): void
    {
        $this->request('user', '/admin/widgets');
        $this->assertSelectorTextSame('[data-ea-widget="shared-cached-stat"] .ea-stat-value', 'Shared cached 1');

        $this->request('admin', '/admin/widgets');
        $this->assertSelectorTextSame('[data-ea-widget="shared-cached-stat"] .ea-stat-value', 'Shared cached 1');

        $this->request('super_admin', '/admin/widgets/_widget/shared-cached-stat');
        $this->assertSelectorTextSame('.ea-stat-value', 'Shared cached 1');

        $this->assertSame(1, WidgetsSecuredDashboardController::$sharedCacheComputations);
    }

    /**
     * The fragment route is an admin route, so '?routeName=' behaves like in the dashboard
     * URL: an admin route is redirected to its own URL (with its own admin context) and the
     * widget is never rendered under the context of another controller.
     */
    public function testRouteNameOfAnAdminRouteRedirectsInsteadOfRenderingTheWidget(): void
    {
        $this->request('admin', '/admin/widgets/_widget/admin-stat?routeName=secured_admin_category_index');

        $this->assertResponseStatusCodeSame(Response::HTTP_TEMPORARY_REDIRECT);
        $this->assertResponseRedirects('/admin/category');
        $this->assertFalse($this->client->getResponse()->headers->has('X-EasyAdmin-Widget'));
        $this->assertStringNotContainsString('Admin value', (string) $this->client->getResponse()->getContent());
    }

    /**
     * A regular Symfony route runs its controller instead of the widget (like in the dashboard
     * URL), after checking the 'access_control' rules of that route.
     */
    public function testRouteNameOfARegularRouteRunsItsControllerWithItsAccessControlRules(): void
    {
        $this->request('user', '/admin/widgets/_widget/public-stat?routeName=secure_danger_zone');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertStringNotContainsString(SecureRouteController::SECRET, (string) $this->client->getResponse()->getContent());

        $this->request('admin', '/admin/widgets/_widget/public-stat?routeName=secure_danger_zone');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(SecureRouteController::SECRET, (string) $this->client->getResponse()->getContent());
        $this->assertStringNotContainsString('Public value', (string) $this->client->getResponse()->getContent());
        $this->assertFalse($this->client->getResponse()->headers->has('X-EasyAdmin-Widget'));
    }

    public function testRouteNameOfTheWidgetRouteRedirectsToTheFragmentUrl(): void
    {
        $this->request('admin', '/admin/widgets?routeName=secured_widgets_admin_widget&routeParams[widgetName]=admin-stat');

        $this->assertResponseStatusCodeSame(Response::HTTP_TEMPORARY_REDIRECT);
        $this->assertResponseRedirects('/admin/widgets/_widget/admin-stat');
    }

    private function request(string $username, string $url): Crawler
    {
        return $this->client->request('GET', $url, [], [], ['PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => '1234']);
    }
}
