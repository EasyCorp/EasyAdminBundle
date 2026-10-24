<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\BatchActions;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\BatchActions\ConfirmationTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::askConfirmationOnBatchActions() configuration method.
 */
class ConfirmationTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return ConfirmationTestCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return \EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testBatchActionConfirmationConfiguration(): void
    {
        CategoryFactory::createMany(2);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // verify data is displayed
        $this->assertIndexPageEntityCount(2);

        // verify batch action checkboxes exist
        $batchCheckboxes = $crawler->filter('tbody input[type="checkbox"].form-batch-checkbox');
        static::assertCount(2, $batchCheckboxes, 'Each row should have a batch action checkbox');

        // verify batch action form/controls exist
        static::assertSelectorExists('.content');
    }

    public function testCustomConfirmationMessageIsShownInsteadOfTheModalTitle(): void
    {
        CategoryFactory::createMany(2);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertSame('Are you sure you want to proceed?', $crawler->filter('[data-action-batch]')->first()->attr('data-batch-action-confirm-message'));
        static::assertCount(1, $crawler->filter('#modal-batch-action h4#batch-action-confirmation-title'));
        static::assertCount(1, $crawler->filter('#modal-batch-action p#batch-action-confirmation-message.modal-message.d-none'));
        static::assertSame('There is no undo for this operation.', $crawler->filter('#modal-batch-action #batch-action-confirmation-message + p')->text());
    }
}
