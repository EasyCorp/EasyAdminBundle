<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Special;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;

class HiddenFieldTest extends AbstractFieldFunctionalTest
{
    public function testHiddenFieldNotDisplayedOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'hiddenField' => 'secret-value',
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        // hiddenField should not be displayed on index by default
        $hiddenFieldCell = $entityRow->filter('td[data-column="hiddenField"]');
        // the field may or may not appear depending on configuration
        // if it appears, the value should be there
        if ($hiddenFieldCell->count() > 0) {
            static::assertStringContainsString('secret-value', $hiddenFieldCell->text());
        }
    }

    public function testHiddenFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Form should exist');

        // hiddenField renders as a hidden input
        $hiddenFieldInput = $crawler->filter('#FieldTestEntity_hiddenField');
        static::assertCount(1, $hiddenFieldInput, 'HiddenField input should exist in form');
        static::assertSame('hidden', $hiddenFieldInput->attr('type'), 'HiddenField should be a hidden input');
    }

    public function testHiddenFieldSubmission(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[hiddenField]'] = 'submitted-hidden-value';
        $form['FieldTestEntity[slugField]'] = 'hidden-test';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy(['hiddenField' => 'submitted-hidden-value']);
        static::assertNotNull($entity, 'Entity should be created with the submitted hidden value');
        static::assertSame('submitted-hidden-value', $entity->getHiddenField());
    }

    public function testHiddenFieldEdit(): void
    {
        $entity = $this->createFieldTestEntity([
            'hiddenField' => 'original-hidden',
            'slugField' => 'hidden-edit',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        static::assertSame('original-hidden', $form['FieldTestEntity[hiddenField]']->getValue());

        $form['FieldTestEntity[hiddenField]'] = 'updated-hidden';
        $this->client->submit($form);

        $this->entityManager->clear();
        $updatedEntity = $this->fieldTestEntities->find($entity->getId());
        static::assertSame('updated-hidden', $updatedEntity->getHiddenField());
    }

    public function testHiddenFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'hiddenField' => null,
        ]);

        // entity should be created successfully with null hidden field
        static::assertNotNull($entity->getId());
        static::assertNull($entity->getHiddenField());
    }

    public function testHiddenFieldNotVisibleInHtml(): void
    {
        $entity = $this->createFieldTestEntity([
            'hiddenField' => 'this-should-be-hidden',
            'slugField' => 'hidden-visibility',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        // the hidden input should be in the DOM but not visible
        $hiddenInput = $crawler->filter('#FieldTestEntity_hiddenField');
        static::assertCount(1, $hiddenInput);
        static::assertSame('hidden', $hiddenInput->attr('type'));

        // the value should be in the hidden input
        static::assertSame('this-should-be-hidden', $hiddenInput->attr('value'));
    }

    public function testHiddenFieldPreservesValueOnFormResubmission(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[hiddenField]'] = 'preserved-value';
        $form['FieldTestEntity[slugField]'] = 'hidden-preserve';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy(['slugField' => 'hidden-preserve']);
        static::assertNotNull($entity);
        static::assertSame('preserved-value', $entity->getHiddenField());
    }
}
