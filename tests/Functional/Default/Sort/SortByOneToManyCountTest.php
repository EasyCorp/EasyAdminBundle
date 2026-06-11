<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SortTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\SortTestEntity;

/**
 * Tests sorting by OneToMany relationship count.
 */
class SortByOneToManyCountTest extends AbstractCrudTestCase
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
        $entities = $this->repository->findAll();

        if (null !== $sortFunction) {
            $sortFunction($entities);
        }

        /**
         * @var SortTestEntity $entity
         */
        foreach ($entities as $entity) {
            $expectedAmountMapping[$entity->getTextField()] = $entity->getOneToManyRelations()->count();
        }

        // act
        $url = $this->generateIndexUrl();
        if (!empty($query)) {
            $url .= '?'.http_build_query($query);
        }
        $crawler = $this->client->request('GET', $url);

        // assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('th.header-for-field-association[data-column="oneToManyRelations"] > a', 'One To Many Relations');
        $this->assertSelectorExists('th.header-for-field-association[data-column="oneToManyRelations"] span.icon svg');

        $index = 1;

        foreach ($expectedAmountMapping as $expectedName => $expectedValue) {
            $expectedRow = $index++;

            $this->assertSelectorTextSame('tbody tr:nth-child('.$expectedRow.') td[data-column="textField"]', $expectedName, sprintf('Expected "%s" in row %d', $expectedName, $expectedRow));
            $this->assertSelectorTextSame('tbody tr:nth-child('.$expectedRow.') td[data-column="oneToManyRelations"]', $expectedValue, sprintf('Expected "%s" in row %d', $expectedValue, $expectedRow));
        }
    }

    public static function sorting(): \Generator
    {
        yield 'default sorting (no sort applied)' => [
            [],
            null,
            'internal:sort-arrows',
        ];

        yield 'ascending by oneToManyRelations count' => [
            ['sort' => ['oneToManyRelations' => 'ASC']],
            /**
             * @param list<SortTestEntity> $array
             */
            static function (array &$array) {
                usort($array, static function (SortTestEntity $a, SortTestEntity $b) {
                    $aCount = $a->getOneToManyRelations()->count();
                    $bCount = $b->getOneToManyRelations()->count();

                    if ($aCount === $bCount) {
                        return $a->getId() <=> $b->getId();
                    }

                    return $aCount <=> $bCount;
                });
            },
            'internal:sort-arrow-up',
        ];

        yield 'descending by oneToManyRelations count' => [
            ['sort' => ['oneToManyRelations' => 'DESC']],
            /**
             * @param list<SortTestEntity> $array
             */
            static function (array &$array) {
                usort($array, static function (SortTestEntity $a, SortTestEntity $b) {
                    $aCount = $a->getOneToManyRelations()->count();
                    $bCount = $b->getOneToManyRelations()->count();

                    if ($aCount === $bCount) {
                        return $b->getId() <=> $a->getId();
                    }

                    return $bCount <=> $aCount;
                });
            },
            'internal:sort-arrow-down',
        ];
    }
}
