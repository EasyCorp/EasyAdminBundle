<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Intl;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class CountryFieldTest extends AbstractFieldFunctionalTest
{
    public function testCountryFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'countryField' => 'US',
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $countryFieldCell = $entityRow->filter('td[data-column="countryField"]');
        $cellText = $countryFieldCell->text();
        // should display country name or flag
        static::assertTrue(
            str_contains($cellText, 'United States') || str_contains($cellText, 'US') || str_contains($cellText, 'Estados Unidos'),
            sprintf('CountryField should display country name, got: %s', $cellText)
        );
    }

    public function testCountryFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'countryField' => 'ES',
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $countryFieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'CountryField') || false !== stripos($label, 'Country Field') || false !== stripos($label, 'Country')) {
                $countryFieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value')->text();
                static::assertTrue(
                    str_contains($fieldValue, 'Spain') || str_contains($fieldValue, 'ES') || str_contains($fieldValue, 'España'),
                    sprintf('CountryField should display country name on detail, got: %s', $fieldValue)
                );
                break;
            }
        }

        static::assertTrue($countryFieldFound, 'CountryField should be displayed on detail page');
    }

    public function testCountryFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Form should exist');

        // countryField renders as a select dropdown
        $countryFieldSelect = $crawler->filter('#FieldTestEntity_countryField');
        static::assertCount(1, $countryFieldSelect, 'CountryField select should exist in form');
        static::assertSame('select', $countryFieldSelect->nodeName(), 'CountryField should be a select element');
    }

    public function testCountryFieldHasOptions(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $countryOptions = $crawler->filter('#FieldTestEntity_countryField option');
        // should have many country options
        static::assertGreaterThan(100, $countryOptions->count(), 'CountryField should have many country options');
    }

    public function testCountryFieldSubmission(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[countryField]'] = 'FR';
        $form['FieldTestEntity[slugField]'] = 'country-test';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy(['countryField' => 'FR']);
        static::assertNotNull($entity, 'Entity should be created with the submitted country');
        static::assertSame('FR', $entity->getCountryField());
    }

    public function testCountryFieldEdit(): void
    {
        $originalCountry = 'DE';
        $entity = $this->createFieldTestEntity([
            'countryField' => $originalCountry,
            'slugField' => 'country-edit',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        static::assertSame($originalCountry, $form['FieldTestEntity[countryField]']->getValue());

        $form['FieldTestEntity[countryField]'] = 'IT';
        $this->client->submit($form);

        $this->entityManager->clear();
        $updatedEntity = $this->fieldTestEntities->find($entity->getId());
        static::assertSame('IT', $updatedEntity->getCountryField());
    }

    public function testCountryFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'countryField' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $countryFieldCell = $entityRow->filter('td[data-column="countryField"]');
        static::assertCount(1, $countryFieldCell, 'Country field cell should exist even with null value');
    }
}
