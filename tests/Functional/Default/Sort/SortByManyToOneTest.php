<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SortTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\SortTestEntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\SortTestRelatedEntityFactory;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests sorting by ManyToOne relationship property.
 */
class SortByManyToOneTest extends AbstractCrudTestCase
{
    /**
     * @var array<string, string>
     */
    private array $relatedNameByTextField = [];

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

        // related entities are created in non-alphabetical order so sorting by
        // their name gives a different result than sorting by their id
        $gamma = SortTestRelatedEntityFactory::createOne(['name' => 'Gamma']);
        $alpha = SortTestRelatedEntityFactory::createOne(['name' => 'Alpha']);
        $beta = SortTestRelatedEntityFactory::createOne(['name' => 'Beta']);

        $rows = [
            'Zebra Entity' => $alpha,
            'Apple Entity' => $gamma,
            'Mango Entity' => null,
            'Banana Entity' => $beta,
        ];
        foreach ($rows as $textField => $related) {
            SortTestEntityFactory::createOne(['textField' => $textField, 'manyToOneRelation' => $related]);
            // easyAdmin shows "Null" label for null associations
            $this->relatedNameByTextField[$textField] = $related?->getName() ?? 'Null';
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
        $this->assertSelectorTextSame('th.header-for-field-association[data-column="manyToOneRelation"] > a', 'Many To One Relation');
        $this->assertSelectorExists('th.header-for-field-association[data-column="manyToOneRelation"] span.icon svg');
        $this->assertSelectorCount(\count($this->relatedNameByTextField), 'tbody tr');

        foreach ($expectedTextFields as $index => $textField) {
            $row = $index + 1;
            $relatedName = $this->relatedNameByTextField[$textField];
            $this->assertSelectorTextSame('tbody tr:nth-child('.$row.') td[data-column="textField"]', $textField, sprintf('Expected "%s" in row %d', $textField, $row));
            $this->assertSelectorTextSame('tbody tr:nth-child('.$row.') td[data-column="manyToOneRelation"]', $relatedName, sprintf('Expected "%s" in row %d', $relatedName, $row));
        }
    }

    public static function sorting(): \Generator
    {
        // default sort is by ID ASC (configured in the CRUD controller)
        yield 'default sorting (by id ASC)' => [
            [],
            ['Zebra Entity', 'Apple Entity', 'Mango Entity', 'Banana Entity'],
        ];

        // SQLite sorts NULL values first in ASC order and last in DESC order
        yield 'ascending by manyToOneRelation name' => [
            ['sort' => ['manyToOneRelation' => 'ASC']],
            ['Mango Entity', 'Zebra Entity', 'Banana Entity', 'Apple Entity'],
        ];

        yield 'descending by manyToOneRelation name' => [
            ['sort' => ['manyToOneRelation' => 'DESC']],
            ['Apple Entity', 'Banana Entity', 'Zebra Entity', 'Mango Entity'],
        ];
    }
}
