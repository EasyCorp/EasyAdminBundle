<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\UserMenuDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;

/**
 * Tests for user menu customization with authenticated users.
 */
class AuthenticatedUserMenuTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

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
        $this->client->setServerParameters(['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);
    }

    public function testAuthenticatedUserShowsCustomName(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // when authenticated, the custom name should appear
        $html = $crawler->html();
        static::assertStringContainsString('Custom User Name', $html);
    }

    public function testUserMenuShowsCustomAvatar(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the avatar image should have our custom URL
        $avatarImg = $crawler->filter('img.user-avatar');
        static::assertGreaterThan(0, $avatarImg->count());
        static::assertStringContainsString('https://example.com/avatar.png', $avatarImg->attr('src') ?? '');
    }

    public function testUserMenuShowsCustomItems(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // custom menu items should be present
        $html = $crawler->html();

        static::assertStringContainsString('Custom Link', $html);
        static::assertStringContainsString('https://example.com', $html);
        static::assertStringContainsString('Another Link', $html);
        static::assertStringContainsString('https://symfony.com', $html);
    }

    public function testUserMenuDisplaysNameWhenEnabled(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the user name should be visible in the header (displayUserName is enabled)
        $userNameSpan = $crawler->filter('.content-top .user-menu-wrapper .user-name');
        static::assertGreaterThan(0, $userNameSpan->count());
    }

    public function testUserMenuDropdownContainsLoggedInAsLabel(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // the dropdown should contain "Logged in as" text (translated)
        $html = $crawler->html();
        static::assertStringContainsString('user-label', $html);
    }

    public function testUserMenuHasCorrectStructure(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // verify structure: user details button, user avatar, user name
        static::assertGreaterThan(0, $crawler->filter('.user-details')->count());
        static::assertGreaterThan(0, $crawler->filter('.user-avatar')->count());
    }
}
