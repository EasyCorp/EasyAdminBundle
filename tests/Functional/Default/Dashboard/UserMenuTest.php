<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Dashboard\UserMenuDashboardController;

/**
 * Tests for user menu layout in the dashboard (anonymous user).
 *
 * Tests for authenticated user menu customization are in
 * \EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security\AuthenticatedUserMenuTest
 */
class UserMenuTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return CategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return UserMenuDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testUserMenuExistsInLayout(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // user menu wrapper should exist in the layout
        static::assertGreaterThan(0, $crawler->filter('.user-menu-wrapper')->count());
    }

    public function testAnonymousUserShowsAnonymousLabel(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // when not authenticated, the user name should display "Anonymous"
        // the translation key "user.anonymous" gets translated to "Anonymous"
        $html = $crawler->html();
        static::assertStringContainsString('Anonymous', $html);
    }
}
