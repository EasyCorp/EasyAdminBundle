<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Numeric;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class IntegerFieldTest extends AbstractFieldFunctionalTest
{
    public function testIntegerFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'integerField' => 12345,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $integerFieldCell = $entityRow->filter('td[data-column="integerField"]');
        static::assertStringContainsString('12345', $integerFieldCell->text());
    }

    public function testIntegerFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'integerField' => 67890,
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $fieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'IntegerField') || false !== stripos($label, 'Integer Field')) {
                $fieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value')->text();
                static::assertStringContainsString('67890', $fieldValue);
                break;
            }
        }

        static::assertTrue($fieldFound, 'IntegerField should be displayed on detail page');
    }

    public function testIntegerFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $integerInput = $crawler->filter('#FieldTestEntity_integerField');
        static::assertCount(1, $integerInput, 'IntegerField input should exist in form');
        static::assertSame('number', $integerInput->attr('type'), 'IntegerField should be a number input');
    }

    public function testIntegerFieldSubmission(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[integerField]'] = '999';
        $form['FieldTestEntity[slugField]'] = 'integer-test';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy(['integerField' => 999]);
        static::assertNotNull($entity, 'Entity should be created with the submitted integer value');
        static::assertSame(999, $entity->getIntegerField());
    }

    public function testIntegerFieldEdit(): void
    {
        $originalValue = 100;
        $entity = $this->createFieldTestEntity([
            'integerField' => $originalValue,
            'slugField' => 'original-slug',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        static::assertSame((string) $originalValue, $form['FieldTestEntity[integerField]']->getValue());

        $form['FieldTestEntity[integerField]'] = '200';
        $this->client->submit($form);

        $this->entityManager->clear();
        $updatedEntity = $this->fieldTestEntities->find($entity->getId());
        static::assertSame(200, $updatedEntity->getIntegerField());
    }

    public function testIntegerFieldWithZero(): void
    {
        $entity = $this->createFieldTestEntity([
            'integerField' => 0,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');
        $integerFieldCell = $entityRow->filter('td[data-column="integerField"]');
        static::assertStringContainsString('0', $integerFieldCell->text());
    }

    public function testIntegerFieldWithNegativeValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'integerField' => -42,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');
        $integerFieldCell = $entityRow->filter('td[data-column="integerField"]');
        static::assertStringContainsString('-42', $integerFieldCell->text());
    }
}
