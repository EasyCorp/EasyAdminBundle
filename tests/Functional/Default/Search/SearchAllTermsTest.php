<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Search;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Search\SearchAllTermsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
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
        BlogPostFactory::createOne(['title' => 'Introduction to PHP Programming']);

        $crawler = $this->client->request('GET', $this->generateIndexUrl('PHP'));

        $crawler = $this->client->click($crawler->filter('th[data-column="title"] a')->link());

        $form = $crawler->filter('form.form-action-search');
        $formSearchInput = $form->filter('input[name="query"]');
        $this->assertSame('PHP', $formSearchInput->attr('value'));
        $this->assertSelectorExists('form.form-action-search .content-search-reset');
    }

    /**
     * @param list<array{title: string, content: string}> $entitiesToCreate
     */
    #[DataProvider('provideSearchTests')]
    public function testSearch(array $entitiesToCreate, string $query, int $expectedResultCount): void
    {
        BlogPostFactory::createSequence($entitiesToCreate);

        $this->client->request('GET', $this->generateIndexUrl($query));
        static::assertIndexFullEntityCount($expectedResultCount);
    }

    /**
     * @return iterable<string, array{list<array{title: string, content: string}>, string, int}>
     */
    public static function provideSearchTests(): iterable
    {
        yield 'search all posts containing PHP' => [
            [
                ['title' => 'Introduction to PHP Programming', 'content' => 'A popular scripting language.'],
                ['title' => 'JavaScript and PHP Integration', 'content' => 'Frontend and backend together.'],
                ['title' => 'Testing Best Practices', 'content' => 'PHPUnit is the standard testing framework.'],
                ['title' => 'Database Design Patterns', 'content' => 'Repository and unit of work.'],
            ],
            'PHP',
            3, // the term matches the title, the content and partial words ("PHPUnit")
        ];

        yield 'search with quoted phrase' => [
            [
                ['title' => 'Advanced Symfony Framework Guide', 'content' => 'Dependency injection and events.'],
                ['title' => 'Framework Comparison', 'content' => 'Symfony versus other tools.'],
            ],
            '"Symfony Framework"',
            1, // the second post contains both words, but not the exact phrase
        ];

        yield 'search all terms (AND logic)' => [
            [
                ['title' => 'Database Design Patterns', 'content' => 'Repository and unit of work.'],
                ['title' => 'Database Indexes', 'content' => 'How to make queries faster.'],
            ],
            'Database patterns',
            1, // only the first post matches both terms
        ];

        yield 'search all terms inverted order' => [
            [
                ['title' => 'Database Design Patterns', 'content' => 'Repository and unit of work.'],
                ['title' => 'Database Indexes', 'content' => 'How to make queries faster.'],
            ],
            'patterns Database',
            1, // order doesn't matter in all-terms search
        ];

        yield 'search with quoted terms not matching' => [
            [
                ['title' => 'Database Design Patterns', 'content' => 'Repository and unit of work.'],
            ],
            '"Patterns Database"',
            0, // no post has this exact phrase
        ];

        yield 'quoted terms with inside quotes' => [
            [
                ['title' => 'Foo "Bar Baz Article', 'content' => 'Content about testing.'],
                ['title' => 'Unrelated Article', 'content' => 'Nothing in common.'],
            ],
            '"foo "bar"',
            1,
        ];

        yield 'multiple quoted terms' => [
            [
                ['title' => 'REST API Development', 'content' => 'This article covers HTTP methods and status codes.'],
                ['title' => 'REST API Security', 'content' => 'This article covers authentication tokens.'],
            ],
            '"REST API" "HTTP methods"',
            1, // only the first post matches both phrases
        ];

        yield 'multiple quoted terms and unquoted terms' => [
            [
                ['title' => 'Security in Web Applications', 'content' => 'XSS attacks and CSRF protection.'],
                ['title' => 'Web Security Basics', 'content' => 'XSS attacks and SQL injection.'],
            ],
            '"web" security CSRF',
            1, // only the first post matches all terms
        ];
    }
}
