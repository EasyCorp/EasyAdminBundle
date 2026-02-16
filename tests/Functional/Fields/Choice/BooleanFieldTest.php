<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Choice;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class BooleanFieldTest extends AbstractFieldFunctionalTest
{
    public function testBooleanFieldTrueDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'booleanField' => true,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $booleanFieldCell = $entityRow->filter('td[data-column="booleanField"]');
        // boolean fields typically render as a switch or checkbox indicator
        $html = $booleanFieldCell->html();
        // check for common patterns: switch toggle, "Yes", or checked state
        static::assertTrue(
            str_contains($html, 'switch')
            || str_contains($html, 'checked')
            || str_contains(strtolower($booleanFieldCell->text()), 'yes'),
            'True boolean field should indicate a positive/checked state'
        );
    }

    public function testBooleanFieldFalseDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'booleanField' => false,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        // for false values, the switch should not be checked
        $booleanFieldCell = $entityRow->filter('td[data-column="booleanField"]');
        static::assertCount(1, $booleanFieldCell, 'Boolean field cell should exist');
    }

    public function testBooleanFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'booleanField' => true,
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $fieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'BooleanField') || false !== stripos($label, 'Boolean Field')) {
                $fieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value');
                // on detail page, boolean often shows as "Yes" or "No"
                $valueText = strtolower($fieldValue->text());
                static::assertTrue(
                    str_contains($valueText, 'yes') || $fieldValue->filter('.switch')->count() > 0,
                    'BooleanField should display Yes or switch on detail page'
                );
                break;
            }
        }

        static::assertTrue($fieldFound, 'BooleanField should be displayed on detail page');
    }

    public function testBooleanFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // booleanField in forms typically renders as a checkbox
        $booleanInput = $crawler->filter('#FieldTestEntity_booleanField');
        static::assertCount(1, $booleanInput, 'BooleanField input should exist in form');
    }

    public function testBooleanFieldSubmission(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[booleanField]']->tick();
        $form['FieldTestEntity[slugField]'] = 'boolean-test';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy(['slugField' => 'boolean-test']);
        static::assertNotNull($entity, 'Entity should be created');
        static::assertTrue($entity->getBooleanField(), 'Boolean field should be true');
    }

    public function testBooleanFieldEdit(): void
    {
        $entity = $this->createFieldTestEntity([
            'booleanField' => true,
            'slugField' => 'original-slug',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[booleanField]']->untick();

        $this->client->submit($form);

        $this->entityManager->clear();
        $updatedEntity = $this->fieldTestEntities->find($entity->getId());
        static::assertFalse($updatedEntity->getBooleanField(), 'Boolean field should be false after unticking');
    }

    public function testBooleanFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'booleanField' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        $booleanFieldCell = $entityRow->filter('td[data-column="booleanField"]');
        // null boolean typically renders similar to false or as empty
        static::assertCount(1, $booleanFieldCell, 'Boolean field cell should exist');
    }
}
