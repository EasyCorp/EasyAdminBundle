<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext\BlogPostsCollectionUserCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext\EmbeddedAuthorBlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext\EntityPermissionFormBlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext\NestedFavoriteCategoryBlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext\RepositoryQueryBuilderBlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext\RestrictedFormBlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext\RestrictedTabBlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext\VoterEntityPermissionFormBlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\ProtectedUserCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\RestrictedDashboard\GuestDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\RestrictedDashboard\GuestUserCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecuredDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

/**
 * The autocomplete action only applies the options of the field named in its
 * "autocompleteContext" query parameter when the user can see that field.
 */
class AutocompleteContextTest extends AbstractCrudTestCase
{
    private const USER_LABEL = 'User 1';
    private const USER_LABEL_WITH_EMAIL = 'User 1 (user1@example.com)';

    private BlogPost $unpublishedBlogPost;
    private BlogPost $publishedBlogPost;

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

        $author = UserFactory::createOne(['name' => 'User 1', 'email' => 'user1@example.com']);
        $this->unpublishedBlogPost = BlogPostFactory::createOne(['author' => $author, 'publishedAt' => null]);
        $this->publishedBlogPost = BlogPostFactory::createOne(['author' => $author, 'publishedAt' => new \DateTimeImmutable('2020-01-02 00:00:00')]);
    }

    public function testWidgetOfNewPageAppliesTheFieldOptions(): void
    {
        $crawler = $this->request('admin', $this->generateNewFormUrl());
        $results = $this->requestAutocompleteResults('admin', $this->getAutocompleteUrl($crawler, 'BlogPost[author][autocomplete]'));

        static::assertSame([self::USER_LABEL_WITH_EMAIL], $results);
    }

    public function testWidgetOfEditPageCheckedWithTheEditedEntity(): void
    {
        $crawler = $this->request('user', $this->generateEditFormUrl($this->unpublishedBlogPost->getId()));
        $results = $this->requestAutocompleteResults('user', $this->getAutocompleteUrl($crawler, 'BlogPost[author][autocomplete]'));

        static::assertSame([self::USER_LABEL_WITH_EMAIL], $results);
    }

    /**
     * @param array<string, string> $autocompleteContext
     */
    #[DataProvider('provideForbiddenAutocompleteContexts')]
    public function testForbiddenAutocompleteContext(string $username, string $dashboardFqcn, string $targetCrudControllerFqcn, array $autocompleteContext): void
    {
        if (isset($autocompleteContext[EA::ENTITY_ID])) {
            $autocompleteContext[EA::ENTITY_ID] = (string) $this->publishedBlogPost->getId();
        }
        CategoryFactory::createOne(['name' => 'Category 1']);

        $url = $this->getCrudUrl('autocomplete', null, ['autocompleteContext' => $autocompleteContext], $dashboardFqcn, $targetCrudControllerFqcn);
        $this->request($username, $url);

        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        static::assertStringNotContainsString('user1@example.com', (string) $this->client->getResponse()->getContent());
        static::assertStringNotContainsString('Category 1', (string) $this->client->getResponse()->getContent());
    }

    public static function provideForbiddenAutocompleteContexts(): \Generator
    {
        yield 'page that the user cannot access' => ['user', SecuredDashboardController::class, ProtectedUserCrudController::class, [
            EA::CRUD_CONTROLLER_FQCN => RestrictedFormBlogPostCrudController::class,
            'propertyName' => 'author',
            'originatingPage' => 'new',
        ]];

        yield 'page that the user cannot access for the given entity' => ['user', SecuredDashboardController::class, ProtectedUserCrudController::class, [
            EA::CRUD_CONTROLLER_FQCN => RestrictedFormBlogPostCrudController::class,
            'propertyName' => 'author',
            'originatingPage' => 'edit',
            EA::ENTITY_ID => 'replaced by the id of the published blog post',
        ]];

        yield 'page whose permission needs an entity but no entity is given' => ['user', SecuredDashboardController::class, ProtectedUserCrudController::class, [
            EA::CRUD_CONTROLLER_FQCN => RestrictedFormBlogPostCrudController::class,
            'propertyName' => 'author',
            'originatingPage' => 'edit',
        ]];

        yield 'new page denied by the entity permission' => ['user', SecuredDashboardController::class, ProtectedUserCrudController::class, [
            EA::CRUD_CONTROLLER_FQCN => EntityPermissionFormBlogPostCrudController::class,
            'propertyName' => 'author',
            'originatingPage' => 'new',
        ]];

        yield 'new page denied by the entity permission, with an entity' => ['user', SecuredDashboardController::class, ProtectedUserCrudController::class, [
            EA::CRUD_CONTROLLER_FQCN => EntityPermissionFormBlogPostCrudController::class,
            'propertyName' => 'author',
            'originatingPage' => 'new',
            EA::ENTITY_ID => 'replaced by the id of the published blog post',
        ]];

        yield 'edit page denied by the entity permission, without an entity' => ['user', SecuredDashboardController::class, ProtectedUserCrudController::class, [
            EA::CRUD_CONTROLLER_FQCN => EntityPermissionFormBlogPostCrudController::class,
            'propertyName' => 'author',
            'originatingPage' => 'edit',
        ]];

        yield 'detail page denied by the entity permission, without an entity' => ['user', SecuredDashboardController::class, ProtectedUserCrudController::class, [
            EA::CRUD_CONTROLLER_FQCN => EntityPermissionFormBlogPostCrudController::class,
            'propertyName' => 'author',
            'originatingPage' => 'detail',
        ]];

        yield 'page name in another case' => ['user', SecuredDashboardController::class, ProtectedUserCrudController::class, [
            EA::CRUD_CONTROLLER_FQCN => RestrictedFormBlogPostCrudController::class,
            'propertyName' => 'author',
            'originatingPage' => 'NEW',
        ]];

        yield 'CRUD controller that is not available in the current dashboard' => ['admin', GuestDashboardController::class, GuestUserCrudController::class, [
            EA::CRUD_CONTROLLER_FQCN => RestrictedFormBlogPostCrudController::class,
            'propertyName' => 'author',
            'originatingPage' => 'new',
        ]];

        yield 'query builder of another entity' => ['user', SecuredDashboardController::class, ProtectedUserCrudController::class, [
            EA::CRUD_CONTROLLER_FQCN => RepositoryQueryBuilderBlogPostCrudController::class,
            'propertyName' => 'categories',
            'originatingPage' => 'new',
        ]];
    }

    public function testWidgetOfPageProtectedByTheEntityPermission(): void
    {
        $crawler = $this->request('admin', $this->generateNewFormUrl(controllerFqcn: EntityPermissionFormBlogPostCrudController::class));
        static::assertSame([self::USER_LABEL_WITH_EMAIL], $this->requestAutocompleteResults('admin', $this->getAutocompleteUrl($crawler, 'BlogPost[author][autocomplete]')));

        $crawler = $this->request('admin', $this->generateEditFormUrl($this->publishedBlogPost->getId(), controllerFqcn: EntityPermissionFormBlogPostCrudController::class));
        static::assertSame([self::USER_LABEL_WITH_EMAIL], $this->requestAutocompleteResults('admin', $this->getAutocompleteUrl($crawler, 'BlogPost[author][autocomplete]')));
    }

    public function testNewPageIgnoresTheGivenEntity(): void
    {
        $url = $this->getCrudUrl('autocomplete', null, ['autocompleteContext' => [
            EA::CRUD_CONTROLLER_FQCN => VoterEntityPermissionFormBlogPostCrudController::class,
            'propertyName' => 'author',
            'originatingPage' => 'new',
            EA::ENTITY_ID => (string) $this->unpublishedBlogPost->getId(),
        ]], SecuredDashboardController::class, ProtectedUserCrudController::class);
        $this->request('user', $url);

        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        static::assertStringNotContainsString('user1@example.com', (string) $this->client->getResponse()->getContent());
    }

    public function testFieldThatTheUserCannotSeeIsIgnored(): void
    {
        CategoryFactory::createOne(['name' => 'Category 1']);

        $url = $this->getCrudUrl('autocomplete', null, ['autocompleteContext' => [
            EA::CRUD_CONTROLLER_FQCN => RestrictedFormBlogPostCrudController::class,
            'propertyName' => 'publisher',
            'originatingPage' => 'edit',
            EA::ENTITY_ID => (string) $this->unpublishedBlogPost->getId(),
        ]], SecuredDashboardController::class, ProtectedUserCrudController::class);

        static::assertSame([self::USER_LABEL], $this->requestAutocompleteResults('user', $url));
        static::assertSame([self::USER_LABEL_WITH_EMAIL], $this->requestAutocompleteResults('admin', $url));
    }

    public function testFieldInsideATabThatTheUserCannotSeeIsIgnored(): void
    {
        $url = $this->getCrudUrl('autocomplete', null, ['autocompleteContext' => [
            EA::CRUD_CONTROLLER_FQCN => RestrictedTabBlogPostCrudController::class,
            'propertyName' => 'publisher',
            'originatingPage' => 'edit',
            EA::ENTITY_ID => (string) $this->unpublishedBlogPost->getId(),
        ]], SecuredDashboardController::class, ProtectedUserCrudController::class);

        static::assertSame([self::USER_LABEL], $this->requestAutocompleteResults('user', $url));
        static::assertSame([self::USER_LABEL_WITH_EMAIL], $this->requestAutocompleteResults('admin', $url));

        $authorUrl = $this->getCrudUrl('autocomplete', null, ['autocompleteContext' => [
            EA::CRUD_CONTROLLER_FQCN => RestrictedTabBlogPostCrudController::class,
            'propertyName' => 'author',
            'originatingPage' => 'edit',
            EA::ENTITY_ID => (string) $this->unpublishedBlogPost->getId(),
        ]], SecuredDashboardController::class, ProtectedUserCrudController::class);

        static::assertSame([self::USER_LABEL_WITH_EMAIL], $this->requestAutocompleteResults('user', $authorUrl));
    }

    public function testWidgetOfQueryBuilderOfTheTargetEntityKeepsWorking(): void
    {
        CategoryFactory::createOne(['name' => 'Category 1']);

        $crawler = $this->request('admin', $this->generateNewFormUrl(controllerFqcn: RepositoryQueryBuilderBlogPostCrudController::class));
        $results = $this->requestAutocompleteResults('admin', $this->getAutocompleteUrl($crawler, 'BlogPost[categories][autocomplete][]'));

        static::assertSame(['Category 1'], $results);
    }

    public function testWidgetOfEmbeddedFormAppliesTheOptionsOfTheEmbeddedField(): void
    {
        CategoryFactory::createOne(['name' => 'Category <1>']);

        $crawler = $this->request('admin', $this->generateNewFormUrl(controllerFqcn: EmbeddedAuthorBlogPostCrudController::class));

        static::assertSame(
            ['<strong>Category &lt;1&gt;</strong>'],
            $this->requestAutocompleteResults('admin', $this->getAutocompleteUrl($crawler, 'BlogPost[author][favoriteCategory][autocomplete]')),
        );
        static::assertSame(
            ['<em>Category &lt;1&gt;</em>'],
            $this->requestAutocompleteResults('admin', $this->getAutocompleteUrl($crawler, 'BlogPost[publisher][favoriteCategory][autocomplete]')),
        );
    }

    public function testWidgetOfCollectionEntryAppliesTheOptionsOfTheEntryField(): void
    {
        CategoryFactory::createOne(['name' => 'Category <1>']);

        $crawler = $this->request('admin', $this->generateNewFormUrl(controllerFqcn: BlogPostsCollectionUserCrudController::class));
        static::assertResponseIsSuccessful();
        $prototype = new Crawler((string) $crawler->filter('[data-prototype]')->attr('data-prototype'));
        $widget = $prototype->filter('select[name="User[blogPosts][__blogPostsname__][categories][autocomplete][]"]');
        static::assertCount(1, $widget);

        static::assertSame(
            ['<strong>Category &lt;1&gt;</strong>'],
            $this->requestAutocompleteResults('admin', (string) $widget->attr('data-ea-autocomplete-endpoint-url')),
        );
    }

    public function testWidgetOfNestedPropertyAppliesTheFieldOptions(): void
    {
        $category = CategoryFactory::createOne(['name' => 'Category <1>']);
        $author = UserFactory::createOne(['name' => 'User 2', 'favoriteCategory' => $category]);
        $blogPost = BlogPostFactory::createOne(['author' => $author]);

        $crawler = $this->request('admin', $this->generateEditFormUrl($blogPost->getId(), controllerFqcn: NestedFavoriteCategoryBlogPostCrudController::class));
        $autocompleteUrl = $this->getAutocompleteUrl($crawler, 'BlogPost[author_favoriteCategory][autocomplete]');

        static::assertStringContainsString('autocompleteContext%5BpropertyName%5D=author.favoriteCategory', $autocompleteUrl);
        static::assertSame(['<strong>Category &lt;1&gt;</strong>'], $this->requestAutocompleteResults('admin', $autocompleteUrl));
    }

    private function request(string $username, string $url): Crawler
    {
        return $this->client->request('GET', $url, [], [], ['PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => '1234']);
    }

    /**
     * @return list<string>
     */
    private function requestAutocompleteResults(string $username, string $url): array
    {
        $this->request($username, $url);
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
}
