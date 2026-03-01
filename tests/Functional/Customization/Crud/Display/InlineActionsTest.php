<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Display;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Display\InlineActionsTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::showEntityActionsInlined() configuration method.
 */
class InlineActionsTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return InlineActionsTestCrudController::class;
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

    public function testInlineActionsConfiguration(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // verify data is displayed
        $this->assertIndexPageEntityCount(20);

        // get first row
        $firstRow = $crawler->filter('tbody tr')->first();

        // when actions are inlined, they appear as direct action links/buttons
        // check for action links in the row (edit, delete, detail actions)
        $actionCell = $firstRow->filter('td.actions');

        static::assertGreaterThan(0, $actionCell->count(), 'Row should have actions cell');

        // verify there are action links/buttons visible
        $actionLinks = $actionCell->filter('a.action-edit, a.action-delete, a.action-detail');
        static::assertGreaterThan(0, $actionLinks->count(), 'Row should have inline action links');

        // verify NO dropdown toggle exists (actions are inlined, not in dropdown)
        $dropdown = $firstRow->filter('.dropdown-toggle');
        static::assertSame(0, $dropdown->count(), 'Actions should be inlined, not in dropdown');
    }
}
