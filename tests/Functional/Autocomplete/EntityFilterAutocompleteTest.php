<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Autocomplete;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FilterTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\FilterTestEntityFactory;
use Symfony\Component\DomCrawler\Crawler;

class EntityFilterAutocompleteTest extends AbstractCrudTestCase
{
    /**
     * @var list<Category>
     */
    private array $categories = [];

    protected function getControllerFqcn(): string
    {
        return FilterTestEntityCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();

        // the default slug ('Category 12 slug') could also match the searched names
        $this->categories = CategoryFactory::createSequence([
            ['name' => 'Category 1', 'slug' => 'category-1'],
            ['name' => 'Category 2', 'slug' => 'category-2'],
            ['name' => 'Category 3', 'slug' => 'category-3'],
        ]);
    }

    public function testFilterAutocompleteReturnsJsonWithCorrectStructure(): void
    {
        $autocompleteUrl = $this->generateFilterAutocompleteUrl();

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();

        static::assertTrue($response->isSuccessful(), sprintf(
            'Filter autocomplete request should be successful. Got status %d. URL: %s',
            $response->getStatusCode(),
            $autocompleteUrl
        ));
        static::assertSame('application/json', $response->headers->get('Content-Type'));

        $data = json_decode($response->getContent(), true);
        static::assertIsArray($data, 'Response should be valid JSON');

        // the response should always have a next_page key
        static::assertArrayHasKey('next_page', $data, 'Response should contain next_page key');

        static::assertCount(3, $data['results']);
        foreach ($data['results'] as $result) {
            static::assertArrayHasKey('entityId', $result, 'Result should have entityId');
            static::assertArrayHasKey('entityAsString', $result, 'Result should have entityAsString');
        }
    }

    public function testFilterAutocompleteReturnsAllEntities(): void
    {
        $autocompleteUrl = $this->generateFilterAutocompleteUrl();

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertCount(3, $data['results'], 'Should return all 3 categories');
    }

    public function testFilterAutocompleteFiltersResultsByQuery(): void
    {
        // search for "Category 1"
        $autocompleteUrl = $this->generateFilterAutocompleteUrl('Category 1');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertCount(1, $data['results'], 'Should return only entities matching "Category 1"');
        static::assertSame((string) $this->categories[0]->getId(), $data['results'][0]['entityId']);
        static::assertSame('Category 1', $data['results'][0]['entityAsString']);
    }

    public function testFilterAutocompleteReturnsEmptyForNonMatchingQuery(): void
    {
        $autocompleteUrl = $this->generateFilterAutocompleteUrl('DoesNotExist');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertCount(0, $data['results'], 'Should return no results for non-matching query');
    }

    public function testFilterAutocompleteResultsHaveCorrectStructure(): void
    {
        $autocompleteUrl = $this->generateFilterAutocompleteUrl();

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertCount(3, $data['results']);

        foreach ($data['results'] as $result) {
            static::assertArrayHasKey('entityId', $result, 'Result should have entityId');
            static::assertArrayHasKey('entityAsString', $result, 'Result should have entityAsString');
            static::assertNotEmpty($result['entityId'], 'entityId should not be empty');
            static::assertNotEmpty($result['entityAsString'], 'entityAsString should not be empty');
        }

        static::assertArrayHasKey('next_page', $data, 'Response should include next_page field');
    }

    public function testFilterAutocompleteReturnsCorrectEntityValues(): void
    {
        $autocompleteUrl = $this->generateFilterAutocompleteUrl();

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertArrayHasKey('results', $data);

        $expectedResults = array_map(
            static fn (Category $category): array => ['entityId' => (string) $category->getId(), 'entityAsString' => $category->getName()],
            $this->categories
        );
        $actualResults = array_map(
            static fn (array $result): array => ['entityId' => $result['entityId'], 'entityAsString' => $result['entityAsString']],
            $data['results']
        );
        static::assertSame($expectedResults, $actualResults);
    }

    public function testFilterAutocompletePartialSearch(): void
    {
        // search with partial name "Categ" - should match all 3 categories
        $autocompleteUrl = $this->generateFilterAutocompleteUrl('Categ');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertCount(3, $data['results'], 'Partial search "Categ" should match all 3 categories');
    }

    public function testFilterAutocompleteCaseInsensitiveSearch(): void
    {
        // search with lowercase
        $autocompleteUrl = $this->generateFilterAutocompleteUrl('category 1');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertCount(1, $data['results'], 'Lowercase search "category 1" should match "Category 1"');
        static::assertSame((string) $this->categories[0]->getId(), $data['results'][0]['entityId']);
    }

    public function testFilterAutocompleteIntegrationWithFilter(): void
    {
        // first, get an entity via autocomplete
        $autocompleteUrl = $this->generateFilterAutocompleteUrl('Category 2');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $autocompleteData = json_decode($response->getContent(), true);

        static::assertArrayHasKey('results', $autocompleteData);
        static::assertCount(1, $autocompleteData['results']);
        $selectedEntityId = $autocompleteData['results'][0]['entityId'];
        static::assertSame((string) $this->categories[1]->getId(), $selectedEntityId);

        $matchingEntity = FilterTestEntityFactory::createOne(['relatedEntity' => $this->categories[1]]);
        FilterTestEntityFactory::createOne(['relatedEntity' => $this->categories[0]]);

        // now verify we can use this ID in a filter
        $filterUrl = $this->generateFilteredIndexUrl([
            'relatedEntity' => [
                'comparison' => '=',
                'value' => $selectedEntityId,
            ],
        ]);

        $crawler = $this->client->request('GET', $filterUrl);
        $response = $this->client->getResponse();

        static::assertTrue($response->isSuccessful(), 'Filter with autocomplete-selected entity should work');
        $rowIds = $crawler->filter('table.datagrid tbody tr[data-id]')->each(static fn (Crawler $row) => $row->attr('data-id'));
        static::assertSame([(string) $matchingEntity->getId()], $rowIds);
    }

    private function generateFilterAutocompleteUrl(?string $query = null, int $page = 1): string
    {
        $options = [
            'autocompleteContext[crudControllerFqcn]' => FilterTestEntityCrudController::class,
            'autocompleteContext[propertyName]' => 'relatedEntity',
            'autocompleteContext[originatingPage]' => 'index',
        ];

        if (null !== $query) {
            $options['query'] = $query;
        }

        $options['page'] = $page;

        return $this->getCrudUrl(
            'autocomplete',
            null,
            $options,
            DashboardController::class,
            CategoryCrudController::class
        );
    }

    /**
     * @param array<string, array<string, mixed>> $filters Array of filter configurations
     */
    private function generateFilteredIndexUrl(array $filters): string
    {
        $options = [];
        foreach ($filters as $fieldName => $filterConfig) {
            foreach ($filterConfig as $key => $value) {
                $options["filters[{$fieldName}][{$key}]"] = $value;
            }
        }

        return $this->getCrudUrl('index', null, $options);
    }
}
