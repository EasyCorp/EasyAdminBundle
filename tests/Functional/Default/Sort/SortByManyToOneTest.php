<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SortTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\SortTestEntity;

/**
 * Tests sorting by ManyToOne relationship property.
 */
class SortByManyToOneTest extends AbstractCrudTestCase
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
     * @dataProvider sorting
     */
    public function testSorting(array $query, ?\Closure $sortFunction, string $expectedSortIcon): void
    {
        // arrange
        $expectedAmountMapping = [];

        /** @var list<SortTestEntity> $entities */
        $entities = $this->repository->findAll();

        if (null !== $sortFunction) {
            $sortFunction($entities);
        }

        /**
         * @var SortTestEntity $entity
         */
        foreach ($entities as $entity) {
            // easyAdmin shows "Null" label for null associations
            $relatedName = $entity->getManyToOneRelation()?->getName() ?? 'Null';
            $expectedAmountMapping[$entity->getTextField()] = $relatedName;
        }

        // act
        $url = $this->generateIndexUrl();
        if (!empty($query)) {
            $url .= '?'.http_build_query($query);
        }
        $crawler = $this->client->request('GET', $url);

        // assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('th.header-for-field-association[data-column="manyToOneRelation"] > a', 'Many To One Relation');
        $this->assertSelectorExists('th.header-for-field-association[data-column="manyToOneRelation"] span.icon svg');

        $index = 1;

        foreach ($expectedAmountMapping as $expectedEntityName => $expectedRelatedName) {
            $expectedRow = $index++;

            $this->assertSelectorTextSame('tbody tr:nth-child('.$expectedRow.') td[data-column="textField"]', $expectedEntityName, sprintf('Expected "%s" in row %d', $expectedEntityName, $expectedRow));
            $this->assertSelectorTextSame('tbody tr:nth-child('.$expectedRow.') td[data-column="manyToOneRelation"]', $expectedRelatedName, sprintf('Expected "%s" in row %d', $expectedRelatedName, $expectedRow));
        }
    }

    public static function sorting(): \Generator
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
            'internal:sort-arrows',
        ];

        yield 'ascending by manyToOneRelation name' => [
            ['sort' => ['manyToOneRelation' => 'ASC']],
            /**
             * @param list<SortTestEntity> $array
             */
            static function (array &$array) {
                usort($array, static function (SortTestEntity $a, SortTestEntity $b) {
                    $aName = $a->getManyToOneRelation()?->getName();
                    $bName = $b->getManyToOneRelation()?->getName();

                    // sQLite: NULL values are sorted FIRST in ASC order
                    if (null === $aName && null === $bName) {
                        return $a->getId() <=> $b->getId();
                    }
                    if (null === $aName) {
                        return -1;
                    }
                    if (null === $bName) {
                        return 1;
                    }

                    $cmp = $aName <=> $bName;

                    return 0 !== $cmp ? $cmp : $a->getId() <=> $b->getId();
                });
            },
            'internal:sort-arrow-up',
        ];

        yield 'descending by manyToOneRelation name' => [
            ['sort' => ['manyToOneRelation' => 'DESC']],
            /**
             * @param list<SortTestEntity> $array
             */
            static function (array &$array) {
                usort($array, static function (SortTestEntity $a, SortTestEntity $b) {
                    $aName = $a->getManyToOneRelation()?->getName();
                    $bName = $b->getManyToOneRelation()?->getName();

                    // sQLite: NULL values are sorted LAST in DESC order
                    if (null === $aName && null === $bName) {
                        return $b->getId() <=> $a->getId();
                    }
                    if (null === $aName) {
                        return 1;
                    }
                    if (null === $bName) {
                        return -1;
                    }

                    $cmp = $bName <=> $aName;

                    return 0 !== $cmp ? $cmp : $b->getId() <=> $a->getId();
                });
            },
            'internal:sort-arrow-down',
        ];
    }
}
