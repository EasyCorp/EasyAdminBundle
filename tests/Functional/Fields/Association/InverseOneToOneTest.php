<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Association;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Association\InverseOneToOneCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Trait\ExecutedQueriesTrait;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Doctrine loads the inverse side of one-to-one associations with one query per row while
 * hydrating the entities, and each of those queries flushes the pending eager loads of
 * Doctrine. The to-one associations displayed on the page must still be loaded with a
 * single query for all the rows.
 *
 * Anchors the index page associations rule in the "CRUD controller" section of
 * skills/easyadmin/SKILL.md: displayed to-one associations are loaded with one query per
 * target class, while the inverse side of one-to-one associations still runs one query per
 * row and needs a fetch join (pattern 14 of skills/easyadmin/references/patterns.md).
 *
 * If this behavior changes, update that skill rule too.
 */
class InverseOneToOneTest extends AbstractCrudTestCase
{
    use ExecutedQueriesTrait;

    protected function getControllerFqcn(): string
    {
        return InverseOneToOneCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testToOneAssociationIsLoadedWithOneQuery(): void
    {
        $this->requestIndexPage();

        // 20 entities written by 5 different users
        $this->assertCount(1, $this->getExecutedQueriesMatching('/FROM ["`]?user["`]? .* IN \(\?, \?, \?, \?, \?\)/'));
        $this->assertCount(0, $this->getExecutedQueriesMatching('/FROM ["`]?user["`]? .*\.id = \?/'));
        // the inverse side of the one-to-one association is still loaded once per row
        $this->assertCount(20, $this->getExecutedQueriesMatching('/FROM inverse_one_to_one_test_details /'));
    }

    public function testToOneAssociationIsDisplayed(): void
    {
        $crawler = $this->requestIndexPage();

        foreach (range(0, 19) as $i) {
            $this->assertSame('User '.($i % 5), trim($crawler->filter(sprintf('tr[data-id="%d"] td[data-column="author"]', $i + 1))->text()));
        }
    }

    private function requestIndexPage(): Crawler
    {
        // the entity manager is shared with the first request, so it's cleared to make
        // sure that no related entity is already loaded in memory
        $this->entityManager->clear();
        $this->resetExecutedQueries();

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $this->assertResponseIsSuccessful();

        return $crawler;
    }
}
