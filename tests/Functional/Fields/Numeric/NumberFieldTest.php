<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Numeric;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class NumberFieldTest extends AbstractFieldFunctionalTest
{
    public function testNumberFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'numberField' => 3.14159,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $numberFieldCell = $entityRow->filter('td[data-column="numberField"]');
        static::assertStringContainsString('3.14', $numberFieldCell->text());
    }

    public function testNumberFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'numberField' => 2.71828,
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $fieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'NumberField') || false !== stripos($label, 'Number Field')) {
                $fieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value')->text();
                static::assertStringContainsString('2.71', $fieldValue);
                break;
            }
        }

        static::assertTrue($fieldFound, 'NumberField should be displayed on detail page');
    }

    public function testNumberFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $numberInput = $crawler->filter('#FieldTestEntity_numberField');
        static::assertCount(1, $numberInput, 'NumberField input should exist in form');
        // numberField may use text type with step attribute for decimal support
        $inputType = $numberInput->attr('type');
        static::assertTrue(
            'number' === $inputType || 'text' === $inputType,
            'NumberField should be a number or text input'
        );
    }

    public function testNumberFieldSubmission(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[numberField]'] = '9.876';
        $form['FieldTestEntity[slugField]'] = 'number-test';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy([], ['id' => 'DESC']);
        static::assertNotNull($entity, 'Entity should be created');
        static::assertEqualsWithDelta(9.876, $entity->getNumberField(), 0.001);
    }

    public function testNumberFieldEdit(): void
    {
        $originalValue = 1.5;
        $entity = $this->createFieldTestEntity([
            'numberField' => $originalValue,
            'slugField' => 'original-slug',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        static::assertSame((string) $originalValue, $form['FieldTestEntity[numberField]']->getValue());

        $form['FieldTestEntity[numberField]'] = '2.5';
        $this->client->submit($form);

        $this->entityManager->clear();
        $updatedEntity = $this->fieldTestEntities->find($entity->getId());
        static::assertEqualsWithDelta(2.5, $updatedEntity->getNumberField(), 0.001);
    }

    public function testNumberFieldWithZero(): void
    {
        $entity = $this->createFieldTestEntity([
            'numberField' => 0.0,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');
        $numberFieldCell = $entityRow->filter('td[data-column="numberField"]');
        static::assertStringContainsString('0', $numberFieldCell->text());
    }
}
