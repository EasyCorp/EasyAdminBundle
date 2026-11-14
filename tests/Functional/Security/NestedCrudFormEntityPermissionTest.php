<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\NestedCrudForm\BlogPostWithActionRestrictedCategoriesCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\NestedCrudForm\BlogPostWithAuthorAndRestrictedFavoriteCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\NestedCrudForm\BlogPostWithRestrictedCategoriesCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\NestedCrudForm\CategoryWithBlogPostsAndRestrictedAuthorFavoriteCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\NestedCrudForm\UserWithBlogPostsAndRestrictedCategoriesCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\NestedCrudForm\UserWithFavoriteCategoryAndRestrictedBlogPostsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\NestedCrudForm\UserWithRestrictedBlogPostsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\NestedCrudForm\UserWithRestrictedFavoriteCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecuredDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The CRUD forms nested in the form of another controller (AssociationField::renderAsEmbeddedForm()
 * and CollectionField::useEntryCrudForm()) apply the entity permission of their own controller:
 * the denied entities are not displayed, changed, unlinked or removed, and new entities can't be
 * created when the entity permission denies the new page of that controller.
 *
 * RestrictedCategoryCrudController grants every category to admins and only the active ones to
 * other users; RestrictedBlogPostCrudController grants blog posts only to admins. The other
 * controllers define no permissions.
 */
class NestedCrudFormEntityPermissionTest extends AbstractCrudTestCase
{
    private const USER = ['PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => '1234'];
    private const ADMIN = ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234'];

    private int $userId;
    private int $blogPostId;
    private int $deniedCategoryId;
    private int $grantedCategoryId;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return UserWithRestrictedBlogPostsCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return SecuredDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $deniedCategory = CategoryFactory::createOne(['name' => 'Category Denied', 'slug' => 'category-denied', 'active' => false]);
        $grantedCategory = CategoryFactory::createOne(['name' => 'Category Granted', 'slug' => 'category-granted', 'active' => true]);
        $user = UserFactory::createOne(['name' => 'User 1', 'favoriteCategory' => $deniedCategory]);
        $blogPost = BlogPostFactory::createOne(['title' => 'Blog Post Denied', 'author' => $user, 'categories' => [$deniedCategory, $grantedCategory]]);

        $this->userId = $user->getId();
        $this->blogPostId = $blogPost->getId();
        $this->deniedCategoryId = $deniedCategory->getId();
        $this->grantedCategoryId = $grantedCategory->getId();

        // the inverse sides of the associations are only filled when the entities are loaded again
        $this->entityManager->clear();
    }

    /**
     * @param class-string $controllerFqcn
     */
    #[DataProvider('provideNestedForms')]
    public function testDeniedEntitiesAreNotDisplayedNorChanged(string $controllerFqcn, string $rootEntity, string $deniedValue, string $deniedFieldName): void
    {
        $editUrl = $this->generateEditFormUrl($this->getRootEntityId($rootEntity), controllerFqcn: $controllerFqcn);

        $this->client->request('GET', $editUrl, [], [], self::ADMIN);
        static::assertResponseIsSuccessful();
        static::assertStringContainsString($deniedValue, $this->client->getResponse()->getContent());

        $crawler = $this->client->request('GET', $editUrl, [], [], self::USER);
        static::assertResponseIsSuccessful();
        static::assertStringNotContainsString($deniedValue, $this->client->getResponse()->getContent());

        parse_str($deniedFieldName.'=Changed', $deniedFormValues);
        $formValues = array_replace_recursive($crawler->filter($this->getEntityFormSelector())->form()->getPhpValues(), $deniedFormValues);
        $this->client->request('POST', $editUrl, $formValues, [], self::USER);
        static::assertResponseRedirects();

        $this->assertFixturesAreUnchanged();
    }

    public static function provideNestedForms(): \Generator
    {
        yield 'embedded association' => [UserWithRestrictedFavoriteCategoryCrudController::class, 'user', 'Category Denied', 'User[favoriteCategory][name]'];
        yield 'collection entries' => [BlogPostWithRestrictedCategoriesCrudController::class, 'blog_post', 'Category Denied', 'BlogPost[categories][0][name]'];
        yield 'collection entries with orphan removal' => [UserWithRestrictedBlogPostsCrudController::class, 'user', 'Blog Post Denied', 'User[blogPosts][0][title]'];
        yield 'collection entries inside an embedded association' => [UserWithFavoriteCategoryAndRestrictedBlogPostsCrudController::class, 'user', 'Blog Post Denied', 'User[favoriteCategory][blogPosts][0][title]'];
        yield 'collection entries inside collection entries' => [UserWithBlogPostsAndRestrictedCategoriesCrudController::class, 'user', 'Category Denied', 'User[blogPosts][0][categories][0][name]'];
        yield 'embedded association inside an embedded association' => [BlogPostWithAuthorAndRestrictedFavoriteCategoryCrudController::class, 'blog_post', 'Category Denied', 'BlogPost[author][favoriteCategory][name]'];
        yield 'embedded association inside collection entries' => [CategoryWithBlogPostsAndRestrictedAuthorFavoriteCategoryCrudController::class, 'granted_category', 'Category Denied', 'Category[blogPosts][0][author][favoriteCategory][name]'];
    }

    /**
     * @param class-string $controllerFqcn
     */
    #[DataProvider('provideCraftedSubmissions')]
    public function testDeniedEntitiesAreNotChangedByCraftedSubmissions(string $controllerFqcn, string $rootEntity, string $submittedData): void
    {
        $editUrl = $this->generateEditFormUrl($this->getRootEntityId($rootEntity), controllerFqcn: $controllerFqcn);
        $crawler = $this->client->request('GET', $editUrl, [], [], self::USER);
        $formValues = $crawler->filter($this->getEntityFormSelector())->form()->getPhpValues();
        $formName = array_key_first($formValues);

        parse_str($submittedData, $craftedFormValues);
        $formValues[$formName] = array_replace($formValues[$formName], $craftedFormValues[$formName]);
        $this->client->request('POST', $editUrl, $formValues, [], self::USER);
        static::assertResponseRedirects();

        $this->assertFixturesAreUnchanged();
    }

    public static function provideCraftedSubmissions(): \Generator
    {
        yield 'entries omitted (orphan removal)' => [UserWithRestrictedBlogPostsCrudController::class, 'user', 'User[blogPosts]='];
        yield 'entries omitted (many-to-many)' => [BlogPostWithRestrictedCategoriesCrudController::class, 'blog_post', 'BlogPost[categories]='];
        yield 'one entry replaced by a new one' => [BlogPostWithRestrictedCategoriesCrudController::class, 'blog_post', 'BlogPost[categories][0][name]=Changed&BlogPost[categories][2][name]=New&BlogPost[categories][2][slug]=new'];
        yield 'embedded association emptied' => [UserWithRestrictedFavoriteCategoryCrudController::class, 'user', 'User[favoriteCategory]='];
    }

    public function testEntriesGrantedByTheEntityPermissionCanBeEdited(): void
    {
        $blogPost = BlogPostFactory::createOne(['title' => 'Blog Post 2', 'author' => $this->entityManager->find(User::class, $this->userId), 'categories' => [$this->entityManager->find(Category::class, $this->grantedCategoryId)]]);
        $editUrl = $this->generateEditFormUrl($blogPost->getId(), controllerFqcn: BlogPostWithRestrictedCategoriesCrudController::class);

        $crawler = $this->client->request('GET', $editUrl, [], [], self::USER);
        static::assertSame(['Category Granted'], $crawler->filter('input[name="BlogPost[categories][0][name]"]')->extract(['value']));

        $form = $crawler->filter($this->getEntityFormSelector())->form(['BlogPost[categories][0][name]' => 'Category Granted Changed']);
        $this->client->submit($form, [], self::USER);
        static::assertResponseRedirects();

        $this->entityManager->clear();
        static::assertSame('Category Granted Changed', $this->entityManager->find(Category::class, $this->grantedCategoryId)->getName());
    }

    public function testUsersWithTheEntityPermissionCanEditAndRemoveEntries(): void
    {
        $editUrl = $this->generateEditFormUrl($this->userId);

        $crawler = $this->client->request('GET', $editUrl, [], [], self::ADMIN);
        $form = $crawler->filter($this->getEntityFormSelector())->form(['User[blogPosts][0][title]' => 'Blog Post Changed']);
        $this->client->submit($form, [], self::ADMIN);
        static::assertResponseRedirects();

        $this->entityManager->clear();
        static::assertSame('Blog Post Changed', $this->entityManager->find(BlogPost::class, $this->blogPostId)->getTitle());

        $crawler = $this->client->request('GET', $editUrl, [], [], self::ADMIN);
        $formValues = $crawler->filter($this->getEntityFormSelector())->form()->getPhpValues();
        unset($formValues['User']['blogPosts']);
        $this->client->request('POST', $editUrl, $formValues, [], self::ADMIN);
        static::assertResponseRedirects();

        $this->entityManager->clear();
        static::assertNull($this->entityManager->find(BlogPost::class, $this->blogPostId));
    }

    public function testCollectionEntriesCantBeAddedWhenTheEntityPermissionDeniesNewEntities(): void
    {
        $blogPost = BlogPostFactory::createOne(['title' => 'Blog Post 2', 'author' => $this->entityManager->find(User::class, $this->userId)]);
        $editUrl = $this->generateEditFormUrl($blogPost->getId(), controllerFqcn: BlogPostWithRestrictedCategoriesCrudController::class);
        $newEntry = ['name' => 'Category New', 'slug' => 'category-new'];

        $crawler = $this->client->request('GET', $editUrl, [], [], self::USER);
        static::assertSame('false', $crawler->filter('[data-ea-collection-field]')->attr('data-allow-add'));
        $formValues = $crawler->filter($this->getEntityFormSelector())->form()->getPhpValues();
        $formValues['BlogPost']['categories'] = [$newEntry];
        $this->client->request('POST', $editUrl, $formValues, [], self::USER);
        // the collection doesn't allow adding entries, so Symfony rejects the new entry as an extra field
        static::assertResponseIsUnprocessable();
        static::assertSame(0, $this->entityManager->getRepository(Category::class)->count(['name' => 'Category New']));

        // the association doesn't cascade persist, so this only checks that the new entries are allowed
        $crawler = $this->client->request('GET', $editUrl, [], [], self::ADMIN);
        static::assertSame('true', $crawler->filter('[data-ea-collection-field]')->attr('data-allow-add'));
    }

    public function testEmbeddedEntitiesCantBeCreatedWhenTheEntityPermissionDeniesNewEntities(): void
    {
        $user = UserFactory::createOne(['name' => 'User 2']);
        $editUrl = $this->generateEditFormUrl($user->getId(), controllerFqcn: UserWithRestrictedFavoriteCategoryCrudController::class);
        $newCategory = ['name' => 'Category New', 'slug' => 'category-new'];

        $crawler = $this->client->request('GET', $editUrl, [], [], self::USER);
        static::assertCount(0, $crawler->filter('input[name="User[favoriteCategory][name]"]'));
        $formValues = $crawler->filter($this->getEntityFormSelector())->form()->getPhpValues();
        $formValues['User']['favoriteCategory'] = $newCategory;
        $this->client->request('POST', $editUrl, $formValues, [], self::USER);
        static::assertResponseRedirects();

        $this->entityManager->clear();
        static::assertNull($this->entityManager->find(User::class, $user->getId())->getFavoriteCategory());
        static::assertSame(0, $this->entityManager->getRepository(Category::class)->count(['name' => 'Category New']));

        // the association doesn't cascade persist, so this only checks that the new entity form is displayed
        $crawler = $this->client->request('GET', $editUrl, [], [], self::ADMIN);
        static::assertCount(1, $crawler->filter('input[name="User[favoriteCategory][name]"]'));
    }

    public function testActionPermissionsOfTheNestedFormControllerAreNotApplied(): void
    {
        $editUrl = $this->generateEditFormUrl($this->blogPostId, controllerFqcn: BlogPostWithActionRestrictedCategoriesCrudController::class);

        $crawler = $this->client->request('GET', $editUrl, [], [], self::USER);
        static::assertSame(['Category Denied'], $crawler->filter('input[name="BlogPost[categories][0][name]"]')->extract(['value']));

        $form = $crawler->filter($this->getEntityFormSelector())->form(['BlogPost[categories][0][name]' => 'Category Changed']);
        $this->client->submit($form, [], self::USER);
        static::assertResponseRedirects();

        $this->entityManager->clear();
        static::assertSame('Category Changed', $this->entityManager->find(Category::class, $this->deniedCategoryId)->getName());
    }

    private function getRootEntityId(string $rootEntity): int
    {
        return match ($rootEntity) {
            'user' => $this->userId,
            'blog_post' => $this->blogPostId,
            'granted_category' => $this->grantedCategoryId,
        };
    }

    private function assertFixturesAreUnchanged(): void
    {
        // the requests used this same entity manager: read the values from the database, not from its identity map
        $this->entityManager->clear();

        $deniedCategory = $this->entityManager->find(Category::class, $this->deniedCategoryId);
        static::assertSame('Category Denied', $deniedCategory->getName());

        $blogPost = $this->entityManager->find(BlogPost::class, $this->blogPostId);
        static::assertNotNull($blogPost);
        static::assertSame('Blog Post Denied', $blogPost->getTitle());
        static::assertSame($this->userId, $blogPost->getAuthor()->getId());
        static::assertEqualsCanonicalizing(
            [$this->deniedCategoryId, $this->grantedCategoryId],
            $blogPost->getCategories()->map(static fn (Category $category): int => $category->getId())->toArray(),
        );

        static::assertSame($this->deniedCategoryId, $this->entityManager->find(User::class, $this->userId)->getFavoriteCategory()?->getId());
        static::assertSame(2, $this->entityManager->getRepository(Category::class)->count([]));
        static::assertSame(1, $this->entityManager->getRepository(BlogPost::class)->count([]));
    }
}
