<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Exception\InsufficientEntityPermissionException;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\EntityPermissionCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecuredDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Anchors rule S1 of skills/easyadmin/SKILL.md: Crud::setEntityPermission() is
 * evaluated per entity instance, so the index page silently drops the rows the
 * user can't access (and shows the 'hidden results' notice) while detail, edit,
 * new and delete throw InsufficientEntityPermissionException instead.
 *
 * The controller keeps the default index actions on purpose: building the entity
 * actions of a hidden row used to throw when generating their URLs, so this test
 * also proves that ActionFactory skips the inaccessible rows.
 *
 * If this behavior changes, update that skill rule too.
 */
class EntityPermissionAccessTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return EntityPermissionCategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return SecuredDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testDetailThrowsInsufficientEntityPermissionExceptionForUnauthorizedUser(): void
    {
        $this->expectException(InsufficientEntityPermissionException::class);
        $this->client->catchExceptions(false);

        $this->client->request(
            'GET',
            $this->generateDetailUrl($this->getFirstCategoryId()),
            [],
            [],
            ['PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => '1234']
        );
    }

    public function testDetailRendersErrorPageForUnauthorizedUser(): void
    {
        $this->client->catchExceptions(true);

        $this->client->request(
            'GET',
            $this->generateDetailUrl($this->getFirstCategoryId()),
            [],
            [],
            ['PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => '1234']
        );

        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        static::assertSelectorTextContains('.error-message', 'permission to access this item.');
    }

    public function testDetailIsAllowedForAuthorizedUser(): void
    {
        $this->client->request(
            'GET',
            $this->generateDetailUrl($this->getFirstCategoryId()),
            [],
            [],
            ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']
        );

        static::assertResponseIsSuccessful();
    }

    public function testEditThrowsInsufficientEntityPermissionExceptionForUnauthorizedUser(): void
    {
        $this->expectException(InsufficientEntityPermissionException::class);
        $this->client->catchExceptions(false);

        $this->client->request(
            'GET',
            $this->generateEditFormUrl($this->getFirstCategoryId()),
            [],
            [],
            ['PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => '1234']
        );
    }

    public function testNewThrowsInsufficientEntityPermissionExceptionForUnauthorizedUser(): void
    {
        $this->expectException(InsufficientEntityPermissionException::class);
        $this->client->catchExceptions(false);

        $this->client->request(
            'GET',
            $this->generateNewFormUrl(),
            [],
            [],
            ['PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => '1234']
        );
    }

    public function testIndexHidesInaccessibleRowsForUnauthorizedUser(): void
    {
        $crawler = $this->client->request(
            'GET',
            $this->generateIndexUrl(),
            [],
            [],
            ['PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => '1234']
        );

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('tbody tr[data-id]'));
        static::assertCount(1, $crawler->filter('tbody tr.datagrid-row-empty .datagrid-row-empty-message'));
        static::assertStringContainsString(
            'Some results can\'t be displayed',
            $crawler->filter('tbody tr.datagrid-row-empty .datagrid-row-empty-message')->text()
        );
    }

    public function testIndexShowsAllRowsForAuthorizedUser(): void
    {
        $crawler = $this->client->request(
            'GET',
            $this->generateIndexUrl(),
            [],
            [],
            ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']
        );

        static::assertResponseIsSuccessful();
        static::assertGreaterThan(0, $crawler->filter('tbody tr[data-id]')->count());
        static::assertCount(1, $crawler->filter(sprintf('tbody tr[data-id="%s"]', $this->getFirstCategoryId())));
        static::assertCount(0, $crawler->filter('tbody tr.datagrid-row-empty'));
    }

    private function getFirstCategoryId(): int
    {
        $category = $this->entityManager->getRepository(Category::class)->findOneBy([]);
        static::assertInstanceOf(Category::class, $category);

        return $category->getId();
    }
}
