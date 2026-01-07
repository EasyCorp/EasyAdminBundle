<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\SearchMode;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class SearchDtoTest extends TestCase
{
    public function testQueryStringIsTrimmedAutomatically(): void
    {
        $dto = new SearchDto(new Request(), null, '  foo  ', [], [], null);
        $this->assertSame('foo', $dto->getQuery());
    }

    public function testDefaultSort(): void
    {
        $dto = new SearchDto(new Request(), null, null, ['foo' => 'ASC'], [], null);
        $this->assertSame(['foo' => 'ASC'], $dto->getSort());
    }

    /**
     * @dataProvider provideSortConfigMergeTests
     */
    public function testSortConfigMerging(array $defaultSort, array $customSort, array $expectedSortConfig): void
    {
        $dto = new SearchDto(new Request(), null, null, $defaultSort, $customSort, null);
        $this->assertSame($expectedSortConfig, $dto->getSort());
    }

    /**
     * @dataProvider provideIsSortingFieldTests
     */
    public function testIsSortingField(array $defaultSort, array $customSort, string $fieldName, bool $expectedResult): void
    {
        $dto = new SearchDto(new Request(), null, null, $defaultSort, $customSort, null);
        $this->assertSame($expectedResult, $dto->isSortingField($fieldName));
    }

    /**
     * @dataProvider provideSortDirectionTests
     */
    public function testGetSortDirection(array $defaultSort, array $customSort, string $fieldName, string $expectedDirection): void
    {
        $dto = new SearchDto(new Request(), null, null, $defaultSort, $customSort, null);
        $this->assertSame($expectedDirection, $dto->getSortDirection($fieldName));
    }

    /**
     * @dataProvider provideGetQueryTermsTests
     */
    public function testGetQueryTerms(string $query, array $expectedQueryTerms): void
    {
        $dto = new SearchDto(new Request(), null, $query, [], [], null);
        $this->assertSame($expectedQueryTerms, $dto->getQueryTerms());
    }

    public function testDefaultSearchMode(): void
    {
        $dto = new SearchDto(new Request(), null, null, ['foo' => 'ASC'], [], null);
        $this->assertSame(SearchMode::ALL_TERMS, $dto->getSearchMode());
    }

    public function testSearchMode(): void
    {
        foreach ([SearchMode::ANY_TERMS, SearchMode::ALL_TERMS] as $searchMode) {
            $dto = new SearchDto(new Request(), null, null, ['foo' => 'ASC'], [], null, $searchMode);
            $this->assertSame($searchMode, $dto->getSearchMode());
        }
    }

    public static function provideSortDirectionTests(): iterable
    {
        yield 'no default sort, no custom sort' => [
            [],
            [],
            'foo',
            'DESC',
        ];

        yield 'no default sort, custom sort' => [
            [],
            ['foo' => 'ASC'],
            'foo',
            'ASC',
        ];

        yield 'default sort, no custom sort' => [
            ['foo' => 'ASC'],
            [],
            'foo',
            'ASC',
        ];

        yield 'default sort, custom sort' => [
            ['foo' => 'ASC'],
            ['foo' => 'DESC'],
            'foo',
            'DESC',
        ];
    }

    public static function provideIsSortingFieldTests(): iterable
    {
        yield 'no default sort, no custom sort' => [
            [],
            [],
            'foo',
            false,
        ];

        yield 'no default sort, custom sort, is sorting' => [
            [],
            ['foo' => 'ASC', 'bar' => 'DESC'],
            'foo',
            true,
        ];

        yield 'no default sort, custom sort, is not sorting' => [
            [],
            ['bar' => 'ASC', 'foo' => 'DESC'],
            'foo',
            false,
        ];

        yield 'default sort, no custom sort, is sorting' => [
            ['foo' => 'ASC', 'bar' => 'DESC'],
            [],
            'foo',
            true,
        ];

        yield 'default sort, no custom sort, is not sorting' => [
            ['bar' => 'ASC', 'foo' => 'DESC'],
            [],
            'foo',
            false,
        ];

        yield 'default sort, custom sort, is sorting' => [
            ['foo' => 'ASC', 'bar' => 'DESC'],
            ['foo' => 'DESC', 'qux' => 'DESC'],
            'foo',
            true,
        ];

        yield 'default sort, custom sort, is not sorting' => [
            ['bar' => 'ASC', 'foo' => 'DESC'],
            ['qux' => 'DESC', 'foo' => 'DESC'],
            'foo',
            false,
        ];
    }

    public static function provideSortConfigMergeTests(): iterable
    {
        yield 'no default sort, no custom sort' => [
            [],
            [],
            [],
        ];

        yield 'no default sort, custom sort' => [
            [],
            ['foo' => 'ASC'],
            ['foo' => 'ASC'],
        ];

        yield 'default sort, no custom sort' => [
            ['foo' => 'ASC'],
            [],
            ['foo' => 'ASC'],
        ];

        yield 'default sort, custom sort' => [
            ['foo' => 'ASC'],
            ['bar' => 'DESC'],
            ['bar' => 'DESC', 'foo' => 'ASC'],
        ];

        yield 'default sort, custom sort with same field' => [
            ['foo' => 'ASC'],
            ['foo' => 'DESC'],
            ['foo' => 'DESC'],
        ];

        yield 'default sort, custom sort with same field and same order' => [
            ['foo' => 'ASC'],
            ['foo' => 'ASC'],
            ['foo' => 'ASC'],
        ];

        yield 'default sort, custom sort with different fields' => [
            ['foo' => 'ASC', 'bar' => 'DESC'],
            ['baz' => 'DESC', 'qux' => 'ASC'],
            ['baz' => 'DESC', 'qux' => 'ASC', 'foo' => 'ASC', 'bar' => 'DESC'],
        ];

        yield 'default sort, custom sort with intersecting and non-intersecting fields' => [
            ['foo' => 'ASC', 'bar' => 'DESC'],
            ['foo' => 'DESC', 'qux' => 'DESC'],
            ['foo' => 'DESC', 'qux' => 'DESC', 'bar' => 'DESC'],
        ];
    }

    public static function provideGetQueryTermsTests(): iterable
    {
        yield 'empty query' => [
            '',
            [],
        ];

        yield 'query with one term' => [
            'foo',
            ['foo'],
        ];

        yield 'query with multiple terms' => [
            'foo bar',
            ['foo', 'bar'],
        ];

        yield 'query with multiple terms and extra spaces' => [
            ' foo    bar ',
            ['foo', 'bar'],
        ];

        yield 'query with quoted terms' => [
            'foo "bar baz"',
            ['foo', 'bar baz'],
        ];

        yield 'query with quoted terms and escaped quotes' => [
            'foo "bar \\"baz"',
            ['foo', 'bar \\"baz'],
        ];

        yield 'query with quoted terms and extra spaces' => [
            'foo "  bar   baz" ',
            ['foo', 'bar   baz'],
        ];

        yield 'query with multiple quoted terms' => [
            '"foo bar" "baz qux"',
            ['foo bar', 'baz qux'],
        ];

        yield 'query with multiple terms and multiple quoted terms' => [
            'foo bar "foo bar" baz "baz qux" qux',
            ['foo', 'bar', 'foo bar', 'baz', 'baz qux', 'qux'],
        ];
    }
}
