<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\FormLayout;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FormFieldsetsSyntheticCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;

/**
 * Tests for fieldsets with fields both inside and outside fieldsets.
 * This tests the automatic fieldset creation and manual fieldset configuration.
 */
class FieldsetsEdgeCasesTest extends AbstractCrudTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    protected function getControllerFqcn(): string
    {
        return FormFieldsetsSyntheticCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testFieldsWithoutFieldsetAreAssignedAnAutomaticFieldsetInForms(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());
        // the 'id' field does not belong to any explicit fieldset, so EasyAdmin creates a new fieldset for it
        static::assertSame('FormTestEntity[id]', $crawler->filter('form.ea-new-form .form-fieldset')->first()->filter('input')->attr('name'));

        // create an entity for edit form test
        $entity = new FormTestEntity();
        $entity->setName('Test Entity');
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));
        // the 'id' field does not belong to any explicit fieldset, so EasyAdmin creates a new fieldset for it
        static::assertSame('FormTestEntity[id]', $crawler->filter('form.ea-edit-form .form-fieldset')->first()->filter('input')->attr('name'));
    }

    public function testFieldsWithoutFieldsetAreAssignedAnAutomaticFieldsetInDetailPage(): void
    {
        $entity = new FormTestEntity();
        $entity->setName('Test Entity');
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        static::assertSame('ID', $crawler->filter('.content-body .field-group')->first()->filter('.field-label')->text());
    }

    public function testFieldsInsideFieldsetsInForms(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertCount(1, $crawler->filter('.form-fieldset:contains("Fieldset 1")'));
        static::assertCount(1, $crawler->filter('.form-fieldset:contains("Fieldset 1") input'));
        static::assertSame('FormTestEntity[name]', trim($crawler->filter('.form-fieldset:contains("Fieldset 1") input')->attr('name')));
        static::assertCount(1, $crawler->filter('.field-form_fieldset.bg-info.form-fieldset:contains("Fieldset 1")'));
        static::assertStringContainsString('fa fa-cog', $crawler->filter('.form-fieldset:contains("Fieldset 1") .form-fieldset-title i')->attr('class'));

        static::assertCount(1, $crawler->filter('.form-fieldset:contains("Fieldset 2")'));
        static::assertCount(1, $crawler->filter('.form-fieldset:contains("Fieldset 2") textarea'));
        static::assertSame('FormTestEntity[description]', trim($crawler->filter('.form-fieldset:contains("Fieldset 2") textarea')->attr('name')));
        static::assertCount(1, $crawler->filter('.field-form_fieldset.bg-warning.form-fieldset:contains("Fieldset 2")'));
        static::assertStringContainsString('fa fa-user', $crawler->filter('.form-fieldset:contains("Fieldset 2") .form-fieldset-title i')->attr('class'));
    }

    public function testFieldsInsideFieldsetsInDetailPage(): void
    {
        $entity = new FormTestEntity();
        $entity->setName('Test Entity');
        $entity->setDescription('Test Description');
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        static::assertCount(1, $crawler->filter('.form-fieldset:contains("Fieldset 1")'));
        static::assertCount(1, $crawler->filter('.form-fieldset:contains("Fieldset 1") .field-group'));
        static::assertSame('Name', trim($crawler->filter('.form-fieldset:contains("Fieldset 1") .field-group .field-label')->text()));
        static::assertCount(1, $crawler->filter('.field-form_fieldset.bg-info.form-fieldset:contains("Fieldset 1")'));
        static::assertStringContainsString('fa fa-cog', $crawler->filter('.form-fieldset:contains("Fieldset 1") .form-fieldset-title i')->attr('class'));

        static::assertCount(1, $crawler->filter('.form-fieldset:contains("Fieldset 2")'));
        static::assertCount(1, $crawler->filter('.form-fieldset:contains("Fieldset 2") .field-group'));
        static::assertSame('Description', trim($crawler->filter('.form-fieldset:contains("Fieldset 2") .field-group .field-label')->text()));
        static::assertCount(1, $crawler->filter('.field-form_fieldset.bg-warning.form-fieldset:contains("Fieldset 2")'));
        static::assertStringContainsString('fa fa-user', $crawler->filter('.form-fieldset:contains("Fieldset 2") .form-fieldset-title i')->attr('class'));
    }

    public function testFieldsetWithoutFieldsInForms(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertCount(1, $crawler->filter('.form-fieldset:contains("Fieldset 3")'));
        static::assertCount(0, $crawler->filter('.form-fieldset:contains("Fieldset 3") input'));
        static::assertCount(1, $crawler->filter('.field-form_fieldset.bg-danger.form-fieldset:contains("Fieldset 3")'));
        static::assertStringContainsString('fa fa-file-alt', $crawler->filter('.form-fieldset:contains("Fieldset 3") .form-fieldset-title i')->attr('class'));
    }

    public function testFieldsetWithoutFieldsInDetailPage(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertCount(1, $crawler->filter('.form-fieldset:contains("Fieldset 3")'));
        static::assertCount(0, $crawler->filter('.form-fieldset:contains("Fieldset 3") dt'));
        static::assertCount(1, $crawler->filter('.field-form_fieldset.bg-danger.form-fieldset:contains("Fieldset 3")'));
        static::assertStringContainsString('fa fa-file-alt', $crawler->filter('.form-fieldset:contains("Fieldset 3") .form-fieldset-title i')->attr('class'));
    }
}
