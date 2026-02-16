<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\FormLayout;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FormLayoutRowsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;

class RowsTest extends AbstractCrudTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    protected function getControllerFqcn(): string
    {
        return FormLayoutRowsCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testRowBreaksAreRenderedInNewForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // check that form uses columns (rows work within columns)
        static::assertCount(2, $crawler->filter('.form-column'), 'Should have 2 columns');

        // check that row elements are present (they have field-form_row class)
        $rowElements = $crawler->filter('.field-form_row');
        static::assertGreaterThanOrEqual(1, $rowElements->count(), 'Should have row break elements');
    }

    public function testRowsCreateVisualBreaks(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // row elements exist in the form (they create visual breaks using various flex classes)
        $rowElements = $crawler->filter('.field-form_row');
        static::assertTrue($rowElements->count() > 0, 'Should have row elements');

        // verify they have flex-related classes (can be flex-fill or flex-md-fill depending on breakpoint)
        foreach ($rowElements as $rowElement) {
            $classes = $rowElement->getAttribute('class');
            static::assertTrue(
                str_contains($classes, 'flex-fill') || str_contains($classes, 'flex-md-fill') || str_contains($classes, 'd-flex'),
                'Row elements should have flex-related classes for layout'
            );
        }
    }

    public function testFormSubmissionWorksWithRows(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form.ea-new-form')->form();
        $form['FormTestEntity[name]'] = 'Row Test Entity';
        $form['FormTestEntity[email]'] = 'row@test.com';
        $form['FormTestEntity[city]'] = 'Row City';

        $this->client->submit($form);

        // check that entity was created
        $entity = $this->entityManager->getRepository(FormTestEntity::class)->findOneBy(['name' => 'Row Test Entity']);
        static::assertNotNull($entity, 'Entity should be created');
        static::assertSame('row@test.com', $entity->getEmail());
        static::assertSame('Row City', $entity->getCity());
    }

    public function testRowsAreRenderedInEditForm(): void
    {
        $entity = new FormTestEntity();
        $entity->setName('Edit Row Test');
        $entity->setEmail('edit.row@test.com');
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        // check that row elements are present in edit form
        $rowElements = $crawler->filter('.field-form_row');
        static::assertGreaterThanOrEqual(1, $rowElements->count(), 'Edit form should have row break elements');
    }
}
