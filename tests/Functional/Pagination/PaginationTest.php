<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Pagination;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Pagination\CustomRangeSizeCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Pagination\DefaultPaginationCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Pagination\NoPaginationRangeCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Pagination\SmallPageSizeCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use Symfony\Component\DomCrawler\Crawler;

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
        $this->createCategories(21);

        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        // with 21 categories and default pageSize of 20, first page should show 20 items
        $this->assertIndexPageEntityCount(20);

        static::assertIndexFullEntityCount(21);

        // page 1 should be marked as active
        $this->assertSelectorTextSame('.page-item.active .page-link', '1');

        // with 21 items and pageSize 20, we should have 2 pages
        $this->assertIndexPagesCount(2);

        // on first page, "Previous" button should be disabled
        $this->assertSelectorExists('.page-item-previous.disabled');

        // on first page, "Next" button should be active
        $this->assertSelectorExists('.page-item-next:not(.disabled)');
    }

    public function testDefaultPaginationOnSecondPage(): void
    {
        $this->createCategories(21);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $this->client->click($crawler->selectLink('Next')->link());

        $this->assertResponseIsSuccessful();

        // second page should show the remaining item (21 total - 20 on first page)
        $this->assertIndexPageEntityCount(1);

        // page 2 should now be active
        $this->assertSelectorTextSame('.page-item.active .page-link', '2');

        // on second page, "Previous" button should be active
        $this->assertSelectorExists('.page-item-previous:not(.disabled)');

        // on last page (page 2), "Next" button should be disabled
        $this->assertSelectorExists('.page-item-next.disabled');
    }

    public function testPaginatorWithCustomPageSize(): void
    {
        $this->createCategories(11);

        $crawler = $this->client->request('GET', $this->generateIndexUrl(
            controllerFqcn: SmallPageSizeCrudController::class
        ));

        // navigate through all pages using "Next" button
        foreach ([1 => 5, 2 => 5, 3 => 1] as $page => $expectedEntityCount) {
            $this->assertResponseIsSuccessful();

            $this->assertSelectorTextSame('.page-item.active .page-link', (string) $page);
            static::assertIndexFullEntityCount(11);

            // with 11 items and pageSize=5, we should have 3 pages
            $this->assertIndexPagesCount(3);
            $this->assertIndexPageEntityCount($expectedEntityCount);

            if (3 > $page) {
                $crawler = $this->client->click($crawler->selectLink('Next')->link());
            }
        }

        $this->assertSelectorExists('.page-item-next.disabled');
    }

    public function testCustomRangeSizeShowsPageNumbers(): void
    {
        // 6 pages of 5 items are needed to reproduce the range edge case described below
        $this->createCategories(26);

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
        $pageTexts = $pageNumbers->each(static fn (Crawler $node) => trim($node->text()));

        $this->assertSame(['1', '2', '3', '4', '5', '6'], $pageTexts, 'All 6 page numbers should be shown');
        $this->assertSelectorTextSame('.page-item.active .page-link', '3');
    }

    public function testCustomRangeSizeShowsAllPagesWhenOnFirstPage(): void
    {
        $this->createCategories(26);

        $crawler = $this->client->request('GET', $this->generateIndexUrl(
            controllerFqcn: CustomRangeSizeCrudController::class
        ));

        $this->assertResponseIsSuccessful();

        $pageTexts = $crawler
            ->filter('.pagination .page-item:not(.page-item-previous):not(.page-item-next) .page-link')
            ->each(static fn (Crawler $node) => trim($node->text()));

        // the ellipsis item includes a visually hidden 'More pages' label
        $this->assertSame(['1', '2', '…More pages', '6'], $pageTexts);
        $this->assertSelectorTextSame('.page-item.active .page-link', '1');
    }

    public function testNoPaginationRangeShowsOnlyPrevNext(): void
    {
        $this->createCategories(6);

        $crawler = $this->client->request('GET', $this->generateIndexUrl(
            controllerFqcn: NoPaginationRangeCrudController::class
        ));

        $this->assertResponseIsSuccessful();

        $this->assertSelectorExists('.page-item-previous');
        $this->assertSelectorExists('.page-item-next');
        $this->assertCount(0, $crawler->filter('.pagination .page-item:not(.page-item-previous):not(.page-item-next)'));
    }

    public function testNoPaginationRangeCanStillNavigate(): void
    {
        $this->createCategories(6);

        $crawler = $this->client->request('GET', $this->generateIndexUrl(
            controllerFqcn: NoPaginationRangeCrudController::class
        ));

        $this->assertResponseIsSuccessful();

        // navigate to next page
        $this->client->click($crawler->selectLink('Next')->link());

        // the second page shows the only item that doesn't fit in the first page
        $this->assertIndexPageEntityCount(1);
    }

    public function testNoPaginationWhenNotNeeded(): void
    {
        // without the search query, these results wouldn't fit in one page
        $this->createCategories(20);
        CategoryFactory::createOne(['name' => 'Searchable', 'slug' => 'searchable']);

        $this->client->request('GET', $this->generateIndexUrl('Searchable'));

        $this->assertResponseIsSuccessful();
        $this->assertIndexPageEntityCount(1);

        // when results fit in one page, pagination nav should not be displayed
        $this->assertSelectorNotExists('.list-pagination nav.pager');
    }

    public function testPaginationViaUrlParameter(): void
    {
        $this->createCategories(21);

        $url = $this->getCrudUrl(
            'index',
            options: ['page' => 2]
        );
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();

        // page 2 should be active
        $this->assertSelectorTextSame('.page-item.active .page-link', '2');

        $this->assertIndexPageEntityCount(1);
    }

    public function testInvalidPageParameterShowsFirstPage(): void
    {
        $this->createCategories(21);

        // page 0 is invalid
        $url = $this->getCrudUrl(
            'index',
            options: ['page' => 0]
        );
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextSame('.page-item.active .page-link', '1');
        $this->assertIndexPageEntityCount(20);
    }

    public function testOutOfRangePageParameter(): void
    {
        $this->createCategories(21);

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

    private function createCategories(int $count): void
    {
        CategoryFactory::createMany($count, static fn (int $i) => [
            'name' => sprintf('Category %d', $i),
            'slug' => sprintf('category-%d', $i),
        ]);
    }
}
