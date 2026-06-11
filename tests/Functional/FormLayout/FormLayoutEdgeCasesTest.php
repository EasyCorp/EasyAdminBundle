<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\FormLayout;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FormLayoutEdgeCasesCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;

/**
 * Tests for edge cases and unusual but valid configurations.
 */
class FormLayoutEdgeCasesTest extends AbstractCrudTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    protected function getControllerFqcn(): string
    {
        return FormLayoutEdgeCasesCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testEmptyFieldsetDoesNotBreakRendering(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // verify form renders successfully with empty fieldset
        static::assertSelectorExists('form.ea-new-form', 'Form should render successfully');

        // empty fieldset might still render its header
        $html = $crawler->html();
        static::assertStringContainsString('Empty Fieldset', $html, 'Empty fieldset header should be present');
    }

    public function testSingleFieldFieldsetRenders(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // single Field Fieldset should have one field
        $html = $crawler->html();
        static::assertStringContainsString('Single Field Fieldset', $html, 'Single field fieldset should be present');
    }

    public function testManyFieldsRenderCorrectly(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $secondTabPane = $crawler->filter('.tab-pane')->eq(1);

        // verify many fields in "Many Fields" tab are present
        // the form should handle many fields without issue
        static::assertCount(1, $secondTabPane->filter('[name="FormTestEntity[email]"]'), 'Should have email field');
        static::assertCount(1, $secondTabPane->filter('[name="FormTestEntity[phone]"]'), 'Should have phone field');
        static::assertCount(1, $secondTabPane->filter('[name="FormTestEntity[street]"]'), 'Should have street field');
        static::assertCount(1, $secondTabPane->filter('[name="FormTestEntity[city]"]'), 'Should have city field');
    }

    public function testManyFieldsFormSubmission(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();

        // fill multiple fields
        $form['FormTestEntity[name]'] = 'Many Fields Test';
        $form['FormTestEntity[email]'] = 'manyfields@test.com';
        $form['FormTestEntity[priority]'] = '5';
        $form['FormTestEntity[status]'] = 'published';

        $this->client->submit($form);

        $entity = $this->entityManager->getRepository(FormTestEntity::class)
            ->findOneBy(['name' => 'Many Fields Test']);

        static::assertNotNull($entity, 'Entity should be created with many fields');
        static::assertSame('manyfields@test.com', $entity->getEmail());
        static::assertSame('published', $entity->getStatus());
    }

    public function testMultipleConsecutiveRowBreaks(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // verify the form renders with multiple consecutive row breaks
        $rowBreaks = $crawler->filter('.field-form_row');
        static::assertGreaterThan(0, $rowBreaks->count(), 'Should have row breaks');

        // form should still be functional
        static::assertSelectorExists('form.ea-new-form', 'Form should render successfully');
    }

    public function testMultipleRowBreaksFormSubmission(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();

        $form['FormTestEntity[name]'] = 'Multiple Rows Test';
        $form['FormTestEntity[email]'] = 'multiplerows@test.com';

        $this->client->submit($form);

        $entity = $this->entityManager->getRepository(FormTestEntity::class)
            ->findOneBy(['name' => 'Multiple Rows Test']);

        static::assertNotNull($entity, 'Entity should be created with multiple row breaks');
    }

    public function testAllTabsAreRendered(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertCount(3, $crawler->filter('.nav-tabs .nav-item'), 'Should have 3 tabs');

        $tabsNav = $crawler->filter('.nav-tabs');
        static::assertStringContainsString('Empty Tab', $tabsNav->text());
        static::assertStringContainsString('Many Fields', $tabsNav->text());
        static::assertStringContainsString('Multiple Rows', $tabsNav->text());
    }

    public function testEditFormWithEdgeCases(): void
    {
        $entity = new FormTestEntity();
        $entity->setName('Edge Case Edit');
        $entity->setEmail('edgecase@edit.com');
        $entity->setPriority(1);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        // verify all tabs render in edit form
        static::assertCount(3, $crawler->filter('.nav-tabs .nav-item'), 'Edit form should have all 3 tabs');

        // verify data is loaded
        $form = $crawler->filter('form.ea-edit-form')->form();
        static::assertSame('Edge Case Edit', $form['FormTestEntity[name]']->getValue());
        static::assertSame('edgecase@edit.com', $form['FormTestEntity[email]']->getValue());
    }

    public function testFormSubmissionPreservesDataFromAllEdgeCases(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();

        // fill fields across all edge case tabs
        $form['FormTestEntity[name]'] = 'All Edge Cases';
        $form['FormTestEntity[email]'] = 'alledges@test.com';
        $form['FormTestEntity[street]'] = '555 Edge Rd';
        $form['FormTestEntity[city]'] = 'Edge City';
        $form['FormTestEntity[priority]'] = '10';

        $this->client->submit($form);

        $entity = $this->entityManager->getRepository(FormTestEntity::class)
            ->findOneBy(['name' => 'All Edge Cases']);

        static::assertNotNull($entity, 'Entity should be created from all edge cases');
        static::assertSame('alledges@test.com', $entity->getEmail());
        static::assertSame('555 Edge Rd', $entity->getStreet());
        static::assertSame('Edge City', $entity->getCity());
        static::assertSame(10, $entity->getPriority());
    }
}
