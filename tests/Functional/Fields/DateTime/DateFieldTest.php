<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\DateTime;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class DateFieldTest extends AbstractFieldFunctionalTest
{
    public function testDateFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'dateField' => new \DateTime('2024-03-15'),
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $dateFieldCell = $entityRow->filter('td[data-column="dateField"]');
        // the date format depends on locale, so check for year and month presence
        $cellText = $dateFieldCell->text();
        static::assertTrue(
            str_contains($cellText, '2024') || str_contains($cellText, 'Mar') || str_contains($cellText, '15'),
            'DateField should display the date components'
        );
    }

    public function testDateFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'dateField' => new \DateTime('2024-12-25'),
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $fieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'DateField') || false !== stripos($label, 'Date Field')) {
                $fieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value')->text();
                static::assertTrue(
                    str_contains($fieldValue, '2024') || str_contains($fieldValue, 'Dec') || str_contains($fieldValue, '25'),
                    'DateField should display the date on detail page'
                );
                break;
            }
        }

        static::assertTrue($fieldFound, 'DateField should be displayed on detail page');
    }

    public function testDateFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // dateField typically uses date type input or a custom widget
        $dateInput = $crawler->filter('#FieldTestEntity_dateField');
        static::assertCount(1, $dateInput, 'DateField input should exist in form');
    }

    public function testDateFieldEdit(): void
    {
        $entity = $this->createFieldTestEntity([
            'dateField' => new \DateTime('2024-01-01'),
            'slugField' => 'original-slug',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $dateInput = $crawler->filter('#FieldTestEntity_dateField');
        static::assertCount(1, $dateInput, 'DateField input should exist in edit form');
    }

    public function testDateFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'dateField' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');
        // null values render in the cell - the test verifies the entity is displayed
        // easyAdmin may render null as empty, dash, or other placeholder depending on configuration
        $dateFieldCell = $entityRow->filter('td[data-column="dateField"]');
        static::assertCount(1, $dateFieldCell, 'Date field cell should exist even with null value');
    }
}
