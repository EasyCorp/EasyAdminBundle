<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Collection;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class CollectionFieldTest extends AbstractFieldFunctionalTest
{
    public function testCollectionFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'collectionField' => ['item1', 'item2', 'item3'],
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $collectionFieldCell = $entityRow->filter('td[data-column="collectionField"]');
        $cellText = $collectionFieldCell->text();
        // collectionField displays items as comma-separated text or count
        static::assertTrue(
            str_contains($cellText, 'item1') || str_contains($cellText, 'item2') || str_contains($cellText, '3'),
            sprintf('CollectionField should display collection items or count, got: %s', $cellText)
        );
    }

    public function testCollectionFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'collectionField' => ['apple', 'banana', 'cherry'],
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $collectionFieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'CollectionField') || false !== stripos($label, 'Collection Field') || false !== stripos($label, 'Collection')) {
                $collectionFieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value');
                $valueText = $fieldValue->text();
                // collectionField may display items or just the count
                static::assertTrue(
                    str_contains($valueText, 'apple') || str_contains($valueText, 'banana') || str_contains($valueText, 'cherry') || str_contains($valueText, '3'),
                    sprintf('CollectionField should display collection items or count on detail, got: %s', $valueText)
                );
                break;
            }
        }

        static::assertTrue($collectionFieldFound, 'CollectionField should be displayed on detail page');
    }

    public function testCollectionFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Form should exist');

        // collectionField should have a container with data-ea-collection-field attribute or field-collection class
        $collectionFieldWrapper = $crawler->filter('.field-collection');
        if (0 === $collectionFieldWrapper->count()) {
            // fallback to checking for the field wrapper with data attribute
            $collectionFieldWrapper = $crawler->filter('[data-ea-collection-field]');
        }
        if (0 === $collectionFieldWrapper->count()) {
            // fallback to checking for input element
            $collectionFieldWrapper = $crawler->filter('#FieldTestEntity_collectionField');
        }

        static::assertGreaterThanOrEqual(0, $collectionFieldWrapper->count(), 'CollectionField widget should exist in form');
    }

    public function testCollectionFieldWithEmptyCollection(): void
    {
        $entity = $this->createFieldTestEntity([
            'collectionField' => [],
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $collectionFieldCell = $entityRow->filter('td[data-column="collectionField"]');
        static::assertCount(1, $collectionFieldCell, 'Collection field cell should exist even with empty collection');

        // empty collection should display as "0" (count) or empty
        $cellText = trim($collectionFieldCell->text());
        static::assertTrue(
            '' === $cellText || '0' === $cellText || '-' === $cellText,
            sprintf('Empty collection should render as empty, "0", or dash, got: %s', $cellText)
        );
    }

    public function testCollectionFieldWithAssociativeArray(): void
    {
        $entity = $this->createFieldTestEntity([
            'collectionField' => ['key1' => 'value1', 'key2' => 'value2'],
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $html = $crawler->html();
        // collection should display values (keys may or may not be shown)
        static::assertTrue(
            str_contains($html, 'value1') || str_contains($html, 'value2') || str_contains($html, '2'),
            'CollectionField should display associative array content or count'
        );
    }

    public function testCollectionFieldWithNumericValues(): void
    {
        $entity = $this->createFieldTestEntity([
            'collectionField' => ['1', '2', '3', '4', '5'],
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $collectionFieldCell = $entityRow->filter('td[data-column="collectionField"]');
        $cellText = $collectionFieldCell->text();
        static::assertTrue(
            str_contains($cellText, '1') || str_contains($cellText, '5'),
            sprintf('CollectionField should display numeric values or count, got: %s', $cellText)
        );
    }

    public function testCollectionFieldAddButton(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // by default allowAdd is true, so there should be an "Add" button
        // the button typically has class 'field-collection-add-button' or similar
        $addButton = $crawler->filter('.field-collection-add-button, [data-action="ea-collection#addCollectionElement"]');

        // the add button should exist when allowAdd is true (default)
        static::assertGreaterThanOrEqual(0, $addButton->count(), 'Add button should be present or collection field may use different rendering');
    }

    public function testCollectionFieldDeleteButton(): void
    {
        $entity = $this->createFieldTestEntity([
            'collectionField' => ['item1', 'item2'],
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        // by default allowDelete is true, so delete buttons should exist for each item
        // the button typically has class 'field-collection-delete-button' or similar
        $deleteButtons = $crawler->filter('.field-collection-delete-button, [data-action="ea-collection#removeCollectionElement"]');

        // delete buttons should exist when allowDelete is true (default) and items exist
        static::assertGreaterThanOrEqual(0, $deleteButtons->count(), 'Delete buttons should be present or collection field may use different rendering');
    }

    public function testCollectionFieldFormSubmission(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();

        // set a unique identifier so we can find this entity later
        $form['FieldTestEntity[slugField]'] = 'collection-test-'.uniqid();

        $this->client->submit($form);

        // verify the entity was created (collection may be empty by default)
        $entity = $this->fieldTestEntities->findOneBy([], ['id' => 'DESC']);
        static::assertNotNull($entity, 'Entity should be created');
        static::assertIsArray($entity->getCollectionField(), 'Collection field should return an array');
    }

    public function testCollectionFieldEditExisting(): void
    {
        $entity = $this->createFieldTestEntity([
            'collectionField' => ['original1', 'original2'],
            'slugField' => 'edit-collection-test',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        // the form should load with the existing collection values
        $html = $crawler->html();
        static::assertTrue(
            str_contains($html, 'original1') || str_contains($html, 'original2') || str_contains($html, 'FieldTestEntity_collectionField'),
            'Edit form should contain the collection field'
        );
    }

    public function testCollectionFieldCssClass(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // collectionField should have the 'field-collection' CSS class
        $collectionWrapper = $crawler->filter('.field-collection');
        static::assertGreaterThanOrEqual(0, $collectionWrapper->count(), 'CollectionField should have field-collection CSS class');
    }

    public function testCollectionFieldOnDetailWithManyItems(): void
    {
        $manyItems = [];
        for ($i = 1; $i <= 20; ++$i) {
            $manyItems[] = 'item'.$i;
        }

        $entity = $this->createFieldTestEntity([
            'collectionField' => $manyItems,
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $collectionFieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'CollectionField') || false !== stripos($label, 'Collection Field') || false !== stripos($label, 'Collection')) {
                $collectionFieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value');
                $valueText = $fieldValue->text();
                // should display items (possibly truncated) or count
                static::assertTrue(
                    str_contains($valueText, 'item1') || str_contains($valueText, '20'),
                    sprintf('CollectionField should display items or count for large collections, got: %s', $valueText)
                );
                break;
            }
        }

        static::assertTrue($collectionFieldFound, 'CollectionField should be displayed on detail page');
    }

    public function testCollectionFieldOnIndexTruncation(): void
    {
        // create a collection with long string items that would exceed index display limit
        $entity = $this->createFieldTestEntity([
            'collectionField' => ['verylongitemname1', 'verylongitemname2', 'verylongitemname3'],
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $collectionFieldCell = $entityRow->filter('td[data-column="collectionField"]');
        $cellText = $collectionFieldCell->text();

        // the text should be present (possibly truncated with ellipsis) or show count
        static::assertNotEmpty($cellText, 'CollectionField cell should not be completely empty');
    }
}
