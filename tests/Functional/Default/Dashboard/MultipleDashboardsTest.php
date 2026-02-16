<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Dashboard\SecondDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests for multiple dashboards functionality:
 * - Different dashboards can coexist independently
 * - Each dashboard has its own configuration
 * - Different menu items per dashboard
 * - Different titles and branding per dashboard.
 */
class MultipleDashboardsTest extends WebTestCase
{
    private function getCrudIndexUrl(string $dashboardFqcn): string
    {
        $container = static::getContainer();

        /** @var AdminUrlGenerator $adminUrlGenerator */
        $adminUrlGenerator = $container->get(AdminUrlGenerator::class);

        return $adminUrlGenerator
            ->setDashboard($dashboardFqcn)
            ->setController(CategoryCrudController::class)
            ->setAction('index')
            ->generateUrl();
    }

    public function testFirstDashboardIsAccessible(): void
    {
        $client = static::createClient();
        $client->followRedirects();
        $client->request('GET', '/admin');

        static::assertResponseIsSuccessful();
    }

    public function testSecondDashboardIsAccessible(): void
    {
        $client = static::createClient();
        $client->followRedirects();
        $client->request('GET', '/second_admin');

        static::assertResponseIsSuccessful();
    }

    public function testFirstDashboardHasCorrectTitle(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        // navigate to a CRUD page to see the dashboard layout with title
        $url = $this->getCrudIndexUrl(DashboardController::class);
        $crawler = $client->request('GET', $url);

        static::assertResponseIsSuccessful();

        // first dashboard should have its own title in the logo area
        $logo = $crawler->filter('.logo .logo-custom');
        static::assertGreaterThan(0, $logo->count());
        static::assertStringContainsString('EasyAdmin Tests', $logo->text());
    }

    public function testSecondDashboardHasCorrectTitle(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        // navigate to a CRUD page to see the dashboard layout with title
        $url = $this->getCrudIndexUrl(SecondDashboardController::class);
        $crawler = $client->request('GET', $url);

        static::assertResponseIsSuccessful();

        // second dashboard should have its own title in the logo area
        $logo = $crawler->filter('.logo .logo-custom');
        static::assertGreaterThan(0, $logo->count());
        static::assertStringContainsString('Second Dashboard', $logo->text());
    }

    public function testFirstDashboardHasBlogPostsMenuItem(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        // navigate to a CRUD page to see the menu
        $url = $this->getCrudIndexUrl(DashboardController::class);
        $crawler = $client->request('GET', $url);

        static::assertResponseIsSuccessful();

        // first dashboard menu should include Blog Posts
        $html = $crawler->html();
        static::assertStringContainsString('Blog Posts', $html);
    }

    public function testSecondDashboardDoesNotHaveBlogPostsMenuItem(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        // navigate to a CRUD page to see the menu
        $url = $this->getCrudIndexUrl(SecondDashboardController::class);
        $crawler = $client->request('GET', $url);

        static::assertResponseIsSuccessful();

        // second dashboard menu should NOT include Blog Posts
        $html = $crawler->html();
        static::assertStringNotContainsString('Blog Posts', $html);
    }

    public function testSecondDashboardHasHelpCenterMenuItem(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        // navigate to a CRUD page to see the menu
        $url = $this->getCrudIndexUrl(SecondDashboardController::class);
        $crawler = $client->request('GET', $url);

        static::assertResponseIsSuccessful();

        // second dashboard menu should include Help Center
        $html = $crawler->html();
        static::assertStringContainsString('Help Center', $html);
    }

    public function testFirstDashboardDoesNotHaveHelpCenterMenuItem(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        // navigate to a CRUD page to see the menu
        $url = $this->getCrudIndexUrl(DashboardController::class);
        $crawler = $client->request('GET', $url);

        static::assertResponseIsSuccessful();

        // first dashboard menu should NOT include Help Center
        $html = $crawler->html();
        static::assertStringNotContainsString('Help Center', $html);
    }

    public function testDashboardsHaveDifferentMenuSections(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        // first dashboard has "Synthetic Tests" section
        $url1 = $this->getCrudIndexUrl(DashboardController::class);
        $crawler1 = $client->request('GET', $url1);
        static::assertResponseIsSuccessful();
        $html1 = $crawler1->html();
        static::assertStringContainsString('Synthetic Tests', $html1);

        // second dashboard has "Second Dashboard Content" section
        $url2 = $this->getCrudIndexUrl(SecondDashboardController::class);
        $crawler2 = $client->request('GET', $url2);
        static::assertResponseIsSuccessful();
        $html2 = $crawler2->html();
        static::assertStringContainsString('Second Dashboard Content', $html2);
        static::assertStringNotContainsString('Synthetic Tests', $html2);
    }

    public function testBothDashboardsHaveCategoriesMenuItem(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        // first dashboard should have Categories in the menu
        $url1 = $this->getCrudIndexUrl(DashboardController::class);
        $crawler1 = $client->request('GET', $url1);
        static::assertResponseIsSuccessful();
        $html1 = $crawler1->html();
        static::assertStringContainsString('Categories', $html1);

        // second dashboard uses "Manage Categories" instead
        $url2 = $this->getCrudIndexUrl(SecondDashboardController::class);
        $crawler2 = $client->request('GET', $url2);
        static::assertResponseIsSuccessful();
        $html2 = $crawler2->html();
        static::assertStringContainsString('Manage Categories', $html2);
    }

    public function testDashboardsHaveIndependentBranding(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        // first dashboard branding
        $url1 = $this->getCrudIndexUrl(DashboardController::class);
        $crawler1 = $client->request('GET', $url1);
        static::assertResponseIsSuccessful();
        $logo1 = $crawler1->filter('.logo');
        static::assertGreaterThan(0, $logo1->count());
        $title1 = $crawler1->filter('.logo .logo-custom')->text();

        // second dashboard branding
        $url2 = $this->getCrudIndexUrl(SecondDashboardController::class);
        $crawler2 = $client->request('GET', $url2);
        static::assertResponseIsSuccessful();
        $logo2 = $crawler2->filter('.logo');
        static::assertGreaterThan(0, $logo2->count());
        $title2 = $crawler2->filter('.logo .logo-custom')->text();

        // titles should be different
        static::assertNotSame($title1, $title2);
    }

    public function testSecondDashboardUsesHomeLabel(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        // navigate to a CRUD page to see the menu
        $url = $this->getCrudIndexUrl(SecondDashboardController::class);
        $crawler = $client->request('GET', $url);

        static::assertResponseIsSuccessful();

        // second dashboard uses "Home" as the dashboard link label
        $html = $crawler->html();
        static::assertStringContainsString('Home', $html);
    }

    public function testFirstDashboardMenuHasMultipleCrudLinks(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        // navigate to a CRUD page to see the menu
        $url = $this->getCrudIndexUrl(DashboardController::class);
        $crawler = $client->request('GET', $url);

        static::assertResponseIsSuccessful();

        // first dashboard should have multiple CRUD links
        $html = $crawler->html();
        static::assertStringContainsString('Categories', $html);
        static::assertStringContainsString('Blog Posts', $html);
        static::assertStringContainsString('Field Tests', $html);
        static::assertStringContainsString('Filter Tests', $html);
    }

    public function testSecondDashboardHasSimplifiedMenu(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        // navigate to a CRUD page to see the menu
        $url = $this->getCrudIndexUrl(SecondDashboardController::class);
        $crawler = $client->request('GET', $url);

        static::assertResponseIsSuccessful();

        // count menu items in second dashboard (should have fewer items)
        $menuItems = $crawler->filter('.menu-item');
        // second dashboard has: Home, Manage Categories, Help Center = fewer items than first dashboard
        static::assertGreaterThan(0, $menuItems->count());
        static::assertLessThan(10, $menuItems->count());
    }

    public function testDashboardUrlsAreIndependent(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        // get URLs for both dashboards
        $url1 = $this->getCrudIndexUrl(DashboardController::class);
        $url2 = $this->getCrudIndexUrl(SecondDashboardController::class);

        // uRLs should be different (contain different dashboard identifiers)
        static::assertNotSame($url1, $url2);
    }
}
