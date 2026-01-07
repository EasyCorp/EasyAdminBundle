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

    public function testFieldsHelpMessagesInForms(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // fields with no help defined
        static::assertSelectorNotExists('#tab-tab-1 .tab-help', 'The Tab 1 does not define a help message.');
        static::assertSelectorNotExists('.form-column.column-1 .form-column-help', 'The Column 1 does not define a help message.');
        static::assertSelectorNotExists('.form-fieldset-title:contains("Fieldset 1") .form-fieldset-help', 'The Fieldset 1 does not define a help message.');
        static::assertSelectorNotExists('.form-group #BlogPost_id + .form-help', 'The ID field does not define a help message.');
        static::assertSelectorNotExists('.form-group #BlogPost_title + .form-help', 'The title field defines an empty string as a help message, so it does not render an HTML element for that help message.');

        // fields with help defined as simple text strings
        static::assertSelectorTextContains('#tab-tab-2 .tab-help', 'Tab 2 Lorem Ipsum', 'The Tab 2 field defines a text help message.');
        static::assertSelectorTextContains('.form-column.column-2 .form-column-help', 'Column 2 Lorem Ipsum', 'The Column 2 field defines a text help message.');
        static::assertSelectorTextContains('.form-fieldset-title:contains("Fieldset 2") .form-fieldset-help', 'Fieldset 2 Lorem Ipsum', 'The Fieldset 2 field defines a text help message.');
        static::assertSelectorTextContains('.form-group #BlogPost_slug + .form-help', 'Slug Lorem Ipsum', 'The slug field defines a text help message.');

        // fields with help defined as text strings with HTML contents
        static::assertSame('<a href="https://example.com">Tab 3</a> <b>Lorem</b> Ipsum', trim($crawler->filter('#tab-tab-3 .tab-help')->html()), 'The Tab 3 field defines a help message with HTML contents, which must be rendered instead of escaped.');
        static::assertSame('<a href="https://example.com">Column 3</a> <b>Lorem</b> Ipsum', trim($crawler->filter('.form-column.column-3 .form-column-help')->html()), 'The Column 3 field defines a help message with HTML contents, which must be rendered instead of escaped.');
        static::assertSame('<a href="https://example.com">Fieldset 3</a> <b>Lorem</b> Ipsum', trim($crawler->filter('.form-fieldset-title:contains("Fieldset 3") .form-fieldset-help')->html()), 'The Fieldset 3 field defines a help message with HTML contents, which must be rendered instead of escaped.');
        static::assertSame('<a href="https://example.com">Content</a> <b>Lorem</b> Ipsum', $crawler->filter('.form-group #BlogPost_content + .form-help')->html(), 'The content field defines an help message with HTML contents, which must be rendered instead of escaped.');

        // fields with help defined as Translatable objects using simple text strings
        static::assertSelectorTextContains('#tab-tab-4 .tab-help', 'Tab 4 Lorem Ipsum', 'The Tab 4 field defines a translatable text help message.');
        static::assertSelectorTextContains('.form-column.column-4 .form-column-help', 'Column 4 Lorem Ipsum', 'The Column 4 field defines a translatable text help message.');
        static::assertSelectorTextContains('.form-fieldset-title:contains("Fieldset 4") .form-fieldset-help', 'Fieldset 4 Lorem Ipsum', 'The Fieldset 4 field defines a translatable text help message.');
        static::assertSelectorTextContains('.form-group:contains("Created At") .form-help', 'CreatedAt Lorem Ipsum', 'The createdAt field defines a translatable text help message.');

        // fields with help defined as Translatable objects using text strings with HTML contents
        static::assertSelectorTextContains('#tab-tab-5 .tab-help', 'Tab 5 Lorem Ipsum', 'The Tab 5 field defines a translatable help message with HTML contents, which must be rendered instead of escaped.');
        static::assertSelectorTextContains('.form-column.column-5 .form-column-help', 'Column 5 Lorem Ipsum', 'The Column 5 field defines a translatable help message with HTML contents, which must be rendered instead of escaped..');
        static::assertSelectorTextContains('.form-fieldset-title:contains("Fieldset 5") .form-fieldset-help', 'Fieldset 5 Lorem Ipsum', 'The Fieldset 5 field defines a translatable help message with HTML contents, which must be rendered instead of escaped..');
        static::assertSame('<a href="https://example.com">PublishedAt</a> <b>Lorem</b> Ipsum', $crawler->filter('.form-group:contains("Published At") .form-help')->html(), 'The publishedAt field defines a translatable help message with HTML contents, which must be rendered instead of escaped.');
    }

    public function testFieldsHelpMessagesOnDetailPage(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // fields with no help defined
        static::assertSelectorNotExists('#tab-tab-1 .tab-help', 'The Tab 1 does not define a help message.');
        static::assertSelectorNotExists('.form-column.column-1 .form-column-help', 'The Column 1 does not define a help message.');
        static::assertSelectorNotExists('.form-fieldset-title:contains("Fieldset 1") .form-fieldset-help', 'The Fieldset 1 does not define a help message.');
        static::assertSelectorNotExists('.form-group #BlogPost_id + .form-help', 'The ID field does not define a help message.');
        static::assertSelectorNotExists('.form-group #BlogPost_title + .form-help', 'The title field defines an empty string as a help message, so it does not render an HTML element for that help message.');

        // fields with help defined as simple text strings
        static::assertSelectorTextContains('#tab-tab-2 .tab-help', 'Tab 2 Lorem Ipsum', 'The Tab 2 field defines a text help message.');
        static::assertSelectorTextContains('.form-column.column-2 .form-column-help', 'Column 2 Lorem Ipsum', 'The Column 2 field defines a text help message.');
        static::assertSelectorTextContains('.form-fieldset-title:contains("Fieldset 2") .form-fieldset-help', 'Fieldset 2 Lorem Ipsum', 'The Fieldset 2 field defines a text help message.');
        static::assertSelectorTextContains('.form-group #BlogPost_slug + .form-help', 'Slug Lorem Ipsum', 'The slug field defines a text help message.');

        // fields with help defined as text strings with HTML contents
        static::assertSame('<a href="https://example.com">Tab 3</a> <b>Lorem</b> Ipsum', trim($crawler->filter('#tab-tab-3 .tab-help')->html()), 'The Tab 3 field defines a help message with HTML contents, which must be rendered instead of escaped.');
        static::assertSame('<a href="https://example.com">Column 3</a> <b>Lorem</b> Ipsum', trim($crawler->filter('.form-column.column-3 .form-column-help')->html()), 'The Column 3 field defines a help message with HTML contents, which must be rendered instead of escaped.');
        static::assertSame('<a href="https://example.com">Fieldset 3</a> <b>Lorem</b> Ipsum', trim($crawler->filter('.form-fieldset-title:contains("Fieldset 3") .form-fieldset-help')->html()), 'The Fieldset 3 field defines a help message with HTML contents, which must be rendered instead of escaped.');
        static::assertSame('<a href="https://example.com">Content</a> <b>Lorem</b> Ipsum', $crawler->filter('.form-group #BlogPost_content + .form-help')->html(), 'The content field defines an help message with HTML contents, which must be rendered instead of escaped.');

        // fields with help defined as Translatable objects using simple text strings
        static::assertSelectorTextContains('#tab-tab-4 .tab-help', 'Tab 4 Lorem Ipsum', 'The Tab 4 field defines a translatable text help message.');
        static::assertSelectorTextContains('.form-column.column-4 .form-column-help', 'Column 4 Lorem Ipsum', 'The Column 4 field defines a translatable text help message.');
        static::assertSelectorTextContains('.form-fieldset-title:contains("Fieldset 4") .form-fieldset-help', 'Fieldset 4 Lorem Ipsum', 'The Fieldset 4 field defines a translatable text help message.');
        static::assertSelectorTextContains('.form-group:contains("Created At") .form-help', 'CreatedAt Lorem Ipsum', 'The createdAt field defines a translatable text help message.');

        // fields with help defined as Translatable objects using text strings with HTML contents
        static::assertSelectorTextContains('#tab-tab-5 .tab-help', 'Tab 5 Lorem Ipsum', 'The Tab 5 field defines a translatable help message with HTML contents, which must be rendered instead of escaped.');
        static::assertSelectorTextContains('.form-column.column-5 .form-column-help', 'Column 5 Lorem Ipsum', 'The Column 5 field defines a translatable help message with HTML contents, which must be rendered instead of escaped..');
        static::assertSelectorTextContains('.form-fieldset-title:contains("Fieldset 5") .form-fieldset-help', 'Fieldset 5 Lorem Ipsum', 'The Fieldset 5 field defines a translatable help message with HTML contents, which must be rendered instead of escaped..');
        static::assertSame('<a href="https://example.com">PublishedAt</a> <b>Lorem</b> Ipsum', $crawler->filter('.form-group:contains("Published At") .form-help')->html(), 'The publishedAt field defines a translatable help message with HTML contents, which must be rendered instead of escaped.');
    }
}
