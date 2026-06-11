<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Intl;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class TimezoneFieldTest extends AbstractFieldFunctionalTest
{
    public function testTimezoneFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'timezoneField' => 'America/New_York',
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $timezoneFieldCell = $entityRow->filter('td[data-column="timezoneField"]');
        $cellText = $timezoneFieldCell->text();
        // should display timezone name
        static::assertTrue(
            str_contains($cellText, 'New York') || str_contains($cellText, 'America/New_York') || str_contains($cellText, 'Nueva York'),
            sprintf('TimezoneField should display timezone name, got: %s', $cellText)
        );
    }

    public function testTimezoneFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'timezoneField' => 'Europe/Madrid',
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $timezoneFieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'TimezoneField') || false !== stripos($label, 'Timezone Field') || false !== stripos($label, 'Timezone')) {
                $timezoneFieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value')->text();
                static::assertTrue(
                    str_contains($fieldValue, 'Madrid') || str_contains($fieldValue, 'Europe/Madrid'),
                    sprintf('TimezoneField should display timezone name on detail, got: %s', $fieldValue)
                );
                break;
            }
        }

        static::assertTrue($timezoneFieldFound, 'TimezoneField should be displayed on detail page');
    }

    public function testTimezoneFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Form should exist');

        // timezoneField renders as a select dropdown
        $timezoneFieldSelect = $crawler->filter('#FieldTestEntity_timezoneField');
        static::assertCount(1, $timezoneFieldSelect, 'TimezoneField select should exist in form');
        static::assertSame('select', $timezoneFieldSelect->nodeName(), 'TimezoneField should be a select element');
    }

    public function testTimezoneFieldHasOptions(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $timezoneOptions = $crawler->filter('#FieldTestEntity_timezoneField option');
        // should have many timezone options (there are hundreds of timezones)
        static::assertGreaterThan(100, $timezoneOptions->count(), 'TimezoneField should have many timezone options');
    }

    public function testTimezoneFieldSubmission(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[timezoneField]'] = 'Europe/Paris';
        $form['FieldTestEntity[slugField]'] = 'timezone-test';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy(['timezoneField' => 'Europe/Paris']);
        static::assertNotNull($entity, 'Entity should be created with the submitted timezone');
        static::assertSame('Europe/Paris', $entity->getTimezoneField());
    }

    public function testTimezoneFieldEdit(): void
    {
        $originalTimezone = 'Asia/Tokyo';
        $entity = $this->createFieldTestEntity([
            'timezoneField' => $originalTimezone,
            'slugField' => 'timezone-edit',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        static::assertSame($originalTimezone, $form['FieldTestEntity[timezoneField]']->getValue());

        $form['FieldTestEntity[timezoneField]'] = 'Australia/Sydney';
        $this->client->submit($form);

        $this->entityManager->clear();
        $updatedEntity = $this->fieldTestEntities->find($entity->getId());
        static::assertSame('Australia/Sydney', $updatedEntity->getTimezoneField());
    }

    public function testTimezoneFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'timezoneField' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $timezoneFieldCell = $entityRow->filter('td[data-column="timezoneField"]');
        static::assertCount(1, $timezoneFieldCell, 'Timezone field cell should exist even with null value');
    }

    public function testTimezoneFieldWithUTCTimezone(): void
    {
        $entity = $this->createFieldTestEntity([
            'timezoneField' => 'UTC',
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        $timezoneFieldCell = $entityRow->filter('td[data-column="timezoneField"]');
        $cellText = $timezoneFieldCell->text();
        static::assertStringContainsString('UTC', $cellText);
    }
}
