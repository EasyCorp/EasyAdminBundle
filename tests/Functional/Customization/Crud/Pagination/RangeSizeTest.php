<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Pagination;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Pagination\RangeSizeTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setPaginatorRangeSize() configuration method.
 */
class RangeSizeTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return RangeSizeTestCrudController::class;
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

    public function testRangeSizeConfiguration(): void
    {
        // 5 pages with the default page size (20)
        CategoryFactory::createMany(81);

        $crawler = $this->client->request('GET', $this->getCrudUrl('index', options: ['page' => 3]));

        static::assertResponseIsSuccessful();

        $this->assertIndexPagesCount(5);
        static::assertSelectorTextSame('.page-item.active .page-link', '3');

        // with rangeSize=3 on page 3 of 5 pages, all pages are visible and no ellipsis is shown
        $pageNumbers = $crawler->filter('.pagination .page-item:not(.page-item-previous):not(.page-item-next) .page-link')->each(static fn ($link) => trim($link->text()));
        static::assertSame(['1', '2', '3', '4', '5'], $pageNumbers);

        static::assertSelectorExists('.list-pagination');
    }
}
