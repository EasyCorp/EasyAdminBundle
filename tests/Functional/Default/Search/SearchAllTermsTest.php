<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Search;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SearchAllTermsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\SearchTestAuthor;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\SearchTestEntity;

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
     * @dataProvider provideSearchTests
     */
    public function testSearch(array $newEntitiesToCreate, string $query, int $expectedResultCount): void
    {
        foreach ($newEntitiesToCreate as $entityData) {
            $entity = $this->createSearchTestEntity(
                $entityData['searchableTextField'],
                $entityData['searchableContentField'],
                $entityData['nonSearchableField'] ?? null,
                $entityData['authorEmail'] ?? null
            );
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();

        $this->client->request('GET', $this->generateIndexUrl($query));
        static::assertIndexFullEntityCount($expectedResultCount);
    }

    public static function provideSearchTests(): iterable
    {
        // from fixtures: 8 SearchTestEntity entities
        $totalNumberOfEntities = 8;

        yield 'search all entities containing PHP' => [
            [],
            'PHP',
            4, // 4 entities mention PHP: "Introduction to PHP Programming", "JavaScript and PHP Integration", "Testing Best Practices" (PHPUnit), "Doctrine ORM Mastery"
        ];

        yield 'search with quoted phrase' => [
            [],
            '"Symfony Framework"',
            1, // Only "Advanced Symfony Framework Guide" matches
        ];

        yield 'search all terms (AND logic)' => [
            [],
            'Database patterns',
            1, // Only "Database Design Patterns" matches both terms
        ];

        yield 'search all terms inverted order' => [
            [],
            'patterns Database',
            1, // Same as above - order doesn't matter in all-terms search
        ];

        yield 'search with quoted terms not matching' => [
            [],
            '"Patterns Database"',
            0, // No entity has this exact phrase
        ];

        yield 'quoted terms with inside quotes' => [
            [
                ['searchableTextField' => 'Foo "Bar Baz Article', 'searchableContentField' => 'Content about testing.'],
            ],
            '"foo "bar"',
            1,
        ];

        yield 'multiple quoted terms' => [
            [],
            '"REST API" "HTTP methods"',
            1, // Only "REST API Development" matches both phrases
        ];

        yield 'multiple quoted terms and unquoted terms' => [
            [],
            '"web" security CSRF',
            1, // Only "Security in Web Applications" matches all terms
        ];
    }

    private function createSearchTestEntity(
        string $searchableTextField,
        string $searchableContentField,
        ?string $nonSearchableField = null,
        ?string $authorEmail = null,
    ): SearchTestEntity {
        $author = null;
        if (null !== $authorEmail) {
            $author = $this->entityManager->getRepository(SearchTestAuthor::class)->findOneBy(['email' => $authorEmail]);
        }

        $entity = (new SearchTestEntity())
            ->setSearchableTextField($searchableTextField)
            ->setSearchableContentField($searchableContentField)
            ->setNonSearchableField($nonSearchableField);

        if (null !== $author) {
            $entity->setAuthor($author);
        }

        return $entity;
    }
}
