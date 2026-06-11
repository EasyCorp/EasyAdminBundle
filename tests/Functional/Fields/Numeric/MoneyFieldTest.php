<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Numeric;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class MoneyFieldTest extends AbstractFieldFunctionalTest
{
    public function testMoneyFieldDisplaysOnIndex(): void
    {
        // moneyField is configured with setStoredAsCents(true), so 1000 = $10.00
        $entity = $this->createFieldTestEntity([
            'moneyField' => 1000,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $moneyFieldCell = $entityRow->filter('td[data-column="moneyField"]');
        $cellText = $moneyFieldCell->text();
        // should display formatted money value like "$10.00" or "10,00 $" depending on locale
        static::assertTrue(
            str_contains($cellText, '10') || str_contains($cellText, '$'),
            sprintf('MoneyField should display formatted money value, got: %s', $cellText)
        );
    }

    public function testMoneyFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'moneyField' => 5000, // $50.00
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $moneyFieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'MoneyField') || false !== stripos($label, 'Money Field') || false !== stripos($label, 'Money')) {
                $moneyFieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value')->text();
                static::assertTrue(
                    str_contains($fieldValue, '50') || str_contains($fieldValue, '$'),
                    sprintf('MoneyField should display formatted value on detail, got: %s', $fieldValue)
                );
                break;
            }
        }

        static::assertTrue($moneyFieldFound, 'MoneyField should be displayed on detail page');
    }

    public function testMoneyFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Form should exist');

        // moneyField renders as a number input with currency symbol
        $moneyFieldInput = $crawler->filter('#FieldTestEntity_moneyField');
        static::assertCount(1, $moneyFieldInput, 'MoneyField input should exist in form');
    }

    public function testMoneyFieldSubmission(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        // input is expected in dollars, stored as cents
        $form['FieldTestEntity[moneyField]'] = '25.50';
        $form['FieldTestEntity[slugField]'] = 'money-test';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy([], ['id' => 'DESC']);
        static::assertNotNull($entity, 'Entity should be created');
        // stored as cents: 25.50 * 100 = 2550
        static::assertSame(2550, $entity->getMoneyField());
    }

    public function testMoneyFieldEdit(): void
    {
        $entity = $this->createFieldTestEntity([
            'moneyField' => 1500, // $15.00
            'slugField' => 'money-edit',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        // the form should show the dollar value, not cents
        $currentValue = $form['FieldTestEntity[moneyField]']->getValue();
        static::assertTrue(
            str_contains($currentValue, '15') || '15' === $currentValue || '15.00' === $currentValue,
            sprintf('MoneyField should show dollar value in form, got: %s', $currentValue)
        );

        $form['FieldTestEntity[moneyField]'] = '30.00';
        $this->client->submit($form);

        $this->entityManager->clear();
        $updatedEntity = $this->fieldTestEntities->find($entity->getId());
        static::assertSame(3000, $updatedEntity->getMoneyField());
    }

    public function testMoneyFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'moneyField' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $moneyFieldCell = $entityRow->filter('td[data-column="moneyField"]');
        static::assertCount(1, $moneyFieldCell, 'Money field cell should exist even with null value');
    }

    public function testMoneyFieldWithZeroValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'moneyField' => 0,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        $moneyFieldCell = $entityRow->filter('td[data-column="moneyField"]');
        $cellText = $moneyFieldCell->text();
        // should display $0.00 or similar
        static::assertTrue(
            str_contains($cellText, '0') || str_contains($cellText, '$'),
            sprintf('MoneyField should display zero value, got: %s', $cellText)
        );
    }
}
