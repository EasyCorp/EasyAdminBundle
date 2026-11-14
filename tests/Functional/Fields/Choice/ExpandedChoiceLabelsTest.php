<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Choice;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ExpandedChoices\BlogPostWithExpandedChoicesCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ExpandedChoices\BlogPostWithExpandedHtmlChoicesCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ExpandedChoices\UserWithNestedExpandedChoicesCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The labels of the checkboxes and radio buttons of expanded fields whose choices
 * are entities display the string representation of those entities, which is
 * stored data, so they must be escaped unless the field renders them as HTML.
 */
class ExpandedChoiceLabelsTest extends AbstractCrudTestCase
{
    private Category $category;
    private User $user;
    private BlogPost $blogPost;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = (new Category())->setName('<b>Category 1</b>')->setSlug('expanded-choice-labels-category');
        $this->user = (new User())->setName('<b>User 1</b>')->setEmail('expanded-choice-labels@example.com');
        $this->blogPost = (new BlogPost())
            ->setTitle('Expanded choice labels')
            ->setSlug('expanded-choice-labels')
            ->setContent('Lorem Ipsum Dolor Sit Amet.')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setAuthor($this->user)
            ->addCategory($this->category);
        $this->blogPost->setPublisher($this->user);

        $this->entityManager->persist($this->category);
        $this->entityManager->persist($this->user);
        $this->entityManager->persist($this->blogPost);
        $this->entityManager->flush();
        // the inverse side of the author association (User::$blogPosts) must be loaded from the database
        $this->entityManager->clear();
    }

    protected function tearDown(): void
    {
        // the database is shared by all tests, so remove the entities with HTML contents
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        foreach ([BlogPost::class => $this->blogPost->getId(), Category::class => $this->category->getId(), User::class => $this->user->getId()] as $entityFqcn => $entityId) {
            if (null !== $entity = $entityManager->find($entityFqcn, $entityId)) {
                $entityManager->remove($entity);
                $entityManager->flush();
            }
        }

        parent::tearDown();
    }

    protected function getControllerFqcn(): string
    {
        return BlogPostWithExpandedChoicesCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    /**
     * @testWith ["new", "categories", "<b>Category 1</b>"]
     *           ["edit", "categories", "<b>Category 1</b>"]
     *           ["new", "author", "<b>User 1</b>"]
     *           ["edit", "author", "<b>User 1</b>"]
     *           ["new", "publisher", "<b>User 1</b>"]
     *           ["edit", "publisher", "<b>User 1</b>"]
     */
    public function testEntityChoiceLabelsAreEscapedByDefault(string $pageName, string $fieldName, string $choiceLabel): void
    {
        $crawler = $this->requestFormPage($pageName, BlogPostWithExpandedChoicesCrudController::class);

        $labels = $crawler->filter(sprintf('label[for^="BlogPost_%s_"]', $fieldName));
        static::assertContains($choiceLabel, $labels->each(static fn (Crawler $label): string => $label->text()));
        static::assertCount(0, $labels->filter('b'));
    }

    /**
     * @testWith ["new"]
     *           ["edit"]
     */
    public function testFieldLabelAndDeveloperChoiceLabelsAreStillRenderedAsHtml(string $pageName): void
    {
        $crawler = $this->requestFormPage($pageName, BlogPostWithExpandedChoicesCrudController::class);

        static::assertSame('<b>Categories</b>', trim($crawler->filter('.field-association legend')->first()->html()));
        static::assertSame('<b>Choice 1</b>', trim($crawler->filter('label[for="BlogPost_slug_0"]')->html()));
        static::assertSame('<i>None</i>', trim($crawler->filter('label[for="BlogPost_publisher_placeholder"]')->html()));
    }

    /**
     * @testWith ["new"]
     *           ["edit"]
     */
    public function testEntityChoiceLabelsAreRenderedAsHtmlWhenTheFieldEnablesIt(string $pageName): void
    {
        $crawler = $this->requestFormPage($pageName, BlogPostWithExpandedHtmlChoicesCrudController::class);

        static::assertSame('<b>Category 1</b>', trim($crawler->filter(sprintf('label[for="BlogPost_categories_%d"]', $this->category->getId()))->html()));
        static::assertSame('<b>User 1</b>', trim($crawler->filter(sprintf('label[for="BlogPost_author_%d"]', $this->user->getId()))->html()));
        static::assertSame('<b>Choice 1</b>', trim($crawler->filter('label[for="BlogPost_slug_0"]')->html()));
    }

    public function testEntityChoiceLabelsAreEscapedInNestedCrudForms(): void
    {
        $crawler = $this->client->request('GET', $this->generateEditFormUrl($this->user->getId(), null, UserWithNestedExpandedChoicesCrudController::class));

        $this->assertEscapedNestedChoiceLabels($crawler, 'User_blogPosts_0_');
        // the prototype is inserted as HTML when adding new entries to the collection
        $this->assertEscapedNestedChoiceLabels(new Crawler($crawler->filter('[data-prototype]')->attr('data-prototype')), 'User_blogPosts___blogPostsname___');
    }

    public function testEntityChoiceLabelsAreEscapedInFilters(): void
    {
        $crawler = $this->client->request('GET', $this->generateFilterRenderUrl());

        $labels = $crawler->filter('label[for^="filters_categories_value_"]');
        static::assertContains('<b>Category 1</b>', $labels->each(static fn (Crawler $label): string => $label->text()));
        static::assertCount(0, $labels->filter('b'));
    }

    private function requestFormPage(string $pageName, string $controllerFqcn): Crawler
    {
        $url = 'new' === $pageName
            ? $this->generateNewFormUrl(null, $controllerFqcn)
            : $this->generateEditFormUrl($this->blogPost->getId(), null, $controllerFqcn);

        return $this->client->request('GET', $url);
    }

    private function assertEscapedNestedChoiceLabels(Crawler $crawler, string $idPrefix): void
    {
        $labels = $crawler->filter(sprintf('label[for^="%1$scategories_"], label[for^="%1$sauthor_"]', $idPrefix));
        $labelContents = $labels->each(static fn (Crawler $label): string => $label->text());

        static::assertContains('<b>Category 1</b>', $labelContents);
        static::assertContains('<b>User 1</b>', $labelContents);
        static::assertCount(0, $labels->filter('b'));
    }
}
