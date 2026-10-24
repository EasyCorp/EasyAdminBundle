<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Collection;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm\BlogPostNestedCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm\UserWithNestedBlogPostsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm\UserWithNullCreateEntityBlogPostsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;
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
     * Tests that the embedded CRUD controller's createEntity() override shapes the
     * prototype HTML, so users see their defaults in the rendered form (see #6991).
     */
    public function testNestedCreateEntityShapesPrototypeHtml(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());
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
