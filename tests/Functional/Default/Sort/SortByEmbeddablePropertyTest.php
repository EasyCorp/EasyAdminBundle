<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SearchTestAuthorCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\SearchTestAuthor;

/**
 * Sorting by an embedded (embeddable) property such as `address.city`.
 *
 * #7621 hardened the URL `?sort[...]` parameter by rejecting any key containing a
 * dot before the structural checks; embeddable properties are written with a dot,
 * so this regressed embeddable sorting (#7635). This test guards the fix that lets
 * real Doctrine fields with a dotted name (embeddables) through while still
 * rejecting multi-segment association keys.
 */
class SortByEmbeddablePropertyTest extends AbstractCrudTestCase
{
    private EntityRepository $repository;

    protected function getControllerFqcn(): string
    {
        return SearchTestAuthorCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
        $this->repository = $this->entityManager->getRepository(SearchTestAuthor::class);
    }

    public function testSortByEmbeddablePropertyAscending(): void
    {
        $expected = $this->citiesInOrder(\SORT_ASC);

        $url = $this->generateIndexUrl().'?'.http_build_query(['sort' => ['address.city' => 'ASC']]);
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertCitiesInOrder($expected);
    }

    public function testSortByEmbeddablePropertyDescending(): void
    {
        $expected = $this->citiesInOrder(\SORT_DESC);

        $url = $this->generateIndexUrl().'?'.http_build_query(['sort' => ['address.city' => 'DESC']]);
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertCitiesInOrder($expected);
    }

    /**
     * Returns the embedded `address.city` values of every fixture row, ordered by
     * the given direction. Duplicate cities are fine: the assertion compares the
     * city values in order, which are deterministic under `ORDER BY address.city`.
     *
     * @return list<string>
     */
    private function citiesInOrder(int $direction): array
    {
        $cities = array_map(
            static fn (SearchTestAuthor $author): string => $author->getAddress()->getCity(),
            $this->repository->findAll(),
        );

        \SORT_ASC === $direction ? sort($cities) : rsort($cities);

        return $cities;
    }

    /**
     * @param list<string> $expectedCities
     */
    private function assertCitiesInOrder(array $expectedCities): void
    {
        foreach ($expectedCities as $i => $city) {
            $this->assertSelectorTextSame(
                sprintf('tbody tr:nth-child(%d) td[data-column="address.city"]', $i + 1),
                $city,
                sprintf('Expected "%s" in row %d', $city, $i + 1),
            );
        }
    }
}
