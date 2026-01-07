<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Orm;

use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityPaginator;
use PHPUnit\Framework\TestCase;

class EntityPaginatorTest extends TestCase
{
    /**
     * @testWith [5, 5]
     *           [-1, 1]
     *           [0, 1]
     */
    public function testGetCurrentPage(int $pageNumber, int $expectedPage): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: $pageNumber, pageSize: 10, totalResults: 100);

        $this->assertSame($expectedPage, $paginator->getCurrentPage());
    }

    /**
     * @testWith [95, 10]
     *           [100, 10]
     *           [5, 1]
     *           [0, 1]
     */
    public function testGetLastPage(int $totalResults, int $expectedLastPage): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 10, totalResults: $totalResults);

        $this->assertSame($expectedLastPage, $paginator->getLastPage());
    }

    public function testGetPageSize(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 25, totalResults: 100);

        $this->assertSame(25, $paginator->getPageSize());
    }

    public function testHasPreviousPageOnFirstPage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 10, totalResults: 100);

        $this->assertFalse($paginator->hasPreviousPage());
    }

    public function testHasPreviousPageOnSecondPage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 2, pageSize: 10, totalResults: 100);

        $this->assertTrue($paginator->hasPreviousPage());
    }

    public function testGetPreviousPageOnFirstPage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 10, totalResults: 100);

        $this->assertSame(1, $paginator->getPreviousPage());
    }

    public function testGetPreviousPageOnMiddlePage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 5, pageSize: 10, totalResults: 100);

        $this->assertSame(4, $paginator->getPreviousPage());
    }

    public function testHasNextPageOnLastPage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 10, pageSize: 10, totalResults: 100);

        $this->assertFalse($paginator->hasNextPage());
    }

    public function testHasNextPageOnFirstPage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 10, totalResults: 100);

        $this->assertTrue($paginator->hasNextPage());
    }

    public function testGetNextPageOnLastPage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 10, pageSize: 10, totalResults: 100);

        $this->assertSame(10, $paginator->getNextPage());
    }

    public function testGetNextPageOnMiddlePage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 5, pageSize: 10, totalResults: 100);

        $this->assertSame(6, $paginator->getNextPage());
    }

    public function testHasToPaginateWithManyResults(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 10, totalResults: 100);

        $this->assertTrue($paginator->hasToPaginate());
    }

    public function testHasToPaginateWithFewResults(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 10, totalResults: 5);

        $this->assertFalse($paginator->hasToPaginate());
    }

    public function testHasToPaginateWithExactPageSize(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 10, totalResults: 10);

        $this->assertFalse($paginator->hasToPaginate());
    }

    public function testIsOutOfRangeOnFirstPage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 10, totalResults: 100);

        $this->assertFalse($paginator->isOutOfRange());
    }

    public function testIsOutOfRangeOnValidMiddlePage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 5, pageSize: 10, totalResults: 100);

        $this->assertFalse($paginator->isOutOfRange());
    }

    public function testIsOutOfRangeOnPageBeyondLast(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 15, pageSize: 10, totalResults: 100);

        $this->assertTrue($paginator->isOutOfRange());
    }

    public function testIsOutOfRangeWithEmptyResultsOnFirstPage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 10, totalResults: 0);

        $this->assertFalse($paginator->isOutOfRange());
    }

    public function testGetNumResults(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 10, totalResults: 135);

        $this->assertSame(135, $paginator->getNumResults());
    }

    public function testGetRangeFirstResultNumber(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 3, pageSize: 20, totalResults: 100);

        $this->assertSame(41, $paginator->getRangeFirstResultNumber());
    }

    public function testGetRangeFirstResultNumberOnFirstPage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 20, totalResults: 100);

        $this->assertSame(1, $paginator->getRangeFirstResultNumber());
    }

    public function testGetRangeLastResultNumber(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 2, pageSize: 20, totalResults: 100);

        $this->assertSame(40, $paginator->getRangeLastResultNumber());
    }

    public function testGetRangeLastResultNumberOnLastPage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 5, pageSize: 20, totalResults: 95);

        $this->assertSame(95, $paginator->getRangeLastResultNumber());
    }

    /**
     * @dataProvider pageRangeDataProvider
     */
    public function testGetPageRange(int $currentPage, int $lastPage, int $pagesOnEachSide, int $pagesOnEdges, array $expectedRange): void
    {
        $pageSize = 10;
        $totalResults = $lastPage * $pageSize;

        $paginator = $this->createPaginatedPaginator(
            pageNumber: $currentPage,
            pageSize: $pageSize,
            totalResults: $totalResults,
            rangeSize: $pagesOnEachSide,
            rangeEdgeSize: $pagesOnEdges
        );

        $range = iterator_to_array($paginator->getPageRange(), false);

        $this->assertSame($expectedRange, $range);
    }

    public static function pageRangeDataProvider(): iterable
    {
        // Small paginator - all pages shown without gaps
        yield 'small paginator with 5 pages, current page 1' => [
            'currentPage' => 1,
            'lastPage' => 5,
            'pagesOnEachSide' => 3,
            'pagesOnEdges' => 1,
            'expectedRange' => [1, 2, 3, 4, 5],
        ];

        yield 'small paginator with 5 pages, current page 3' => [
            'currentPage' => 3,
            'lastPage' => 5,
            'pagesOnEachSide' => 3,
            'pagesOnEdges' => 1,
            'expectedRange' => [1, 2, 3, 4, 5],
        ];

        // Large paginator - gaps should appear
        yield 'large paginator, current page at start' => [
            'currentPage' => 1,
            'lastPage' => 35,
            'pagesOnEachSide' => 3,
            'pagesOnEdges' => 1,
            'expectedRange' => [1, 2, 3, 4, null, 35],
        ];

        yield 'large paginator, current page 2' => [
            'currentPage' => 2,
            'lastPage' => 35,
            'pagesOnEachSide' => 3,
            'pagesOnEdges' => 1,
            'expectedRange' => [1, 2, 3, 4, 5, null, 35],
        ];

        yield 'large paginator, current page in the middle' => [
            'currentPage' => 18,
            'lastPage' => 35,
            'pagesOnEachSide' => 3,
            'pagesOnEdges' => 1,
            'expectedRange' => [1, null, 15, 16, 17, 18, 19, 20, 21, null, 35],
        ];

        yield 'large paginator, current page at end' => [
            'currentPage' => 35,
            'lastPage' => 35,
            'pagesOnEachSide' => 3,
            'pagesOnEdges' => 1,
            'expectedRange' => [1, null, 32, 33, 34, 35],
        ];

        yield 'large paginator, current page near end' => [
            'currentPage' => 34,
            'lastPage' => 35,
            'pagesOnEachSide' => 3,
            'pagesOnEdges' => 1,
            'expectedRange' => [1, null, 31, 32, 33, 34, 35],
        ];

        // Edge case: pagesOnEachSide = 0 returns null
        yield 'pagesOnEachSide is zero' => [
            'currentPage' => 5,
            'lastPage' => 10,
            'pagesOnEachSide' => 0,
            'pagesOnEdges' => 1,
            'expectedRange' => [],
        ];

        // Edge case: single page
        yield 'single page' => [
            'currentPage' => 1,
            'lastPage' => 1,
            'pagesOnEachSide' => 3,
            'pagesOnEdges' => 1,
            'expectedRange' => [1],
        ];

        // Different pagesOnEdges values
        yield 'large paginator with 2 pages on edges' => [
            'currentPage' => 15,
            'lastPage' => 30,
            'pagesOnEachSide' => 2,
            'pagesOnEdges' => 2,
            'expectedRange' => [1, 2, null, 13, 14, 15, 16, 17, null, 29, 30],
        ];

        // Transition zone - where current page is near the threshold
        yield 'near start threshold' => [
            'currentPage' => 5,
            'lastPage' => 35,
            'pagesOnEachSide' => 3,
            'pagesOnEdges' => 1,
            'expectedRange' => [1, 2, 3, 4, 5, 6, 7, 8, null, 35],
        ];

        yield 'near end threshold' => [
            'currentPage' => 31,
            'lastPage' => 35,
            'pagesOnEachSide' => 3,
            'pagesOnEdges' => 1,
            'expectedRange' => [1, null, 28, 29, 30, 31, 32, 33, 34, 35],
        ];
    }

    public function testGetPageRangeWithCustomParameters(): void
    {
        $paginator = $this->createPaginatedPaginator(
            pageNumber: 10,
            pageSize: 10,
            totalResults: 200,
            rangeSize: 3,
            rangeEdgeSize: 1
        );

        // Call with custom parameters that override defaults
        $range = iterator_to_array($paginator->getPageRange(pagesOnEachSide: 2, pagesOnEdges: 2), false);

        $this->assertSame([1, 2, null, 8, 9, 10, 11, 12, null, 19, 20], $range);
    }

    private function createPaginatedPaginator(int $pageNumber, int $pageSize, int $totalResults, int $rangeSize = 3, int $rangeEdgeSize = 1): EntityPaginator
    {
        // create an EntityPaginator without calling its constructor (avoid mocking final classes)
        $reflection = new \ReflectionClass(EntityPaginator::class);
        $paginator = $reflection->newInstanceWithoutConstructor();

        $currentPageProp = $reflection->getProperty('currentPage');
        $currentPageProp->setValue($paginator, max(1, $pageNumber));

        $pageSizeProp = $reflection->getProperty('pageSize');
        $pageSizeProp->setValue($paginator, $pageSize);

        $numResultsProp = $reflection->getProperty('numResults');
        $numResultsProp->setValue($paginator, $totalResults);

        $rangeSizeProp = $reflection->getProperty('rangeSize');
        $rangeSizeProp->setValue($paginator, $rangeSize);

        $rangeEdgeSizeProp = $reflection->getProperty('rangeEdgeSize');
        $rangeEdgeSizeProp->setValue($paginator, $rangeEdgeSize);

        $rangeFirstResultNumberProp = $reflection->getProperty('rangeFirstResultNumber');
        $rangeFirstResultNumberProp->setValue($paginator, $pageSize * (max(1, $pageNumber) - 1) + 1);

        $rangeLastResultNumberProp = $reflection->getProperty('rangeLastResultNumber');
        $rangeLastResultNumber = $pageSize * max(1, $pageNumber);
        if ($rangeLastResultNumber > $totalResults) {
            $rangeLastResultNumber = $totalResults;
        }
        $rangeLastResultNumberProp->setValue($paginator, $rangeLastResultNumber);

        return $paginator;
    }
}
