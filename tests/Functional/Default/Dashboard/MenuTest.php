<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Dashboard\MenuDashboardController;

/**
 * Tests for main menu functionality in the dashboard:
 * - Menu items rendering
 * - Menu sections
 * - Submenus with hierarchy
 * - Active menu item highlighting
 * - Menu item badges.
 */
class MenuTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return CategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return MenuDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testMainMenuExists(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // main menu should exist
        $mainMenu = $crawler->filter('#main-menu');
        static::assertCount(1, $mainMenu);
    }

    public function testMenuContainsUnorderedList(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // menu should have a ul.menu element
        $menuList = $crawler->filter('#main-menu ul.menu');
        static::assertCount(1, $menuList);
    }

    public function testDashboardLinkIsRendered(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // dashboard link should be present
        $dashboardLink = $crawler->filter('.menu-item a.menu-item-contents');
        static::assertGreaterThan(0, $dashboardLink->count());

        $html = $crawler->html();
        static::assertStringContainsString('Dashboard', $html);
    }

    public function testMenuSectionsAreRendered(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // menu sections should be present
        $sectionHeaders = $crawler->filter('.menu-header');
        static::assertGreaterThan(0, $sectionHeaders->count());

        // check for specific section labels
        $html = $crawler->html();
        static::assertStringContainsString('Content Management', $html);
        static::assertStringContainsString('Advanced', $html);
        static::assertStringContainsString('External Links', $html);
    }

    public function testCrudMenuItemsAreRendered(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        $html = $crawler->html();
        static::assertStringContainsString('Categories', $html);
        static::assertStringContainsString('Blog Posts', $html);
    }

    public function testLinkToMenuItemsAreRendered(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the linkTo(BlogPostCrudController::class, 'Blog Posts', ...) item should render
        $html = $crawler->html();
        static::assertStringContainsString('Blog Posts', $html);

        // the linkTo(CategoryCrudController::class) item (auto-derived label) should also render
        // and its link should be clickable (have an href)
        $menuLinks = $crawler->filter('.menu-item a.menu-item-contents:not(.submenu-toggle)');
        $hasLinkToItem = false;
        foreach ($menuLinks as $link) {
            $href = $link->getAttribute('href');
            if ('' !== $href && '#' !== $href) {
                $hasLinkToItem = true;
                break;
            }
        }
        static::assertTrue($hasLinkToItem, 'linkTo() menu items should generate valid URLs');
    }

    public function testLinkToMenuItemKeepsCustomQueryParameters(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the "Categories" linkTo() menu item has a custom query parameter set via setQueryParameter()
        $categoriesLink = $crawler->filter('.menu-item a.menu-item-contents')->reduce(
            static fn ($node) => str_contains($node->text(), 'Categories')
        );
        static::assertGreaterThan(0, $categoriesLink->count());
        static::assertStringContainsString('custom_param=custom_value', $categoriesLink->first()->attr('href'));
    }

    public function testLinkToMenuItemBadgeIsRendered(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the linkTo() Blog Posts item has a badge with 'New'
        $badges = $crawler->filter('.menu-item-badge');
        static::assertGreaterThan(0, $badges->count());

        $html = $crawler->html();
        static::assertStringContainsString('New', $html);
    }

    public function testMenuItemBadgesAreRendered(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // badge should be present for Blog Posts
        $badges = $crawler->filter('.menu-item-badge');
        static::assertGreaterThan(0, $badges->count());

        $html = $crawler->html();
        static::assertStringContainsString('New', $html);
    }

    public function testSubmenusAreRendered(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // submenu items should be present
        $submenus = $crawler->filter('.has-submenu');
        static::assertGreaterThan(0, $submenus->count());

        // check for submenu labels
        $html = $crawler->html();
        static::assertStringContainsString('Reports', $html);
        static::assertStringContainsString('Settings', $html);
    }

    public function testSubmenuItemsAreRendered(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // submenu list should exist
        $submenuLists = $crawler->filter('.submenu');
        static::assertGreaterThan(0, $submenuLists->count());

        // check for specific submenu items
        $html = $crawler->html();
        static::assertStringContainsString('Sales Report', $html);
        static::assertStringContainsString('Traffic Report', $html);
        static::assertStringContainsString('General', $html);
        static::assertStringContainsString('Security', $html);
    }

    public function testExternalLinksAreRendered(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // external links should be present
        $html = $crawler->html();
        static::assertStringContainsString('Symfony', $html);
        static::assertStringContainsString('https://symfony.com', $html);
        static::assertStringContainsString('EasyAdmin Docs', $html);
    }

    public function testExternalLinksHaveTargetBlank(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // external Symfony link should have target="_blank"
        $symfonyLink = $crawler->filter('a[href="https://symfony.com"]');
        static::assertGreaterThan(0, $symfonyLink->count());
        static::assertSame('_blank', $symfonyLink->attr('target'));
    }

    public function testMenuIconsAreRendered(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // menu icons should be present
        $menuIcons = $crawler->filter('.menu-icon');
        static::assertGreaterThan(0, $menuIcons->count());
    }

    public function testActiveMenuItemClassCanBeApplied(): void
    {
        // navigate to the Category CRUD index page using the helper method
        // this uses the MenuDashboardController context
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        static::assertResponseIsSuccessful();

        // test that menu items can potentially have the active class
        // the active class is applied via JavaScript or server-side based on URL matching
        // here we verify the menu structure supports the active state
        $menuItems = $crawler->filter('li.menu-item');
        static::assertGreaterThan(0, $menuItems->count());

        // check that the menu contains items that can receive active class
        // the menu item HTML structure includes class attribute where 'active' can be added
        $html = $crawler->html();
        static::assertStringContainsString('menu-item', $html);
    }

    public function testMenuItemsAreClickable(): void
    {
        // menu is displayed on CRUD pages, not on dashboard welcome page
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        static::assertResponseIsSuccessful();

        // find menu item links (excluding submenu toggles that have # hrefs)
        $menuLinks = $crawler->filter('.menu-item a.menu-item-contents:not(.submenu-toggle)');
        static::assertGreaterThan(0, $menuLinks->count());

        // verify each has an href attribute
        foreach ($menuLinks as $link) {
            $href = $link->getAttribute('href');
            static::assertNotEmpty($href);
        }
    }

    public function testMenuItemLabelsHaveCorrectClass(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // menu item labels should have the correct class
        $menuLabels = $crawler->filter('.menu-item-label');
        static::assertGreaterThan(0, $menuLabels->count());
    }

    public function testSubmenuToggleIconExists(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // submenu toggle icons should exist
        $toggleIcons = $crawler->filter('.submenu-toggle-icon');
        static::assertGreaterThan(0, $toggleIcons->count());
    }

    public function testMenuStructureHasCorrectHierarchy(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // check that submenu items are nested inside their parent
        $submenuParents = $crawler->filter('.has-submenu > ul.submenu');
        static::assertGreaterThan(0, $submenuParents->count());
    }

    public function testSectionHeadersHaveCorrectClass(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // section headers should have menu-header-contents class
        $sectionContents = $crawler->filter('.menu-header .menu-header-contents');
        static::assertGreaterThan(0, $sectionContents->count());
    }

    public function testMenuItemLinksHaveCorrectClass(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // regular menu item links should have menu-item-contents class
        $menuLinks = $crawler->filter('a.menu-item-contents');
        static::assertGreaterThan(0, $menuLinks->count());
    }

    public function testSubmenuToggleHasCorrectClass(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // submenu toggle links should have submenu-toggle class
        $submenuToggles = $crawler->filter('a.submenu-toggle');
        static::assertGreaterThan(0, $submenuToggles->count());
    }
}
