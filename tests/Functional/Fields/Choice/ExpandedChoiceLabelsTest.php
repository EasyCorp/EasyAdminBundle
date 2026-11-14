<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Choice;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ExpandedChoices\BlogPostWithExpandedChoicesCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ExpandedChoices\BlogPostWithExpandedHtmlChoicesCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ExpandedChoices\UserWithNestedExpandedChoicesCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The labels of the checkboxes and radio buttons of expanded fields whose choices
 * are entities display the string representation of those entities, which is
 * stored data, so they must be escaped unless the field renders them as HTML.
 */
class ExpandedChoiceLabelsTest extends AbstractCrudTestCase
{
    private User $user;
    private BlogPost $blogPost;

    protected function setUp(): void
    {
        parent::setUp();

        $category = CategoryFactory::createOne(['name' => '<b>Category 1</b>']);
        $this->user = UserFactory::createOne(['name' => '<b>User 1</b>']);
        $this->blogPost = BlogPostFactory::createOne(['author' => $this->user, 'publisher' => $this->user, 'categories' => [$category]]);
    }

    protected function getControllerFqcn(): string
    {
        return BlogPostWithExpandedChoicesCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    #[TestWith(['new', 'categories', '<b>Category 1</b>'])]
    #[TestWith(['edit', 'categories', '<b>Category 1</b>'])]
    #[TestWith(['new', 'author', '<b>User 1</b>'])]
    #[TestWith(['edit', 'author', '<b>User 1</b>'])]
    #[TestWith(['new', 'publisher', '<b>User 1</b>'])]
    #[TestWith(['edit', 'publisher', '<b>User 1</b>'])]
    public function testEntityChoiceLabelsAreEscapedByDefault(string $pageName, string $fieldName, string $choiceLabel): void
    {
        $crawler = $this->requestFormPage($pageName, BlogPostWithExpandedChoicesCrudController::class);

        $labels = $crawler->filter(sprintf('label[for^="BlogPost_%s_"]', $fieldName));
        static::assertContains($choiceLabel, $labels->each(static fn (Crawler $label): string => $label->text()));
        static::assertCount(0, $labels->filter('b'));
    }

    #[TestWith(['new'])]
    #[TestWith(['edit'])]
    public function testFieldLabelAndDeveloperChoiceLabelsAreStillRenderedAsHtml(string $pageName): void
    {
        $crawler = $this->requestFormPage($pageName, BlogPostWithExpandedChoicesCrudController::class);

        static::assertSame('<b>Categories</b>', $crawler->filter('.field-association legend')->first()->html());
        static::assertSame('<b>Choice 1</b>', $crawler->filter('label[for="BlogPost_views_0"]')->html());
        static::assertSame('<i>None</i>', $crawler->filter('label[for="BlogPost_publisher_placeholder"]')->html());
    }

    #[TestWith(['new'])]
    #[TestWith(['edit'])]
    public function testEntityChoiceLabelsAreRenderedAsHtmlWhenTheFieldEnablesIt(string $pageName): void
    {
        $crawler = $this->requestFormPage($pageName, BlogPostWithExpandedHtmlChoicesCrudController::class);

        static::assertSame('<b>Category 1</b>', $crawler->filter(sprintf('label[for="BlogPost_categories_%d"]', $this->blogPost->getCategories()->first()->getId()))->html());
        static::assertSame('<b>User 1</b>', $crawler->filter(sprintf('label[for="BlogPost_author_%d"]', $this->user->getId()))->html());
        static::assertSame('<b>Choice 1</b>', $crawler->filter('label[for="BlogPost_views_0"]')->html());
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
