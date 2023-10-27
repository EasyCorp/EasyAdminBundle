<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\FormFieldHelpController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\BlogPost;

class FormFieldHelpControllerTest extends AbstractCrudTestCase
{
    protected EntityRepository $blogPosts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();

        $this->blogPosts = $this->entityManager->getRepository(BlogPost::class);
    }

    protected function getControllerFqcn(): string
    {
        return FormFieldHelpController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testFieldsWithoutHelp()
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertSelectorNotExists('.form-group #BlogPost_id + .form-help', 'The ID field does not define a help message.');

        static::assertSelectorNotExists('.form-group #BlogPost_title + .form-help', 'The title field defines an empty string as a help message, so it does not render an HTML element for that help message.');

        static::assertSelectorTextContains('.form-group #BlogPost_slug + .form-help', 'Lorem Ipsum 1', 'The slug field defines a text help message.');

        static::assertSame('<b>Lorem</b> Ipsum <em class="foo">2</em>', $crawler->filter('.form-group #BlogPost_content + .form-help')->html(), 'The content field defines an help message with HTML contents, which must be rendered instead of escaped.');

        static::assertSelectorTextContains('.form-group:contains("Created At") .form-help', 'Lorem Ipsum 3', 'The createdAt field defines a translatable text help message.');

        static::assertSame('Lorem <a href="https://example.com">Ipsum</a> <b>4</b>', $crawler->filter('.form-group:contains("Published At") .form-help')->html(), 'The publishedAt field defines a translatable help message with HTML contents, which must be rendered instead of escaped.');
    }
}
