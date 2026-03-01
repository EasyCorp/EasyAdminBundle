<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Text;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class TextEditorFieldTest extends AbstractFieldFunctionalTest
{
    public function testTextEditorFieldDisplaysOnIndex(): void
    {
        $content = '<p>This is <strong>rich text</strong> content.</p>';
        $entity = $this->createFieldTestEntity([
            'textEditorField' => $content,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $textEditorFieldCell = $entityRow->filter('td[data-column="textEditorField"]');
        // on index, HTML may be stripped or truncated
        $cellText = $textEditorFieldCell->text();
        static::assertTrue(
            str_contains($cellText, 'rich text') || str_contains($cellText, 'This is'),
            sprintf('TextEditorField should display content, got: %s', $cellText)
        );
    }

    public function testTextEditorFieldDisplaysOnDetail(): void
    {
        $content = '<p>Detailed <em>formatted</em> content here.</p>';
        $entity = $this->createFieldTestEntity([
            'textEditorField' => $content,
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $textEditorFieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'TextEditorField') || false !== stripos($label, 'Text Editor') || false !== stripos($label, 'TextEditor')) {
                $textEditorFieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value');
                static::assertStringContainsString('Detailed', $fieldValue->text());
                static::assertStringContainsString('formatted', $fieldValue->text());
                break;
            }
        }

        static::assertTrue($textEditorFieldFound, 'TextEditorField should be displayed on detail page');
    }

    public function testTextEditorFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Form should exist');

        // textEditorField renders as a textarea that will be enhanced by EasyMDE
        $textEditorFieldInput = $crawler->filter('#FieldTestEntity_textEditorField');
        static::assertCount(1, $textEditorFieldInput, 'TextEditorField input should exist in form');
        static::assertSame('textarea', $textEditorFieldInput->nodeName(), 'TextEditorField should be a textarea');
    }

    public function testTextEditorFieldSubmission(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $content = '<p>Submitted content with <strong>bold</strong> text.</p>';
        $form['FieldTestEntity[textEditorField]'] = $content;
        $form['FieldTestEntity[slugField]'] = 'text-editor-test';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy([], ['id' => 'DESC']);
        static::assertNotNull($entity, 'Entity should be created');
        static::assertSame($content, $entity->getTextEditorField());
    }

    public function testTextEditorFieldEdit(): void
    {
        $originalContent = '<p>Original content</p>';
        $entity = $this->createFieldTestEntity([
            'textEditorField' => $originalContent,
            'slugField' => 'text-editor-edit',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        static::assertSame($originalContent, $form['FieldTestEntity[textEditorField]']->getValue());

        $newContent = '<p>Updated content with <em>italic</em>.</p>';
        $form['FieldTestEntity[textEditorField]'] = $newContent;
        $this->client->submit($form);

        $this->entityManager->clear();
        $updatedEntity = $this->fieldTestEntities->find($entity->getId());
        static::assertSame($newContent, $updatedEntity->getTextEditorField());
    }

    public function testTextEditorFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'textEditorField' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $textEditorFieldCell = $entityRow->filter('td[data-column="textEditorField"]');
        static::assertCount(1, $textEditorFieldCell, 'TextEditor field cell should exist even with null value');
    }

    public function testTextEditorFieldWithHtmlContent(): void
    {
        $htmlContent = '<h2>Title</h2><p>Paragraph with <a href="#">link</a>.</p><ul><li>Item 1</li><li>Item 2</li></ul>';
        $entity = $this->createFieldTestEntity([
            'textEditorField' => $htmlContent,
            'slugField' => 'html-content',
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        // Find the TextEditorField value container
        $fieldValue = null;
        $fieldGroups = $crawler->filter('.content-body .field-group');
        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();
            if (false !== stripos($label, 'TextEditorField') || false !== stripos($label, 'Text Editor') || false !== stripos($label, 'TextEditor')) {
                $fieldValue = $groupCrawler->filter('.field-value');
                break;
            }
        }

        static::assertNotNull($fieldValue, 'TextEditorField should be found on detail page');

        // HTML is escaped by Twig for security reasons
        $fieldHtml = trim($fieldValue->html());
        $expectedEscapedHtml = htmlspecialchars($htmlContent, \ENT_NOQUOTES, 'UTF-8');
        static::assertStringContainsString($expectedEscapedHtml, $fieldHtml, 'HTML content should be properly escaped');
    }
}
