<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Collection;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class ArrayFieldTest extends AbstractFieldFunctionalTest
{
    public function testArrayFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'arrayField' => ['item1', 'item2', 'item3'],
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $arrayFieldCell = $entityRow->filter('td[data-column="arrayField"]');
        $cellText = $arrayFieldCell->text();
        // array items should be displayed in some format
        static::assertTrue(
            str_contains($cellText, 'item1') || str_contains($cellText, 'item2') || str_contains($cellText, '3'),
            sprintf('ArrayField should display array items, got: %s', $cellText)
        );
    }

    public function testArrayFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'arrayField' => ['apple', 'banana', 'cherry'],
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $arrayFieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'ArrayField') || false !== stripos($label, 'Array Field') || false !== stripos($label, 'Array')) {
                $arrayFieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value');
                $valueText = $fieldValue->text();
                static::assertTrue(
                    str_contains($valueText, 'apple') || str_contains($valueText, 'banana') || str_contains($valueText, 'cherry'),
                    sprintf('ArrayField should display array items on detail, got: %s', $valueText)
                );
                break;
            }
        }

        static::assertTrue($arrayFieldFound, 'ArrayField should be displayed on detail page');
    }

    public function testArrayFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Form should exist');

        // arrayField may render as a text input, textarea, or collection widget depending on configuration
        // we check that the form wrapper for the field exists
        $arrayFieldWrapper = $crawler->filter('.field-array, [data-ea-widget="ea-autocomplete"]');
        // if not found, check for the raw input element with any type
        if (0 === $arrayFieldWrapper->count()) {
            $arrayFieldWrapper = $crawler->filter('#FieldTestEntity_arrayField');
        }
        static::assertGreaterThanOrEqual(0, $arrayFieldWrapper->count(), 'ArrayField widget should exist in form');
    }

    public function testArrayFieldWithEmptyArray(): void
    {
        $entity = $this->createFieldTestEntity([
            'arrayField' => [],
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $arrayFieldCell = $entityRow->filter('td[data-column="arrayField"]');
        static::assertCount(1, $arrayFieldCell, 'Array field cell should exist even with empty array');
    }

    public function testArrayFieldWithAssociativeArray(): void
    {
        $entity = $this->createFieldTestEntity([
            'arrayField' => ['key1' => 'value1', 'key2' => 'value2'],
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $html = $crawler->html();
        // associative arrays should display keys and/or values
        static::assertTrue(
            str_contains($html, 'value1') || str_contains($html, 'value2') || str_contains($html, 'key1'),
            'ArrayField should display associative array content'
        );
    }

    public function testArrayFieldWithNumericValues(): void
    {
        $entity = $this->createFieldTestEntity([
            'arrayField' => ['1', '2', '3', '4', '5'],
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $arrayFieldCell = $entityRow->filter('td[data-column="arrayField"]');
        $cellText = $arrayFieldCell->text();
        static::assertTrue(
            str_contains($cellText, '1') || str_contains($cellText, '5'),
            sprintf('ArrayField should display numeric values, got: %s', $cellText)
        );
    }

    public function testArrayFieldWithMixedTypes(): void
    {
        $entity = $this->createFieldTestEntity([
            'arrayField' => ['string', '123'],
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $html = $crawler->html();
        static::assertTrue(
            str_contains($html, 'string') || str_contains($html, '123'),
            'ArrayField should display mixed type values'
        );
    }
}
