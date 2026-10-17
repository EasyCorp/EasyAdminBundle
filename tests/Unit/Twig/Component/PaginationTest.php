<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Twig\Component;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\EntityPaginatorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\Radius;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Pagination;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PaginationTest extends TestCase
{
    public function testPaginatorAndRawPropsAreExclusive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('you cannot pass any of the "currentPage", "lastPage", "totalItems", "pageSize" or "urlPattern" props');

        $this->createPagination()->mount(paginator: $this->createMock(EntityPaginatorInterface::class), currentPage: 1);
    }

    public function testRawModeRequiresCurrentPageAndUrlPattern(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('you must pass both the "currentPage" and "urlPattern" props');

        $this->createPagination()->mount(currentPage: 1, lastPage: 10);
    }

    public function testRawModeRequiresThePagePlaceholderInUrlPattern(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must contain the "{page}" placeholder');

        $this->createPagination()->mount(currentPage: 1, lastPage: 10, urlPattern: '?page=1');
    }

    public function testRawModeRejectsLastPageCombinedWithTotalItems(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pass either the "lastPage" prop or the "totalItems" and "pageSize" props, but not both');

        $this->createPagination()->mount(currentPage: 1, lastPage: 10, totalItems: 100, urlPattern: '?page={page}');
    }

    public function testRawModeRequiresLastPageOrTotalItemsAndPageSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('you must pass either the "lastPage" prop or both the "totalItems" and "pageSize" props');

        $this->createPagination()->mount(currentPage: 1, totalItems: 100, urlPattern: '?page={page}');
    }

    public function testRawModeRejectsInvalidPageSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be 1 or higher');

        $this->createPagination()->mount(currentPage: 1, totalItems: 100, pageSize: 0, urlPattern: '?page={page}');
    }

    public function testRawModeDerivesLastPageFromTotalItemsAndPageSize(): void
    {
        $pagination = $this->createPagination();
        $pagination->mount(currentPage: 2, totalItems: 101, pageSize: 20, urlPattern: '?page={page}');

        $this->assertSame(6, $pagination->getLastPage());
        $this->assertSame(101, $pagination->getNumResults());
    }

    public function testRawModeWithZeroTotalItemsHasOnePage(): void
    {
        $pagination = $this->createPagination();
        $pagination->mount(currentPage: 1, totalItems: 0, pageSize: 20, urlPattern: '?page={page}');

        $this->assertSame(1, $pagination->getLastPage());
        $this->assertFalse($pagination->hasPreviousPage());
        $this->assertFalse($pagination->hasNextPage());
    }

    public function testRawModeWithLastPageOnlyHasUnknownNumResults(): void
    {
        $pagination = $this->createPagination();
        $pagination->mount(currentPage: 1, lastPage: 10, urlPattern: '?page={page}');

        $this->assertNull($pagination->getNumResults());
    }

    public function testRawModeReplacesThePagePlaceholderInUrls(): void
    {
        $pagination = $this->createPagination();
        $pagination->mount(currentPage: 1, lastPage: 10, urlPattern: '/items?foo=bar&page={page}');

        $this->assertSame('/items?foo=bar&page=7', $pagination->getUrlForPage(7));
    }

    public function testRawModeClampsCurrentPreviousAndNextPages(): void
    {
        $pagination = $this->createPagination();
        $pagination->mount(currentPage: -5, lastPage: 10, urlPattern: '?page={page}');

        $this->assertSame(1, $pagination->getCurrentPage());
        $this->assertSame(1, $pagination->getPreviousPage());
        $this->assertFalse($pagination->hasPreviousPage());
        $this->assertSame(2, $pagination->getNextPage());

        $pagination = $this->createPagination();
        $pagination->mount(currentPage: 10, lastPage: 10, urlPattern: '?page={page}');

        $this->assertSame(10, $pagination->getNextPage());
        $this->assertFalse($pagination->hasNextPage());
        $this->assertSame(9, $pagination->getPreviousPage());
    }

    public function testRawModePageRangeUsesDefaultRangeSizes(): void
    {
        $pagination = $this->createPagination();
        $pagination->mount(currentPage: 18, lastPage: 35, urlPattern: '?page={page}');

        $this->assertSame([1, null, 15, 16, 17, 18, 19, 20, 21, null, 35], iterator_to_array($pagination->getPageRange(), false));
    }

    public function testRawModePageRangeUsesCustomRangeSizes(): void
    {
        $pagination = $this->createPagination();
        $pagination->mount(currentPage: 18, lastPage: 35, urlPattern: '?page={page}');
        $pagination->rangeSize = 1;
        $pagination->rangeEdgeSize = 1;

        $this->assertSame([1, null, 17, 18, 19, null, 35], iterator_to_array($pagination->getPageRange(), false));
    }

    public function testObjectModeDelegatesToThePaginator(): void
    {
        $paginator = $this->createMock(EntityPaginatorInterface::class);
        $paginator->method('getCurrentPage')->willReturn(3);
        $paginator->method('getLastPage')->willReturn(7);
        $paginator->method('hasPreviousPage')->willReturn(true);
        $paginator->method('hasNextPage')->willReturn(true);
        $paginator->method('getPreviousPage')->willReturn(2);
        $paginator->method('getNextPage')->willReturn(4);
        $paginator->method('getNumResults')->willReturn(140);
        $paginator->expects($this->once())->method('generateUrlForPage')->with(4)->willReturn('/admin?page=4');

        $pagination = $this->createPagination();
        $pagination->mount(paginator: $paginator);

        $this->assertSame(3, $pagination->getCurrentPage());
        $this->assertSame(7, $pagination->getLastPage());
        $this->assertTrue($pagination->hasPreviousPage());
        $this->assertTrue($pagination->hasNextPage());
        $this->assertSame(2, $pagination->getPreviousPage());
        $this->assertSame(4, $pagination->getNextPage());
        $this->assertSame(140, $pagination->getNumResults());
        $this->assertSame('/admin?page=4', $pagination->getUrlForPage(4));
    }

    public function testObjectModePageRangeReceivesTheRangeSizeOverrides(): void
    {
        $paginator = $this->createMock(EntityPaginatorInterface::class);
        $paginator->expects($this->once())->method('getPageRange')->with(2, 1)->willReturn([1, 2, 3]);

        $pagination = $this->createPagination();
        $pagination->mount(paginator: $paginator);
        $pagination->rangeSize = 2;
        $pagination->rangeEdgeSize = 1;

        $this->assertSame([1, 2, 3], $pagination->getPageRange());
    }

    public function testObjectModePageRangeDefaultsToThePaginatorConfiguredSizes(): void
    {
        $paginator = $this->createMock(EntityPaginatorInterface::class);
        $paginator->expects($this->once())->method('getPageRange')->with(null, null)->willReturn([1, 2, 3]);

        $pagination = $this->createPagination();
        $pagination->mount(paginator: $paginator);

        $this->assertSame([1, 2, 3], $pagination->getPageRange());
    }

    #[DataProvider('provideRadiusValues')]
    public function testMountResolvesRadius(string|Radius|null $radius, ?Radius $expectedRadius): void
    {
        $pagination = $this->createPagination();
        $pagination->mount(currentPage: 1, lastPage: 10, urlPattern: '?page={page}', radius: $radius);

        $this->assertSame($expectedRadius, $pagination->radius);
    }

    public static function provideRadiusValues(): iterable
    {
        yield 'null' => [null, null];
        yield 'string' => ['full', Radius::Full];
        yield 'enum' => [Radius::Small, Radius::Small];
        yield 'unknown string falls back to null' => ['nope', null];
    }

    public function testCssClassBuilders(): void
    {
        $pagination = $this->createPagination();
        $pagination->mount(currentPage: 1, lastPage: 10, urlPattern: '?page={page}');

        $this->assertSame('list-pagination', $pagination->getDefaultCssClass());
        $this->assertSame('pager list-pagination-paginator first-page', $pagination->getNavCssClass());
        $this->assertSame('pagination', $pagination->getListCssClass());
        $this->assertSame('page-link', $pagination->getPageLinkCssClass());

        $pagination = $this->createPagination();
        $pagination->mount(currentPage: 10, lastPage: 10, urlPattern: '?page={page}', radius: Radius::Full);
        $pagination->size = 'sm';

        $this->assertSame('pager list-pagination-paginator last-page', $pagination->getNavCssClass());
        $this->assertSame('pagination pagination-sm', $pagination->getListCssClass());
        $this->assertSame('page-link ea-rounded-full', $pagination->getPageLinkCssClass());

        $pagination = $this->createPagination();
        $pagination->mount(currentPage: 5, lastPage: 10, urlPattern: '?page={page}');

        $this->assertSame('pager list-pagination-paginator', $pagination->getNavCssClass());
    }

    public function testIsRtlIsFalseWithoutAdminContext(): void
    {
        $pagination = $this->createPagination();
        $pagination->mount(currentPage: 1, lastPage: 10, urlPattern: '?page={page}');

        $this->assertFalse($pagination->isRtl());
    }

    private function createPagination(): Pagination
    {
        $adminContextProvider = $this->createMock(AdminContextProviderInterface::class);
        $adminContextProvider->method('getContext')->willReturn(null);

        return new Pagination($adminContextProvider);
    }
}
