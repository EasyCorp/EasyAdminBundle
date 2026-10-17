<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Search;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SearchAllTermsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\SearchTestEntityFactory;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for the default all-terms search mode (AND logic).
 * All search terms must match for a result to be returned.
 */
class SearchAllTermsTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return SearchAllTermsCrudController::class;
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

    public function testDefaultEmptySearchForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $this->assertSelectorNotExists('form.form-action-search .content-search-reset', 'The empty search form should not display the button to reset contents');

        $form = $crawler->filter('form.form-action-search');

        // with pretty URLs (the default), form action is set and hidden inputs are not rendered
        $this->assertNotEmpty($form->attr('action'), 'Form action should be set when using pretty URLs');

        $formSearchInput = $form->filter('input[name="query"]');
        $this->assertSame('', $formSearchInput->attr('value'));
        $this->assertSame('Search', $formSearchInput->attr('placeholder'));
        $this->assertSame('false', $formSearchInput->attr('spellcheck'));
        $this->assertSame('off', $formSearchInput->attr('autocorrect'));
    }

    public function testSearchFormAfterMakingAQuery(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl('PHP'));

        $form = $crawler->filter('form.form-action-search');

        // with pretty URLs (the default), form action is set and hidden inputs are not rendered
        $this->assertNotEmpty($form->attr('action'), 'Form action should be set when using pretty URLs');

        $formSearchInput = $form->filter('input[name="query"]');
        $this->assertSame('PHP', $formSearchInput->attr('value'));
        $this->assertSame('Search', $formSearchInput->attr('placeholder'));
        $this->assertSame('false', $formSearchInput->attr('spellcheck'));
        $this->assertSame('off', $formSearchInput->attr('autocorrect'));

        $this->assertSelectorExists('form.form-action-search .content-search-reset', 'After making a query, the search form should display the button to reset contents');
        // the reset URL may include ?page=1 with pretty URLs
        $resetUrl = $crawler->filter('form.form-action-search .content-search-reset')->attr('href');
        $this->assertStringStartsWith($this->generateIndexUrl(), $resetUrl);
    }

    public function testSearchIsPersistedAfterPaginationAndSorting(): void
    {
        // the sortable column headers are only rendered when the search returns some results
        SearchTestEntityFactory::createOne(['searchableTextField' => 'Introduction to PHP Programming']);

        // make some query
        $crawler = $this->client->request('GET', $this->generateIndexUrl('PHP'));

        // click on a table column to sort the results
        $crawler = $this->client->click($crawler->filter('th[data-column="searchableTextField"] a')->link());

        // assert that the search query is persisted
        $form = $crawler->filter('form.form-action-search');
        $formSearchInput = $form->filter('input[name="query"]');
        $this->assertSame('PHP', $formSearchInput->attr('value'));
        $this->assertSelectorExists('form.form-action-search .content-search-reset');
    }

    /**
     * @param list<array{searchableTextField: string, searchableContentField: string}> $entitiesToCreate
     */
    #[DataProvider('provideSearchTests')]
    public function testSearch(array $entitiesToCreate, string $query, int $expectedResultCount): void
    {
        SearchTestEntityFactory::createSequence($entitiesToCreate);

        $this->client->request('GET', $this->generateIndexUrl($query));
        static::assertIndexFullEntityCount($expectedResultCount);
    }

    /**
     * @return iterable<string, array{list<array{searchableTextField: string, searchableContentField: string}>, string, int}>
     */
    public static function provideSearchTests(): iterable
    {
        yield 'search all entities containing PHP' => [
            [
                ['searchableTextField' => 'Introduction to PHP Programming', 'searchableContentField' => 'A popular scripting language.'],
                ['searchableTextField' => 'JavaScript and PHP Integration', 'searchableContentField' => 'Frontend and backend together.'],
                ['searchableTextField' => 'Testing Best Practices', 'searchableContentField' => 'PHPUnit is the standard testing framework.'],
                ['searchableTextField' => 'Database Design Patterns', 'searchableContentField' => 'Repository and unit of work.'],
            ],
            'PHP',
            3, // the term matches the text field, the content field and partial words ("PHPUnit")
        ];

        yield 'search with quoted phrase' => [
            [
                ['searchableTextField' => 'Advanced Symfony Framework Guide', 'searchableContentField' => 'Dependency injection and events.'],
                ['searchableTextField' => 'Framework Comparison', 'searchableContentField' => 'Symfony versus other tools.'],
            ],
            '"Symfony Framework"',
            1, // the second entity contains both words, but not the exact phrase
        ];

        yield 'search all terms (AND logic)' => [
            [
                ['searchableTextField' => 'Database Design Patterns', 'searchableContentField' => 'Repository and unit of work.'],
                ['searchableTextField' => 'Database Indexes', 'searchableContentField' => 'How to make queries faster.'],
            ],
            'Database patterns',
            1, // only the first entity matches both terms
        ];

        yield 'search all terms inverted order' => [
            [
                ['searchableTextField' => 'Database Design Patterns', 'searchableContentField' => 'Repository and unit of work.'],
                ['searchableTextField' => 'Database Indexes', 'searchableContentField' => 'How to make queries faster.'],
            ],
            'patterns Database',
            1, // order doesn't matter in all-terms search
        ];

        yield 'search with quoted terms not matching' => [
            [
                ['searchableTextField' => 'Database Design Patterns', 'searchableContentField' => 'Repository and unit of work.'],
            ],
            '"Patterns Database"',
            0, // no entity has this exact phrase
        ];

        yield 'quoted terms with inside quotes' => [
            [
                ['searchableTextField' => 'Foo "Bar Baz Article', 'searchableContentField' => 'Content about testing.'],
                ['searchableTextField' => 'Unrelated Article', 'searchableContentField' => 'Nothing in common.'],
            ],
            '"foo "bar"',
            1,
        ];

        yield 'multiple quoted terms' => [
            [
                ['searchableTextField' => 'REST API Development', 'searchableContentField' => 'This article covers HTTP methods and status codes.'],
                ['searchableTextField' => 'REST API Security', 'searchableContentField' => 'This article covers authentication tokens.'],
            ],
            '"REST API" "HTTP methods"',
            1, // only the first entity matches both phrases
        ];

        yield 'multiple quoted terms and unquoted terms' => [
            [
                ['searchableTextField' => 'Security in Web Applications', 'searchableContentField' => 'XSS attacks and CSRF protection.'],
                ['searchableTextField' => 'Web Security Basics', 'searchableContentField' => 'XSS attacks and SQL injection.'],
            ],
            '"web" security CSRF',
            1, // only the first entity matches all terms
        ];
    }
}
