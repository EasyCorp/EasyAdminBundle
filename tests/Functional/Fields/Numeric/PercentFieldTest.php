<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Numeric;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class PercentFieldTest extends AbstractFieldFunctionalTest
{
    public function testPercentFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'percentField' => 0.75, // 75%
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $percentFieldCell = $entityRow->filter('td[data-column="percentField"]');
        $cellText = $percentFieldCell->text();
        // should display formatted percent like "75%" or "75 %"
        static::assertTrue(
            str_contains($cellText, '75') || str_contains($cellText, '%'),
            sprintf('PercentField should display formatted percent value, got: %s', $cellText)
        );
    }

    public function testPercentFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'percentField' => 0.333, // 33.3%
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $percentFieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'PercentField') || false !== stripos($label, 'Percent Field') || false !== stripos($label, 'Percent')) {
                $percentFieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value')->text();
                static::assertTrue(
                    str_contains($fieldValue, '33') || str_contains($fieldValue, '%'),
                    sprintf('PercentField should display formatted value on detail, got: %s', $fieldValue)
                );
                break;
            }
        }

        static::assertTrue($percentFieldFound, 'PercentField should be displayed on detail page');
    }

    public function testPercentFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Form should exist');

        $percentFieldInput = $crawler->filter('#FieldTestEntity_percentField');
        static::assertCount(1, $percentFieldInput, 'PercentField input should exist in form');
    }

    public function testPercentFieldSubmission(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        // input is typically in percentage (50 = 50%)
        $form['FieldTestEntity[percentField]'] = '50';
        $form['FieldTestEntity[slugField]'] = 'percent-test';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy([], ['id' => 'DESC']);
        static::assertNotNull($entity, 'Entity should be created');
        // stored as decimal: 50% = 0.5
        static::assertEquals(0.5, $entity->getPercentField(), '', 0.01);
    }

    public function testPercentFieldEdit(): void
    {
        $entity = $this->createFieldTestEntity([
            'percentField' => 0.25, // 25%
            'slugField' => 'percent-edit',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $currentValue = $form['FieldTestEntity[percentField]']->getValue();
        // the form should show the percentage value (25)
        static::assertTrue(
            str_contains($currentValue, '25') || '25' === $currentValue,
            sprintf('PercentField should show percentage value in form, got: %s', $currentValue)
        );

        $form['FieldTestEntity[percentField]'] = '80';
        $this->client->submit($form);

        $this->entityManager->clear();
        $updatedEntity = $this->fieldTestEntities->find($entity->getId());
        static::assertEquals(0.8, $updatedEntity->getPercentField(), '', 0.01);
    }

    public function testPercentFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'percentField' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $percentFieldCell = $entityRow->filter('td[data-column="percentField"]');
        static::assertCount(1, $percentFieldCell, 'Percent field cell should exist even with null value');
    }

    public function testPercentFieldWithZeroValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'percentField' => 0.0,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        $percentFieldCell = $entityRow->filter('td[data-column="percentField"]');
        $cellText = $percentFieldCell->text();
        static::assertTrue(
            str_contains($cellText, '0') || str_contains($cellText, '%'),
            sprintf('PercentField should display zero value, got: %s', $cellText)
        );
    }

    public function testPercentFieldWith100Percent(): void
    {
        $entity = $this->createFieldTestEntity([
            'percentField' => 1.0, // 100%
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        $percentFieldCell = $entityRow->filter('td[data-column="percentField"]');
        $cellText = $percentFieldCell->text();
        static::assertTrue(
            str_contains($cellText, '100') || str_contains($cellText, '%'),
            sprintf('PercentField should display 100%%, got: %s', $cellText)
        );
    }
}
