<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\FieldPermission\FieldPermissionBlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\FieldPermission\FieldPermissionCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecuredDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The search, the sort and the filters of the index page must ignore the properties
 * of the fields that the user can't see, because the rows they return (and their
 * order) would reveal the values of those properties.
 */
class FieldPermissionQueryTest extends AbstractCrudTestCase
{
    /** @var list<object> */
    private array $createdEntities = [];

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return FieldPermissionBlogPostCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return SecuredDashboardController::class;
    }

    protected function tearDown(): void
    {
        if ([] !== $this->createdEntities) {
            $this->entityManager->clear();
            // reversed, so blog posts are removed before their authors
            foreach (array_reverse($this->createdEntities) as $entity) {
                $managedEntity = $this->entityManager->find($entity::class, $entity->getId());
                if (null !== $managedEntity) {
                    $this->entityManager->remove($managedEntity);
                    $this->entityManager->flush();
                }
            }
        }

        parent::tearDown();
    }

    /**
     * @dataProvider provideSearchQueries
     *
     * @param list<string> $expectedTitles
     */
    public function testIndexSearch(string $username, string $query, array $expectedTitles): void
    {
        $this->createBlogPosts();

        $crawler = $this->requestAs($username, $this->generateIndexUrl($query));

        static::assertSame($expectedTitles, $this->getColumnValues($crawler, 'title'));
    }

    /**
     * @dataProvider provideSearchQueries
     *
     * @param list<string> $expectedTitles
     */
    public function testAutocompleteSearch(string $username, string $query, array $expectedTitles): void
    {
        $titlesById = $this->createBlogPosts();

        $this->requestAs($username, $this->getCrudUrl('autocomplete'), [
            'query' => $query,
            'autocompleteContext' => ['originatingPage' => 'index', 'propertyName' => 'title'],
        ]);

        $response = json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        $titles = array_map(static fn (array $result): string => $titlesById[(int) $result['entityId']], $response['results']);
        static::assertSame($expectedTitles, $titles);
    }

    public static function provideSearchQueries(): \Generator
    {
        yield 'user, visible field' => ['user', '"Field Permission Post 1"', ['Field Permission Post 1']];
        yield 'user, restricted field' => ['user', 'field-permission-content-b', []];
        yield 'user, property of a restricted association' => ['user', 'field-permission-email-b', []];
        yield 'admin, visible field' => ['admin', '"Field Permission Post 1"', ['Field Permission Post 1']];
        yield 'admin, restricted field' => ['admin', 'field-permission-content-b', ['Field Permission Post 1']];
        yield 'admin, property of a restricted association' => ['admin', 'field-permission-email-b', ['Field Permission Post 1']];
    }

    /**
     * @dataProvider provideDefaultSearchQueries
     *
     * @param list<string> $expectedNames
     */
    public function testIndexSearchInAllProperties(string $username, string $query, array $expectedNames): void
    {
        $this->persist((new Category())->setName('Field Permission Category 1')->setSlug('field-permission-slug-b'));
        $this->persist((new Category())->setName('Field Permission Category 2')->setSlug('field-permission-slug-a'));

        $crawler = $this->requestAs($username, $this->generateIndexUrl($query, controllerFqcn: FieldPermissionCategoryCrudController::class));

        static::assertSame($expectedNames, $this->getColumnValues($crawler, 'name'));
    }

    public static function provideDefaultSearchQueries(): \Generator
    {
        yield 'user, visible field' => ['user', '"Field Permission Category 1"', ['Field Permission Category 1']];
        yield 'user, restricted field' => ['user', 'field-permission-slug-b', []];
        yield 'admin, restricted field' => ['admin', 'field-permission-slug-b', ['Field Permission Category 1']];
    }

    /**
     * @dataProvider provideSorts
     *
     * @param array<string, string> $sort
     * @param list<string>          $expectedTitles
     */
    public function testIndexSort(string $username, array $sort, array $expectedTitles): void
    {
        $this->createBlogPosts();

        $crawler = $this->requestAs($username, $this->generateIndexUrl('"Field Permission Post"'), ['sort' => $sort]);

        static::assertSame($expectedTitles, $this->getColumnValues($crawler, 'title'));
    }

    public static function provideSorts(): \Generator
    {
        yield 'user, visible field' => ['user', ['title' => 'DESC'], ['Field Permission Post 2', 'Field Permission Post 1']];
        yield 'user, restricted field' => ['user', ['content' => 'ASC'], ['Field Permission Post 1', 'Field Permission Post 2']];
        yield 'user, restricted association' => ['user', ['author' => 'DESC'], ['Field Permission Post 1', 'Field Permission Post 2']];
        yield 'admin, restricted field' => ['admin', ['content' => 'ASC'], ['Field Permission Post 2', 'Field Permission Post 1']];
        yield 'admin, restricted association' => ['admin', ['author' => 'DESC'], ['Field Permission Post 2', 'Field Permission Post 1']];
    }

    /**
     * @dataProvider provideFilters
     *
     * @param list<string> $expectedTitles
     */
    public function testIndexFilters(string $username, string $property, string $value, array $expectedTitles): void
    {
        $this->createBlogPosts();

        $crawler = $this->requestAs($username, $this->generateIndexUrl('"Field Permission Post"'), [
            'filters' => [$property => ['comparison' => 'like', 'value' => $value]],
        ]);

        static::assertSame($expectedTitles, $this->getColumnValues($crawler, 'title'));
    }

    public static function provideFilters(): \Generator
    {
        yield 'user, visible field' => ['user', 'title', 'Field Permission Post 2', ['Field Permission Post 2']];
        yield 'user, restricted field' => ['user', 'content', 'field-permission-content-b', ['Field Permission Post 1', 'Field Permission Post 2']];
        yield 'admin, restricted field' => ['admin', 'content', 'field-permission-content-b', ['Field Permission Post 1']];
    }

    /**
     * @return array<int, string> the titles of the created blog posts indexed by their ID
     */
    private function createBlogPosts(): array
    {
        $author1 = $this->persist((new User())->setName('Field Permission Author 1')->setEmail('field-permission-email-b@example.com'));
        $author2 = $this->persist((new User())->setName('Field Permission Author 2')->setEmail('field-permission-email-a@example.com'));

        $titlesById = [];
        foreach ([[1, 'field-permission-content-b', $author1], [2, 'field-permission-content-a', $author2]] as [$index, $content, $author]) {
            $blogPost = $this->persist((new BlogPost())
                ->setTitle('Field Permission Post '.$index)
                ->setSlug('field-permission-post-'.$index)
                ->setContent($content)
                ->setCreatedAt(new \DateTimeImmutable('2020-01-01'))
                ->setAuthor($author));
            $titlesById[$blogPost->getId()] = $blogPost->getTitle();
        }

        return $titlesById;
    }

    /**
     * @template T of object
     *
     * @param T $entity
     *
     * @return T
     */
    private function persist(object $entity): object
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $this->createdEntities[] = $entity;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function requestAs(string $username, string $url, array $parameters = []): Crawler
    {
        $crawler = $this->client->request('GET', $url, $parameters, [], ['PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => '1234']);
        static::assertResponseIsSuccessful();

        return $crawler;
    }

    /**
     * @return list<string>
     */
    private function getColumnValues(Crawler $crawler, string $column): array
    {
        return $crawler->filter(sprintf('tbody tr[data-id] td[data-column="%s"]', $column))->each(static fn (Crawler $cell): string => trim($cell->text()));
    }
}
