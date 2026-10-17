<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\MultiColumnSortCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\SortTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\SortTestEntityFactory;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests sorting by multiple columns simultaneously.
 */
class SortByMultipleColumnsTest extends AbstractCrudTestCase
{
    /**
     * @var array<string, SortTestEntity>
     */
    private array $entities = [];

    protected function getControllerFqcn(): string
    {
        return MultiColumnSortCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();

        // integerField and textField values repeat so that every secondary (and the
        // tertiary dateTimeField) sort column decides the order of some rows
        $rows = [
            'banana10' => ['textField' => 'Banana', 'integerField' => 10, 'dateTimeField' => new \DateTime('2024-01-01 10:00:00')],
            'apple20' => ['textField' => 'Apple', 'integerField' => 20, 'dateTimeField' => new \DateTime('2024-01-01 10:00:00')],
            'apple10February' => ['textField' => 'Apple', 'integerField' => 10, 'dateTimeField' => new \DateTime('2024-02-01 10:00:00')],
            'apple10Null' => ['textField' => 'Apple', 'integerField' => 10, 'dateTimeField' => null],
            'apple10March' => ['textField' => 'Apple', 'integerField' => 10, 'dateTimeField' => new \DateTime('2024-03-01 10:00:00')],
            'cherry20' => ['textField' => 'Cherry', 'integerField' => 20, 'dateTimeField' => new \DateTime('2024-01-01 10:00:00')],
        ];
        foreach ($rows as $key => $row) {
            $this->entities[$key] = SortTestEntityFactory::createOne($row);
        }
    }

    /**
     * @param array<string, mixed>     $query
     * @param list<array{string, int}> $expectedRows pairs of textField and integerField values
     */
    #[DataProvider('multiColumnSorting')]
    public function testSortingByMultipleColumns(array $query, array $expectedRows): void
    {
        $url = $this->generateIndexUrl();
        if ([] !== $query) {
            $url .= '?'.http_build_query($query);
        }
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorCount(\count($this->entities), 'tbody tr');

        foreach ($expectedRows as $index => [$textField, $integerField]) {
            $this->assertRowTextAndInteger($index + 1, $textField, $integerField);
        }
    }

    public static function multiColumnSorting(): \Generator
    {
        yield 'default sorting (integerField ASC, textField ASC from CRUD config)' => [
            [],
            [['Apple', 10], ['Apple', 10], ['Apple', 10], ['Banana', 10], ['Apple', 20], ['Cherry', 20]],
        ];

        yield 'primary ASC, secondary ASC' => [
            ['sort' => ['integerField' => 'ASC', 'textField' => 'ASC']],
            [['Apple', 10], ['Apple', 10], ['Apple', 10], ['Banana', 10], ['Apple', 20], ['Cherry', 20]],
        ];

        yield 'primary ASC, secondary DESC' => [
            ['sort' => ['integerField' => 'ASC', 'textField' => 'DESC']],
            [['Banana', 10], ['Apple', 10], ['Apple', 10], ['Apple', 10], ['Cherry', 20], ['Apple', 20]],
        ];

        yield 'primary DESC, secondary ASC' => [
            ['sort' => ['integerField' => 'DESC', 'textField' => 'ASC']],
            [['Apple', 20], ['Cherry', 20], ['Apple', 10], ['Apple', 10], ['Apple', 10], ['Banana', 10]],
        ];

        yield 'primary DESC, secondary DESC' => [
            ['sort' => ['integerField' => 'DESC', 'textField' => 'DESC']],
            [['Cherry', 20], ['Apple', 20], ['Banana', 10], ['Apple', 10], ['Apple', 10], ['Apple', 10]],
        ];

        yield 'textField primary ASC, integerField secondary ASC' => [
            ['sort' => ['textField' => 'ASC', 'integerField' => 'ASC']],
            [['Apple', 10], ['Apple', 10], ['Apple', 10], ['Apple', 20], ['Banana', 10], ['Cherry', 20]],
        ];

        yield 'textField primary DESC, integerField secondary DESC' => [
            ['sort' => ['textField' => 'DESC', 'integerField' => 'DESC']],
            [['Cherry', 20], ['Banana', 10], ['Apple', 20], ['Apple', 10], ['Apple', 10], ['Apple', 10]],
        ];
    }

    public function testSortingByThreeColumns(): void
    {
        // use IntlDateFormatter to match EasyAdmin's default datetime format (MEDIUM/MEDIUM)
        $dateFormatter = new \IntlDateFormatter('en', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::MEDIUM);

        $url = $this->generateIndexUrl().'?'.http_build_query([
            'sort' => ['integerField' => 'ASC', 'textField' => 'ASC', 'dateTimeField' => 'DESC'],
        ]);
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorCount(\count($this->entities), 'tbody tr');

        // SQLite sorts NULL values last in DESC order
        $expectedOrder = ['apple10March', 'apple10February', 'apple10Null', 'banana10', 'apple20', 'cherry20'];
        foreach ($expectedOrder as $index => $key) {
            $row = $index + 1;
            $entity = $this->entities[$key];
            $this->assertRowTextAndInteger($row, $entity->getTextField(), $entity->getIntegerField());

            // easyAdmin shows "Null" label for null values
            $expectedDateTime = null !== $entity->getDateTimeField() ? (string) $dateFormatter->format($entity->getDateTimeField()) : 'Null';
            $this->assertSelectorTextSame(
                'tbody tr:nth-child('.$row.') td[data-column="dateTimeField"]',
                $expectedDateTime,
                sprintf('Expected dateTimeField "%s" in row %d', $expectedDateTime, $row)
            );
        }
    }

    private function assertRowTextAndInteger(int $row, ?string $textField, ?int $integerField): void
    {
        $this->assertSelectorTextSame(
            'tbody tr:nth-child('.$row.') td[data-column="textField"]',
            (string) $textField,
            sprintf('Expected textField "%s" in row %d', $textField, $row)
        );
        $this->assertSelectorTextSame(
            'tbody tr:nth-child('.$row.') td[data-column="integerField"]',
            (string) $integerField,
            sprintf('Expected integerField "%s" in row %d', $integerField, $row)
        );
    }
}
