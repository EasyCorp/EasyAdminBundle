<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FilterRelatedEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\FilterRelatedEntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\FilterTestEntityFactory;
use PHPUnit\Framework\Attributes\DataProvider;

class EntityFilterTest extends FilterFunctionalTestCase
{
    /**
     * @var array<string, FilterRelatedEntity>
     */
    private array $relatedEntities = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Related Entity 1', 'Related Entity 2', 'Related Entity 3'] as $name) {
            $this->relatedEntities[$name] = FilterRelatedEntityFactory::createOne(['name' => $name]);
        }

        $relations = [
            'first with 1' => 'Related Entity 1',
            'first with 2' => 'Related Entity 2',
            'second with 1' => 'Related Entity 1',
            'with 3' => 'Related Entity 3',
            'second with 2' => 'Related Entity 2',
            'without relation' => null,
        ];
        foreach ($relations as $key => $relatedName) {
            $this->entities[$key] = FilterTestEntityFactory::createOne([
                'relatedEntity' => null === $relatedName ? null : $this->relatedEntities[$relatedName],
            ]);
        }
    }

    /**
     * @param list<string> $expectedEntities
     */
    #[DataProvider('filterByRelatedEntityProvider')]
    public function testFilterByRelatedEntity(string $relatedName, array $expectedEntities): void
    {
        $url = $this->generateFilteredIndexUrl([
            'relatedEntity' => [
                'comparison' => ComparisonType::EQ,
                'value' => $this->relatedEntities[$relatedName]->getId(),
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(\count($expectedEntities));
        $this->assertVisibleEntities($expectedEntities);
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function filterByRelatedEntityProvider(): iterable
    {
        yield 'matches Related Entity 1' => ['Related Entity 1', ['first with 1', 'second with 1']];
        yield 'matches Related Entity 2' => ['Related Entity 2', ['first with 2', 'second with 2']];
        yield 'matches Related Entity 3' => ['Related Entity 3', ['with 3']];
    }

    public function testFilterByRelatedEntityNotEqual(): void
    {
        // null relations are included in NOT EQUAL
        $url = $this->generateFilteredIndexUrl([
            'relatedEntity' => [
                'comparison' => ComparisonType::NEQ,
                'value' => $this->relatedEntities['Related Entity 1']->getId(),
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(4);
        $this->assertVisibleEntities(['first with 2', 'with 3', 'second with 2', 'without relation']);
    }

    public function testFilterByNullRelation(): void
    {
        $url = $this->generateFilteredIndexUrl([
            'relatedEntity' => [
                'comparison' => ComparisonType::EQ,
                'value' => '',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(1);
        $this->assertVisibleEntities(['without relation']);
    }

    public function testFilterByNotNullRelation(): void
    {
        $url = $this->generateFilteredIndexUrl([
            'relatedEntity' => [
                'comparison' => ComparisonType::NEQ,
                'value' => '',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(5);
        $this->assertVisibleEntities(['first with 1', 'first with 2', 'second with 1', 'with 3', 'second with 2']);
    }
}
