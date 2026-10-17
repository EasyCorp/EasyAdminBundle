<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Association;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SortTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\SortTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Trait\ExecutedQueriesTrait;

/**
 * An index page with a to-one, a one-to-many and a many-to-many association field, where
 * the collections have different sizes (including empty ones).
 */
class ToManyAssociationCountsTest extends AbstractCrudTestCase
{
    use ExecutedQueriesTrait;

    protected function getControllerFqcn(): string
    {
        return SortTestEntityCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testCollectionsAreCountedWithOneQueryEach(): void
    {
        $this->entityManager->clear();
        $this->resetExecutedQueries();

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $this->assertResponseIsSuccessful();

        $this->assertCount(1, $this->getExecutedQueriesMatching('/FROM sort_test_related_entity .* IN \(/'));
        $this->assertCount(2, $this->getExecutedQueriesMatching('/COUNT\(.* GROUP BY /'));
        // count query, query to get the IDs of the page, query to load the page entities,
        // query to load the to-one entities and two queries to count the collections
        $this->assertCount(6, $this->getExecutedSelectQueries());

        // the displayed numbers are compared with the real collections (loaded after the request)
        $this->entityManager->clear();
        foreach ($this->entityManager->getRepository(SortTestEntity::class)->findAll() as $entity) {
            $this->assertSame((string) \count($entity->getOneToManyRelations()), $this->getCellText($crawler, $entity->getId(), 'oneToManyRelations'));
            $this->assertSame((string) \count($entity->getManyToManyRelations()), $this->getCellText($crawler, $entity->getId(), 'manyToManyRelations'));
            $this->assertSame((string) ($entity->getManyToOneRelation() ?? 'Null'), $this->getCellText($crawler, $entity->getId(), 'manyToOneRelation'));
        }
    }

    private function getCellText(\Symfony\Component\DomCrawler\Crawler $crawler, int $entityId, string $columnName): string
    {
        return trim($crawler->filter(sprintf('tr[data-id="%d"] td[data-column="%s"]', $entityId, $columnName))->text());
    }
}
