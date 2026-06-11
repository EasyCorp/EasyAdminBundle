<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SortTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\SortTestEntity;

/**
 * Tests sorting by basic fields (text, integer, datetime).
 */
class SortByBasicFieldsTest extends AbstractCrudTestCase
{
    private EntityRepository $repository;

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
        $this->repository = $this->entityManager->getRepository(SortTestEntity::class);
    }

    /**
     * @dataProvider textFieldSorting
     */
    public function testSortingByTextField(array $query, ?string $sortFunction): void
    {
        // arrange
        $expectedValues = [];

        /**
         * @var SortTestEntity $entity
         */
        foreach ($this->repository->findAll() as $entity) {
            $expectedValues[$entity->getId()] = $entity->getTextField();
        }

        if (null !== $sortFunction) {
            $sortFunction($expectedValues);
        }

        // act
        $url = $this->generateIndexUrl();
        if (!empty($query)) {
            $url .= '?'.http_build_query($query);
        }
        $crawler = $this->client->request('GET', $url);

        // assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('th.header-for-field-text[data-column="textField"] > a', 'Text Field');

        $index = 1;
        foreach ($expectedValues as $expectedValue) {
            $expectedRow = $index++;
            $this->assertSelectorTextSame('tbody tr:nth-child('.$expectedRow.') td[data-column="textField"]', $expectedValue, sprintf('Expected "%s" in row %d', $expectedValue, $expectedRow));
        }
    }

    public static function textFieldSorting(): \Generator
    {
        // default sort is by ID ASC (configured in the CRUD controller)
        yield 'default sorting (by id ASC)' => [
            [],
            'ksort',
        ];

        yield 'ascending by textField' => [
            ['sort' => ['textField' => 'ASC']],
            'asort',
        ];

        yield 'descending by textField' => [
            ['sort' => ['textField' => 'DESC']],
            'arsort',
        ];
    }

    /**
     * @dataProvider integerFieldSorting
     */
    public function testSortingByIntegerField(array $query, ?\Closure $sortFunction): void
    {
        // arrange
        $expectedMapping = [];
        $entities = $this->repository->findAll();

        if (null !== $sortFunction) {
            $sortFunction($entities);
        }

        /**
         * @var SortTestEntity $entity
         */
        foreach ($entities as $entity) {
            $expectedMapping[$entity->getTextField()] = $entity->getIntegerField();
        }

        // act
        $url = $this->generateIndexUrl();
        if (!empty($query)) {
            $url .= '?'.http_build_query($query);
        }
        $crawler = $this->client->request('GET', $url);

        // assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('th.header-for-field-integer[data-column="integerField"] > a', 'Integer Field');

        $index = 1;
        foreach ($expectedMapping as $expectedName => $expectedValue) {
            $expectedRow = $index++;
            $this->assertSelectorTextSame('tbody tr:nth-child('.$expectedRow.') td[data-column="textField"]', $expectedName, sprintf('Expected "%s" in row %d', $expectedName, $expectedRow));
            $this->assertSelectorTextSame('tbody tr:nth-child('.$expectedRow.') td[data-column="integerField"]', (string) $expectedValue, sprintf('Expected "%s" in row %d', $expectedValue, $expectedRow));
        }
    }

    public static function integerFieldSorting(): \Generator
    {
        // default sort is by ID ASC (configured in the CRUD controller)
        yield 'default sorting (by id ASC)' => [
            [],
            /**
             * @param list<SortTestEntity> $array
             */
            static function (array &$array) {
                usort($array, static fn (SortTestEntity $a, SortTestEntity $b) => $a->getId() <=> $b->getId());
            },
        ];

        yield 'ascending by integerField' => [
            ['sort' => ['integerField' => 'ASC']],
            /**
             * @param list<SortTestEntity> $array
             */
            static function (array &$array) {
                usort($array, static function (SortTestEntity $a, SortTestEntity $b) {
                    $aValue = $a->getIntegerField();
                    $bValue = $b->getIntegerField();

                    if ($aValue === $bValue) {
                        return $a->getId() <=> $b->getId();
                    }

                    return $aValue <=> $bValue;
                });
            },
        ];

        yield 'descending by integerField' => [
            ['sort' => ['integerField' => 'DESC']],
            /**
             * @param list<SortTestEntity> $array
             */
            static function (array &$array) {
                usort($array, static function (SortTestEntity $a, SortTestEntity $b) {
                    $aValue = $a->getIntegerField();
                    $bValue = $b->getIntegerField();

                    if ($aValue === $bValue) {
                        return $b->getId() <=> $a->getId();
                    }

                    return $bValue <=> $aValue;
                });
            },
        ];
    }

    /**
     * @dataProvider dateTimeFieldSorting
     */
    public function testSortingByDateTimeField(array $query, ?\Closure $sortFunction): void
    {
        // arrange
        $expectedMapping = [];
        $entities = $this->repository->findAll();

        if (null !== $sortFunction) {
            $sortFunction($entities);
        }

        // use IntlDateFormatter to match EasyAdmin's default datetime format (MEDIUM/MEDIUM)
        $dateFormatter = new \IntlDateFormatter('en', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::MEDIUM);

        /**
         * @var SortTestEntity $entity
         */
        foreach ($entities as $entity) {
            $dateTime = $entity->getDateTimeField();
            // easyAdmin shows "Null" label for null values
            $expectedMapping[$entity->getTextField()] = null !== $dateTime ? $dateFormatter->format($dateTime) : 'Null';
        }

        // act
        $url = $this->generateIndexUrl();
        if (!empty($query)) {
            $url .= '?'.http_build_query($query);
        }
        $crawler = $this->client->request('GET', $url);

        // assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('th.header-for-field-datetime[data-column="dateTimeField"] > a', 'Date Time Field');

        $index = 1;
        foreach ($expectedMapping as $expectedName => $expectedValue) {
            $expectedRow = $index++;
            $this->assertSelectorTextSame('tbody tr:nth-child('.$expectedRow.') td[data-column="textField"]', $expectedName, sprintf('Expected "%s" in row %d', $expectedName, $expectedRow));
            $this->assertSelectorTextSame('tbody tr:nth-child('.$expectedRow.') td[data-column="dateTimeField"]', $expectedValue, sprintf('Expected "%s" in row %d', $expectedValue, $expectedRow));
        }
    }

    public static function dateTimeFieldSorting(): \Generator
    {
        // default sort is by ID ASC (configured in the CRUD controller)
        yield 'default sorting (by id ASC)' => [
            [],
            /**
             * @param list<SortTestEntity> $array
             */
            static function (array &$array) {
                usort($array, static fn (SortTestEntity $a, SortTestEntity $b) => $a->getId() <=> $b->getId());
            },
        ];

        yield 'ascending by dateTimeField' => [
            ['sort' => ['dateTimeField' => 'ASC']],
            /**
             * @param list<SortTestEntity> $array
             */
            static function (array &$array) {
                usort($array, static function (SortTestEntity $a, SortTestEntity $b) {
                    $aValue = $a->getDateTimeField();
                    $bValue = $b->getDateTimeField();

                    // handle nulls (nulls go first in ASC)
                    if (null === $aValue && null === $bValue) {
                        return $a->getId() <=> $b->getId();
                    }
                    if (null === $aValue) {
                        return -1;
                    }
                    if (null === $bValue) {
                        return 1;
                    }

                    if ($aValue == $bValue) {
                        return $a->getId() <=> $b->getId();
                    }

                    return $aValue <=> $bValue;
                });
            },
        ];

        yield 'descending by dateTimeField' => [
            ['sort' => ['dateTimeField' => 'DESC']],
            /**
             * @param list<SortTestEntity> $array
             */
            static function (array &$array) {
                usort($array, static function (SortTestEntity $a, SortTestEntity $b) {
                    $aValue = $a->getDateTimeField();
                    $bValue = $b->getDateTimeField();

                    // handle nulls (nulls go last in DESC)
                    if (null === $aValue && null === $bValue) {
                        return $b->getId() <=> $a->getId();
                    }
                    if (null === $aValue) {
                        return 1;
                    }
                    if (null === $bValue) {
                        return -1;
                    }

                    if ($aValue == $bValue) {
                        return $b->getId() <=> $a->getId();
                    }

                    return $bValue <=> $aValue;
                });
            },
        ];
    }
}
