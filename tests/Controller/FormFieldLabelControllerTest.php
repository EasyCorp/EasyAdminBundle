<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\FormFieldLabelController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\BlogPost;

class FormFieldLabelControllerTest extends AbstractCrudTestCase
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
        return FormFieldLabelController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testFieldsLabelsInForms()
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertSelectorTextSame('label[for="BlogPost_id"]', 'ID', 'The "id" field does not define a label explicitly, so it is generated automatically based on the field name. Edge-case: fields named "id" are labeled as uppercase "ID" instead of the default title-case "Id" of the rest of the fields.');

        static::assertSelectorTextSame('label[for="BlogPost_title"]', 'Title', 'The "title" field uses NULL as the value of the label, which tells EasyAdmin to generate a label automatically as the title-case value of the field name.');

        static::assertSelectorNotExists('.form-group.field-slug label', 'The "slug" field uses FALSE as the value of the label, which means that no <label> element should be rendered for that field.');

        static::assertSelectorTextSame('label[for="BlogPost_content"]', '', 'The "content" field defines an empty string as the label, so it renders a <label> element to keep the design layout but without any contents inside.');

        static::assertSelectorTextSame('label[for="BlogPost_createdAt"]', 'Lorem Ipsum 1', 'The "createdAt" field defines a regular text string as its label (no HTML contents).');

        static::assertSelectorTextSame('label[for="BlogPost_publishedAt"]', 'Lorem Ipsum 2', 'The "publishedAt" field defines its label as a translatable string with regular text (no HTML contents).');

        static::assertSame($crawler->filter('label[for="BlogPost_author"]')->html(), 'Lorem <a href="https://example.com">Ipsum</a> <b>3</b>', 'The "author" field defines its label as regular string with HTML contents, which must be rendered instead of escaped (HTML is allowed in field labels).');

        static::assertSame($crawler->filter('label[for="BlogPost_publisher"]')->html(), 'Lorem <a href="https://example.com">Ipsum</a> <b>4</b>', 'The "publisher" field defines its label as translatable string with HTML contents, which must be rendered instead of escaped (HTML is allowed in field labels).');
    }

    public function testFieldsLabelsInDetailPage()
    {
        $blogPost = $this->blogPosts->findOneBy([]);
        $crawler = $this->client->request('GET', $this->generateDetailUrl($blogPost->getId()));

        static::assertSelectorTextSame('.field-group.field-id .field-label > div', 'ID', 'The "id" field does not define a label explicitly, so it is generated automatically based on the field name. Edge-case: fields named "id" are labeled as uppercase "ID" instead of the default title-case "Id" of the rest of the fields.');

        static::assertSelectorTextSame('.field-group.field-title .field-label > div', 'Title', 'The "title" field uses NULL as the value of the label, which tells EasyAdmin to generate a label automatically as the title-case value of the field name.');

        static::assertSelectorNotExists('.field-group.field-slug .field-label', 'The "slug" field uses FALSE as the value of the label, which means that no <label> element should be rendered for that field.');

        static::assertSelectorTextSame('.field-group.field-content .field-label > div', '', 'The "content" field defines an empty string as the label, so it renders a <label> element to keep the design layout but without any contents inside.');

        static::assertSelectorTextSame('.field-group.field-created-at .field-label > div', 'Lorem Ipsum 1', 'The "createdAt" field defines a regular text string as its label (no HTML contents).');

        static::assertSelectorTextSame('.field-group.field-published-at .field-label > div', 'Lorem Ipsum 2', 'The "publishedAt" field defines its label as a translatable string with regular text (no HTML contents).');

        static::assertSame(trim($crawler->filter('.field-group.field-author .field-label > div')->html()), 'Lorem <a href="https://example.com">Ipsum</a> <b>3</b>', 'The "author" field defines its label as regular string with HTML contents, which must be rendered instead of escaped (HTML is allowed in field labels).');

        static::assertSame(trim($crawler->filter('.field-group.field-publisher .field-label > div')->html()), 'Lorem <a href="https://example.com">Ipsum</a> <b>4</b>', 'The "publisher" field defines its label as translatable string with HTML contents, which must be rendered instead of escaped (HTML is allowed in field labels).');
    }
}
