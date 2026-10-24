<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Autocomplete;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Autocomplete\BlogPostWithAutocompleteCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Autocomplete\BlogPostWithGroupByCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Autocomplete\BlogPostWithGroupByPropertyPathCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;

class AutocompleteGroupByTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return BlogPostWithGroupByCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function requestAutocompleteResults(string $targetControllerFqcn, string $originatingControllerFqcn, string $propertyName, string $query): array
    {
        $autocompleteUrl = $this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController($targetControllerFqcn)
            ->setAction('autocomplete')
            ->set('page', 1)
            ->set('query', $query)
            ->set('autocompleteContext', [
                'crudControllerFqcn' => $originatingControllerFqcn,
                'propertyName' => $propertyName,
                'originatingPage' => 'new',
            ])
            ->generateUrl();

        $this->client->request('GET', $autocompleteUrl);
        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('results', $data);

        return $data['results'];
    }

    public function testGroupByCallableAddsTheGroupToEachResult(): void
    {
        CategoryFactory::createOne(['name' => 'Category 1', 'active' => true]);
        CategoryFactory::createOne(['name' => 'Category 2', 'active' => false]);

        $results = $this->requestAutocompleteResults(
            CategoryCrudController::class,
            BlogPostWithGroupByCrudController::class,
            'categories',
            'Category'
        );

        $this->assertCount(2, $results);

        $resultsByLabel = array_column($results, null, 'entityAsString');
        $this->assertSame('Active', $resultsByLabel['Category 1']['entityGroup']);
        // a null group leaves the result ungrouped: the key must not even be present
        $this->assertArrayNotHasKey('entityGroup', $resultsByLabel['Category 2']);
    }

    public function testGroupByPropertyPathAddsTheGroupToEachResult(): void
    {
        CategoryFactory::createOne(['name' => 'Category 1', 'slug' => 'category-1']);

        $results = $this->requestAutocompleteResults(
            CategoryCrudController::class,
            BlogPostWithGroupByPropertyPathCrudController::class,
            'categories',
            'Category 1'
        );

        $this->assertCount(1, $results);
        $this->assertSame('category-1', $results[0]['entityGroup']);
    }

    public function testResultsHaveNoGroupWhenGroupByIsNotDefined(): void
    {
        CategoryFactory::createOne(['name' => 'Category 1', 'active' => true]);

        $results = $this->requestAutocompleteResults(
            CategoryCrudController::class,
            BlogPostWithAutocompleteCrudController::class,
            'categories',
            'Category 1'
        );

        $this->assertCount(1, $results);
        $this->assertArrayNotHasKey('entityGroup', $results[0]);
    }

    public function testSelectedChoicesAreRenderedInsideOptgroupsInTheForm(): void
    {
        $activeCategory = CategoryFactory::createOne(['name' => 'Category 1', 'active' => true]);
        $blogPost = BlogPostFactory::createOne(['categories' => [$activeCategory]]);

        $editUrl = $this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController(BlogPostWithGroupByCrudController::class)
            ->setAction('edit')
            ->setEntityId($blogPost->getId())
            ->generateUrl();

        $crawler = $this->client->request('GET', $editUrl);
        $this->assertResponseIsSuccessful();

        $selectedOption = $crawler->filter('optgroup[label="Active"] option[selected]');
        $this->assertCount(1, $selectedOption);
        $this->assertSame('Category 1', $selectedOption->text());
    }
}
