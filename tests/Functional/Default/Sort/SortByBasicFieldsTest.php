<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SortTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\SortTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\SortTestEntityFactory;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests sorting by basic fields (text, integer, datetime).
 */
class SortByBasicFieldsTest extends AbstractCrudTestCase
{
    /**
     * @var array<string, SortTestEntity>
     */
    private array $entities = [];

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

        // the order by id, text, integer and datetime is different for each column
        $rows = [
            ['textField' => 'Zebra Entity', 'integerField' => 100, 'dateTimeField' => new \DateTime('2024-01-15 10:00:00')],
            ['textField' => 'Apple Entity', 'integerField' => 50, 'dateTimeField' => new \DateTime('2024-03-20 14:30:00')],
            ['textField' => 'Mango Entity', 'integerField' => 75, 'dateTimeField' => null],
            ['textField' => 'Banana Entity', 'integerField' => 200, 'dateTimeField' => new \DateTime('2024-02-10 08:15:00')],
        ];
        foreach ($rows as $row) {
            $this->entities[$row['textField']] = SortTestEntityFactory::createOne($row);
        }
    }

    /**
     * @param array<string, mixed> $query
     * @param list<string>         $expectedTextFields
     */
    #[DataProvider('textFieldSorting')]
    public function testSortingByTextField(array $query, array $expectedTextFields): void
    {
        $this->requestIndex($query);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('th.header-for-field-text[data-column="textField"] > a', 'Text Field');
        $this->assertRowsInOrder($expectedTextFields, 'textField', static fn (SortTestEntity $entity): string => $entity->getTextField());
    }

    public static function textFieldSorting(): \Generator
    {
        // default sort is by ID ASC (configured in the CRUD controller)
        yield 'default sorting (by id ASC)' => [
            [],
            ['Zebra Entity', 'Apple Entity', 'Mango Entity', 'Banana Entity'],
        ];

        yield 'ascending by textField' => [
            ['sort' => ['textField' => 'ASC']],
            ['Apple Entity', 'Banana Entity', 'Mango Entity', 'Zebra Entity'],
        ];

        yield 'descending by textField' => [
            ['sort' => ['textField' => 'DESC']],
            ['Zebra Entity', 'Mango Entity', 'Banana Entity', 'Apple Entity'],
        ];
    }

    /**
     * @param array<string, mixed> $query
     * @param list<string>         $expectedTextFields
     */
    #[DataProvider('integerFieldSorting')]
    public function testSortingByIntegerField(array $query, array $expectedTextFields): void
    {
        $this->requestIndex($query);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('th.header-for-field-integer[data-column="integerField"] > a', 'Integer Field');
        $this->assertRowsInOrder($expectedTextFields, 'integerField', static fn (SortTestEntity $entity): string => (string) $entity->getIntegerField());
    }

    public static function integerFieldSorting(): \Generator
    {
        // default sort is by ID ASC (configured in the CRUD controller)
        yield 'default sorting (by id ASC)' => [
            [],
            ['Zebra Entity', 'Apple Entity', 'Mango Entity', 'Banana Entity'],
        ];

        yield 'ascending by integerField' => [
            ['sort' => ['integerField' => 'ASC']],
            ['Apple Entity', 'Mango Entity', 'Zebra Entity', 'Banana Entity'],
        ];

        yield 'descending by integerField' => [
            ['sort' => ['integerField' => 'DESC']],
            ['Banana Entity', 'Zebra Entity', 'Mango Entity', 'Apple Entity'],
        ];
    }

    /**
     * @param array<string, mixed> $query
     * @param list<string>         $expectedTextFields
     */
    #[DataProvider('dateTimeFieldSorting')]
    public function testSortingByDateTimeField(array $query, array $expectedTextFields): void
    {
        // use IntlDateFormatter to match EasyAdmin's default datetime format (MEDIUM/MEDIUM)
        $dateFormatter = new \IntlDateFormatter('en', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::MEDIUM);

        $this->requestIndex($query);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('th.header-for-field-datetime[data-column="dateTimeField"] > a', 'Date Time Field');
        // easyAdmin shows "Null" label for null values
        $this->assertRowsInOrder($expectedTextFields, 'dateTimeField', static fn (SortTestEntity $entity): string => null !== $entity->getDateTimeField() ? (string) $dateFormatter->format($entity->getDateTimeField()) : 'Null');
    }

    public static function dateTimeFieldSorting(): \Generator
    {
        // default sort is by ID ASC (configured in the CRUD controller)
        yield 'default sorting (by id ASC)' => [
            [],
            ['Zebra Entity', 'Apple Entity', 'Mango Entity', 'Banana Entity'],
        ];

        // SQLite sorts NULL values first in ASC order and last in DESC order
        yield 'ascending by dateTimeField' => [
            ['sort' => ['dateTimeField' => 'ASC']],
            ['Mango Entity', 'Zebra Entity', 'Banana Entity', 'Apple Entity'],
        ];

        yield 'descending by dateTimeField' => [
            ['sort' => ['dateTimeField' => 'DESC']],
            ['Apple Entity', 'Banana Entity', 'Zebra Entity', 'Mango Entity'],
        ];
    }

    /**
     * @param array<string, mixed> $query
     */
    private function requestIndex(array $query): void
    {
        $url = $this->generateIndexUrl();
        if ([] !== $query) {
            $url .= '?'.http_build_query($query);
        }

        $this->client->request('GET', $url);
    }

    /**
     * @param list<string>                     $expectedTextFields
     * @param \Closure(SortTestEntity): string $expectedColumnValue
     */
    private function assertRowsInOrder(array $expectedTextFields, string $column, \Closure $expectedColumnValue): void
    {
        $this->assertSelectorCount(\count($this->entities), 'tbody tr');

        foreach ($expectedTextFields as $index => $textField) {
            $row = $index + 1;
            $expectedValue = $expectedColumnValue($this->entities[$textField]);
            $this->assertSelectorTextSame('tbody tr:nth-child('.$row.') td[data-column="textField"]', $textField, sprintf('Expected "%s" in row %d', $textField, $row));
            $this->assertSelectorTextSame('tbody tr:nth-child('.$row.') td[data-column="'.$column.'"]', $expectedValue, sprintf('Expected "%s" in row %d', $expectedValue, $row));
        }
    }
}
