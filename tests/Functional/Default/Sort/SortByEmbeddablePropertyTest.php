<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SearchTestAuthorCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\EntityFactory\Address;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\SearchTestAuthorFactory;

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

        // cities are created in non-alphabetical order so the sort is visible
        foreach (['Paris', 'London', 'New York'] as $street => $city) {
            SearchTestAuthorFactory::createOne(['address' => (new Address())->setStreet($street)->setCity($city)]);
        }
    }

    public function testSortByEmbeddablePropertyAscending(): void
    {
        $url = $this->generateIndexUrl().'?'.http_build_query(['sort' => ['address.city' => 'ASC']]);
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertCitiesInOrder(['London', 'New York', 'Paris']);
    }

    public function testSortByEmbeddablePropertyDescending(): void
    {
        $url = $this->generateIndexUrl().'?'.http_build_query(['sort' => ['address.city' => 'DESC']]);
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertCitiesInOrder(['Paris', 'New York', 'London']);
    }

    /**
     * @param list<string> $expectedCities
     */
    private function assertCitiesInOrder(array $expectedCities): void
    {
        $this->assertSelectorCount(\count($expectedCities), 'tbody tr');

        foreach ($expectedCities as $i => $city) {
            $this->assertSelectorTextSame(
                sprintf('tbody tr:nth-child(%d) td[data-column="address.city"]', $i + 1),
                $city,
                sprintf('Expected "%s" in row %d', $city, $i + 1),
            );
        }
    }
}
