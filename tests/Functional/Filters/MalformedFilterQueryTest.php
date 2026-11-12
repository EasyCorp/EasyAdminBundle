<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\FilterTestEntityFactory;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Query strings are written by hand, by bots and by attackers, so a filter with
 * a wrong or missing comparison or value must be ignored instead of failing.
 */
class MalformedFilterQueryTest extends FilterFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->entities['with values'] = FilterTestEntityFactory::createOne([
            'textFilter' => 'Filter 1',
            'numericFilter' => 1,
            'dateTimeFilter' => new \DateTime('2024-01-01 10:00:00'),
            'choiceFilter' => 'option_a',
            'arrayFilter' => ['tag1'],
            'simpleArrayFilter' => ['tag1'],
            'comparisonFilter' => 1,
        ]);
        $this->entities['without values'] = FilterTestEntityFactory::createOne();
    }

    /**
     * @param array<string, mixed> $filters
     */
    #[DataProvider('malformedFilters')]
    public function testMalformedFilterIsIgnoredInIndex(array $filters): void
    {
        $this->client->request('GET', $this->getCrudUrl('index', null, [EA::FILTERS => $filters]));

        static::assertResponseIsSuccessful();
        $this->assertFilteredCount(\count($this->entities));
        $this->assertVisibleEntities(array_keys($this->entities));
    }

    /**
     * @param array<string, mixed> $filters
     */
    #[DataProvider('malformedFilters')]
    public function testMalformedFilterDoesNotBreakTheFiltersForm(array $filters): void
    {
        $this->client->request('GET', $this->getCrudUrl('renderFilters', null, [EA::FILTERS => $filters]));

        // an invalid filter form is rendered with a 422 status code, which is fine
        static::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        static::assertSelectorExists('form[name="filters"]');
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function malformedFilters(): iterable
    {
        yield 'text: unknown comparison' => [['textFilter' => ['comparison' => 'bogus', 'value' => 'Filter']]];
        yield 'text: array comparison' => [['textFilter' => ['comparison' => ['like'], 'value' => 'Filter']]];
        yield 'text: array value' => [['textFilter' => ['comparison' => 'like', 'value' => ['Filter']]]];
        yield 'text: no comparison' => [['textFilter' => ['value' => 'Filter 1']]];
        yield 'text: empty comparison' => [['textFilter' => ['comparison' => '', 'value' => 'Filter 1']]];
        yield 'text: empty comparison and no value' => [['textFilter' => ['comparison' => '']]];

        yield 'numeric: unknown comparison' => [['numericFilter' => ['comparison' => 'bogus', 'value' => '1']]];
        yield 'numeric: text value' => [['numericFilter' => ['comparison' => '=', 'value' => 'abc']]];
        yield 'numeric: array value' => [['numericFilter' => ['comparison' => '=', 'value' => ['1']]]];
        yield 'numeric: text second value' => [['numericFilter' => ['comparison' => 'between', 'value' => '1', 'value2' => 'abc']]];
        yield 'numeric: array second value' => [['numericFilter' => ['comparison' => 'between', 'value' => '1', 'value2' => ['1']]]];
        yield 'numeric: no comparison' => [['numericFilter' => ['value' => '1']]];
        yield 'numeric: empty comparison' => [['numericFilter' => ['comparison' => '', 'value' => '1']]];
        yield 'numeric: empty comparison and no value' => [['numericFilter' => ['comparison' => '']]];
        yield 'numeric: empty value and no comparison' => [['numericFilter' => ['value' => '']]];

        yield 'datetime: unknown comparison' => [['dateTimeFilter' => ['comparison' => 'bogus']]];
        yield 'datetime: unknown comparison with value' => [['dateTimeFilter' => ['comparison' => 'bogus', 'value' => '2024-01-01T10:00']]];
        yield 'datetime: text value' => [['dateTimeFilter' => ['comparison' => '=', 'value' => 'abc']]];
        yield 'datetime: text second value' => [['dateTimeFilter' => ['comparison' => 'between', 'value' => '2024-01-01T10:00', 'value2' => 'abc']]];
        yield 'datetime: no comparison' => [['dateTimeFilter' => ['value' => '2024-01-01T10:00']]];
        yield 'datetime: empty comparison' => [['dateTimeFilter' => ['comparison' => '', 'value' => '2024-01-01T10:00']]];
        yield 'datetime: empty comparison and no value' => [['dateTimeFilter' => ['comparison' => '']]];

        yield 'choice: unknown comparison' => [['choiceFilter' => ['comparison' => 'bogus', 'value' => 'option_a']]];
        yield 'choice: unknown value' => [['choiceFilter' => ['comparison' => '=', 'value' => 'bogus']]];
        yield 'choice: array value' => [['choiceFilter' => ['comparison' => '=', 'value' => ['option_a']]]];
        yield 'choice: no comparison' => [['choiceFilter' => ['value' => 'option_a']]];
        yield 'choice: empty comparison' => [['choiceFilter' => ['comparison' => '', 'value' => 'option_a']]];

        yield 'array: unknown comparison' => [['arrayFilter' => ['comparison' => 'bogus']]];
        yield 'array: unknown comparison with value' => [['arrayFilter' => ['comparison' => 'bogus', 'value' => ['tag1']]]];
        yield 'array: scalar value' => [['arrayFilter' => ['comparison' => 'like', 'value' => 'tag1']]];
        yield 'array: unknown value' => [['arrayFilter' => ['comparison' => 'like', 'value' => ['bogus']]]];
        yield 'array: no comparison' => [['arrayFilter' => ['value' => ['tag1']]]];
        yield 'array: empty comparison' => [['arrayFilter' => ['comparison' => '', 'value' => ['tag1']]]];
        yield 'array: empty comparison and no value' => [['arrayFilter' => ['comparison' => '']]];

        yield 'array without choices: scalar value' => [['simpleArrayFilter' => ['comparison' => 'like', 'value' => 'tag1']]];
        yield 'array without choices: no comparison' => [['simpleArrayFilter' => ['value' => ['tag1']]]];

        yield 'entity: unknown comparison' => [['relatedEntity' => ['comparison' => 'bogus', 'value' => 'bogus']]];
        yield 'entity: unknown value' => [['relatedEntity' => ['comparison' => '=', 'value' => 'bogus']]];
        yield 'entity: no comparison' => [['relatedEntity' => ['value' => 'bogus']]];
        yield 'entity: empty comparison' => [['relatedEntity' => ['comparison' => '']]];

        yield 'comparison: unknown comparison' => [['comparisonFilter' => ['comparison' => 'bogus', 'value' => '1']]];
        yield 'comparison: text value' => [['comparisonFilter' => ['comparison' => '=', 'value' => 'abc']]];
        yield 'comparison: no comparison' => [['comparisonFilter' => ['value' => '1']]];
        yield 'comparison: empty comparison' => [['comparisonFilter' => ['comparison' => '', 'value' => '1']]];

        yield 'scalar instead of comparison and value' => [['textFilter' => 'Filter 1']];
    }
}
