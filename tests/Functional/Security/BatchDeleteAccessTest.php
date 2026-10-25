<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Events;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Exception\InsufficientEntityPermissionException;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\BatchDeleteSecurityCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\EntityPermissionCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecuredDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;

/**
 * Anchors the "Security" rules of skills/easyadmin/SKILL.md: batch delete checks the
 * permission of the batch delete action and the entity permission of every selected
 * entity, and it deletes nothing when any of them is forbidden.
 *
 * The CSRF token of batch delete is shared by all CRUD controllers of the same entity,
 * so these tests take it from a CRUD controller without restrictions.
 */
class BatchDeleteAccessTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return BatchDeleteSecurityCategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return SecuredDashboardController::class;
    }

    public function testBatchDeletePermissionIsChecked(): void
    {
        $category = CategoryFactory::createOne(['active' => true]);

        $this->expectException(ForbiddenActionException::class);
        $this->client->catchExceptions(false);

        try {
            $this->submitBatchDelete('user', BatchDeleteSecurityCategoryCrudController::class, [$category->getId()]);
        } finally {
            $this->assertCategoriesExist([$category->getId()]);
        }
    }

    public function testEntityPermissionIsCheckedForEverySelectedEntity(): void
    {
        $category = CategoryFactory::createOne();

        $this->expectException(InsufficientEntityPermissionException::class);
        $this->client->catchExceptions(false);

        try {
            $this->submitBatchDelete('user', EntityPermissionCategoryCrudController::class, [$category->getId()]);
        } finally {
            $this->assertCategoriesExist([$category->getId()]);
        }
    }

    public function testBatchMixingAllowedAndForbiddenEntitiesDeletesNothing(): void
    {
        $activeCategory = CategoryFactory::createOne(['active' => true]);
        $inactiveCategory = CategoryFactory::createOne(['active' => false]);

        $this->expectException(InsufficientEntityPermissionException::class);
        $this->client->catchExceptions(false);

        try {
            $this->submitBatchDelete('admin', BatchDeleteSecurityCategoryCrudController::class, [$activeCategory->getId(), $inactiveCategory->getId()]);
        } finally {
            $this->assertCategoriesExist([$activeCategory->getId(), $inactiveCategory->getId()]);
        }
    }

    public function testBatchWithAllowedEntitiesIsDeleted(): void
    {
        $firstCategory = CategoryFactory::createOne(['active' => true]);
        $secondCategory = CategoryFactory::createOne(['active' => true]);

        $this->submitBatchDelete('admin', BatchDeleteSecurityCategoryCrudController::class, [$firstCategory->getId(), $secondCategory->getId()]);

        static::assertResponseRedirects();
        $repository = $this->entityManager->getRepository(Category::class);
        $this->entityManager->clear();
        static::assertNull($repository->find($firstCategory->getId()));
        static::assertNull($repository->find($secondCategory->getId()));
    }

    public function testEntityReferencedByLoadedAssociationIsDeleted(): void
    {
        $category = CategoryFactory::createOne(['active' => true]);
        $blogPost = BlogPostFactory::createOne(['categories' => [$category]]);
        $categoryId = $category->getId();
        $blogPostId = $blogPost->getId();
        $this->client->disableReboot();

        static::getContainer()->get('event_dispatcher')->addListener(BeforeCrudActionEvent::class, function (BeforeCrudActionEvent $event) use ($blogPostId): void {
            if (Action::BATCH_DELETE !== $event->getAdminContext()->getCrud()->getCurrentAction()) {
                return;
            }

            $this->entityManager->find(BlogPost::class, $blogPostId)->getCategories()->toArray();
        });

        $this->client->catchExceptions(false);
        $this->submitBatchDelete('admin', BatchDeleteSecurityCategoryCrudController::class, [$categoryId]);

        static::assertResponseRedirects();
        $this->entityManager->clear();
        static::assertNull($this->entityManager->getRepository(Category::class)->find($categoryId));
    }

    public function testEntityReferencedByAssociationLoadedWhileAuthorizingIsDeleted(): void
    {
        $category = CategoryFactory::createOne(['active' => true]);
        $blogPost = BlogPostFactory::createOne(['categories' => [$category]]);
        $categoryId = $category->getId();
        $this->client->disableReboot();

        $this->entityManager->getEventManager()->addEventListener([Events::postLoad], new class($blogPost->getId()) {
            private bool $isLoadingBlogPost = false;

            public function __construct(private int $blogPostId)
            {
            }

            public function postLoad(PostLoadEventArgs $event): void
            {
                if (!$event->getObject() instanceof Category || $this->isLoadingBlogPost) {
                    return;
                }

                $this->isLoadingBlogPost = true;
                try {
                    $event->getObjectManager()->find(BlogPost::class, $this->blogPostId)->getCategories()->toArray();
                } finally {
                    $this->isLoadingBlogPost = false;
                }
            }
        });

        $this->client->catchExceptions(false);
        $this->submitBatchDelete('admin', BatchDeleteSecurityCategoryCrudController::class, [$categoryId]);

        static::assertResponseRedirects();
        $this->entityManager->clear();
        static::assertNull($this->entityManager->getRepository(Category::class)->find($categoryId));
    }

    /**
     * @param class-string $crudControllerFqcn
     * @param list<int>    $entityIds
     */
    private function submitBatchDelete(string $username, string $crudControllerFqcn, array $entityIds): void
    {
        $credentials = ['PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => '1234'];

        $crawler = $this->client->request('GET', $this->generateIndexUrl(null, null, CategoryCrudController::class), [], [], $credentials);
        $batchDeleteButton = $crawler->filter('.batch-actions [data-action-name="'.Action::BATCH_DELETE.'"]');
        static::assertCount(1, $batchDeleteButton);
        $this->entityManager->clear();

        $batchActionUrl = $this->getCrudUrl(Action::BATCH_DELETE, null, [], null, $crudControllerFqcn);
        $this->client->request('POST', $batchActionUrl, [
            'batchActionName' => Action::BATCH_DELETE,
            'entityFqcn' => Category::class,
            'batchActionUrl' => $batchActionUrl,
            'batchActionCsrfToken' => $batchDeleteButton->attr('data-action-csrf-token'),
            'batchActionEntityIds' => $entityIds,
        ], [], $credentials);
    }

    /**
     * @param list<int> $entityIds
     */
    private function assertCategoriesExist(array $entityIds): void
    {
        $this->entityManager->clear();
        $repository = $this->entityManager->getRepository(Category::class);
        foreach ($entityIds as $entityId) {
            static::assertNotNull($repository->find($entityId), sprintf('The category with ID %d must not be deleted.', $entityId));
        }
    }
}
