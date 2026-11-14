<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\UglyUrls\Security;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Exception\InsufficientEntityPermissionException;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\UglyUrlsApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\UglyUrlsApp\Controller\EntityPermissionVoterCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\UglyUrlsApp\Kernel;

/**
 * Same as the EntityPermissionVoterTest of the secured app, but with the legacy
 * URLs, where the CRUD action and the entity ID are query parameters. This app
 * has no users, so the voter only grants access to the existing inactive categories.
 *
 * @group legacy
 */
class EntityPermissionVoterTest extends AbstractCrudTestCase
{
    /** @var list<int> */
    private array $createdCategoryIds = [];

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
        return DashboardController::class;
    }

    protected function tearDown(): void
    {
        if ([] !== $this->createdCategoryIds) {
            $this->entityManager->clear();
            foreach ($this->entityManager->getRepository(Category::class)->findBy(['id' => $this->createdCategoryIds]) as $category) {
                $this->entityManager->remove($category);
            }
            $this->entityManager->flush();
        }

        parent::tearDown();
    }

    public function testNewIsDenied(): void
    {
        $this->expectException(InsufficientEntityPermissionException::class);
        $this->client->catchExceptions(false);

        $this->client->request('GET', $this->generateNewFormUrl());
    }

    public function testNewIgnoresTheIdOfAnAccessibleEntity(): void
    {
        $accessibleCategoryId = $this->createCategory(active: false);

        $this->client->request('GET', $this->generateEditFormUrl($accessibleCategoryId));
        static::assertResponseIsSuccessful();

        $this->expectException(InsufficientEntityPermissionException::class);
        $this->client->catchExceptions(false);

        $this->client->request('GET', '/admin?'.http_build_query([
            EA::CRUD_CONTROLLER_FQCN => EntityPermissionVoterCategoryCrudController::class,
            EA::CRUD_ACTION => Action::NEW,
            EA::ENTITY_ID => $accessibleCategoryId,
        ]));
    }

    public function testSubmittingNewFormWithTheIdOfAnAccessibleEntityDoesNotCreateEntities(): void
    {
        $accessibleCategoryId = $this->createCategory(active: false);
        $categoryCount = $this->entityManager->getRepository(Category::class)->count([]);

        $this->client->request('POST', $this->getCrudUrl(Action::NEW, $accessibleCategoryId), [
            'Category' => ['name' => 'Category created by user', 'slug' => 'category-created-by-user'],
        ]);

        static::assertResponseStatusCodeSame(403);
        $this->entityManager->clear();
        static::assertSame($categoryCount, $this->entityManager->getRepository(Category::class)->count([]));
    }

    public function testEditOfAnInaccessibleEntityIsDenied(): void
    {
        $categoryId = $this->createCategory(active: true);

        $this->expectException(InsufficientEntityPermissionException::class);
        $this->client->catchExceptions(false);

        $this->client->request('GET', $this->generateEditFormUrl($categoryId));
    }

    private function createCategory(bool $active): int
    {
        $category = (new Category())
            ->setName('Entity permission voter category')
            ->setSlug('entity-permission-voter-category')
            ->setActive($active);
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $this->createdCategoryIds[] = $category->getId();
    }
}
