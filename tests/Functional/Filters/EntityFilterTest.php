<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FilterRelatedEntity;

/**
 * Fixture data (relatedEntity field):
 * - ID 1: Related Entity 1
 * - ID 2: Related Entity 2
 * - ID 3: Related Entity 1
 * - ID 4: Related Entity 3
 * - ID 5: Related Entity 2
 * - ID 6: null
 */
class EntityFilterTest extends FilterFunctionalTestCase
{
    /**
     * @dataProvider filterByRelatedEntityProvider
     */
    public function testFilterByRelatedEntity(string $entityName, array $expectedIds): void
    {
        $relatedEntity = $this->entityManager->getRepository(FilterRelatedEntity::class)
            ->findOneBy(['name' => $entityName]);

        $url = $this->generateFilteredIndexUrl([
            'relatedEntity' => [
                'comparison' => ComparisonType::EQ,
                'value' => $relatedEntity->getId(),
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(\count($expectedIds));
        $this->assertVisibleEntityIds($expectedIds);
    }

    public static function filterByRelatedEntityProvider(): iterable
    {
        yield 'matches Related Entity 1' => ['Related Entity 1', [1, 3]];
        yield 'matches Related Entity 2' => ['Related Entity 2', [2, 5]];
        yield 'matches Related Entity 3' => ['Related Entity 3', [4]];
    }

    public function testFilterByRelatedEntityNotEqual(): void
    {
        // get the ID of Related Entity 1
        $relatedEntity = $this->entityManager->getRepository(FilterRelatedEntity::class)
            ->findOneBy(['name' => 'Related Entity 1']);

        // filter for relatedEntity != Related Entity 1
        // should match: ID 2, ID 4, ID 5, ID 6 (null is included in NOT EQUAL)
        $url = $this->generateFilteredIndexUrl([
            'relatedEntity' => [
                'comparison' => ComparisonType::NEQ,
                'value' => $relatedEntity->getId(),
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(4);
        $this->assertVisibleEntityIds([2, 4, 5, 6]);
    }

    public function testFilterByNullRelation(): void
    {
        // filter for relatedEntity IS NULL
        // should match: ID 6
        $url = $this->generateFilteredIndexUrl([
            'relatedEntity' => [
                'comparison' => ComparisonType::EQ,
                'value' => '',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(1);
        $this->assertVisibleEntityIds([6]);
    }

    public function testFilterByNotNullRelation(): void
    {
        // filter for relatedEntity IS NOT NULL
        // should match: ID 1, ID 2, ID 3, ID 4, ID 5
        $url = $this->generateFilteredIndexUrl([
            'relatedEntity' => [
                'comparison' => ComparisonType::NEQ,
                'value' => '',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(5);
        $this->assertVisibleEntityIds([1, 2, 3, 4, 5]);
    }
}
