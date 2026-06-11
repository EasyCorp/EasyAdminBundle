<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Intl;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class LocaleFieldTest extends AbstractFieldFunctionalTest
{
    public function testLocaleFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'localeField' => 'en_US',
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $localeFieldCell = $entityRow->filter('td[data-column="localeField"]');
        $cellText = $localeFieldCell->text();
        // should display locale name
        static::assertTrue(
            str_contains($cellText, 'English') || str_contains($cellText, 'United States') || str_contains($cellText, 'en_US'),
            sprintf('LocaleField should display locale name, got: %s', $cellText)
        );
    }

    public function testLocaleFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'localeField' => 'es_ES',
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $localeFieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'LocaleField') || false !== stripos($label, 'Locale Field') || false !== stripos($label, 'Locale')) {
                $localeFieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value')->text();
                static::assertTrue(
                    str_contains($fieldValue, 'Spanish') || str_contains($fieldValue, 'Spain') || str_contains($fieldValue, 'es_ES') || str_contains($fieldValue, 'España'),
                    sprintf('LocaleField should display locale name on detail, got: %s', $fieldValue)
                );
                break;
            }
        }

        static::assertTrue($localeFieldFound, 'LocaleField should be displayed on detail page');
    }

    public function testLocaleFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Form should exist');

        // localeField renders as a select dropdown
        $localeFieldSelect = $crawler->filter('#FieldTestEntity_localeField');
        static::assertCount(1, $localeFieldSelect, 'LocaleField select should exist in form');
        static::assertSame('select', $localeFieldSelect->nodeName(), 'LocaleField should be a select element');
    }

    public function testLocaleFieldHasOptions(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $localeOptions = $crawler->filter('#FieldTestEntity_localeField option');
        // should have many locale options
        static::assertGreaterThan(50, $localeOptions->count(), 'LocaleField should have many locale options');
    }

    public function testLocaleFieldSubmission(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[localeField]'] = 'fr_FR';
        $form['FieldTestEntity[slugField]'] = 'locale-test';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy(['localeField' => 'fr_FR']);
        static::assertNotNull($entity, 'Entity should be created with the submitted locale');
        static::assertSame('fr_FR', $entity->getLocaleField());
    }

    public function testLocaleFieldEdit(): void
    {
        $originalLocale = 'de_DE';
        $entity = $this->createFieldTestEntity([
            'localeField' => $originalLocale,
            'slugField' => 'locale-edit',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        static::assertSame($originalLocale, $form['FieldTestEntity[localeField]']->getValue());

        $form['FieldTestEntity[localeField]'] = 'it_IT';
        $this->client->submit($form);

        $this->entityManager->clear();
        $updatedEntity = $this->fieldTestEntities->find($entity->getId());
        static::assertSame('it_IT', $updatedEntity->getLocaleField());
    }

    public function testLocaleFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'localeField' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $localeFieldCell = $entityRow->filter('td[data-column="localeField"]');
        static::assertCount(1, $localeFieldCell, 'Locale field cell should exist even with null value');
    }
}
