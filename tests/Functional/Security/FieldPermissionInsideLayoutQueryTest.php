<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\FieldPermission\FieldPermissionInsideTabBlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecuredDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The fields defined inside a form tab, column or fieldset that the user can't see are
 * hidden too, so the search, the sort and the filters of the index page must ignore them.
 */
class FieldPermissionInsideLayoutQueryTest extends AbstractCrudTestCase
{
    /** @var list<object> */
    private array $createdEntities = [];

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return FieldPermissionInsideTabBlogPostCrudController::class;
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

        static::assertSame($expectedTitles, $this->getTitlesOfRows($crawler));
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
        yield 'user, visible field' => ['user', '"Layout Permission Post 1"', ['Layout Permission Post 1']];
        yield 'user, field inside a restricted tab' => ['user', 'layout-permission-slug-b', []];
        yield 'user, field inside a restricted fieldset' => ['user', 'layout-permission-content-b', []];
        yield 'admin, field inside a restricted tab' => ['admin', 'layout-permission-slug-b', ['Layout Permission Post 1']];
        yield 'admin, field inside a restricted fieldset' => ['admin', 'layout-permission-content-b', ['Layout Permission Post 1']];
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

        $crawler = $this->requestAs($username, $this->generateIndexUrl('"Layout Permission Post"'), ['sort' => $sort]);

        static::assertSame($expectedTitles, $this->getTitlesOfRows($crawler));
    }

    public static function provideSorts(): \Generator
    {
        yield 'user, visible field' => ['user', ['title' => 'DESC'], ['Layout Permission Post 2', 'Layout Permission Post 1']];
        yield 'user, field inside a restricted tab' => ['user', ['slug' => 'ASC'], ['Layout Permission Post 1', 'Layout Permission Post 2']];
        yield 'user, association inside a restricted tab' => ['user', ['author' => 'DESC'], ['Layout Permission Post 1', 'Layout Permission Post 2']];
        yield 'user, field inside a restricted fieldset' => ['user', ['content' => 'ASC'], ['Layout Permission Post 1', 'Layout Permission Post 2']];
        yield 'admin, field inside a restricted tab' => ['admin', ['slug' => 'ASC'], ['Layout Permission Post 2', 'Layout Permission Post 1']];
        yield 'admin, association inside a restricted tab' => ['admin', ['author' => 'DESC'], ['Layout Permission Post 2', 'Layout Permission Post 1']];
        yield 'admin, field inside a restricted fieldset' => ['admin', ['content' => 'ASC'], ['Layout Permission Post 2', 'Layout Permission Post 1']];
    }

    /**
     * @dataProvider provideFilters
     *
     * @param list<string> $expectedTitles
     */
    public function testIndexFilters(string $username, string $property, string $value, array $expectedTitles): void
    {
        $this->createBlogPosts();

        $crawler = $this->requestAs($username, $this->generateIndexUrl('"Layout Permission Post"'), [
            'filters' => [$property => ['comparison' => 'like', 'value' => $value]],
        ]);

        static::assertSame($expectedTitles, $this->getTitlesOfRows($crawler));
    }

    public static function provideFilters(): \Generator
    {
        yield 'user, visible field' => ['user', 'title', 'Layout Permission Post 2', ['Layout Permission Post 2']];
        yield 'user, field inside a restricted tab' => ['user', 'slug', 'layout-permission-slug-b', ['Layout Permission Post 1', 'Layout Permission Post 2']];
        yield 'user, field inside a restricted fieldset' => ['user', 'content', 'layout-permission-content-b', ['Layout Permission Post 1', 'Layout Permission Post 2']];
        yield 'admin, field inside a restricted tab' => ['admin', 'slug', 'layout-permission-slug-b', ['Layout Permission Post 1']];
        yield 'admin, field inside a restricted fieldset' => ['admin', 'content', 'layout-permission-content-b', ['Layout Permission Post 1']];
    }

    /**
     * @return array<int, string> the titles of the created blog posts indexed by their ID
     */
    private function createBlogPosts(): array
    {
        $author1 = $this->persist((new User())->setName('Layout Permission Author 1')->setEmail('layout-permission-email-b@example.com'));
        $author2 = $this->persist((new User())->setName('Layout Permission Author 2')->setEmail('layout-permission-email-a@example.com'));

        $titlesById = [];
        foreach ([[1, 'b', $author1], [2, 'a', $author2]] as [$index, $suffix, $author]) {
            $blogPost = $this->persist((new BlogPost())
                ->setTitle('Layout Permission Post '.$index)
                ->setSlug('layout-permission-slug-'.$suffix)
                ->setContent('layout-permission-content-'.$suffix)
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
    private function getTitlesOfRows(Crawler $crawler): array
    {
        return $crawler->filter('tbody tr[data-id] td[data-column="title"]')->each(static fn (Crawler $cell): string => trim($cell->text()));
    }
}
