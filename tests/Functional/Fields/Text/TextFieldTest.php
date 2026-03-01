<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Text;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class TextFieldTest extends AbstractFieldFunctionalTest
{
    public function testTextFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'textField' => 'Test Text Value',
        ]);

        // request the index page sorted by ID descending to see the newest entity first
        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $textFieldCell = $entityRow->filter('td[data-column="textField"]');
        static::assertStringContainsString('Test Text Value', $textFieldCell->text());
    }

    public function testTextFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'textField' => 'Detailed Text Value',
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $textFieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'TextField') || false !== stripos($label, 'Text Field')) {
                $textFieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value')->text();
                static::assertStringContainsString('Detailed Text Value', $fieldValue);
                break;
            }
        }

        static::assertTrue($textFieldFound, 'TextField should be displayed on detail page');
    }

    public function testTextFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Form should exist');

        $textFieldInput = $crawler->filter('#FieldTestEntity_textField');
        static::assertCount(1, $textFieldInput, 'TextField input should exist in form');
        static::assertSame('text', $textFieldInput->attr('type'), 'TextField should be a text input');
    }

    public function testTextFieldSubmission(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[textField]'] = 'Submitted Text Value';
        $form['FieldTestEntity[slugField]'] = 'test-slug';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy(['textField' => 'Submitted Text Value']);
        static::assertNotNull($entity, 'Entity should be created with the submitted text value');
        static::assertSame('Submitted Text Value', $entity->getTextField());
    }

    public function testTextFieldEdit(): void
    {
        $entity = $this->createFieldTestEntity([
            'textField' => 'Original Text',
            'slugField' => 'original-slug',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        static::assertSame('Original Text', $form['FieldTestEntity[textField]']->getValue());

        $form['FieldTestEntity[textField]'] = 'Updated Text';
        $this->client->submit($form);

        $this->entityManager->clear();
        $updatedEntity = $this->fieldTestEntities->find($entity->getId());
        static::assertSame('Updated Text', $updatedEntity->getTextField());
    }

    public function testTextFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'textField' => null,
        ]);

        // request the index page sorted by ID descending to see the newest entity first
        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        // null values render in the cell - the test verifies the entity is displayed
        // easyAdmin may render null as empty, dash, or other placeholder depending on configuration
        $textFieldCell = $entityRow->filter('td[data-column="textField"]');
        static::assertCount(1, $textFieldCell, 'Text field cell should exist even with null value');
    }

    public function testTextFieldWithSpecialCharacters(): void
    {
        $entity = $this->createFieldTestEntity([
            'textField' => 'Text with <special> & "characters"',
            'slugField' => 'special-chars',
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        // HTML entities should be properly escaped
        $html = $crawler->html();
        static::assertStringContainsString('Text with', $html);
        // the special characters should be escaped in the HTML
        static::assertStringContainsString('&lt;special&gt;', $html);
    }
}
