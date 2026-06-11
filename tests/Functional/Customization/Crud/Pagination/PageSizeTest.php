<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Pagination;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Pagination\PageSizeTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setPaginatorPageSize() configuration method.
 */
class PageSizeTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return PageSizeTestCrudController::class;
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

    public function testPageSizeConfiguration(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // with pageSize=50 and 100 entities, first page should show 50 items
        $this->assertIndexPageEntityCount(50);

        // total count should show 100
        static::assertIndexFullEntityCount(100);

        // with 100 items and pageSize=50, we should have 2 pages
        $this->assertIndexPagesCount(2);

        // page 1 should be active
        static::assertSelectorTextSame('.page-item.active .page-link', '1');
    }
}
