<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Intl;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class LanguageFieldTest extends AbstractFieldFunctionalTest
{
    public function testLanguageFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'languageField' => 'en',
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $languageFieldCell = $entityRow->filter('td[data-column="languageField"]');
        $cellText = $languageFieldCell->text();
        // should display language name
        static::assertTrue(
            str_contains($cellText, 'English') || str_contains($cellText, 'en') || str_contains($cellText, 'Inglés'),
            sprintf('LanguageField should display language name, got: %s', $cellText)
        );
    }

    public function testLanguageFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'languageField' => 'es',
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $languageFieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'LanguageField') || false !== stripos($label, 'Language Field') || false !== stripos($label, 'Language')) {
                $languageFieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value')->text();
                static::assertTrue(
                    str_contains($fieldValue, 'Spanish') || str_contains($fieldValue, 'es') || str_contains($fieldValue, 'Español'),
                    sprintf('LanguageField should display language name on detail, got: %s', $fieldValue)
                );
                break;
            }
        }

        static::assertTrue($languageFieldFound, 'LanguageField should be displayed on detail page');
    }

    public function testLanguageFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Form should exist');

        // languageField renders as a select dropdown
        $languageFieldSelect = $crawler->filter('#FieldTestEntity_languageField');
        static::assertCount(1, $languageFieldSelect, 'LanguageField select should exist in form');
        static::assertSame('select', $languageFieldSelect->nodeName(), 'LanguageField should be a select element');
    }

    public function testLanguageFieldHasOptions(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $languageOptions = $crawler->filter('#FieldTestEntity_languageField option');
        // should have many language options
        static::assertGreaterThan(50, $languageOptions->count(), 'LanguageField should have many language options');
    }

    public function testLanguageFieldSubmission(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[languageField]'] = 'fr';
        $form['FieldTestEntity[slugField]'] = 'language-test';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy(['languageField' => 'fr']);
        static::assertNotNull($entity, 'Entity should be created with the submitted language');
        static::assertSame('fr', $entity->getLanguageField());
    }

    public function testLanguageFieldEdit(): void
    {
        $originalLanguage = 'de';
        $entity = $this->createFieldTestEntity([
            'languageField' => $originalLanguage,
            'slugField' => 'language-edit',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        static::assertSame($originalLanguage, $form['FieldTestEntity[languageField]']->getValue());

        $form['FieldTestEntity[languageField]'] = 'it';
        $this->client->submit($form);

        $this->entityManager->clear();
        $updatedEntity = $this->fieldTestEntities->find($entity->getId());
        static::assertSame('it', $updatedEntity->getLanguageField());
    }

    public function testLanguageFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'languageField' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $languageFieldCell = $entityRow->filter('td[data-column="languageField"]');
        static::assertCount(1, $languageFieldCell, 'Language field cell should exist even with null value');
    }
}
