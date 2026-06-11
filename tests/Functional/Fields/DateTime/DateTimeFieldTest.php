<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\DateTime;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class DateTimeFieldTest extends AbstractFieldFunctionalTest
{
    public function testDateTimeFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'dateTimeField' => new \DateTime('2024-03-15 14:30:00'),
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $dateTimeFieldCell = $entityRow->filter('td[data-column="dateTimeField"]');
        $cellText = $dateTimeFieldCell->text();
        // check for date components
        static::assertTrue(
            str_contains($cellText, '2024') || str_contains($cellText, 'Mar') || str_contains($cellText, '15'),
            'DateTimeField should display the date components'
        );
    }

    public function testDateTimeFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'dateTimeField' => new \DateTime('2024-12-25 09:00:00'),
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $fieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'DateTimeField') || false !== stripos($label, 'Date Time Field') || false !== stripos($label, 'Datetime Field')) {
                $fieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value')->text();
                static::assertTrue(
                    str_contains($fieldValue, '2024') || str_contains($fieldValue, 'Dec'),
                    'DateTimeField should display the datetime on detail page'
                );
                break;
            }
        }

        static::assertTrue($fieldFound, 'DateTimeField should be displayed on detail page');
    }

    public function testDateTimeFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $dateTimeInput = $crawler->filter('#FieldTestEntity_dateTimeField');
        static::assertCount(1, $dateTimeInput, 'DateTimeField input should exist in form');
    }

    public function testDateTimeFieldEdit(): void
    {
        $entity = $this->createFieldTestEntity([
            'dateTimeField' => new \DateTime('2024-01-01 12:00:00'),
            'slugField' => 'original-slug',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $dateTimeInput = $crawler->filter('#FieldTestEntity_dateTimeField');
        static::assertCount(1, $dateTimeInput, 'DateTimeField input should exist in edit form');
    }

    public function testDateTimeFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'dateTimeField' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');
        // null values render in the cell - the test verifies the entity is displayed
        // easyAdmin may render null as empty, dash, or other placeholder depending on configuration
        $dateTimeFieldCell = $entityRow->filter('td[data-column="dateTimeField"]');
        static::assertCount(1, $dateTimeFieldCell, 'DateTime field cell should exist even with null value');
    }
}
