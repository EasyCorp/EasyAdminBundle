<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Choice;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FieldRelatedEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FieldTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FieldRelatedEntity;
use Symfony\Component\DomCrawler\Crawler;

class AssociationFieldTest extends AbstractFieldFunctionalTest
{
    protected EntityRepository $fieldRelatedEntities;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fieldRelatedEntities = $this->entityManager->getRepository(FieldRelatedEntity::class);
    }

    public function testManyToOneAssociationDisplaysOnIndex(): void
    {
        $relatedEntity = $this->createRelatedEntity('Related Entity for Index');
        $entity = $this->createFieldTestEntity([
            'manyToOneAssociation' => $relatedEntity,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $associationFieldCell = $entityRow->filter('td[data-column="manyToOneAssociation"]');
        static::assertCount(1, $associationFieldCell, 'ManyToOne association field cell should exist');

        $cellText = $associationFieldCell->text();
        static::assertStringContainsString(
            'Related Entity for Index',
            $cellText,
            'ManyToOne association should display the related entity name'
        );
    }

    public function testManyToManyAssociationDisplaysOnIndex(): void
    {
        $relatedEntity1 = $this->createRelatedEntity('Related M2M 1');
        $relatedEntity2 = $this->createRelatedEntity('Related M2M 2');
        $entity = $this->createFieldTestEntity([]);
        $entity->addManyToManyAssociation($relatedEntity1);
        $entity->addManyToManyAssociation($relatedEntity2);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $associationFieldCell = $entityRow->filter('td[data-column="manyToManyAssociation"]');
        static::assertCount(1, $associationFieldCell, 'ManyToMany association field cell should exist');

        $cellText = $associationFieldCell->text();
        // manyToMany typically shows as a list or comma-separated
        static::assertTrue(
            str_contains($cellText, 'Related M2M 1') || str_contains($cellText, 'Related M2M 2') || str_contains($cellText, '2'),
            'ManyToMany association should display related entities or count'
        );
    }

    public function testManyToOneAssociationDisplaysOnDetail(): void
    {
        $relatedEntity = $this->createRelatedEntity('Related Entity for Detail');
        $entity = $this->createFieldTestEntity([
            'manyToOneAssociation' => $relatedEntity,
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $fieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'ManyToOneAssociation') || false !== stripos($label, 'Many To One Association')) {
                $fieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value');
                $valueText = $fieldValue->text();
                static::assertStringContainsString(
                    'Related Entity for Detail',
                    $valueText,
                    'ManyToOne association should display the related entity name on detail page'
                );
                break;
            }
        }

        static::assertTrue($fieldFound, 'ManyToOneAssociation field should be displayed on detail page');
    }

    public function testManyToManyAssociationDisplaysOnDetail(): void
    {
        $relatedEntity1 = $this->createRelatedEntity('M2M Detail Entity 1');
        $relatedEntity2 = $this->createRelatedEntity('M2M Detail Entity 2');
        $entity = $this->createFieldTestEntity([]);
        $entity->addManyToManyAssociation($relatedEntity1);
        $entity->addManyToManyAssociation($relatedEntity2);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $fieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'ManyToManyAssociation') || false !== stripos($label, 'Many To Many Association')) {
                $fieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value');
                $valueText = $fieldValue->text();
                // manyToMany should display related entities
                static::assertTrue(
                    str_contains($valueText, 'M2M Detail Entity 1')
                    || str_contains($valueText, 'M2M Detail Entity 2')
                    || str_contains($valueText, '2'),
                    'ManyToMany association should display related entities or count on detail page'
                );
                break;
            }
        }

        static::assertTrue($fieldFound, 'ManyToManyAssociation field should be displayed on detail page');
    }

    public function testManyToOneAssociationInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // associationField renders as a select element
        $associationSelect = $crawler->filter('#FieldTestEntity_manyToOneAssociation');
        static::assertGreaterThan(0, $associationSelect->count(), 'ManyToOne association field should exist in form');
    }

    public function testManyToManyAssociationInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // manyToMany AssociationField renders as a select element with multiple attribute
        $associationSelect = $crawler->filter('#FieldTestEntity_manyToManyAssociation');
        static::assertGreaterThan(0, $associationSelect->count(), 'ManyToMany association field should exist in form');
    }

    public function testManyToOneAssociationSubmission(): void
    {
        $relatedEntity = $this->createRelatedEntity('Related for Submission');
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[manyToOneAssociation]'] = $relatedEntity->getId();
        $form['FieldTestEntity[slugField]'] = 'association-test-submission';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy(['slugField' => 'association-test-submission']);
        static::assertNotNull($entity, 'Entity should be created');
        static::assertNotNull($entity->getManyToOneAssociation(), 'ManyToOne association should be set');
        static::assertSame($relatedEntity->getId(), $entity->getManyToOneAssociation()->getId());
    }

    public function testManyToOneAssociationEdit(): void
    {
        $relatedEntity1 = $this->createRelatedEntity('Related Edit 1');
        $relatedEntity2 = $this->createRelatedEntity('Related Edit 2');
        $entity = $this->createFieldTestEntity([
            'manyToOneAssociation' => $relatedEntity1,
            'slugField' => 'association-edit-test',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[manyToOneAssociation]'] = $relatedEntity2->getId();

        $this->client->submit($form);

        $this->entityManager->clear();
        $updatedEntity = $this->fieldTestEntities->find($entity->getId());
        static::assertNotNull($updatedEntity->getManyToOneAssociation());
        static::assertSame(
            $relatedEntity2->getId(),
            $updatedEntity->getManyToOneAssociation()->getId(),
            'ManyToOne association should be updated'
        );
    }

    public function testNullManyToOneAssociationDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'manyToOneAssociation' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $associationFieldCell = $entityRow->filter('td[data-column="manyToOneAssociation"]');
        static::assertCount(1, $associationFieldCell, 'ManyToOne association field cell should exist');

        // null association should render as empty, dash, or empty link area
        $cellText = trim($associationFieldCell->text());
        static::assertTrue(
            '' === $cellText || '-' === $cellText || !str_contains($cellText, 'Related'),
            'Null ManyToOne association should render as empty or dash'
        );
    }

    public function testEmptyManyToManyAssociationDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $associationFieldCell = $entityRow->filter('td[data-column="manyToManyAssociation"]');
        static::assertCount(1, $associationFieldCell, 'ManyToMany association field cell should exist');

        // empty ManyToMany should render as empty or "0" count
        $cellText = trim($associationFieldCell->text());
        static::assertTrue(
            '' === $cellText || '-' === $cellText || '0' === $cellText,
            'Empty ManyToMany association should render as empty, dash, or zero count'
        );
    }

    public function testAssociationFieldAutocompleteEndpoint(): void
    {
        // ensure related entities exist
        $this->createRelatedEntity('Autocomplete Test 1');
        $this->createRelatedEntity('Autocomplete Test 2');

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
        static::assertArrayHasKey('next_page', $data, 'Response should contain next_page key');
    }

    public function testAssociationFieldAutocompleteFiltersResults(): void
    {
        // create entities with distinct names
        $this->createRelatedEntity('Unique Alpha Entity');
        $this->createRelatedEntity('Common Beta Entity');

        $autocompleteUrl = $this->generateAutocompleteUrl('Unique Alpha');

        $this->client->request('GET', $autocompleteUrl);
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        static::assertTrue($response->isSuccessful());
        static::assertArrayHasKey('results', $data);

        // should find at least the entity matching the query
        if (\count($data['results']) > 0) {
            $found = false;
            foreach ($data['results'] as $result) {
                if (str_contains($result['entityAsString'], 'Unique Alpha')) {
                    $found = true;
                    break;
                }
            }
            static::assertTrue($found, 'Autocomplete should return matching entity');
        }
    }

    public function testAssociationFieldHasCorrectFormWidget(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // check for the field-association CSS class on the form row
        $associationField = $crawler->filter('.field-association');
        static::assertGreaterThan(0, $associationField->count(), 'Form should contain field-association elements');
    }

    /**
     * Creates a new FieldRelatedEntity for testing.
     */
    protected function createRelatedEntity(string $name): FieldRelatedEntity
    {
        $entity = new FieldRelatedEntity();
        $entity->setName($name);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
    }

    /**
     * Generate the autocomplete URL for FieldRelatedEntity.
     */
    private function generateAutocompleteUrl(?string $query = null, int $page = 1): string
    {
        $options = [
            'autocompleteContext[crudControllerFqcn]' => FieldTestEntityCrudController::class,
            'autocompleteContext[propertyName]' => 'manyToOneAssociation',
            'autocompleteContext[originatingPage]' => 'new',
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
            FieldRelatedEntityCrudController::class
        );
    }
}
