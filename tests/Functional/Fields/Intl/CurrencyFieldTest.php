<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Intl;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class CurrencyFieldTest extends AbstractFieldFunctionalTest
{
    public function testCurrencyFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'currencyField' => 'USD',
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $currencyFieldCell = $entityRow->filter('td[data-column="currencyField"]');
        $cellText = $currencyFieldCell->text();
        // should display currency name or code
        static::assertTrue(
            str_contains($cellText, 'USD') || str_contains($cellText, 'Dollar') || str_contains($cellText, '$'),
            sprintf('CurrencyField should display currency info, got: %s', $cellText)
        );
    }

    public function testCurrencyFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'currencyField' => 'EUR',
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $currencyFieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'CurrencyField') || false !== stripos($label, 'Currency Field') || false !== stripos($label, 'Currency')) {
                $currencyFieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value')->text();
                static::assertTrue(
                    str_contains($fieldValue, 'EUR') || str_contains($fieldValue, 'Euro') || str_contains($fieldValue, '€'),
                    sprintf('CurrencyField should display currency info on detail, got: %s', $fieldValue)
                );
                break;
            }
        }

        static::assertTrue($currencyFieldFound, 'CurrencyField should be displayed on detail page');
    }

    public function testCurrencyFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Form should exist');

        // currencyField renders as a select dropdown
        $currencyFieldSelect = $crawler->filter('#FieldTestEntity_currencyField');
        static::assertCount(1, $currencyFieldSelect, 'CurrencyField select should exist in form');
        static::assertSame('select', $currencyFieldSelect->nodeName(), 'CurrencyField should be a select element');
    }

    public function testCurrencyFieldHasOptions(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $currencyOptions = $crawler->filter('#FieldTestEntity_currencyField option');
        // should have many currency options (there are 150+ currencies)
        static::assertGreaterThan(100, $currencyOptions->count(), 'CurrencyField should have many currency options');
    }

    public function testCurrencyFieldSubmission(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[currencyField]'] = 'GBP';
        $form['FieldTestEntity[slugField]'] = 'currency-test';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy(['currencyField' => 'GBP']);
        static::assertNotNull($entity, 'Entity should be created with the submitted currency');
        static::assertSame('GBP', $entity->getCurrencyField());
    }

    public function testCurrencyFieldEdit(): void
    {
        $originalCurrency = 'JPY';
        $entity = $this->createFieldTestEntity([
            'currencyField' => $originalCurrency,
            'slugField' => 'currency-edit',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        static::assertSame($originalCurrency, $form['FieldTestEntity[currencyField]']->getValue());

        $form['FieldTestEntity[currencyField]'] = 'CHF';
        $this->client->submit($form);

        $this->entityManager->clear();
        $updatedEntity = $this->fieldTestEntities->find($entity->getId());
        static::assertSame('CHF', $updatedEntity->getCurrencyField());
    }

    public function testCurrencyFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'currencyField' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $currencyFieldCell = $entityRow->filter('td[data-column="currencyField"]');
        static::assertCount(1, $currencyFieldCell, 'Currency field cell should exist even with null value');
    }
}
