<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\MultiColumnSortCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\SortTestEntity;

/**
 * Tests sorting by multiple columns simultaneously.
 */
class SortByMultipleColumnsTest extends AbstractCrudTestCase
{
    private EntityRepository $repository;

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
        $this->repository = $this->entityManager->getRepository(SortTestEntity::class);
    }

    /**
     * @dataProvider multiColumnSorting
     */
    public function testSortingByMultipleColumns(array $query, \Closure $sortFunction): void
    {
        // arrange
        $entities = $this->repository->findAll();
        $sortFunction($entities);

        $expectedMapping = [];
        /** @var SortTestEntity $entity */
        foreach ($entities as $entity) {
            $expectedMapping[] = [
                'textField' => $entity->getTextField(),
                'integerField' => $entity->getIntegerField(),
            ];
        }

        // act
        $url = $this->generateIndexUrl();
        if (!empty($query)) {
            $url .= '?'.http_build_query($query);
        }
        $this->client->request('GET', $url);

        // assert
        $this->assertResponseIsSuccessful();

        $index = 1;
        foreach ($expectedMapping as $expected) {
            $expectedRow = $index++;
            $this->assertSelectorTextSame(
                'tbody tr:nth-child('.$expectedRow.') td[data-column="textField"]',
                $expected['textField'],
                sprintf('Expected textField "%s" in row %d', $expected['textField'], $expectedRow)
            );
            $this->assertSelectorTextSame(
                'tbody tr:nth-child('.$expectedRow.') td[data-column="integerField"]',
                (string) $expected['integerField'],
                sprintf('Expected integerField "%s" in row %d', $expected['integerField'], $expectedRow)
            );
        }
    }

    public static function multiColumnSorting(): \Generator
    {
        yield 'default sorting (integerField ASC, textField ASC from CRUD config)' => [
            [],
            /**
             * @param list<SortTestEntity> $array
             */
            static function (array &$array) {
                usort($array, static function (SortTestEntity $a, SortTestEntity $b) {
                    $intCompare = $a->getIntegerField() <=> $b->getIntegerField();
                    if (0 !== $intCompare) {
                        return $intCompare;
                    }

                    return $a->getTextField() <=> $b->getTextField();
                });
            },
        ];

        yield 'primary ASC, secondary ASC' => [
            ['sort' => ['integerField' => 'ASC', 'textField' => 'ASC']],
            /**
             * @param list<SortTestEntity> $array
             */
            static function (array &$array) {
                usort($array, static function (SortTestEntity $a, SortTestEntity $b) {
                    $intCompare = $a->getIntegerField() <=> $b->getIntegerField();
                    if (0 !== $intCompare) {
                        return $intCompare;
                    }

                    return $a->getTextField() <=> $b->getTextField();
                });
            },
        ];

        yield 'primary ASC, secondary DESC' => [
            ['sort' => ['integerField' => 'ASC', 'textField' => 'DESC']],
            /**
             * @param list<SortTestEntity> $array
             */
            static function (array &$array) {
                usort($array, static function (SortTestEntity $a, SortTestEntity $b) {
                    $intCompare = $a->getIntegerField() <=> $b->getIntegerField();
                    if (0 !== $intCompare) {
                        return $intCompare;
                    }

                    return $b->getTextField() <=> $a->getTextField();
                });
            },
        ];

        yield 'primary DESC, secondary ASC' => [
            ['sort' => ['integerField' => 'DESC', 'textField' => 'ASC']],
            /**
             * @param list<SortTestEntity> $array
             */
            static function (array &$array) {
                usort($array, static function (SortTestEntity $a, SortTestEntity $b) {
                    $intCompare = $b->getIntegerField() <=> $a->getIntegerField();
                    if (0 !== $intCompare) {
                        return $intCompare;
                    }

                    return $a->getTextField() <=> $b->getTextField();
                });
            },
        ];

        yield 'primary DESC, secondary DESC' => [
            ['sort' => ['integerField' => 'DESC', 'textField' => 'DESC']],
            /**
             * @param list<SortTestEntity> $array
             */
            static function (array &$array) {
                usort($array, static function (SortTestEntity $a, SortTestEntity $b) {
                    $intCompare = $b->getIntegerField() <=> $a->getIntegerField();
                    if (0 !== $intCompare) {
                        return $intCompare;
                    }

                    return $b->getTextField() <=> $a->getTextField();
                });
            },
        ];

        yield 'textField primary ASC, integerField secondary ASC' => [
            ['sort' => ['textField' => 'ASC', 'integerField' => 'ASC']],
            /**
             * @param list<SortTestEntity> $array
             */
            static function (array &$array) {
                usort($array, static function (SortTestEntity $a, SortTestEntity $b) {
                    $textCompare = $a->getTextField() <=> $b->getTextField();
                    if (0 !== $textCompare) {
                        return $textCompare;
                    }

                    return $a->getIntegerField() <=> $b->getIntegerField();
                });
            },
        ];

        yield 'textField primary DESC, integerField secondary DESC' => [
            ['sort' => ['textField' => 'DESC', 'integerField' => 'DESC']],
            /**
             * @param list<SortTestEntity> $array
             */
            static function (array &$array) {
                usort($array, static function (SortTestEntity $a, SortTestEntity $b) {
                    $textCompare = $b->getTextField() <=> $a->getTextField();
                    if (0 !== $textCompare) {
                        return $textCompare;
                    }

                    return $b->getIntegerField() <=> $a->getIntegerField();
                });
            },
        ];

        yield 'three columns: integerField ASC, textField ASC, dateTimeField DESC' => [
            ['sort' => ['integerField' => 'ASC', 'textField' => 'ASC', 'dateTimeField' => 'DESC']],
            /**
             * @param list<SortTestEntity> $array
             */
            static function (array &$array) {
                usort($array, static function (SortTestEntity $a, SortTestEntity $b) {
                    $intCompare = $a->getIntegerField() <=> $b->getIntegerField();
                    if (0 !== $intCompare) {
                        return $intCompare;
                    }

                    $textCompare = $a->getTextField() <=> $b->getTextField();
                    if (0 !== $textCompare) {
                        return $textCompare;
                    }

                    // handle nulls (nulls go last in DESC)
                    $aDateTime = $a->getDateTimeField();
                    $bDateTime = $b->getDateTimeField();
                    if (null === $aDateTime && null === $bDateTime) {
                        return 0;
                    }
                    if (null === $aDateTime) {
                        return 1;
                    }
                    if (null === $bDateTime) {
                        return -1;
                    }

                    return $bDateTime <=> $aDateTime;
                });
            },
        ];
    }
}
