<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Autocomplete;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FilterRelatedEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FilterTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FilterRelatedEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\FilterRelatedEntityFactory;

class AssociationFieldAutocompleteTest extends AbstractCrudTestCase
{
    /**
     * @var list<FilterRelatedEntity>
     */
    private array $relatedEntities = [];

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

        $this->relatedEntities = FilterRelatedEntityFactory::createSequence([
            ['name' => 'Related Entity 1'],
            ['name' => 'Related Entity 2'],
            ['name' => 'Related Entity 3'],
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
        static::assertCount(3, $data['results'], 'Should return all 3 FilterRelatedEntity items');
        static::assertSame($this->getRelatedEntityIds(), array_column($data['results'], 'entityId'));
    }

    public function testAutocompleteFiltersResultsByQuery(): void
    {
        // search for "Entity 1"
        $autocompleteUrl = $this->generateAutocompleteUrl('Entity 1');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertCount(1, $data['results'], 'Should return only entities matching "Entity 1"');

        $result = $data['results'][0];
        static::assertSame((string) $this->relatedEntities[0]->getId(), $result['entityId']);
        static::assertSame('Related Entity 1', $result['entityAsString']);
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
        static::assertSame((string) $this->relatedEntities[0]->getId(), $firstResult['entityId']);
        static::assertSame('Related Entity 1', $firstResult['entityAsString']);

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
        // search for "Related Entity 2"
        $autocompleteUrl = $this->generateAutocompleteUrl('Related Entity 2');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);
        static::assertCount(1, $data['results'], 'Should return exactly 1 entity matching "Related Entity 2"');
        static::assertSame((string) $this->relatedEntities[1]->getId(), $data['results'][0]['entityId']);
        static::assertSame('Related Entity 2', $data['results'][0]['entityAsString']);
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
        static::assertSame($this->getRelatedEntityIds(), array_column($data['results'], 'entityId'));
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
        static::assertSame($this->getRelatedEntityIds(), array_column($data['results'], 'entityId'));
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

    /**
     * @return list<string>
     */
    private function getRelatedEntityIds(): array
    {
        return array_map(static fn (FilterRelatedEntity $entity) => (string) $entity->getId(), $this->relatedEntities);
    }
}
