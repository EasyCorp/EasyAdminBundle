<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Util;

use EasyCorp\Bundle\EasyAdminBundle\Util\PageRangeCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PageRangeCalculatorTest extends TestCase
{
    #[DataProvider('providePageRanges')]
    public function testCalculate(array $expectedRange, int $currentPage, int $lastPage, int $pagesOnEachSide = 3, int $pagesOnEdges = 1): void
    {
        $range = PageRangeCalculator::calculate($currentPage, $lastPage, $pagesOnEachSide, $pagesOnEdges);

        $this->assertSame($expectedRange, $range);
    }

    public static function providePageRanges(): iterable
    {
        yield 'single page' => [[1], 1, 1];
        yield 'two pages' => [[1, 2], 1, 2];
        yield 'few pages render all of them' => [[1, 2, 3, 4, 5, 6, 7, 8], 4, 8];
        yield 'page count equal to the no-gap threshold' => [[1, 2, 3, 4, 5, 6, 7, 8], 5, 8, 3, 1];

        yield 'first page of 35' => [[1, 2, 3, 4, null, 35], 1, 35];
        yield 'middle page of 35' => [[1, null, 15, 16, 17, 18, 19, 20, 21, null, 35], 18, 35];
        yield 'last page of 35' => [[1, null, 32, 33, 34, 35], 35, 35];
        yield 'page near the start' => [[1, 2, 3, 4, 5, 6, 7, 8, null, 35], 5, 35];
        yield 'page near the end' => [[1, null, 28, 29, 30, 31, 32, 33, 34, 35], 31, 35];

        yield 'custom pages on each side' => [[1, null, 17, 18, 19, null, 35], 18, 35, 1, 1];
        yield 'custom pages on edges' => [[1, 2, null, 15, 16, 17, 18, 19, 20, 21, null, 34, 35], 18, 35, 3, 2];

        yield 'zero pages on each side yields nothing' => [[], 18, 35, 0, 1];
    }
}
