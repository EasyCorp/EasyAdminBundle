<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Collection;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm\BlogPostWithCyclicAuthorCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm\BlogPostWithEmbeddedAuthorCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm\BlogPostWithEmbeddedAuthorFavoriteCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm\CategoryWithNestedBlogPostsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;

/**
 * Tests that a CRUD form embedded with AssociationField::renderAsEmbeddedForm() in another
 * nested CRUD form (a collection entry, another embedded form or a nested property path) is
 * built for the entity that it edits: the EDIT page of its CRUD controller for existing
 * entities and the NEW page otherwise.
 *
 * In the CRUD controllers used by this test, the email of users and the slug of categories
 * are only part of the forms used to create them, and the description of categories is only
 * part of the form used to edit them.
 */
class EmbeddedFormInNestedCrudFormTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return CategoryWithNestedBlogPostsCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testFormEmbeddedInCollectionEntryEditsExistingEntitiesWithTheEditPage(): void
    {
        $blogPost = $this->createBlogPost(withFavoriteCategory: true);
        $category = $blogPost->getCategories()->first();
        $author = $blogPost->getAuthor();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($category->getId()));
        static::assertResponseIsSuccessful();

        static::assertCount(1, $crawler->filter('input[name="Category[blogPosts][0][title]"]'));
        // collection entry > embedded form
        static::assertSame('User 1', $crawler->filter('input[name="Category[blogPosts][0][author][name]"]')->attr('value'));
        static::assertCount(0, $crawler->filter('input[name="Category[blogPosts][0][author][email]"]'));
        // collection entry > embedded form > embedded form
        static::assertSame('Category 2', $crawler->filter('input[name="Category[blogPosts][0][author][favoriteCategory][name]"]')->attr('value'));
        static::assertCount(0, $crawler->filter('input[name="Category[blogPosts][0][author][favoriteCategory][slug]"]'));
        static::assertSame('Description 2', $crawler->filter('input[name="Category[blogPosts][0][author][favoriteCategory][description]"]')->attr('value'));

        // the fields that are not part of the EDIT page are ignored when submitting them
        $values = $crawler->filter('form[name="Category"]')->form()->getPhpValues();
        $values['Category']['blogPosts'][0]['author']['name'] = 'User 1 changed';
        $values['Category']['blogPosts'][0]['author']['email'] = 'changed@example.com';
        $values['Category']['blogPosts'][0]['author']['favoriteCategory']['slug'] = 'changed';
        $values['Category']['blogPosts'][0]['author']['favoriteCategory']['description'] = 'Description 2 changed';
        $this->client->request('POST', $this->generateEditFormUrl($category->getId()), $values);
        static::assertResponseRedirects();

        $this->entityManager->clear();
        $author = $this->entityManager->find(User::class, $author->getId());
        static::assertSame('User 1 changed', $author->getName());
        static::assertSame('user1@example.com', $author->getEmail());
        static::assertSame('category-2', $author->getFavoriteCategory()->getSlug());
        static::assertSame('Description 2 changed', $author->getFavoriteCategory()->getDescription());
    }

    public function testFormEmbeddedInNewCollectionEntryUsesTheNewPage(): void
    {
        $blogPost = $this->createBlogPost(withFavoriteCategory: true);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($blogPost->getCategories()->first()->getId()));
        static::assertResponseIsSuccessful();

        $prototype = $crawler->filter('[data-prototype]')->attr('data-prototype');
        static::assertStringContainsString('[author][name]', $prototype);
        static::assertStringContainsString('[author][email]', $prototype);
        static::assertStringContainsString('[author][favoriteCategory][name]', $prototype);
        static::assertStringContainsString('[author][favoriteCategory][slug]', $prototype);
        static::assertStringNotContainsString('[author][favoriteCategory][description]', $prototype);
    }

    public function testFormEmbeddedInEmbeddedFormEditsExistingEntitiesWithTheEditPage(): void
    {
        $blogPost = $this->createBlogPost(withFavoriteCategory: true);
        $editUrl = $this->generateEditFormUrl($blogPost->getId(), controllerFqcn: BlogPostWithEmbeddedAuthorCrudController::class);

        $crawler = $this->client->request('GET', $editUrl);
        static::assertResponseIsSuccessful();

        static::assertSame('User 1', $crawler->filter('input[name="BlogPost[author][name]"]')->attr('value'));
        static::assertCount(0, $crawler->filter('input[name="BlogPost[author][email]"]'));
        static::assertSame('Category 2', $crawler->filter('input[name="BlogPost[author][favoriteCategory][name]"]')->attr('value'));
        static::assertCount(0, $crawler->filter('input[name="BlogPost[author][favoriteCategory][slug]"]'));
        static::assertSame('Description 2', $crawler->filter('input[name="BlogPost[author][favoriteCategory][description]"]')->attr('value'));

        // the fields that are not part of the EDIT page are ignored when submitting them
        $values = $crawler->filter('form[name="BlogPost"]')->form()->getPhpValues();
        $values['BlogPost']['author']['favoriteCategory']['name'] = 'Category 2 changed';
        $values['BlogPost']['author']['favoriteCategory']['slug'] = 'changed';
        $this->client->request('POST', $editUrl, $values);
        static::assertResponseRedirects();

        $this->entityManager->clear();
        $favoriteCategory = $this->entityManager->find(BlogPost::class, $blogPost->getId())->getAuthor()->getFavoriteCategory();
        static::assertSame('Category 2 changed', $favoriteCategory->getName());
        static::assertSame('category-2', $favoriteCategory->getSlug());
    }

    public function testFormEmbeddedInEmbeddedFormUsesTheNewPageWhenThereIsNoEntity(): void
    {
        $blogPost = $this->createBlogPost(withFavoriteCategory: false);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($blogPost->getId(), controllerFqcn: BlogPostWithEmbeddedAuthorCrudController::class));
        static::assertResponseIsSuccessful();

        static::assertSame('User 1', $crawler->filter('input[name="BlogPost[author][name]"]')->attr('value'));
        static::assertCount(0, $crawler->filter('input[name="BlogPost[author][email]"]'));
        static::assertCount(1, $crawler->filter('input[name="BlogPost[author][favoriteCategory][name]"]'));
        static::assertCount(1, $crawler->filter('input[name="BlogPost[author][favoriteCategory][slug]"]'));
        static::assertCount(0, $crawler->filter('input[name="BlogPost[author][favoriteCategory][description]"]'));
    }

    public function testFormEmbeddedOnNestedPropertyPathEditsExistingEntitiesWithTheCustomEditPage(): void
    {
        $blogPost = $this->createBlogPost(withFavoriteCategory: true);
        $editUrl = $this->generateEditFormUrl($blogPost->getId(), controllerFqcn: BlogPostWithEmbeddedAuthorFavoriteCategoryCrudController::class);

        $crawler = $this->client->request('GET', $editUrl);
        static::assertResponseIsSuccessful();

        // the custom EDIT page shows the name and the priority; the custom NEW page shows the name and the slug
        static::assertSame('Category 2', $crawler->filter('input[name="BlogPost[author_favoriteCategory][name]"]')->attr('value'));
        static::assertSame('5', $crawler->filter('input[name="BlogPost[author_favoriteCategory][priority]"]')->attr('value'));
        static::assertCount(0, $crawler->filter('input[name="BlogPost[author_favoriteCategory][slug]"]'));

        $values = $crawler->filter('form[name="BlogPost"]')->form()->getPhpValues();
        $values['BlogPost']['author_favoriteCategory']['priority'] = '7';
        $values['BlogPost']['author_favoriteCategory']['slug'] = 'changed';
        $this->client->request('POST', $editUrl, $values);
        static::assertResponseRedirects();

        $this->entityManager->clear();
        $favoriteCategory = $this->entityManager->find(BlogPost::class, $blogPost->getId())->getAuthor()->getFavoriteCategory();
        static::assertSame(7, $favoriteCategory->getPriority());
        static::assertSame('category-2', $favoriteCategory->getSlug());
    }

    /**
     * The blog post embeds the form of its author and the EDIT page of the author lists their
     * blog posts with the same blog post form. The forms would embed each other forever, so the
     * repeated author form shows no fields for existing authors.
     */
    public function testFormsThatEmbedEachOtherShowNoFieldsForExistingEntitiesWhenTheyAreRepeated(): void
    {
        $blogPost = $this->createBlogPost(withFavoriteCategory: false);
        $author = $blogPost->getAuthor();
        $editUrl = $this->generateEditFormUrl($blogPost->getId(), controllerFqcn: BlogPostWithCyclicAuthorCrudController::class);

        $crawler = $this->client->request('GET', $editUrl);
        static::assertResponseIsSuccessful();

        static::assertSame('User 1', $crawler->filter('input[name="BlogPost[author][name]"]')->attr('value'));
        static::assertCount(0, $crawler->filter('input[name="BlogPost[author][email]"]'));
        static::assertSame('Blog Post 1', $crawler->filter('input[name="BlogPost[author][blogPosts][0][title]"]')->attr('value'));
        static::assertCount(0, $crawler->filter('[name^="BlogPost[author][blogPosts][0][author]"]'));

        // new blog posts added to the collection can still create their author
        $prototype = $crawler->filter('[data-prototype]')->attr('data-prototype');
        static::assertStringContainsString('[author][name]', $prototype);
        static::assertStringContainsString('[author][email]', $prototype);

        $values = $crawler->filter('form[name="BlogPost"]')->form()->getPhpValues();
        $values['BlogPost']['author']['name'] = 'User 1 changed';
        $values['BlogPost']['author']['blogPosts'][0]['author']['email'] = 'changed@example.com';
        $this->client->request('POST', $editUrl, $values);
        static::assertResponseRedirects();

        $this->entityManager->clear();
        $author = $this->entityManager->find(User::class, $author->getId());
        static::assertSame('user1@example.com', $author->getEmail());
        static::assertSame('User 1 changed', $author->getName());
    }

    private function createBlogPost(bool $withFavoriteCategory): BlogPost
    {
        $blogPost = BlogPostFactory::createOne([
            'title' => 'Blog Post 1',
            'author' => UserFactory::new([
                'name' => 'User 1',
                'email' => 'user1@example.com',
                'favoriteCategory' => $withFavoriteCategory ? CategoryFactory::new(['name' => 'Category 2', 'slug' => 'category-2', 'description' => 'Description 2', 'priority' => 5]) : null,
            ]),
            'categories' => [CategoryFactory::new(['name' => 'Category 1', 'slug' => 'category-1'])],
        ]);

        // needed to load the inverse side of the associations of blog posts
        $this->entityManager->clear();

        return $blogPost;
    }
}
