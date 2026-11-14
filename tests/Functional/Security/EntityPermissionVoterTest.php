<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Exception\InsufficientEntityPermissionException;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\EntityPermissionVoterCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecuredDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;

/**
 * The 'new' action has no entity yet, so Crud::setEntityPermission() is voted
 * with a null subject. The ID of an existing entity added to the URL must not
 * replace that subject.
 */
class EntityPermissionVoterTest extends AbstractCrudTestCase
{
    private const ADMIN = ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234'];
    private const USER = ['PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => '1234'];

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return EntityPermissionVoterCategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return SecuredDashboardController::class;
    }

    public function testNewIsAllowedForAuthorizedUser(): void
    {
        $this->client->request('GET', $this->generateNewFormUrl(), [], [], self::ADMIN);

        static::assertResponseIsSuccessful();
    }

    public function testNewWithTheIdOfAnEntityIsAllowedForAuthorizedUser(): void
    {
        // EasyAdmin generates these URLs too, because AdminUrlGenerator keeps the entity ID of the
        // current request (e.g. when redirecting after saving an edit form with 'save and add another')
        $categoryId = CategoryFactory::createOne(['active' => true])->getId();

        $this->client->request('GET', $this->getCrudUrl(Action::NEW, $categoryId), [], [], self::ADMIN);

        static::assertResponseIsSuccessful();
    }

    public function testNewDoesNotDisplayTheEntityIdOfTheQueryString(): void
    {
        $crawler = $this->client->request('GET', $this->getCrudUrl(Action::NEW, '<b>Category 1</b>'), [], [], self::ADMIN);

        static::assertResponseIsSuccessful();
        static::assertSame('New category', trim($crawler->filter('h1.title')->text()));
        static::assertStringNotContainsString('<b>Category 1</b>', $this->client->getResponse()->getContent());
    }

    public function testNewIsDeniedForUnauthorizedUser(): void
    {
        $this->expectException(InsufficientEntityPermissionException::class);
        $this->client->catchExceptions(false);

        $this->client->request('GET', $this->generateNewFormUrl(), [], [], self::USER);
    }

    public function testNewIgnoresTheIdOfAnAccessibleEntity(): void
    {
        $accessibleCategoryId = CategoryFactory::createOne(['active' => false])->getId();

        $this->client->request('GET', $this->generateEditFormUrl($accessibleCategoryId), [], [], self::USER);
        static::assertResponseIsSuccessful();

        $this->expectException(InsufficientEntityPermissionException::class);
        $this->client->catchExceptions(false);

        $this->client->request('GET', $this->getCrudUrl(Action::NEW, $accessibleCategoryId), [], [], self::USER);
    }

    public function testSubmittingNewFormWithTheIdOfAnAccessibleEntityDoesNotCreateEntities(): void
    {
        $accessibleCategoryId = CategoryFactory::createOne(['active' => false])->getId();

        $this->client->request('POST', $this->getCrudUrl(Action::NEW, $accessibleCategoryId), [
            'Category' => ['name' => 'Category 2', 'slug' => 'category-2'],
        ], [], self::USER);

        static::assertResponseStatusCodeSame(403);
        $this->entityManager->clear();
        static::assertSame(1, $this->entityManager->getRepository(Category::class)->count([]));
    }

    public function testEditOfAnAccessibleEntityIsAllowed(): void
    {
        $categoryId = CategoryFactory::createOne(['active' => false])->getId();

        $this->client->request('GET', $this->generateEditFormUrl($categoryId), [], [], self::USER);

        static::assertResponseIsSuccessful();
    }

    public function testEditOfAnInaccessibleEntityIsDenied(): void
    {
        $categoryId = CategoryFactory::createOne(['active' => true])->getId();

        $this->expectException(InsufficientEntityPermissionException::class);
        $this->client->catchExceptions(false);

        $this->client->request('GET', $this->generateEditFormUrl($categoryId), [], [], self::USER);
    }
}
