<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\NestedCrudForm\BlogPostWithActionRestrictedCategoriesCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\NestedCrudForm\BlogPostWithPublisherAndRestrictedBlogPostsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\NestedCrudForm\BlogPostWithRestrictedCategoriesCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\NestedCrudForm\BlogPostWithRestrictedPublisherCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\NestedCrudForm\CategoryWithBlogPostsAndRestrictedPublisherCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\NestedCrudForm\UserWithBlogPostsAndRestrictedCategoriesCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\NestedCrudForm\UserWithRestrictedBlogPostsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecuredDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use Symfony\Component\HttpKernel\Kernel as SymfonyKernel;

/**
 * The CRUD forms nested in the form of another controller (AssociationField::renderAsEmbeddedForm()
 * and CollectionField::useEntryCrudForm()) apply the entity permission of their own controller:
 * the denied entities are not displayed, changed, unlinked or removed, and new entities can't be
 * created when the entity permission denies the new page of that controller.
 *
 * RestrictedCategoryCrudController grants every category to admins and only the active ones to
 * other users; RestrictedBlogPostCrudController and RestrictedUserCrudController grant their
 * entities only to admins. The other controllers define no permissions.
 *
 * The test app database is shared by all tests, so this test creates its own entities and
 * removes them afterwards.
 */
class NestedCrudFormEntityPermissionTest extends AbstractCrudTestCase
{
    private const USER = ['PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => '1234'];
    private const ADMIN = ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234'];

    private int $deniedUserId;
    private int $grantedUserId;
    private int $blogPostId;
    private int $rootBlogPostId;
    private int $deniedCategoryId;
    private int $grantedCategoryId;
    /** @var array<class-string, list<int>> */
    private array $createdEntityIds = [];

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
        // useEntryCrudForm() requires the 'prototype_options' option, added in Symfony 6.1
        if (SymfonyKernel::MAJOR_VERSION < 6 || (6 === SymfonyKernel::MAJOR_VERSION && SymfonyKernel::MINOR_VERSION < 1)) {
            $this->markTestSkipped('useEntryCrudForm() requires Symfony 6.1 or newer.');
        }

        parent::setUp();

        $deniedCategory = $this->createCategory('Nested Category Denied', false);
        $grantedCategory = $this->createCategory('Nested Category Granted', true);
        $deniedUser = $this->createUser('Nested User Denied');
        $grantedUser = $this->createUser('Nested User Granted');
        $blogPost = $this->createBlogPost('Nested Blog Post Denied', $deniedUser, $deniedUser, [$deniedCategory, $grantedCategory]);
        $rootBlogPost = $this->createBlogPost('Nested Blog Post Root', $grantedUser, $deniedUser);

        $this->deniedUserId = $deniedUser->getId();
        $this->grantedUserId = $grantedUser->getId();
        $this->blogPostId = $blogPost->getId();
        $this->rootBlogPostId = $rootBlogPost->getId();
        $this->deniedCategoryId = $deniedCategory->getId();
        $this->grantedCategoryId = $grantedCategory->getId();

        // the inverse sides of the associations are only filled when the entities are loaded again
        $this->entityManager->clear();
    }

    protected function tearDown(): void
    {
        if (!isset($this->entityManager)) {
            parent::tearDown();

            return;
        }

        $this->entityManager->clear();
        foreach ([BlogPost::class, User::class, Category::class] as $entityFqcn) {
            foreach ($this->createdEntityIds[$entityFqcn] ?? [] as $id) {
                if (null !== $entity = $this->entityManager->find($entityFqcn, $id)) {
                    $this->entityManager->remove($entity);
                }
            }
            $this->entityManager->flush();
        }

        parent::tearDown();
    }

    /**
     * @dataProvider provideNestedForms
     *
     * @param class-string $controllerFqcn
     */
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
        yield 'embedded association' => [BlogPostWithRestrictedPublisherCrudController::class, 'blog_post', 'Nested User Denied', 'BlogPost[publisher][name]'];
        yield 'collection entries' => [BlogPostWithRestrictedCategoriesCrudController::class, 'blog_post', 'Nested Category Denied', 'BlogPost[categories][0][name]'];
        yield 'collection entries with orphan removal' => [UserWithRestrictedBlogPostsCrudController::class, 'denied_user', 'Nested Blog Post Denied', 'User[blogPosts][0][title]'];
        yield 'collection entries inside an embedded association' => [BlogPostWithPublisherAndRestrictedBlogPostsCrudController::class, 'root_blog_post', 'Nested Blog Post Denied', 'BlogPost[publisher][blogPosts][0][title]'];
        yield 'collection entries inside collection entries' => [UserWithBlogPostsAndRestrictedCategoriesCrudController::class, 'denied_user', 'Nested Category Denied', 'User[blogPosts][0][categories][0][name]'];
        yield 'embedded association inside collection entries' => [CategoryWithBlogPostsAndRestrictedPublisherCrudController::class, 'granted_category', 'Nested User Denied', 'Category[blogPosts][0][publisher][name]'];
    }

    /**
     * @dataProvider provideCraftedSubmissions
     *
     * @param class-string $controllerFqcn
     */
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
        yield 'entries omitted (orphan removal)' => [UserWithRestrictedBlogPostsCrudController::class, 'denied_user', 'User[blogPosts]='];
        yield 'entries omitted (many-to-many)' => [BlogPostWithRestrictedCategoriesCrudController::class, 'blog_post', 'BlogPost[categories]='];
        yield 'one entry replaced by a new one' => [BlogPostWithRestrictedCategoriesCrudController::class, 'blog_post', 'BlogPost[categories][0][name]=Changed&BlogPost[categories][2][name]=New&BlogPost[categories][2][slug]=new'];
        yield 'embedded association emptied' => [BlogPostWithRestrictedPublisherCrudController::class, 'blog_post', 'BlogPost[publisher]='];
    }

    public function testEntriesGrantedByTheEntityPermissionCanBeEdited(): void
    {
        $blogPost = $this->createBlogPost('Nested Blog Post 2', $this->entityManager->find(User::class, $this->grantedUserId), null, [$this->entityManager->find(Category::class, $this->grantedCategoryId)]);
        $editUrl = $this->generateEditFormUrl($blogPost->getId(), controllerFqcn: BlogPostWithRestrictedCategoriesCrudController::class);

        $crawler = $this->client->request('GET', $editUrl, [], [], self::USER);
        static::assertSame(['Nested Category Granted'], $crawler->filter('input[name="BlogPost[categories][0][name]"]')->extract(['value']));

        $form = $crawler->filter($this->getEntityFormSelector())->form(['BlogPost[categories][0][name]' => 'Nested Category Granted Changed']);
        $this->client->submit($form, [], self::USER);
        static::assertResponseRedirects();

        $this->entityManager->clear();
        static::assertSame('Nested Category Granted Changed', $this->entityManager->find(Category::class, $this->grantedCategoryId)->getName());
    }

    public function testUsersWithTheEntityPermissionCanEditAndRemoveEntries(): void
    {
        $editUrl = $this->generateEditFormUrl($this->deniedUserId);

        $crawler = $this->client->request('GET', $editUrl, [], [], self::ADMIN);
        $form = $crawler->filter($this->getEntityFormSelector())->form(['User[blogPosts][0][title]' => 'Nested Blog Post Changed']);
        $this->client->submit($form, [], self::ADMIN);
        static::assertResponseRedirects();

        $this->entityManager->clear();
        static::assertSame('Nested Blog Post Changed', $this->entityManager->find(BlogPost::class, $this->blogPostId)->getTitle());

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
        $blogPost = $this->createBlogPost('Nested Blog Post 2', $this->entityManager->find(User::class, $this->grantedUserId));
        $editUrl = $this->generateEditFormUrl($blogPost->getId(), controllerFqcn: BlogPostWithRestrictedCategoriesCrudController::class);
        $newEntry = ['name' => 'Nested Category New', 'slug' => 'nested-category-new'];

        $crawler = $this->client->request('GET', $editUrl, [], [], self::USER);
        static::assertSame('false', $crawler->filter('[data-ea-collection-field]')->attr('data-allow-add'));
        $formValues = $crawler->filter($this->getEntityFormSelector())->form()->getPhpValues();
        $formValues['BlogPost']['categories'] = [$newEntry];
        $this->client->request('POST', $editUrl, $formValues, [], self::USER);
        // the collection doesn't allow adding entries, so Symfony rejects the new entry as an extra field
        static::assertResponseIsUnprocessable();
        static::assertSame(0, $this->entityManager->getRepository(Category::class)->count(['name' => 'Nested Category New']));

        // the association doesn't cascade persist, so this only checks that the new entries are allowed
        $crawler = $this->client->request('GET', $editUrl, [], [], self::ADMIN);
        static::assertSame('true', $crawler->filter('[data-ea-collection-field]')->attr('data-allow-add'));
    }

    public function testEmbeddedEntitiesCantBeCreatedWhenTheEntityPermissionDeniesNewEntities(): void
    {
        $blogPost = $this->createBlogPost('Nested Blog Post 2', $this->entityManager->find(User::class, $this->grantedUserId));
        $editUrl = $this->generateEditFormUrl($blogPost->getId(), controllerFqcn: BlogPostWithRestrictedPublisherCrudController::class);
        $newPublisher = ['name' => 'Nested User New', 'email' => 'nested-user-new@example.com'];

        $crawler = $this->client->request('GET', $editUrl, [], [], self::USER);
        static::assertCount(0, $crawler->filter('input[name="BlogPost[publisher][name]"]'));
        $formValues = $crawler->filter($this->getEntityFormSelector())->form()->getPhpValues();
        $formValues['BlogPost']['publisher'] = $newPublisher;
        $this->client->request('POST', $editUrl, $formValues, [], self::USER);
        static::assertResponseRedirects();

        $this->entityManager->clear();
        static::assertNull($this->entityManager->find(BlogPost::class, $blogPost->getId())->getPublisher());
        static::assertSame(0, $this->entityManager->getRepository(User::class)->count(['name' => 'Nested User New']));

        // the association doesn't cascade persist, so this only checks that the new entity form is displayed
        $crawler = $this->client->request('GET', $editUrl, [], [], self::ADMIN);
        static::assertCount(1, $crawler->filter('input[name="BlogPost[publisher][name]"]'));
    }

    public function testActionPermissionsOfTheNestedFormControllerAreNotApplied(): void
    {
        $editUrl = $this->generateEditFormUrl($this->blogPostId, controllerFqcn: BlogPostWithActionRestrictedCategoriesCrudController::class);

        $crawler = $this->client->request('GET', $editUrl, [], [], self::USER);
        static::assertSame(['Nested Category Denied'], $crawler->filter('input[name="BlogPost[categories][0][name]"]')->extract(['value']));

        $form = $crawler->filter($this->getEntityFormSelector())->form(['BlogPost[categories][0][name]' => 'Nested Category Changed']);
        $this->client->submit($form, [], self::USER);
        static::assertResponseRedirects();

        $this->entityManager->clear();
        static::assertSame('Nested Category Changed', $this->entityManager->find(Category::class, $this->deniedCategoryId)->getName());
    }

    private function getRootEntityId(string $rootEntity): int
    {
        return match ($rootEntity) {
            'denied_user' => $this->deniedUserId,
            'blog_post' => $this->blogPostId,
            'root_blog_post' => $this->rootBlogPostId,
            'granted_category' => $this->grantedCategoryId,
        };
    }

    private function assertFixturesAreUnchanged(): void
    {
        // the requests used this same entity manager: read the values from the database, not from its identity map
        $this->entityManager->clear();

        static::assertSame('Nested Category Denied', $this->entityManager->find(Category::class, $this->deniedCategoryId)->getName());
        static::assertSame('Nested User Denied', $this->entityManager->find(User::class, $this->deniedUserId)->getName());

        $blogPost = $this->entityManager->find(BlogPost::class, $this->blogPostId);
        static::assertNotNull($blogPost);
        static::assertSame('Nested Blog Post Denied', $blogPost->getTitle());
        static::assertSame($this->deniedUserId, $blogPost->getAuthor()->getId());
        static::assertSame($this->deniedUserId, $blogPost->getPublisher()?->getId());
        static::assertEqualsCanonicalizing(
            [$this->deniedCategoryId, $this->grantedCategoryId],
            $blogPost->getCategories()->map(static fn (Category $category): int => $category->getId())->toArray(),
        );

        static::assertSame($this->deniedUserId, $this->entityManager->find(BlogPost::class, $this->rootBlogPostId)->getPublisher()?->getId());
        static::assertSame(0, $this->entityManager->getRepository(Category::class)->count(['name' => 'New']));
    }

    private function createCategory(string $name, bool $active): Category
    {
        $category = (new Category())
            ->setName($name)
            ->setSlug(strtolower(str_replace(' ', '-', $name)))
            ->setActive($active);

        return $this->persist($category);
    }

    private function createUser(string $name): User
    {
        $user = (new User())
            ->setName($name)
            ->setEmail(strtolower(str_replace(' ', '-', $name)).'@example.com');

        return $this->persist($user);
    }

    /**
     * @param list<Category> $categories
     */
    private function createBlogPost(string $title, User $author, ?User $publisher = null, array $categories = []): BlogPost
    {
        $blogPost = (new BlogPost())
            ->setTitle($title)
            ->setSlug(strtolower(str_replace(' ', '-', $title)))
            ->setContent('Lorem ipsum')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setAuthor($author);
        $blogPost->setPublisher($publisher);
        foreach ($categories as $category) {
            $blogPost->addCategory($category);
        }

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
        $this->createdEntityIds[$entity::class][] = $entity->getId();

        return $entity;
    }
}
