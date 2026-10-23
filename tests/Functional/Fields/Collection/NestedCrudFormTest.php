<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Collection;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm\BlogPostNestedCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm\UserWithNestedBlogPostsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm\UserWithSideEffectBlogPostsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm\UserWithTwoLevelNestedBlogPostsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm\UserWithNullCreateEntityBlogPostsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Tests that CollectionField with useEntryCrudForm() works correctly when the nested
 * CRUD controller accesses $context->getEntity() in configureFields().
 */
class NestedCrudFormTest extends AbstractCrudTestCase
{
    protected function setUp(): void
    {
        // useEntryCrudForm() requires the 'prototype_options' option, added in Symfony 6.1
        if (Kernel::MAJOR_VERSION < 6 || (6 === Kernel::MAJOR_VERSION && Kernel::MINOR_VERSION < 1)) {
            $this->markTestSkipped('useEntryCrudForm() requires Symfony 6.1 or newer.');
        }

        parent::setUp();
        $this->client->followRedirects();
    }

    protected function getControllerFqcn(): string
    {
        return UserWithNestedBlogPostsCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    /**
     * Tests that the NEW form page loads correctly with nested CRUD forms.
     *
     * The nested BlogPostNestedCrudController accesses $context->getEntity() in
     * configureFields(). Before the fix, this would fail because the context would
     * contain the parent entity (User) instead of the nested entity (BlogPost).
     */
    public function testNewFormLoadsWithNestedCrudForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="User"]');
        static::assertCount(1, $form, 'The User form should exist');

        $collectionField = $crawler->filter('.field-collection');
        static::assertGreaterThanOrEqual(1, $collectionField->count(), 'The collection field should exist');
    }

    /**
     * Tests that the EDIT form page loads correctly with nested CRUD forms.
     */
    public function testEditFormLoadsWithNestedCrudForm(): void
    {
        $user = $this->createUserWithBlogPosts();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($user->getId()));

        static::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="User"]');
        static::assertCount(1, $form, 'The User form should exist');

        $html = $crawler->html();
        static::assertStringContainsString('Blog post 1', $html, 'First blog post should be visible');
        static::assertStringContainsString('Blog post 2', $html, 'Second blog post should be visible');
    }

    /**
     * Regression for #7721: a prototype must not be included in an existing collection
     * merely because its factory synchronizes the bidirectional relation.
     */
    public function testEditDoesNotAddPrototypeToManagedCollection(): void
    {
        $user = $this->createUserWithBlogPosts();
        static::assertCount(2, $user->getBlogPosts());
        $callsBefore = BlogPostNestedCrudController::$createEntityCallCount;

        $crawler = $this->client->request('GET', $this->generateEditFormUrl(
            $user->getId(),
            controllerFqcn: UserWithSideEffectBlogPostsCrudController::class,
        ));
        static::assertResponseIsSuccessful();

        $html = $crawler->html();
        static::assertStringContainsString('Blog post 1', $html);
        static::assertStringContainsString('Blog post 2', $html);
        static::assertCount(2, $user->getBlogPosts(), 'Viewing EDIT must not add a prototype to a managed collection.');
        static::assertSame($callsBefore, BlogPostNestedCrudController::$createEntityCallCount,
            'Viewing EDIT must not invoke the nested createEntity() at all.');
    }

    /**
     * Saving unchanged entries must not persist a phantom entry either.
     */
    public function testSavingEditWithoutAddedItemsDoesNotCreateExtraEntries(): void
    {
        $user = $this->createUserWithBlogPosts();
        $userId = $user->getId();
        $crawler = $this->client->request('GET', $this->generateEditFormUrl(
            $userId,
            controllerFqcn: UserWithSideEffectBlogPostsCrudController::class,
        ));
        static::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="User"]')->form();
        $this->client->request($form->getMethod(), $form->getUri(), $form->getPhpValues());
        static::assertResponseIsSuccessful();

        $this->entityManager->clear();
        $savedUser = $this->entityManager->getRepository(User::class)->find($userId);
        static::assertNotNull($savedUser);
        static::assertCount(2, $savedUser->getBlogPosts());
    }

    /**
     * Adding an entry must still invoke the factory and keep its field defaults.
     */
    public function testAddedEntryRetainsCreateEntityDefaults(): void
    {
        $user = $this->createUserWithBlogPosts();
        $userId = $user->getId();
        $crawler = $this->client->request('GET', $this->generateEditFormUrl(
            $userId,
            controllerFqcn: UserWithSideEffectBlogPostsCrudController::class,
        ));
        static::assertResponseIsSuccessful();
        $form = $crawler->filter('form[name="User"]')->form();
        $values = $form->getPhpValues();
        $values['User']['blogPosts'][] = ['title' => 'Added in regression test'];

        $this->client->request($form->getMethod(), $form->getUri(), $values);
        static::assertResponseIsSuccessful();

        $this->entityManager->clear();
        $savedUser = $this->entityManager->getRepository(User::class)->find($userId);
        static::assertNotNull($savedUser);
        static::assertCount(3, $savedUser->getBlogPosts());
        $savedPost = $this->entityManager->getRepository(BlogPost::class)->findOneBy(['title' => 'Added in regression test']);
        static::assertNotNull($savedPost);
        static::assertSame('new-blog-post', $savedPost->getSlug());
        static::assertSame('New blog post content', $savedPost->getContent());
    }

    /**
     * The requested nested collection may belong to a parent entry added in JavaScript.
     * It exists only inside the outer prototype, not in the server-rendered entries.
     */
    public function testSecondLevelPrototypeIsRenderedInsideParentPrototype(): void
    {
        $crawler = $this->client->request('GET', $this->getCrudUrl(
            Action::NEW,
            options: ['__ea_collection_prototype' => 'categories'],
            controllerFqcn: UserWithTwoLevelNestedBlogPostsCrudController::class,
        ));
        static::assertResponseIsSuccessful();
        $outerPrototype = $crawler->filter('[data-ea-collection-form-id="User_blogPosts"]')->attr('data-prototype');
        static::assertNotNull($outerPrototype);
        $inner = new Crawler($outerPrototype);
        $categoryCollection = $inner->filter('[data-ea-collection-prototype-field="categories"]');
        static::assertCount(1, $categoryCollection);
        static::assertStringContainsString('__blogPostsname__', $categoryCollection->attr('data-ea-collection-form-id'));
        static::assertSame('false', $categoryCollection->attr('data-ea-lazy-prototype'));
        static::assertNotEmpty($categoryCollection->attr('data-prototype'));
    }

    /**
     * Tests that the embedded CRUD controller's createEntity() override shapes the
     * prototype HTML, so users see their defaults in the rendered form (see #6991).
     */
    public function testNestedCreateEntityShapesPrototypeHtml(): void
    {
        // First load does not create any entity just to render a prototype.
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());
        static::assertResponseIsSuccessful();
        static::assertSame('true', $crawler->filter('[data-ea-collection-prototype-field="blogPosts"]')->attr('data-ea-lazy-prototype'));

        // The Add button requests the prototype through the existing admin page.
        $crawler = $this->client->request('GET', $this->getCrudUrl(Action::NEW, options: ['__ea_collection_prototype' => 'blogPosts']));
        static::assertResponseIsSuccessful();

        $prototype = $crawler->filter('[data-prototype]');
        static::assertGreaterThanOrEqual(1, $prototype->count(), 'A collection prototype should exist');
        static::assertStringContainsString(
            BlogPostNestedCrudController::DEFAULT_ENTRY_TITLE,
            $prototype->attr('data-prototype'),
            'createEntity() defaults from the embedded controller should be reflected in the prototype HTML',
        );
    }

    /**
     * Tests that the embedded CRUD controller's createEntity() is reached when Symfony
     * Form binds a new (empty) collection entry on submit, i.e. that
     * `entry_options.empty_data` is wired in addition to `prototype_data` (see #6991).
     */
    public function testNestedCreateEntityIsCalledForEmptyDataOnSubmit(): void
    {
        $user = $this->createUserWithBlogPosts();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($user->getId()));
        static::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="User"]')->form();
        $values = $form->getPhpValues();
        // simulate the JS "add entry" behaviour: append an entry with no submitted fields
        // so Symfony Form has to invoke entry_options.empty_data to materialise the new
        // BlogPost (which then routes to the embedded controller's createEntity())
        $values['User']['blogPosts'][] = [];

        $callsBeforeSubmit = BlogPostNestedCrudController::$createEntityCallCount;
        try {
            // form binding may fail on this fixture (the new BlogPost's `title` is
            // required and not submitted, so PropertyAccessor will refuse to set null);
            // this only checks that empty_data was reached before the binding step ran
            $this->client->catchExceptions(false);
            $this->client->request($form->getMethod(), $form->getUri(), $values, [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);
        } catch (\Throwable) {
            // expected on this fixture, see comment above
        }

        static::assertGreaterThan(
            $callsBeforeSubmit,
            BlogPostNestedCrudController::$createEntityCallCount,
            'createEntity() should be invoked through entry_options.empty_data when a new entry is bound',
        );
    }

    /**
     * Tests that an embedded CRUD controller whose createEntity() returns null doesn't
     * break the form and that new entries are created by the default "empty_data".
     */
    public function testNestedCreateEntityReturningNull(): void
    {
        $this->client->request('GET', $this->generateNewFormUrl(controllerFqcn: UserWithNullCreateEntityBlogPostsCrudController::class));
        static::assertResponseIsSuccessful();

        $user = $this->createUserWithBlogPosts();
        $crawler = $this->client->request('GET', $this->generateEditFormUrl($user->getId(), controllerFqcn: UserWithNullCreateEntityBlogPostsCrudController::class));
        static::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="User"]')->form();
        $values = $form->getPhpValues();
        $values['User']['blogPosts'][] = [
            'title' => 'Blog post created with empty_data',
            'slug' => 'blog-post-created-with-empty-data',
            'content' => 'Blog post content',
            'createdAt' => '2020-01-01T10:00',
        ];
        $this->client->request($form->getMethod(), $form->getUri(), $values);
        static::assertResponseIsSuccessful();

        $this->entityManager->clear();
        $blogPost = $this->entityManager->getRepository(BlogPost::class)->findOneBy(['title' => 'Blog post created with empty_data']);
        static::assertNotNull($blogPost);
        static::assertSame($user->getId(), $blogPost->getAuthor()?->getId());
    }

    /**
     * Tests that renderExpanded(true) on CollectionField renders entries with the
     * accordion already expanded (Bootstrap "show" class on the collapse wrapper and
     * no "collapsed" class on the toggle button).
     */
    public function testRenderExpandedOnCollectionField(): void
    {
        $user = $this->createUserWithBlogPosts();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($user->getId()));

        static::assertResponseIsSuccessful();

        $collapseWrappers = $crawler->filter('.field-collection .accordion-collapse');
        static::assertGreaterThan(0, $collapseWrappers->count(), 'At least one accordion-collapse wrapper should be rendered');

        foreach ($collapseWrappers as $wrapper) {
            static::assertStringContainsString(
                'show',
                $wrapper->getAttribute('class'),
                'renderExpanded(true) should add the "show" class on .accordion-collapse',
            );
        }

        foreach ($crawler->filter('.field-collection .accordion-button') as $button) {
            static::assertStringNotContainsString(
                'collapsed',
                $button->getAttribute('class'),
                'renderExpanded(true) should not add the "collapsed" class on .accordion-button',
            );
        }
    }

    private function createUserWithBlogPosts(): User
    {
        return UserFactory::createOne([
            'blogPosts' => [
                BlogPostFactory::new(['title' => 'Blog post 1']),
                BlogPostFactory::new(['title' => 'Blog post 2']),
            ],
        ]);
    }
}
