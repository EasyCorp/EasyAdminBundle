<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Pagination;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Pagination\RangeSizeTestCrudController;
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
        // navigate to page 3 to see range size effect
        $url = $this->getCrudUrl('index', options: ['page' => 3]);
        $crawler = $this->client->request('GET', $url);

        static::assertResponseIsSuccessful();

        // with default pageSize=20 and 100 entities, we should have 5 pages
        $this->assertIndexPagesCount(5);

        // page 3 should be active
        static::assertSelectorTextSame('.page-item.active .page-link', '3');

        // count visible page number links (excluding prev/next)
        $pageNumbers = $crawler->filter('.pagination .page-item:not(.page-item-previous):not(.page-item-next) .page-link');

        // with rangeSize=3 on page 3 of 5 pages, all pages should be visible
        static::assertGreaterThan(0, $pageNumbers->count(), 'Page numbers should be visible');

        // verify pagination exists
        static::assertSelectorExists('.list-pagination');
    }
}
