<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\FilterTestEntityFactory;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * ArrayFilter on a simple_array column, which Doctrine stores as comma-separated values
 * (e.g. 'state1,state2'); the JSON column case is tested in ArrayFilterTest.
 */
class SimpleArrayFilterTest extends FilterFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $states = [
            'state1+state2' => ['state1', 'state2'],
            'state2+state3' => ['state2', 'state3'],
            'state1' => ['state1'],
            'state10' => ['state10'],
        ];
        foreach ($states as $key => $entityStates) {
            $this->entities[$key] = FilterTestEntityFactory::createOne(['simpleArrayFilter' => $entityStates]);
        }
    }

    /**
     * @param list<string> $values
     * @param list<string> $expectedEntities
     */
    #[TestWith([ComparisonType::CONTAINS, ['state1'], ['state1+state2', 'state1']])]
    #[TestWith([ComparisonType::CONTAINS, ['state2'], ['state1+state2', 'state2+state3']])]
    #[TestWith([ComparisonType::CONTAINS, ['state1', 'state3'], ['state1+state2', 'state2+state3', 'state1']])]
    #[TestWith([ComparisonType::NOT_CONTAINS, ['state1'], ['state2+state3', 'state10']])]
    #[TestWith([ComparisonType::CONTAINS_ALL, ['state1', 'state2'], ['state1+state2']])]
    #[TestWith([ComparisonType::CONTAINS_ALL, ['state2', 'state3'], ['state2+state3']])]
    public function testFilter(string $comparison, array $values, array $expectedEntities): void
    {
        $url = $this->generateFilteredIndexUrl([
            'simpleArrayFilter' => [
                'comparison' => $comparison,
                'value' => $values,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(\count($expectedEntities));
        $this->assertVisibleEntities($expectedEntities);
    }
}
