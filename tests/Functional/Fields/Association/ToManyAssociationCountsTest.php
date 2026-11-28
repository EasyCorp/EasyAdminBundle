<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Association;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Association\BatchedAssociationsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Association\UserAssociationsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Trait\ExecutedQueriesTrait;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Index pages with to-one, one-to-many and many-to-many association fields, where the
 * collections have different sizes (including empty ones).
 */
class ToManyAssociationCountsTest extends AbstractCrudTestCase
{
    use ExecutedQueriesTrait;

    protected function getControllerFqcn(): string
    {
        return UserAssociationsCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testOneToManyCollectionsAreCountedWithOneQuery(): void
    {
        $categories = CategoryFactory::createSequence([['name' => 'Category 0'], ['name' => 'Category 1']]);
        foreach ([0, 1, 3, 0, 2] as $i => $numBlogPosts) {
            $user = UserFactory::createOne(['name' => 'User '.$i, 'favoriteCategory' => 0 === $i ? null : $categories[$i % 2]]);
            BlogPostFactory::createMany($numBlogPosts, ['author' => $user]);
        }

        $this->entityManager->clear();
        $this->resetExecutedQueries();

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $this->assertResponseIsSuccessful();

        $this->assertCount(1, $this->getExecutedQueriesMatching('/FROM category .* IN \(/'));
        $this->assertCount(1, $this->getExecutedQueriesMatching('/COUNT\(.* GROUP BY /'));
        // count query, query to get the IDs of the page, query to load the page entities,
        // query to load the to-one entities and query to count the collection
        $this->assertCount(5, $this->getExecutedSelectQueries());

        // the displayed numbers are compared with the real collections (loaded after the request)
        $this->entityManager->clear();
        foreach ($this->entityManager->getRepository(User::class)->findAll() as $user) {
            $this->assertSame((string) \count($user->getBlogPosts()), $this->getCellText($crawler, $user->getId(), 'blogPosts'));
            $this->assertSame((string) ($user->getFavoriteCategory() ?? 'Null'), $this->getCellText($crawler, $user->getId(), 'favoriteCategory'));
        }
    }

    public function testManyToManyCollectionsAreCountedWithOneQuery(): void
    {
        $author = UserFactory::createOne(['name' => 'User 0']);
        $categories = CategoryFactory::createMany(3);
        foreach ([0, 1, 3, 0, 2] as $numCategories) {
            BlogPostFactory::createOne(['author' => $author, 'categories' => \array_slice($categories, 0, $numCategories)]);
        }

        $this->entityManager->clear();
        $this->resetExecutedQueries();

        $crawler = $this->client->request('GET', $this->generateIndexUrl(controllerFqcn: BatchedAssociationsCrudController::class));
        $this->assertResponseIsSuccessful();

        $this->assertCount(1, $this->getExecutedQueriesMatching('/COUNT\(.* GROUP BY /'));
        // count query, query to get the IDs of the page, query to load the page entities,
        // query to load the to-one entities and query to count the collection
        $this->assertCount(5, $this->getExecutedSelectQueries());

        // the displayed numbers are compared with the real collections (loaded after the request)
        $this->entityManager->clear();
        foreach ($this->entityManager->getRepository(BlogPost::class)->findAll() as $blogPost) {
            $this->assertSame((string) \count($blogPost->getCategories()), $this->getCellText($crawler, $blogPost->getId(), 'categories'));
        }
    }

    private function getCellText(Crawler $crawler, int $entityId, string $columnName): string
    {
        return trim($crawler->filter(sprintf('tr[data-id="%d"] td[data-column="%s"]', $entityId, $columnName))->text());
    }
}
