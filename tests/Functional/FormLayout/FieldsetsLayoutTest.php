<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\FormLayout;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FormLayoutFieldsetsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;

class FieldsetsLayoutTest extends AbstractCrudTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    protected function getControllerFqcn(): string
    {
        return FormLayoutFieldsetsCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testFieldsetsAreRenderedInNewForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // the first field (id) doesn't belong to any fieldset, so EasyAdmin creates an automatic one
        // but the field is not displayed in the new form (only in edit forms)
        // (so, the page should have 4 fieldsets)
        $fieldsets = $crawler->filter('.form-fieldset');
        static::assertCount(4, $fieldsets, 'Should have 5 fieldsets');
    }

    public function testFieldsetsHaveCorrectLabels(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $formContent = $crawler->filter('form.ea-new-form')->text();

        static::assertStringContainsString('Basic Information', $formContent);
        static::assertStringContainsString('Contact Information', $formContent);
        static::assertStringContainsString('Address', $formContent);
        static::assertStringContainsString('Settings', $formContent);
    }

    public function testFieldsetsHaveCorrectIcons(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $formHtml = $crawler->filter('form.ea-new-form')->html();

        static::assertStringContainsString('fa-info-circle', $formHtml);
        static::assertStringContainsString('fa-phone', $formHtml);
        static::assertStringContainsString('fa-map-marker', $formHtml);
        static::assertStringContainsString('fa-cog', $formHtml);
    }

    public function testFieldsetsHaveCustomCssClasses(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // check for custom CSS classes we added
        static::assertCount(1, $crawler->filter('.fieldset-basic'), 'Should have fieldset with class fieldset-basic');
        static::assertCount(1, $crawler->filter('.fieldset-contact'), 'Should have fieldset with class fieldset-contact');
        static::assertCount(1, $crawler->filter('.fieldset-address'), 'Should have fieldset with class fieldset-address');
        static::assertCount(1, $crawler->filter('.fieldset-settings'), 'Should have fieldset with class fieldset-settings');
    }

    public function testBasicInfoFieldsetContainsCorrectFields(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $fieldset = $crawler->filter('.fieldset-basic');

        static::assertCount(1, $fieldset->filter('[name="FormTestEntity[name]"]'), 'Basic Info fieldset should contain name field');
        static::assertCount(1, $fieldset->filter('[name="FormTestEntity[description]"]'), 'Basic Info fieldset should contain description field');
    }

    public function testContactInfoFieldsetContainsCorrectFields(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $fieldset = $crawler->filter('.fieldset-contact');

        static::assertCount(1, $fieldset->filter('[name="FormTestEntity[email]"]'), 'Contact fieldset should contain email field');
        static::assertCount(1, $fieldset->filter('[name="FormTestEntity[phone]"]'), 'Contact fieldset should contain phone field');
    }

    public function testAddressFieldsetContainsCorrectFields(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $fieldset = $crawler->filter('.fieldset-address');

        static::assertCount(1, $fieldset->filter('[name="FormTestEntity[street]"]'), 'Address fieldset should contain street field');
        static::assertCount(1, $fieldset->filter('[name="FormTestEntity[city]"]'), 'Address fieldset should contain city field');
        static::assertCount(1, $fieldset->filter('[name="FormTestEntity[postalCode]"]'), 'Address fieldset should contain postalCode field');
        static::assertCount(1, $fieldset->filter('[name="FormTestEntity[country]"]'), 'Address fieldset should contain country field');
    }

    public function testSettingsFieldsetContainsCorrectFields(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $fieldset = $crawler->filter('.fieldset-settings');

        static::assertCount(1, $fieldset->filter('[name="FormTestEntity[isActive]"]'), 'Settings fieldset should contain isActive field');
        static::assertCount(1, $fieldset->filter('[name="FormTestEntity[createdAt]"]'), 'Settings fieldset should contain createdAt field');
        static::assertCount(1, $fieldset->filter('[name="FormTestEntity[priority]"]'), 'Settings fieldset should contain priority field');
    }

    public function testCollapsibleFieldsetHasCorrectAttributes(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // the Address fieldset should be collapsible (but not collapsed by default)
        $addressFieldset = $crawler->filter('.fieldset-address');

        // should have a collapse button/link
        static::assertTrue(
            $addressFieldset->filter('[data-bs-toggle="collapse"]')->count() > 0
            || $addressFieldset->filter('.form-fieldset-collapse-marker')->count() > 0,
            'Collapsible fieldset should have collapse toggle'
        );
    }

    public function testCollapsedFieldsetIsHiddenByDefault(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // the Settings fieldset should be collapsed by default
        // (the collapsed state is indicated by the body NOT having 'show' class and the header link having 'collapsed' class)
        $settingsFieldset = $crawler->filter('.fieldset-settings');
        $fieldsetBody = $settingsFieldset->filter('.form-fieldset-body.collapse');

        static::assertTrue($fieldsetBody->count() > 0, 'Fieldset body should have collapse class');

        $collapseLink = $settingsFieldset->filter('.form-fieldset-collapse.collapsed');
        static::assertTrue($collapseLink->count() > 0, 'Collapsed fieldset should have collapsed class on header');
    }

    public function testFormSubmissionWorksWithFieldsets(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();
        $form['FormTestEntity[name]'] = 'Fieldset Test Entity';
        $form['FormTestEntity[email]'] = 'fieldset@test.com';
        $form['FormTestEntity[street]'] = '789 Fieldset Street';

        $this->client->submit($form);

        // check that entity was created with fields from different fieldsets
        $entity = $this->entityManager->getRepository(FormTestEntity::class)->findOneBy(['name' => 'Fieldset Test Entity']);
        static::assertNotNull($entity, 'Entity should be created');
        static::assertSame('fieldset@test.com', $entity->getEmail());
        static::assertSame('789 Fieldset Street', $entity->getStreet());
    }

    public function testFieldsetsAreRenderedInEditForm(): void
    {
        $entity = new FormTestEntity();
        $entity->setName('Edit Fieldset Test');
        $entity->setEmail('edit.fieldset@test.com');
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        // the first field (id) doesn't belong to any fieldset, so EasyAdmin creates an automatic one
        // (so, the page should have 5 fieldsets = 4 defined + 1 automatic)
        $fieldsets = $crawler->filter('.form-fieldset');
        static::assertCount(5, $fieldsets, 'Should have 5 fieldsets');

        // check that fieldsets are present with custom classes
        static::assertCount(1, $crawler->filter('.fieldset-basic'), 'Edit form should have Basic Info fieldset');
        static::assertCount(1, $crawler->filter('.fieldset-contact'), 'Edit form should have Contact fieldset');
        static::assertCount(1, $crawler->filter('.fieldset-address'), 'Edit form should have Address fieldset');
        static::assertCount(1, $crawler->filter('.fieldset-settings'), 'Edit form should have Settings fieldset');
    }

    public function testFieldsetsAreRenderedInDetailPage(): void
    {
        $entity = new FormTestEntity();
        $entity->setName('Detail Fieldset Test');
        $entity->setEmail('detail.fieldset@test.com');
        $entity->setStreet('Detail Street');
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        // check that fieldsets are present in detail page
        $detailContent = $crawler->filter('.content-body')->text();
        static::assertStringContainsString('Basic Information', $detailContent);
        static::assertStringContainsString('Contact Information', $detailContent);
        static::assertStringContainsString('Address', $detailContent);
        static::assertStringContainsString('Settings', $detailContent);
    }
}
