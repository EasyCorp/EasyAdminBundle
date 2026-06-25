<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\NestedAssociationSortTestCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\NestedAssociationSortTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\NestedAssociationSortTestEntity;

/**
 * End-to-end checks that ?sort[category.parent] orders the index by the leaf
 * association's `name` (category.parent.name), through a nested AssociationField
 * configured with setSortProperty('name'). Also verifies the nested association cell
 * auto-links to the leaf entity's CRUD controller without setCrudController().
 */
class SortByNestedAssociationTest extends AbstractCrudTestCase
{
    private EntityRepository $repository;

    protected function getControllerFqcn(): string
    {
        return NestedAssociationSortTestCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
        $this->repository = $this->entityManager->getRepository(NestedAssociationSortTestEntity::class);
    }

    /**
     * @dataProvider sorting
     */
    public function testSortByNestedAssociationProperty(string $sortOrder, \Closure $sortFunction): void
    {
        // arrange
        /** @var list<NestedAssociationSortTestEntity> $entities */
        $entities = $this->repository->findAll();
        $sortFunction($entities);
        $expectedNames = array_map(static fn (NestedAssociationSortTestEntity $entity): string => $entity->getName(), $entities);

        // act
        $url = $this->generateIndexUrl().'?'.http_build_query([
            'sort' => ['category.parent' => $sortOrder],
        ]);
        $crawler = $this->client->request('GET', $url);

        // assert
        $this->assertResponseIsSuccessful();
        foreach ($expectedNames as $i => $expectedName) {
            $this->assertSelectorTextSame(
                sprintf('tbody tr:nth-child(%d) td[data-column="name"]', $i + 1),
                $expectedName,
                sprintf('Expected "%s" in row %d', $expectedName, $i + 1),
            );
        }
    }

    public static function sorting(): \Generator
    {
        yield 'ascending by category.parent.name (SQLite nulls first)' => [
            'ASC',
            /**
             * @param list<NestedAssociationSortTestEntity> $entities
             */
            static function (array &$entities): void {
                usort($entities, static function (NestedAssociationSortTestEntity $a, NestedAssociationSortTestEntity $b): int {
                    $aName = $a->getCategory()?->getParent()?->getName();
                    $bName = $b->getCategory()?->getParent()?->getName();

                    // SQLite sorts NULL values FIRST in ASC order
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
        ];

        yield 'descending by category.parent.name (SQLite nulls last)' => [
            'DESC',
            /**
             * @param list<NestedAssociationSortTestEntity> $entities
             */
            static function (array &$entities): void {
                usort($entities, static function (NestedAssociationSortTestEntity $a, NestedAssociationSortTestEntity $b): int {
                    $aName = $a->getCategory()?->getParent()?->getName();
                    $bName = $b->getCategory()?->getParent()?->getName();

                    // SQLite sorts NULL values LAST in DESC order; ties on the primary key are
                    // broken by the controller's setDefaultSort(['id' => 'ASC']), always ascending
                    if (null === $aName && null === $bName) {
                        return $a->getId() <=> $b->getId();
                    }
                    if (null === $aName) {
                        return 1;
                    }
                    if (null === $bName) {
                        return -1;
                    }

                    $cmp = $bName <=> $aName;

                    return 0 !== $cmp ? $cmp : $a->getId() <=> $b->getId();
                });
            },
        ];
    }

    public function testNestedAssociationCellAutoLinksToLeafCrudController(): void
    {
        // the nested AssociationField has no setCrudController(); the configurator must
        // auto-resolve the leaf entity (NestedAssociationSortTestCategory) CRUD controller
        // and render its cell as a link targeting that controller's detail action
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        $hrefs = $crawler
            ->filter('tbody td[data-column="category.parent"] a')
            ->each(static fn ($node): string => $node->attr('href'));

        self::assertNotEmpty($hrefs, 'Expected at least one linked nested association cell');

        // depending on the URL format, the leaf controller is referenced either via its
        // auto-derived pretty-URL slug or via the crudControllerFqcn query parameter; the
        // detail action is identified by the entity id segment (pretty) or crudAction=detail
        $controllerSlug = 'nested-association-sort-test-category';
        $controllerParam = rawurlencode(NestedAssociationSortTestCategoryCrudController::class);
        foreach ($hrefs as $href) {
            self::assertTrue(
                str_contains($href, $controllerSlug) || str_contains($href, $controllerParam),
                sprintf('Expected href "%s" to target the NestedAssociationSortTestCategory CRUD controller', $href),
            );
            self::assertTrue(
                (bool) preg_match('#/'.$controllerSlug.'/\d+#', $href) || str_contains($href, 'crudAction=detail'),
                sprintf('Expected href "%s" to point at the leaf entity detail action', $href),
            );
        }
    }

    public function testNestedAssociationColumnHeaderIsSortable(): void
    {
        // setSortProperty() marks the nested association field as sortable, so its index column
        // header is rendered as a clickable sort link without needing an explicit setSortable(true)
        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('th[data-column="category.parent"] a');
    }
}
