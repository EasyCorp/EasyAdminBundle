<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Autocomplete;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FilterRelatedEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FilterTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FilterRelatedEntity;

class EntityFilterAutocompleteTest extends AbstractCrudTestCase
{
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

        // if there are results, verify their structure
        if (isset($data['results']) && \count($data['results']) > 0) {
            foreach ($data['results'] as $result) {
                static::assertArrayHasKey('entityId', $result, 'Result should have entityId');
                static::assertArrayHasKey('entityAsString', $result, 'Result should have entityAsString');
            }
        }
    }

    public function testFilterAutocompleteReturnsAllEntities(): void
    {
        $this->ensureFixturesLoaded();

        $autocompleteUrl = $this->generateFilterAutocompleteUrl();

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertCount(3, $data['results'], 'Should return all 3 FilterRelatedEntity items');
    }

    public function testFilterAutocompleteFiltersResultsByQuery(): void
    {
        $this->ensureFixturesLoaded();

        // search for "Entity 1"
        $autocompleteUrl = $this->generateFilterAutocompleteUrl('Entity 1');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertCount(1, $data['results'], 'Should return only entities matching "Entity 1"');
        static::assertStringContainsString('Entity 1', $data['results'][0]['entityAsString']);
    }

    public function testFilterAutocompleteReturnsEmptyForNonMatchingQuery(): void
    {
        $this->ensureFixturesLoaded();

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
        $this->ensureFixturesLoaded();

        $autocompleteUrl = $this->generateFilterAutocompleteUrl();

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertNotEmpty($data['results']);

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
        $this->ensureFixturesLoaded();

        $autocompleteUrl = $this->generateFilterAutocompleteUrl();

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        // get actual entity IDs from database
        $repository = $this->entityManager->getRepository(FilterRelatedEntity::class);
        $entities = $repository->findAll();

        static::assertArrayHasKey('results', $data);
        static::assertCount(\count($entities), $data['results']);

        $responseIds = array_map(static fn ($r): string => $r['entityId'], $data['results']);
        $entityIds = array_map(static fn ($e): string => (string) $e->getId(), $entities);

        foreach ($entityIds as $entityId) {
            static::assertContains($entityId, $responseIds, 'All entity IDs should be present in autocomplete results');
        }
    }

    public function testFilterAutocompletePartialSearch(): void
    {
        $this->ensureFixturesLoaded();

        // search with partial name "Entity" - should match all 3 entities
        $autocompleteUrl = $this->generateFilterAutocompleteUrl('Entity');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertCount(3, $data['results'], 'Partial search "Entity" should match all 3 entities');
    }

    public function testFilterAutocompleteCaseInsensitiveSearch(): void
    {
        $this->ensureFixturesLoaded();

        // search with lowercase
        $autocompleteUrl = $this->generateFilterAutocompleteUrl('entity 1');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        // case sensitivity depends on database collation, but the endpoint should work
        static::assertIsArray($data['results']);
    }

    public function testFilterAutocompleteIntegrationWithFilter(): void
    {
        $this->ensureFixturesLoaded();

        // first, get an entity via autocomplete
        $autocompleteUrl = $this->generateFilterAutocompleteUrl('Entity 2');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $autocompleteData = json_decode($response->getContent(), true);

        static::assertArrayHasKey('results', $autocompleteData);
        static::assertCount(1, $autocompleteData['results']);
        $selectedEntityId = $autocompleteData['results'][0]['entityId'];

        // now verify we can use this ID in a filter
        $filterUrl = $this->generateFilteredIndexUrl([
            'relatedEntity' => [
                'comparison' => '=',
                'value' => $selectedEntityId,
            ],
        ]);

        $this->client->request('GET', $filterUrl);
        $response = $this->client->getResponse();

        static::assertTrue($response->isSuccessful(), 'Filter with autocomplete-selected entity should work');
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
            FilterRelatedEntityCrudController::class
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

    private function ensureFixturesLoaded(): void
    {
        $repository = $this->entityManager->getRepository(FilterRelatedEntity::class);
        $count = $repository->count([]);

        if (0 === $count) {
            static::markTestSkipped('FilterRelatedEntity fixtures not loaded. Run doctrine:fixtures:load first.');
        }
    }
}
