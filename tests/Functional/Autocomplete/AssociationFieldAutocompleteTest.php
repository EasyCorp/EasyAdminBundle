<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Autocomplete;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FilterRelatedEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FilterTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FilterRelatedEntity;

class AssociationFieldAutocompleteTest extends AbstractCrudTestCase
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

        // if there are results, verify their structure
        if (isset($data['results']) && \count($data['results']) > 0) {
            $firstResult = $data['results'][0];
            static::assertArrayHasKey('entityId', $firstResult, 'Each result should have an entityId');
            static::assertArrayHasKey('entityAsString', $firstResult, 'Each result should have an entityAsString');
        }
    }

    public function testAutocompleteReturnsAllEntitiesWithoutQuery(): void
    {
        $this->ensureFixturesLoaded();

        $autocompleteUrl = $this->generateAutocompleteUrl();

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertCount(3, $data['results'], 'Should return all 3 FilterRelatedEntity items');
    }

    public function testAutocompleteFiltersResultsByQuery(): void
    {
        $this->ensureFixturesLoaded();

        // search for "Entity 1"
        $autocompleteUrl = $this->generateAutocompleteUrl('Entity 1');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertCount(1, $data['results'], 'Should return only entities matching "Entity 1"');

        $result = $data['results'][0];
        static::assertStringContainsString('Entity 1', $result['entityAsString']);
    }

    public function testAutocompleteReturnsEmptyForNonMatchingQuery(): void
    {
        $this->ensureFixturesLoaded();

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
        $this->ensureFixturesLoaded();

        $autocompleteUrl = $this->generateAutocompleteUrl();

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertNotEmpty($data['results']);

        // check structure of first result
        $firstResult = $data['results'][0];
        static::assertArrayHasKey('entityId', $firstResult, 'Each result should have an entityId');
        static::assertArrayHasKey('entityAsString', $firstResult, 'Each result should have an entityAsString');

        static::assertArrayHasKey('next_page', $data, 'Response should include next_page field');
    }

    public function testAutocompleteRespectsPagination(): void
    {
        $this->ensureFixturesLoaded();

        // request page 1 (should return results)
        $autocompleteUrl = $this->generateAutocompleteUrl(null, 1);

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertNotEmpty($data['results'], 'Page 1 should have results');
    }

    public function testAutocompletePartialNameMatch(): void
    {
        $this->ensureFixturesLoaded();

        // search for "Related" which should match all entities
        $autocompleteUrl = $this->generateAutocompleteUrl('Related');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertCount(3, $data['results'], 'Should return all 3 entities matching "Related"');
    }

    public function testAutocompleteSpecificEntityMatch(): void
    {
        $this->ensureFixturesLoaded();

        // search for "Related Entity 2"
        $autocompleteUrl = $this->generateAutocompleteUrl('Related Entity 2');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertCount(1, $data['results'], 'Should return exactly 1 entity matching "Related Entity 2"');
        static::assertStringContainsString('Entity 2', $data['results'][0]['entityAsString']);
    }

    public function testAutocompleteFromEditContext(): void
    {
        $this->ensureFixturesLoaded();

        // generate autocomplete URL with edit context
        $autocompleteUrl = $this->generateAutocompleteUrl(null, 1, 'edit');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();

        static::assertTrue($response->isSuccessful());
        $data = json_decode($response->getContent(), true);
        static::assertArrayHasKey('results', $data);
    }

    public function testAutocompleteFromNewContext(): void
    {
        $this->ensureFixturesLoaded();

        // generate autocomplete URL with new form context
        $autocompleteUrl = $this->generateAutocompleteUrl(null, 1, 'new');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();

        static::assertTrue($response->isSuccessful());
        $data = json_decode($response->getContent(), true);
        static::assertArrayHasKey('results', $data);
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
            FilterRelatedEntityCrudController::class
        );
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
