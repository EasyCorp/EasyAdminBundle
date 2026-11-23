<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Search;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Search\SearchDisabledCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FilterTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use PHPUnit\Framework\Attributes\TestWith;

class SearchDisabledTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return SearchDisabledCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();

        CategoryFactory::createSequence([
            ['name' => 'Category 1', 'slug' => 'slug-1'],
            ['name' => 'Category 2', 'slug' => 'slug-2'],
            ['name' => 'Category 3', 'slug' => 'slug-3'],
        ]);
    }

    public function testSearchFormIsNotDisplayed(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();
        static::assertSelectorNotExists('form.form-action-search');
        static::assertIndexFullEntityCount(3);
    }

    #[TestWith(['Category 1'])]
    #[TestWith(['slug-2'])]
    #[TestWith(['Category 4'])]
    public function testIndexIgnoresTheQuery(string $query): void
    {
        $this->client->request('GET', $this->generateIndexUrl($query));

        static::assertResponseIsSuccessful();
        static::assertIndexFullEntityCount(3);
    }

    public function testFiltersStillApply(): void
    {
        $this->client->request('GET', $this->getCrudUrl('index', null, [
            'filters' => ['name' => ['comparison' => 'like', 'value' => 'Category 2']],
        ]));

        static::assertResponseIsSuccessful();
        static::assertIndexFullEntityCount(1);
    }

    /**
     * Association fields of other controllers use the autocomplete endpoint of
     * this controller, so it must keep filtering by the query.
     */
    public function testAutocompleteStillAppliesTheQuery(): void
    {
        $this->client->request('GET', $this->getCrudUrl('autocomplete', null, [
            'autocompleteContext[crudControllerFqcn]' => FilterTestEntityCrudController::class,
            'autocompleteContext[propertyName]' => 'relatedEntity',
            'autocompleteContext[originatingPage]' => 'new',
            'query' => 'Category 2',
        ]));

        static::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        static::assertSame(['Category 2'], array_column($data['results'], 'entityAsString'));
    }
}
