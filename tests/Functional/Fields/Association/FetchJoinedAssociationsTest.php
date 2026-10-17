<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Association;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Association\FetchJoinedAssociationsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Trait\ExecutedQueriesTrait;
use Symfony\Component\DomCrawler\Crawler;

/**
 * When the index query fetch joins the associations, they are already loaded when the
 * fields are processed, so EasyAdmin must not run its own queries to load or count them.
 */
class FetchJoinedAssociationsTest extends AbstractCrudTestCase
{
    use ExecutedQueriesTrait;

    protected function getControllerFqcn(): string
    {
        return FetchJoinedAssociationsCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testNoAdditionalQueriesAreExecuted(): void
    {
        $this->entityManager->clear();
        $this->resetExecutedQueries();

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $this->assertResponseIsSuccessful();

        $this->assertCount(0, $this->getExecutedQueriesMatching('/FROM "user"/'));
        $this->assertCount(0, $this->getExecutedQueriesMatching('/COUNT\(.* GROUP BY /'));
        // count query, query to get the IDs of the page and query to load the page entities with their associations
        $this->assertCount(3, $this->getExecutedSelectQueries());

        foreach (range(1, 20) as $blogPostId) {
            $i = $blogPostId - 1;
            $this->assertSame('User '.($i % 5), $this->getCellText($crawler, $blogPostId, 'author'));
            $this->assertSame($i < 10 ? 'User '.(($i + 1) % 5) : 'Null', $this->getCellText($crawler, $blogPostId, 'publisher'));
            $this->assertSame('1', $this->getCellText($crawler, $blogPostId, 'categories'));
        }
    }

    private function getCellText(Crawler $crawler, int $entityId, string $columnName): string
    {
        return trim($crawler->filter(sprintf('tr[data-id="%d"] td[data-column="%s"]', $entityId, $columnName))->text());
    }
}
