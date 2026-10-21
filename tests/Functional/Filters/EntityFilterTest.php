<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\FilterTestEntityFactory;
use PHPUnit\Framework\Attributes\DataProvider;

class EntityFilterTest extends FilterFunctionalTestCase
{
    /**
     * @var array<string, Category>
     */
    private array $categories = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Category 1', 'Category 2', 'Category 3'] as $name) {
            $this->categories[$name] = CategoryFactory::createOne(['name' => $name]);
        }

        $relations = [
            'first with 1' => 'Category 1',
            'first with 2' => 'Category 2',
            'second with 1' => 'Category 1',
            'with 3' => 'Category 3',
            'second with 2' => 'Category 2',
            'without relation' => null,
        ];
        foreach ($relations as $key => $categoryName) {
            $this->entities[$key] = FilterTestEntityFactory::createOne([
                'relatedEntity' => null === $categoryName ? null : $this->categories[$categoryName],
            ]);
        }
    }

    /**
     * @param list<string> $expectedEntities
     */
    #[DataProvider('filterByRelatedEntityProvider')]
    public function testFilterByRelatedEntity(string $categoryName, array $expectedEntities): void
    {
        $url = $this->generateFilteredIndexUrl([
            'relatedEntity' => [
                'comparison' => ComparisonType::EQ,
                'value' => $this->categories[$categoryName]->getId(),
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
        yield 'matches Category 1' => ['Category 1', ['first with 1', 'second with 1']];
        yield 'matches Category 2' => ['Category 2', ['first with 2', 'second with 2']];
        yield 'matches Category 3' => ['Category 3', ['with 3']];
    }

    public function testFilterByRelatedEntityNotEqual(): void
    {
        // null relations are included in NOT EQUAL
        $url = $this->generateFilteredIndexUrl([
            'relatedEntity' => [
                'comparison' => ComparisonType::NEQ,
                'value' => $this->categories['Category 1']->getId(),
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
