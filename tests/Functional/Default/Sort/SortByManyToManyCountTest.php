<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SortTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\SortTestEntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\SortTestRelatedEntityFactory;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests sorting by ManyToMany relationship count.
 */
class SortByManyToManyCountTest extends AbstractCrudTestCase
{
    /**
     * @var array<string, int>
     */
    private const RELATION_COUNT_BY_TEXT_FIELD = [
        'Zebra Entity' => 2,
        'Apple Entity' => 0,
        'Mango Entity' => 3,
        'Banana Entity' => 1,
        // same count as 'Zebra Entity' to check that ties are sorted by id in the same direction
        'Cherry Entity' => 2,
    ];

    protected function getControllerFqcn(): string
    {
        return SortTestEntityCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();

        $relatedEntities = SortTestRelatedEntityFactory::createMany(max(self::RELATION_COUNT_BY_TEXT_FIELD));
        foreach (self::RELATION_COUNT_BY_TEXT_FIELD as $textField => $relationCount) {
            SortTestEntityFactory::createOne([
                'textField' => $textField,
                'manyToManyRelations' => \array_slice($relatedEntities, 0, $relationCount),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $query
     * @param list<string>         $expectedTextFields
     */
    #[DataProvider('sorting')]
    public function testSorting(array $query, array $expectedTextFields): void
    {
        $url = $this->generateIndexUrl();
        if ([] !== $query) {
            $url .= '?'.http_build_query($query);
        }
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('th.header-for-field-association[data-column="manyToManyRelations"] > a', 'Many To Many Relations');
        $this->assertSelectorExists('th.header-for-field-association[data-column="manyToManyRelations"] span.icon svg');
        $this->assertSelectorCount(\count(self::RELATION_COUNT_BY_TEXT_FIELD), 'tbody tr');

        foreach ($expectedTextFields as $index => $textField) {
            $row = $index + 1;
            $relationCount = (string) self::RELATION_COUNT_BY_TEXT_FIELD[$textField];
            $this->assertSelectorTextSame('tbody tr:nth-child('.$row.') td[data-column="textField"]', $textField, sprintf('Expected "%s" in row %d', $textField, $row));
            $this->assertSelectorTextSame('tbody tr:nth-child('.$row.') td[data-column="manyToManyRelations"]', $relationCount, sprintf('Expected "%s" in row %d', $relationCount, $row));
        }
    }

    public static function sorting(): \Generator
    {
        yield 'default sorting (no sort applied)' => [
            [],
            ['Zebra Entity', 'Apple Entity', 'Mango Entity', 'Banana Entity', 'Cherry Entity'],
        ];

        yield 'ascending by manyToManyRelations count' => [
            ['sort' => ['manyToManyRelations' => 'ASC']],
            ['Apple Entity', 'Banana Entity', 'Zebra Entity', 'Cherry Entity', 'Mango Entity'],
        ];

        yield 'descending by manyToManyRelations count' => [
            ['sort' => ['manyToManyRelations' => 'DESC']],
            ['Mango Entity', 'Cherry Entity', 'Zebra Entity', 'Banana Entity', 'Apple Entity'],
        ];
    }
}
