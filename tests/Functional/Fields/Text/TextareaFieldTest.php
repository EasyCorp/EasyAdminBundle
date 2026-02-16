<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Text;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class TextareaFieldTest extends AbstractFieldFunctionalTest
{
    public function testTextareaFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'textareaField' => "This is a multi-line\ntext area content.",
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $textareaFieldCell = $entityRow->filter('td[data-column="textareaField"]');
        // the text might be truncated or have newlines converted
        static::assertStringContainsString('This is a multi-line', $textareaFieldCell->text());
    }

    public function testTextareaFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'textareaField' => "Detailed textarea content\nwith multiple lines.",
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $fieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'TextareaField') || false !== stripos($label, 'Textarea Field')) {
                $fieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value')->text();
                static::assertStringContainsString('Detailed textarea content', $fieldValue);
                break;
            }
        }

        static::assertTrue($fieldFound, 'TextareaField should be displayed on detail page');
    }

    public function testTextareaFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $textareaInput = $crawler->filter('#FieldTestEntity_textareaField');
        static::assertCount(1, $textareaInput, 'TextareaField input should exist in form');
        static::assertSame('textarea', $textareaInput->nodeName(), 'TextareaField should be a textarea element');
    }

    public function testTextareaFieldSubmission(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[textareaField]'] = "Submitted multi-line\ncontent here.";
        $form['FieldTestEntity[slugField]'] = 'textarea-test';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy([], ['id' => 'DESC']);
        static::assertNotNull($entity, 'Entity should be created');
        static::assertStringContainsString('Submitted multi-line', $entity->getTextareaField());
    }

    public function testTextareaFieldEdit(): void
    {
        $originalContent = 'Original textarea content';
        $entity = $this->createFieldTestEntity([
            'textareaField' => $originalContent,
            'slugField' => 'original-slug',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        static::assertSame($originalContent, $form['FieldTestEntity[textareaField]']->getValue());

        $form['FieldTestEntity[textareaField]'] = 'Updated textarea content';
        $this->client->submit($form);

        $this->entityManager->clear();
        $updatedEntity = $this->fieldTestEntities->find($entity->getId());
        static::assertSame('Updated textarea content', $updatedEntity->getTextareaField());
    }

    public function testTextareaFieldWithLongContent(): void
    {
        $longContent = str_repeat('Lorem ipsum dolor sit amet. ', 100);
        $entity = $this->createFieldTestEntity([
            'textareaField' => $longContent,
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $fieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'TextareaField') || false !== stripos($label, 'Textarea Field')) {
                $fieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value')->text();
                static::assertStringContainsString('Lorem ipsum', $fieldValue);
                break;
            }
        }

        static::assertTrue($fieldFound, 'TextareaField should be displayed on detail page');
    }
}
