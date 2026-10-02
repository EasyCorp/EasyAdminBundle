<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Association;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Association\BatchedAssociationsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Trait\ExecutedQueriesTrait;
use Symfony\Component\DomCrawler\Crawler;

/**
 * An index page with two to-one association fields pointing to the same entity and a
 * to-many association field: the related entities are loaded with one query and the
 * number of elements of the to-many association is counted with one query.
 */
class BatchedAssociationsTest extends AbstractCrudTestCase
{
    use ExecutedQueriesTrait;

    protected function getControllerFqcn(): string
    {
        return BatchedAssociationsCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testAssociationsAreLoadedAndCountedWithOneQueryEach(): void
    {
        $this->requestIndexPage();

        // the 'author' and 'publisher' fields point to the same entity, so Doctrine loads them together
        $this->assertCount(1, $this->getExecutedQueriesMatching('/FROM "user" .* IN \(/'));
        $this->assertCount(0, $this->getExecutedQueriesMatching('/FROM "user" .*\.id = \?/'));
        $this->assertCount(1, $this->getExecutedQueriesMatching('/COUNT\(.* blog_post_category .* GROUP BY /'));
        $this->assertCount(0, $this->getExecutedQueriesMatching('/FROM category /'));
        // count query, query to get the IDs of the page, query to load the page entities,
        // query to load the users and query to count the categories
        $this->assertCount(5, $this->getExecutedSelectQueries());
    }

    public function testNumberOfQueriesDoesNotDependOnNumberOfRows(): void
    {
        $this->requestIndexPage();
        $this->assertIndexPageEntityCount(20);
        $numQueriesForFullPage = \count($this->getExecutedSelectQueries());

        $this->requestIndexPage([EA::QUERY => 'Blog Post 1']);
        $this->assertIndexPageEntityCount(12);

        $this->assertCount($numQueriesForFullPage, $this->getExecutedSelectQueries());
    }

    public function testAssociationsAreDisplayed(): void
    {
        $crawler = $this->requestIndexPage();

        foreach (range(1, 20) as $blogPostId) {
            $i = $blogPostId - 1;
            $this->assertSame('User '.($i % 5), $this->getCellText($crawler, $blogPostId, 'author'));
            $this->assertSame($i < 10 ? 'User '.(($i + 1) % 5) : 'Null', $this->getCellText($crawler, $blogPostId, 'publisher'));
            $this->assertSame('1', $this->getCellText($crawler, $blogPostId, 'categories'));
        }
    }

    /**
     * @param array<string, string> $queryParameters
     */
    private function requestIndexPage(array $queryParameters = []): Crawler
    {
        // the entity manager is shared with the first request, so it's cleared to make
        // sure that no related entity is already loaded in memory
        $this->entityManager->clear();
        $this->resetExecutedQueries();

        $crawler = $this->client->request('GET', $this->generateIndexUrl().([] === $queryParameters ? '' : '?'.http_build_query($queryParameters)));
        $this->assertResponseIsSuccessful();

        return $crawler;
    }

    private function getCellText(Crawler $crawler, int $entityId, string $columnName): string
    {
        return trim($crawler->filter(sprintf('tr[data-id="%d"] td[data-column="%s"]', $entityId, $columnName))->text());
    }
}
