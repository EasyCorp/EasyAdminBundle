<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Pagination;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Pagination\PageSizeTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Factory\CategoryFactory;
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
        // one row more than the configured page size (50) to get a second page
        CategoryFactory::createMany(51);

        $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        $this->assertIndexPageEntityCount(50);
        static::assertIndexFullEntityCount(51);
        $this->assertIndexPagesCount(2);

        static::assertSelectorTextSame('.page-item.active .page-link', '1');
    }
}
