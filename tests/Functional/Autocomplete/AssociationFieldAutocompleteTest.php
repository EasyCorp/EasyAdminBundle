<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Autocomplete;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FilterTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;

class AssociationFieldAutocompleteTest extends AbstractCrudTestCase
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

    public function testAutocompleteReturnsJsonWithCorrectStructure(): void
    {
        $autocompleteUrl = $this->generateAutocompleteUrl();

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();

        static::assertTrue($response->isSuccessful(), sprintf(
            'Autocomplete request should be successful. Got status %d. URL: %s',
            $response->getStatusCode(),
            $autocompleteUrl
        ));
        static::assertSame('application/json', $response->headers->get('Content-Type'));

        $data = json_decode($response->getContent(), true);
        static::assertIsArray($data, 'Response should be valid JSON');

        // the response should always have a next_page key (may be null)
        static::assertArrayHasKey('next_page', $data, 'Response should contain next_page key');

        static::assertCount(3, $data['results']);
        $firstResult = $data['results'][0];
        static::assertArrayHasKey('entityId', $firstResult, 'Each result should have an entityId');
        static::assertArrayHasKey('entityAsString', $firstResult, 'Each result should have an entityAsString');
    }

    public function testAutocompleteReturnsAllEntitiesWithoutQuery(): void
    {
        $autocompleteUrl = $this->generateAutocompleteUrl();

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertCount(3, $data['results'], 'Should return all 3 categories');
        static::assertSame($this->getCategoryIds(), array_column($data['results'], 'entityId'));
    }

    public function testAutocompleteFiltersResultsByQuery(): void
    {
        // search for "Category 1"
        $autocompleteUrl = $this->generateAutocompleteUrl('Category 1');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertCount(1, $data['results'], 'Should return only entities matching "Category 1"');

        $result = $data['results'][0];
        static::assertSame((string) $this->categories[0]->getId(), $result['entityId']);
        static::assertSame('Category 1', $result['entityAsString']);
    }

    public function testAutocompleteReturnsEmptyForNonMatchingQuery(): void
    {
        $autocompleteUrl = $this->generateAutocompleteUrl('NonExistentEntity');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertCount(0, $data['results'], 'Should return no results for non-matching query');
    }

    public function testAutocompleteResultStructure(): void
    {
        $autocompleteUrl = $this->generateAutocompleteUrl();

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertCount(3, $data['results']);

        $firstResult = $data['results'][0];
        static::assertSame((string) $this->categories[0]->getId(), $firstResult['entityId']);
        static::assertSame('Category 1', $firstResult['entityAsString']);

        static::assertArrayHasKey('next_page', $data, 'Response should include next_page field');
    }

    public function testAutocompleteRespectsPagination(): void
    {
        // request page 1 (should return results)
        $autocompleteUrl = $this->generateAutocompleteUrl(null, 1);

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertCount(3, $data['results'], 'Page 1 should have results');
        static::assertNull($data['next_page'], 'All results fit in one page');
    }

    public function testAutocompletePartialNameMatch(): void
    {
        // search for "Category" which should match all categories
        $autocompleteUrl = $this->generateAutocompleteUrl('Category');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertCount(3, $data['results'], 'Should return all 3 categories matching "Category"');
    }

    public function testAutocompleteSpecificEntityMatch(): void
    {
        // search for "Category 2"
        $autocompleteUrl = $this->generateAutocompleteUrl('Category 2');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertCount(1, $data['results'], 'Should return exactly 1 entity matching "Category 2"');
        static::assertSame((string) $this->categories[1]->getId(), $data['results'][0]['entityId']);
        static::assertSame('Category 2', $data['results'][0]['entityAsString']);
    }

    public function testAutocompleteFromEditContext(): void
    {
        // generate autocomplete URL with edit context
        $autocompleteUrl = $this->generateAutocompleteUrl(null, 1, 'edit');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();

        static::assertTrue($response->isSuccessful());
        $data = json_decode($response->getContent(), true);
        static::assertArrayHasKey('results', $data);
        static::assertSame($this->getCategoryIds(), array_column($data['results'], 'entityId'));
    }

    public function testAutocompleteFromNewContext(): void
    {
        // generate autocomplete URL with new form context
        $autocompleteUrl = $this->generateAutocompleteUrl(null, 1, 'new');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();

        static::assertTrue($response->isSuccessful());
        $data = json_decode($response->getContent(), true);
        static::assertArrayHasKey('results', $data);
        static::assertSame($this->getCategoryIds(), array_column($data['results'], 'entityId'));
    }

    /**
     * @param string|null $query           Search query
     * @param int         $page            Page number
     * @param string      $originatingPage The page where autocomplete is called from (new, edit, index)
     */
    private function generateAutocompleteUrl(?string $query = null, int $page = 1, string $originatingPage = 'new'): string
    {
        $options = [
            'autocompleteContext[crudControllerFqcn]' => FilterTestEntityCrudController::class,
            'autocompleteContext[propertyName]' => 'relatedEntity',
            'autocompleteContext[originatingPage]' => $originatingPage,
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
     * @return list<string>
     */
    private function getCategoryIds(): array
    {
        return array_map(static fn (Category $category) => (string) $category->getId(), $this->categories);
    }
}
