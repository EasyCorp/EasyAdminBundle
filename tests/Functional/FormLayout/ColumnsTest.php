<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\FormLayout;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FormLayoutColumnsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;

class ColumnsTest extends AbstractCrudTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    protected function getControllerFqcn(): string
    {
        return FormLayoutColumnsCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testColumnsAreRenderedInNewForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // verify the HTML structure of columns
        static::assertSelectorExists('div.row > .form-column', 'Columns should be direct children of div.row');

        $columns = $crawler->filter('.form-column');

        // check that both columns are rendered (form-column class from the template)
        static::assertCount(2, $columns, 'Should have 2 columns');

        // both columns should have col-md-6 class (6 columns = half width)
        static::assertStringContainsString('col-md-6', $columns->eq(0)->attr('class'));
        static::assertStringContainsString('col-md-6', $columns->eq(1)->attr('class'));
    }

    public function testColumnsHaveLabelsAndIcons(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // check for column labels in the form
        $formContent = $crawler->filter('form.ea-new-form')->text();
        static::assertStringContainsString('Left Column', $formContent);
        static::assertStringContainsString('Right Column', $formContent);

        // check for icons in the column titles
        $formHtml = $crawler->filter('form.ea-new-form')->html();
        static::assertStringContainsString('fa-arrow-left', $formHtml);
        static::assertStringContainsString('fa-arrow-right', $formHtml);
    }

    public function testLeftColumnContainsCorrectFields(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $columns = $crawler->filter('.form-column');
        $leftColumn = $columns->eq(0);

        // left column should contain: name, description, email, phone
        static::assertCount(1, $leftColumn->filter('[name="FormTestEntity[name]"]'), 'Left column should contain name field');
        static::assertCount(1, $leftColumn->filter('[name="FormTestEntity[description]"]'), 'Left column should contain description field');
        static::assertCount(1, $leftColumn->filter('[name="FormTestEntity[email]"]'), 'Left column should contain email field');
        static::assertCount(1, $leftColumn->filter('[name="FormTestEntity[phone]"]'), 'Left column should contain phone field');
    }

    public function testRightColumnContainsCorrectFields(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $columns = $crawler->filter('.form-column');
        $rightColumn = $columns->eq(1);

        // right column should contain: street, city, postalCode, country, isActive, createdAt, tags, priority
        static::assertCount(1, $rightColumn->filter('[name="FormTestEntity[street]"]'), 'Right column should contain street field');
        static::assertCount(1, $rightColumn->filter('[name="FormTestEntity[city]"]'), 'Right column should contain city field');
        static::assertCount(1, $rightColumn->filter('[name="FormTestEntity[postalCode]"]'), 'Right column should contain postalCode field');
        static::assertCount(1, $rightColumn->filter('[name="FormTestEntity[country]"]'), 'Right column should contain country field');
        static::assertCount(1, $rightColumn->filter('[name="FormTestEntity[isActive]"]'), 'Right column should contain isActive field');
        static::assertCount(1, $rightColumn->filter('[name="FormTestEntity[createdAt]"]'), 'Right column should contain createdAt field');
        static::assertCount(1, $rightColumn->filter('[name="FormTestEntity[priority]"]'), 'Right column should contain priority field');
    }

    public function testFormSubmissionWorksWithColumns(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();
        $form['FormTestEntity[name]'] = 'Column Test Entity';
        $form['FormTestEntity[street]'] = '123 Test Street';
        $form['FormTestEntity[city]'] = 'Test City';

        $this->client->submit($form);

        // check that entity was created with fields from both columns
        $entity = $this->entityManager->getRepository(FormTestEntity::class)->findOneBy(['name' => 'Column Test Entity']);
        static::assertNotNull($entity, 'Entity should be created');
        static::assertSame('123 Test Street', $entity->getStreet());
        static::assertSame('Test City', $entity->getCity());
    }

    public function testColumnsAreRenderedInEditForm(): void
    {
        // first create an entity
        $entity = new FormTestEntity();
        $entity->setName('Edit Column Test');
        $entity->setStreet('456 Edit Street');
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        // check that columns are present
        static::assertCount(2, $crawler->filter('.form-column'), 'Should have 2 columns in edit form');
    }

    public function testFieldsInsideColumnsHaveFormGroups(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // check that fields inside columns have form-group containers
        $columns = $crawler->filter('.form-column');
        $leftColumn = $columns->eq(0);

        // check that form groups inside columns exist
        $formGroups = $leftColumn->filter('.form-group');
        static::assertTrue($formGroups->count() > 0, 'Should have form groups inside column');
    }
}
