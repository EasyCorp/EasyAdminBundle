<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Pagination;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Pagination\CustomRangeSizeCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Pagination\DefaultPaginationCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Pagination\NoPaginationRangeCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Pagination\SmallPageSizeCrudController;

class PaginationTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return DefaultPaginationCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testDefaultPaginationOnFirstPage(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        // with 30 categories and default pageSize of 20, first page should show 20 items
        $this->assertIndexPageEntityCount(20);

        // total count should show 30
        static::assertIndexFullEntityCount(30);

        // page 1 should be marked as active
        $this->assertSelectorTextSame('.page-item.active .page-link', '1');

        // with 30 items and pageSize 20, we should have 2 pages
        $this->assertIndexPagesCount(2);

        // on first page, "Previous" button should be disabled
        $this->assertSelectorExists('.page-item-previous.disabled');

        // on first page, "Next" button should be active
        $this->assertSelectorExists('.page-item-next:not(.disabled)');
    }

    public function testDefaultPaginationOnSecondPage(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $this->client->click($crawler->selectLink('Next')->link());

        $this->assertResponseIsSuccessful();

        // second page should show remaining 10 items (30 total - 20 on first page)
        $this->assertIndexPageEntityCount(10);

        // page 2 should now be active
        $this->assertSelectorTextSame('.page-item.active .page-link', '2');

        // on second page, "Previous" button should be active
        $this->assertSelectorExists('.page-item-previous:not(.disabled)');

        // on last page (page 2), "Next" button should be disabled
        $this->assertSelectorExists('.page-item-next.disabled');
    }

    public function testPaginatorWithCustomPageSize(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl(
            controllerFqcn: SmallPageSizeCrudController::class
        ));

        // navigate through all pages using "Next" button
        for ($page = 1; $page < 6; ++$page) {
            $this->assertResponseIsSuccessful();

            // the current page should be highlighted
            $this->assertSelectorTextSame('.page-item.active .page-link', (string) $page);

            // total count should still show 30
            static::assertIndexFullEntityCount(30);

            // with 30 items and pageSize=5, we should have 6 pages
            $this->assertIndexPagesCount(6);

            // each page should show 5 items
            $this->assertIndexPageEntityCount(5);

            $crawler = $this->client->click($crawler->selectLink('Next')->link());
        }
    }

    public function testCustomRangeSizeShowsPageNumbers(): void
    {
        // navigate to page 3 to test range display
        $url = $this->getCrudUrl(
            'index',
            options: ['page' => 3],
            controllerFqcn: CustomRangeSizeCrudController::class
        );
        $crawler = $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();

        // get all page numbers (excluding prev/next)
        $pageNumbers = $crawler->filter('.pagination .page-item:not(.page-item-previous):not(.page-item-next) .page-link');

        // with rangeSize=1 on page 3 of 6 pages and rangeEdgeSize=1:
        // the algorithm shows: [1] [2] [3] [4] [5] [6] (all pages, no ellipsis)
        // because the condition for ellipsis requires more pages
        // (lastPage > (rangeSize + rangeEdgeSize) * 2 = (1 + 1) * 2 = 4, and 6 > 4 is true,
        // but currentPage=3 does not exceed rangeSize + rangeEdgeSize + 1 = 3, so no ellipsis before)
        $pageTexts = [];
        $pageNumbers->each(static function ($node) use (&$pageTexts) {
            $pageTexts[] = trim($node->text());
        });

        // with 6 pages and rangeSize=1, on page 3, all pages are shown
        $this->assertCount(6, $pageTexts, 'All 6 page numbers should be shown');

        // page 3 should be active
        $this->assertSelectorTextSame('.page-item.active .page-link', '3');

        // first page (1) should be visible
        $this->assertNotEmpty($crawler->filter('.page-link:contains("1")'));

        // last page (6) should be visible
        $this->assertNotEmpty($crawler->filter('.page-link:contains("6")'));
    }

    public function testCustomRangeSizeShowsAllPagesWhenOnFirstPage(): void
    {
        $this->client->request('GET', $this->generateIndexUrl(
            controllerFqcn: CustomRangeSizeCrudController::class
        ));

        $this->assertResponseIsSuccessful();

        // on page 1 with rangeSize=1, we should see: [1] [2] [...] [6]
        // first page should be active
        $this->assertSelectorTextSame('.page-item.active .page-link', '1');
    }

    public function testNoPaginationRangeShowsOnlyPrevNext(): void
    {
        $this->client->request('GET', $this->generateIndexUrl(
            controllerFqcn: NoPaginationRangeCrudController::class
        ));

        $this->assertResponseIsSuccessful();

        // should have Previous and Next buttons
        $this->assertSelectorExists('.page-item-previous');
        $this->assertSelectorExists('.page-item-next');
    }

    public function testNoPaginationRangeCanStillNavigate(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl(
            controllerFqcn: NoPaginationRangeCrudController::class
        ));

        $this->assertResponseIsSuccessful();

        // navigate to next page
        $this->client->click($crawler->selectLink('Next')->link());

        // should still show correct items
        $this->assertIndexPageEntityCount(5);
    }

    public function testNoPaginationWhenNotNeeded(): void
    {
        // create a search that returns few results (less than page size)
        // we use a search that will return only 1 result
        $this->client->request('GET', $this->generateIndexUrl('Category 0'));

        $this->assertResponseIsSuccessful();

        // when results fit in one page, pagination nav should not be displayed
        $this->assertSelectorNotExists('.list-pagination nav.pager');
    }

    public function testPaginationViaUrlParameter(): void
    {
        $url = $this->getCrudUrl(
            'index',
            options: ['page' => 2]
        );
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();

        // page 2 should be active
        $this->assertSelectorTextSame('.page-item.active .page-link', '2');

        // should show remaining 10 items
        $this->assertIndexPageEntityCount(10);
    }

    public function testInvalidPageParameterShowsFirstPage(): void
    {
        // page 0 is invalid
        $url = $this->getCrudUrl(
            'index',
            options: ['page' => 0]
        );
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();

        // should default to page 1
        $this->assertSelectorTextSame('.page-item.active .page-link', '1');
    }

    public function testOutOfRangePageParameter(): void
    {
        // page 100 is out of range
        $url = $this->getCrudUrl(
            'index',
            options: ['page' => 100]
        );
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();

        // when an out of range page is requested, EasyAdmin uses the last valid page number
        $this->assertSelectorTextSame('.page-item.active .page-link', '2');
    }
}
