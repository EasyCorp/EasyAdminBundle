<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext\AutocompleteUserCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext\BlogPostsCollectionUserCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext\EmbeddedAuthorBlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext\EntityPermissionFormBlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext\GuestDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext\GuestUserCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext\RepositoryQueryBuilderBlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext\RestrictedFormBlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecuredDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as SymfonyKernel;

/**
 * The autocomplete action only applies the options of the field named in its
 * "autocompleteContext" query parameter when the user can see that field.
 *
 * The test app database is shared by all tests, so this test creates its own entities
 * (with names that the autocomplete query matches) and removes them afterwards.
 */
class AutocompleteContextTest extends AbstractCrudTestCase
{
    private const USER_LABEL = 'AcUser1';
    private const USER_LABEL_WITH_EMAIL = 'AcUser1 (acuser1@example.com)';

    private int $unpublishedBlogPostId;
    private int $publishedBlogPostId;
    /** @var list<object> */
    private array $createdEntities = [];

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return RestrictedFormBlogPostCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return SecuredDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $author = $this->createUser('AcUser1', 'acuser1@example.com');
        $this->unpublishedBlogPostId = $this->createBlogPost('Unpublished blog post', $author, null)->getId();
        $this->publishedBlogPostId = $this->createBlogPost('Published blog post', $author, new \DateTimeImmutable('2020-01-02 00:00:00'))->getId();
    }

    protected function tearDown(): void
    {
        $this->entityManager->clear();
        foreach (array_reverse($this->createdEntities) as $entity) {
            if (null !== $managedEntity = $this->entityManager->find($entity::class, $entity->getId())) {
                $this->entityManager->remove($managedEntity);
            }
        }
        $this->entityManager->flush();

        parent::tearDown();
    }

    public function testWidgetOfNewPageAppliesTheFieldOptions(): void
    {
        $crawler = $this->request('admin', $this->generateNewFormUrl());
        $results = $this->requestAutocompleteResults('admin', $this->getAutocompleteUrl($crawler, 'BlogPost[author][autocomplete]'), 'AcUser');

        static::assertSame([self::USER_LABEL_WITH_EMAIL], $results);
    }

    public function testWidgetOfEditPageCheckedWithTheEditedEntity(): void
    {
        $crawler = $this->request('user', $this->generateEditFormUrl($this->unpublishedBlogPostId));
        $results = $this->requestAutocompleteResults('user', $this->getAutocompleteUrl($crawler, 'BlogPost[author][autocomplete]'), 'AcUser');

        static::assertSame([self::USER_LABEL_WITH_EMAIL], $results);
    }

    /**
     * @dataProvider provideForbiddenAutocompleteContexts
     *
     * @param array<string, string> $autocompleteContext
     */
    public function testForbiddenAutocompleteContext(string $username, string $dashboardFqcn, string $targetCrudControllerFqcn, array $autocompleteContext): void
    {
        if (isset($autocompleteContext[EA::ENTITY_ID])) {
            $autocompleteContext[EA::ENTITY_ID] = (string) $this->publishedBlogPostId;
        }
        $this->createCategory('AcCategory1');

        $url = $this->getCrudUrl('autocomplete', null, ['autocompleteContext' => $autocompleteContext, 'query' => 'Ac'], $dashboardFqcn, $targetCrudControllerFqcn);
        $this->request($username, $url);

        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        static::assertStringNotContainsString('acuser1@example.com', (string) $this->client->getResponse()->getContent());
        static::assertStringNotContainsString('AcCategory1', (string) $this->client->getResponse()->getContent());
    }

    public static function provideForbiddenAutocompleteContexts(): \Generator
    {
        yield 'page that the user cannot access' => ['user', SecuredDashboardController::class, AutocompleteUserCrudController::class, [
            EA::CRUD_CONTROLLER_FQCN => RestrictedFormBlogPostCrudController::class,
            'propertyName' => 'author',
            'originatingPage' => 'new',
        ]];

        yield 'page that the user cannot access for the given entity' => ['user', SecuredDashboardController::class, AutocompleteUserCrudController::class, [
            EA::CRUD_CONTROLLER_FQCN => RestrictedFormBlogPostCrudController::class,
            'propertyName' => 'author',
            'originatingPage' => 'edit',
            EA::ENTITY_ID => 'replaced by the id of the published blog post',
        ]];

        yield 'page whose permission needs an entity but no entity is given' => ['user', SecuredDashboardController::class, AutocompleteUserCrudController::class, [
            EA::CRUD_CONTROLLER_FQCN => RestrictedFormBlogPostCrudController::class,
            'propertyName' => 'author',
            'originatingPage' => 'edit',
        ]];

        yield 'page name that is not a CRUD page' => ['user', SecuredDashboardController::class, AutocompleteUserCrudController::class, [
            EA::CRUD_CONTROLLER_FQCN => RestrictedFormBlogPostCrudController::class,
            'propertyName' => 'author',
            'originatingPage' => 'NEW',
        ]];

        yield 'new page denied by the entity permission' => ['user', SecuredDashboardController::class, AutocompleteUserCrudController::class, [
            EA::CRUD_CONTROLLER_FQCN => EntityPermissionFormBlogPostCrudController::class,
            'propertyName' => 'author',
            'originatingPage' => 'new',
        ]];

        yield 'edit page denied by the entity permission for the given entity' => ['user', SecuredDashboardController::class, AutocompleteUserCrudController::class, [
            EA::CRUD_CONTROLLER_FQCN => EntityPermissionFormBlogPostCrudController::class,
            'propertyName' => 'author',
            'originatingPage' => 'edit',
            EA::ENTITY_ID => 'replaced by the id of the published blog post',
        ]];

        yield 'edit page denied by the entity permission without entity' => ['user', SecuredDashboardController::class, AutocompleteUserCrudController::class, [
            EA::CRUD_CONTROLLER_FQCN => EntityPermissionFormBlogPostCrudController::class,
            'propertyName' => 'author',
            'originatingPage' => 'edit',
        ]];

        yield 'CRUD controller that is not available in the current dashboard' => ['admin', GuestDashboardController::class, GuestUserCrudController::class, [
            EA::CRUD_CONTROLLER_FQCN => RestrictedFormBlogPostCrudController::class,
            'propertyName' => 'author',
            'originatingPage' => 'new',
        ]];

        yield 'class that is not a CRUD controller' => ['admin', SecuredDashboardController::class, AutocompleteUserCrudController::class, [
            EA::CRUD_CONTROLLER_FQCN => \stdClass::class,
            'propertyName' => 'author',
            'originatingPage' => 'new',
        ]];

        yield 'query builder of another entity' => ['user', SecuredDashboardController::class, AutocompleteUserCrudController::class, [
            EA::CRUD_CONTROLLER_FQCN => RepositoryQueryBuilderBlogPostCrudController::class,
            'propertyName' => 'categories',
            'originatingPage' => 'new',
        ]];
    }

    public function testWidgetOfPageProtectedByTheEntityPermissionAppliesTheFieldOptions(): void
    {
        $crawler = $this->request('admin', $this->generateEditFormUrl($this->publishedBlogPostId, controllerFqcn: EntityPermissionFormBlogPostCrudController::class));
        $results = $this->requestAutocompleteResults('admin', $this->getAutocompleteUrl($crawler, 'BlogPost[author][autocomplete]'), 'AcUser');

        static::assertSame([self::USER_LABEL_WITH_EMAIL], $results);
    }

    public function testFieldThatTheUserCannotSeeIsIgnored(): void
    {
        $url = $this->getCrudUrl('autocomplete', null, ['autocompleteContext' => [
            EA::CRUD_CONTROLLER_FQCN => RestrictedFormBlogPostCrudController::class,
            'propertyName' => 'publisher',
            'originatingPage' => 'edit',
            EA::ENTITY_ID => (string) $this->unpublishedBlogPostId,
        ]], SecuredDashboardController::class, AutocompleteUserCrudController::class);

        static::assertSame([self::USER_LABEL], $this->requestAutocompleteResults('user', $url, 'AcUser'));
        static::assertSame([self::USER_LABEL_WITH_EMAIL], $this->requestAutocompleteResults('admin', $url, 'AcUser'));
    }

    public function testWidgetOfQueryBuilderOfTheTargetEntityKeepsWorking(): void
    {
        $category = $this->createCategory('AcCategory1');

        $crawler = $this->request('admin', $this->generateNewFormUrl(controllerFqcn: RepositoryQueryBuilderBlogPostCrudController::class));
        $results = $this->requestAutocompleteResults('admin', $this->getAutocompleteUrl($crawler, 'BlogPost[categories][autocomplete][]'), 'AcCategory');

        static::assertSame([sprintf('Category #%d', $category->getId())], $results);
    }

    public function testWidgetOfEmbeddedFormAppliesTheOptionsOfTheEmbeddedField(): void
    {
        $this->createBlogPost('AcPost <1>', $this->createUser('AcUser2', 'acuser2@example.com'), null);

        $crawler = $this->request('admin', $this->generateNewFormUrl(controllerFqcn: EmbeddedAuthorBlogPostCrudController::class));

        static::assertSame(
            ['<strong>AcPost &lt;1&gt;</strong>'],
            $this->requestAutocompleteResults('admin', $this->getAutocompleteUrl($crawler, 'BlogPost[author][blogPosts][autocomplete][]'), 'AcPost'),
        );
        static::assertSame(
            ['<em>AcPost &lt;1&gt;</em>'],
            $this->requestAutocompleteResults('admin', $this->getAutocompleteUrl($crawler, 'BlogPost[publisher][blogPosts][autocomplete][]'), 'AcPost'),
        );
    }

    public function testWidgetOfCollectionEntryAppliesTheOptionsOfTheEntryField(): void
    {
        // useEntryCrudForm() requires the 'prototype_options' option, added in Symfony 6.1
        if (SymfonyKernel::MAJOR_VERSION < 6 || (6 === SymfonyKernel::MAJOR_VERSION && SymfonyKernel::MINOR_VERSION < 1)) {
            $this->markTestSkipped('useEntryCrudForm() requires Symfony 6.1 or newer.');
        }

        $this->createCategory('AcCategory <1>');

        $crawler = $this->request('admin', $this->generateNewFormUrl(controllerFqcn: BlogPostsCollectionUserCrudController::class));
        static::assertResponseIsSuccessful();
        $prototype = new Crawler((string) $crawler->filter('[data-prototype]')->attr('data-prototype'));
        $widget = $prototype->filter('select[name="User[blogPosts][__blogPostsname__][categories][autocomplete][]"]');
        static::assertCount(1, $widget);

        static::assertSame(
            ['<strong>AcCategory &lt;1&gt;</strong>'],
            $this->requestAutocompleteResults('admin', (string) $widget->attr('data-ea-autocomplete-endpoint-url'), 'AcCategory'),
        );
    }

    private function request(string $username, string $url): Crawler
    {
        return $this->client->request('GET', $url, [], [], ['PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => '1234']);
    }

    /**
     * @return list<string>
     */
    private function requestAutocompleteResults(string $username, string $url, string $query): array
    {
        $this->request($username, $url.(str_contains($url, '?') ? '&' : '?').http_build_query(['query' => $query]));
        static::assertResponseIsSuccessful();

        $data = json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        return array_map(static fn (array $result): string => $result['entityAsString'], $data['results']);
    }

    private function getAutocompleteUrl(Crawler $crawler, string $widgetName): string
    {
        static::assertResponseIsSuccessful();
        $widget = $crawler->filter(sprintf('select[name="%s"]', $widgetName));
        static::assertCount(1, $widget, sprintf('The "%s" autocomplete widget is not in the page.', $widgetName));

        return (string) $widget->attr('data-ea-autocomplete-endpoint-url');
    }

    private function createUser(string $name, string $email): User
    {
        return $this->persist((new User())->setName($name)->setEmail($email));
    }

    private function createCategory(string $name): Category
    {
        return $this->persist((new Category())->setName($name)->setSlug(strtolower(preg_replace('/\W+/', '-', $name))));
    }

    private function createBlogPost(string $title, User $author, ?\DateTimeImmutable $publishedAt): BlogPost
    {
        $blogPost = (new BlogPost())
            ->setTitle($title)
            ->setSlug(strtolower(preg_replace('/\W+/', '-', $title)))
            ->setContent('Lorem ipsum')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setPublishedAt($publishedAt)
            ->setAuthor($author);

        return $this->persist($blogPost);
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
        $this->createdEntities[] = $entity;

        return $entity;
    }
}
