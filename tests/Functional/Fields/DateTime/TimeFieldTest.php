<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\DateTime;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class TimeFieldTest extends AbstractFieldFunctionalTest
{
    public function testTimeFieldDisplaysOnIndex(): void
    {
        $time = new \DateTime('14:30:00');
        $entity = $this->createFieldTestEntity([
            'timeField' => $time,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $timeFieldCell = $entityRow->filter('td[data-column="timeField"]');
        // time should display in some readable format (e.g., "2:30 PM" or "14:30")
        $cellText = $timeFieldCell->text();
        static::assertTrue(
            str_contains($cellText, '14:30') || str_contains($cellText, '2:30'),
            sprintf('Time field should display time value, got: %s', $cellText)
        );
    }

    public function testTimeFieldDisplaysOnDetail(): void
    {
        $time = new \DateTime('09:15:00');
        $entity = $this->createFieldTestEntity([
            'timeField' => $time,
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $timeFieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'TimeField') || false !== stripos($label, 'Time Field')) {
                $timeFieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value')->text();
                static::assertTrue(
                    str_contains($fieldValue, '09:15') || str_contains($fieldValue, '9:15'),
                    sprintf('Time field should display time value on detail, got: %s', $fieldValue)
                );
                break;
            }
        }

        static::assertTrue($timeFieldFound, 'TimeField should be displayed on detail page');
    }

    public function testTimeFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Form should exist');

        // timeField renders as time input or multiple select fields (hour/minute)
        $timeFieldInput = $crawler->filter('[name*="timeField"]');
        static::assertGreaterThan(0, $timeFieldInput->count(), 'TimeField input should exist in form');
    }

    public function testTimeFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'timeField' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $timeFieldCell = $entityRow->filter('td[data-column="timeField"]');
        static::assertCount(1, $timeFieldCell, 'Time field cell should exist even with null value');
    }
}
