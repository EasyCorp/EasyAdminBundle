<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\RestrictedDashboard\GuestBlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\RestrictedDashboard\GuestDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checks that a dashboard which restricts its CRUD controllers with the "allowedControllers"
 * option of #[AdminDashboard] uses its own CRUD controllers when EasyAdmin has to find the
 * CRUD controller of an entity (e.g. to render an embedded form).
 */
class RestrictedDashboardControllersTest extends AbstractCrudTestCase
{
    private const AUTH = ['PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => '1234'];

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return GuestBlogPostCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return GuestDashboardController::class;
    }

    public function testExcludedControllerIsNotAvailableInRestrictedDashboard(): void
    {
        $this->client->request('GET', '/admin/guest/staff-user', [], [], self::AUTH);

        static::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testEmbeddedFormUsesCrudControllerOfCurrentDashboard(): void
    {
        $author = UserFactory::createOne(['name' => 'User 1', 'email' => 'user1@example.com']);
        $blogPost = BlogPostFactory::createOne(['author' => $author]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($blogPost->getId()), [], [], self::AUTH);

        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter('[name="BlogPost[author][name]"]'));
        static::assertCount(0, $crawler->filter('[name="BlogPost[author][email]"]'));
    }
}
