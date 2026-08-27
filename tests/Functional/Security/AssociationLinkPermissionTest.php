<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\BlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecuredDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;

/**
 * Tests that links to another CRUD controller respect that controller's action permissions.
 */
class AssociationLinkPermissionTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return BlogPostCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return SecuredDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $author = (new User())->setName('User 1')->setEmail('user1@example.com');
        $blogPost = (new BlogPost())
            ->setTitle('Blog Post 1')
            ->setSlug('blog-post-1')
            ->setContent('Content 1')
            ->setCreatedAt(new \DateTimeImmutable('2024-01-01'))
            ->setAuthor($author);

        $this->entityManager->persist($author);
        $this->entityManager->persist($blogPost);
        $this->entityManager->flush();
    }

    /**
     * @dataProvider provideUsers
     */
    public function testAssociationLinkFollowsTargetControllerPermission(string $username, bool $canSeeLinks): void
    {
        $crawler = $this->client->request(
            'GET',
            $this->generateIndexUrl(),
            [],
            [],
            ['PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => '1234'],
        );

        static::assertResponseIsSuccessful();
        // the author is always displayed; only the link to its detail page depends on the permission
        $authorCell = $crawler->filter('td[data-column="author"]')->first();
        static::assertSame('User 1', trim($authorCell->text()));

        $links = $authorCell->filter('a');
        if ($canSeeLinks) {
            static::assertGreaterThan(0, $links->count());
        } else {
            static::assertCount(0, $links);
        }
    }

    public static function provideUsers(): \Generator
    {
        yield 'user without ROLE_ADMIN sees the author as plain text' => ['user', false];
        yield 'admin with ROLE_ADMIN sees the author as a link' => ['admin', true];
    }
}
