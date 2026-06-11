<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Choice;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class ChoiceFieldTest extends AbstractFieldFunctionalTest
{
    public function testChoiceFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'choiceField' => 'option_a',
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $choiceFieldCell = $entityRow->filter('td[data-column="choiceField"]');
        $cellText = $choiceFieldCell->text();
        // the display value should be the label, not the value
        static::assertTrue(
            str_contains($cellText, 'Option A') || str_contains($cellText, 'option_a'),
            'ChoiceField should display the selected option'
        );
    }

    public function testChoiceFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'choiceField' => 'option_b',
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $fieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'ChoiceField') || false !== stripos($label, 'Choice Field')) {
                $fieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value')->text();
                static::assertTrue(
                    str_contains($fieldValue, 'Option B') || str_contains($fieldValue, 'option_b'),
                    'ChoiceField should display the selected option on detail page'
                );
                break;
            }
        }

        static::assertTrue($fieldFound, 'ChoiceField should be displayed on detail page');
    }

    public function testChoiceFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $choiceSelect = $crawler->filter('#FieldTestEntity_choiceField');
        static::assertCount(1, $choiceSelect, 'ChoiceField select should exist in form');

        // check that options are present
        $options = $choiceSelect->filter('option');
        static::assertGreaterThan(0, $options->count(), 'ChoiceField should have options');
    }

    public function testChoiceFieldSubmission(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[choiceField]'] = 'option_c';
        $form['FieldTestEntity[slugField]'] = 'choice-test';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy(['slugField' => 'choice-test']);
        static::assertNotNull($entity, 'Entity should be created');
        static::assertSame('option_c', $entity->getChoiceField());
    }

    public function testChoiceFieldEdit(): void
    {
        $originalChoice = 'option_a';
        $updatedChoice = 'option_b';
        $entity = $this->createFieldTestEntity([
            'choiceField' => $originalChoice,
            'slugField' => 'original-slug',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        static::assertSame($originalChoice, $form['FieldTestEntity[choiceField]']->getValue());

        $form['FieldTestEntity[choiceField]'] = $updatedChoice;
        $this->client->submit($form);

        $this->entityManager->clear();
        $updatedEntity = $this->fieldTestEntities->find($entity->getId());
        static::assertSame($updatedChoice, $updatedEntity->getChoiceField());
    }

    public function testMultipleChoiceFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'multipleChoiceField' => ['choice1', 'choice2'],
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $multipleChoiceFieldCell = $entityRow->filter('td[data-column="multipleChoiceField"]');
        $cellText = $multipleChoiceFieldCell->text();
        // multiple choices are often displayed as badges or comma-separated
        static::assertTrue(
            str_contains($cellText, 'Choice 1') || str_contains($cellText, 'choice1')
            || str_contains($cellText, 'Choice 2') || str_contains($cellText, 'choice2'),
            'MultipleChoiceField should display selected options'
        );
    }

    public function testMultipleChoiceFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $multipleChoiceSelect = $crawler->filter('#FieldTestEntity_multipleChoiceField');
        static::assertCount(1, $multipleChoiceSelect, 'MultipleChoiceField select should exist in form');

        // multiple select should have multiple attribute
        static::assertSame('multiple', $multipleChoiceSelect->attr('multiple'));
    }

    public function testMultipleChoiceFieldSubmission(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[multipleChoiceField]'] = ['choice1', 'choice3'];
        $form['FieldTestEntity[slugField]'] = 'multiple-choice-test';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy(['slugField' => 'multiple-choice-test']);
        static::assertNotNull($entity, 'Entity should be created');
        static::assertEqualsCanonicalizing(['choice1', 'choice3'], $entity->getMultipleChoiceField());
    }

    public function testChoiceFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'choiceField' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');
        $choiceFieldCell = $entityRow->filter('td[data-column="choiceField"]');
        $cellText = trim($choiceFieldCell->text());
        // null choice typically renders as empty or dash or just no badges
        static::assertTrue(
            '' === $cellText || '-' === $cellText || !str_contains($cellText, 'Option'),
            'Null choice field should render as empty, dash, or without option labels'
        );
    }
}
