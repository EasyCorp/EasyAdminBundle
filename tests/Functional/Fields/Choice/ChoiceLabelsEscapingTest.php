<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Choice;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ChoiceLabels\BlogPostWithHtmlChoiceLabelsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;

/**
 * Choice labels are rendered as HTML in the index and detail pages unless the
 * field calls escapeHtml() explicitly (e.g. because the labels are stored data).
 */
class ChoiceLabelsEscapingTest extends AbstractCrudTestCase
{
    private BlogPost $blogPost;

    protected function setUp(): void
    {
        parent::setUp();

        $this->blogPost = BlogPostFactory::createOne(['views' => 1, 'slug' => 'slug-1', 'title' => 'title-1']);
    }

    protected function getControllerFqcn(): string
    {
        return BlogPostWithHtmlChoiceLabelsCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testLabelsOnIndexPage(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertLabelsAreRenderedAsExpected();
    }

    public function testLabelsOnDetailPage(): void
    {
        $this->client->request('GET', $this->generateDetailUrl($this->blogPost->getId()));

        $this->assertLabelsAreRenderedAsExpected();
    }

    private function assertLabelsAreRenderedAsExpected(): void
    {
        static::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();

        static::assertStringContainsString('<span class="badge badge-secondary"><b>Views 1</b></span>', $content);
        static::assertStringContainsString('<span class="badge badge-secondary">&lt;b&gt;Slug 1&lt;/b&gt;</span>', $content);
        static::assertStringContainsString('&lt;b&gt;Title 1&lt;/b&gt;', $content);
        static::assertStringNotContainsString('<b>Slug 1</b>', $content);
        static::assertStringNotContainsString('<b>Title 1</b>', $content);
    }
}
