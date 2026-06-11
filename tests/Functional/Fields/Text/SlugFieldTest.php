<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Text;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class SlugFieldTest extends AbstractFieldFunctionalTest
{
    public function testSlugFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'slugField' => 'my-test-slug',
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $slugFieldCell = $entityRow->filter('td[data-column="slugField"]');
        static::assertStringContainsString('my-test-slug', $slugFieldCell->text());
    }

    public function testSlugFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'slugField' => 'detailed-slug-value',
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $slugFieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'SlugField') || false !== stripos($label, 'Slug Field') || false !== stripos($label, 'Slug')) {
                $slugFieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value')->text();
                static::assertStringContainsString('detailed-slug-value', $fieldValue);
                break;
            }
        }

        static::assertTrue($slugFieldFound, 'SlugField should be displayed on detail page');
    }

    public function testSlugFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Form should exist');

        $slugFieldInput = $crawler->filter('#FieldTestEntity_slugField');
        static::assertCount(1, $slugFieldInput, 'SlugField input should exist in form');
        static::assertSame('text', $slugFieldInput->attr('type'), 'SlugField should be a text input');
    }

    public function testSlugFieldHasFormInput(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // slugField should have an input in the form
        $slugFieldInput = $crawler->filter('#FieldTestEntity_slugField');
        static::assertCount(1, $slugFieldInput, 'SlugField input should exist in form');
    }

    public function testSlugFieldSubmission(): void
    {
        $slug = 'manually-entered-slug';
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[textField]'] = 'Some Title For Slug';
        $form['FieldTestEntity[slugField]'] = $slug;

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy(['slugField' => $slug]);
        static::assertNotNull($entity, 'Entity should be created with the submitted slug value');
        static::assertSame($slug, $entity->getSlugField());
    }

    public function testSlugFieldEdit(): void
    {
        $originalSlug = 'original-slug';
        $entity = $this->createFieldTestEntity([
            'textField' => 'Original Title',
            'slugField' => $originalSlug,
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        static::assertSame($originalSlug, $form['FieldTestEntity[slugField]']->getValue());

        $form['FieldTestEntity[slugField]'] = 'updated-slug';
        $this->client->submit($form);

        $this->entityManager->clear();
        $updatedEntity = $this->fieldTestEntities->find($entity->getId());
        static::assertSame('updated-slug', $updatedEntity->getSlugField());
    }

    public function testSlugFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'slugField' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $slugFieldCell = $entityRow->filter('td[data-column="slugField"]');
        static::assertCount(1, $slugFieldCell, 'Slug field cell should exist even with null value');
    }
}
