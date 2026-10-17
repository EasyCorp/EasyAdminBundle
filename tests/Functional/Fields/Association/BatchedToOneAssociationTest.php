<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Association;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\BlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Trait\ExecutedQueriesTrait;

/**
 * The blog post index displays the author (a to-one association) with a TextField, which
 * loads the related entity when it's converted to a string. The related entities must be
 * loaded with a single query for all the rows, not with one query per row.
 */
class BatchedToOneAssociationTest extends AbstractCrudTestCase
{
    use ExecutedQueriesTrait;

    protected function getControllerFqcn(): string
    {
        return BlogPostCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testAuthorsAreLoadedWithOneQuery(): void
    {
        $this->requestIndexPage();

        // 20 blog posts written by 5 different users
        $this->assertCount(1, $this->getExecutedQueriesMatching('/FROM "user" .* IN \(\?, \?, \?, \?, \?\)/'));
        $this->assertCount(0, $this->getExecutedQueriesMatching('/FROM "user" .*\.id = \?/'));
        // count query, query to get the IDs of the page, query to load the page entities and the query to load the authors
        $this->assertCount(4, $this->getExecutedSelectQueries());
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

    public function testAuthorsAreDisplayed(): void
    {
        $crawler = $this->requestIndexPage();

        foreach (range(1, 20) as $blogPostId) {
            $this->assertSame('User '.(($blogPostId - 1) % 5), trim($crawler->filter(sprintf('tr[data-id="%d"] td[data-column="author"]', $blogPostId))->text()));
        }
    }

    /**
     * @param array<string, string> $queryParameters
     */
    private function requestIndexPage(array $queryParameters = []): \Symfony\Component\DomCrawler\Crawler
    {
        // the entity manager is shared with the first request, so it's cleared to make
        // sure that no related entity is already loaded in memory
        $this->entityManager->clear();
        $this->resetExecutedQueries();

        $crawler = $this->client->request('GET', $this->generateIndexUrl().([] === $queryParameters ? '' : '?'.http_build_query($queryParameters)));
        $this->assertResponseIsSuccessful();

        return $crawler;
    }
}
