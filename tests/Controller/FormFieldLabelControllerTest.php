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

    public function testFieldsLabelsInForms(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // fields with no label defined
        static::assertSelectorTextSame('ul.nav-tabs #tablist-tab-1', '', 'The "Tab 1" does not define any label, so its label is rendered as an empty string.');
        static::assertSame($crawler->filter('ul.nav-tabs #tablist-tab-1')->attr('href'), '#tab-1', 'Tabs without explicit labels get IDs generated automatically with autoincrement numbers.');
        static::assertSelectorNotExists('.form-column.column-1 .form-column-title-content', 'The "Column 1" field does not define any label, so its label element is not rendered.');
        static::assertStringContainsString('form-column-no-header', $crawler->filter('.form-column.column-1')->attr('class'), 'Columns without explicit labels get a special CSS class to adjust the page design.');
        static::assertSelectorNotExists('.form-fieldset.fieldset-1 .form-fieldset-title-content', 'The "Fieldset 1" field does not define any label, so its label element is not rendered.');
        static::assertStringContainsString('form-fieldset-no-header', $crawler->filter('.form-fieldset.fieldset-1')->attr('class'), 'Fieldsets without explicit labels get a special CSS class to adjust the page design.');
        static::assertSelectorTextSame('label[for="BlogPost_id"]', 'ID', 'The "id" field does not define a label explicitly, so it is generated automatically based on the field name. Edge-case: fields named "id" are labeled as uppercase "ID" instead of the default title-case "Id" of the rest of the fields.');

        // fields with a NULL label defined
        static::assertSelectorTextSame('ul.nav-tabs #tablist-tab-2', '', 'The "Tab 2" defines its label as NULL, so its label is rendered as an empty string.');
        static::assertSame($crawler->filter('ul.nav-tabs #tablist-tab-2')->attr('href'), '#tab-2', 'Tabs without explicit labels get IDs generated automatically with autoincrement numbers.');
        static::assertSelectorNotExists('.form-column.column-2 .form-column-title-content', 'The "Column 2" field defines its label as NULL, so its label element is not rendered.');
        static::assertStringContainsString('form-column-no-header', $crawler->filter('.form-column.column-2')->attr('class'), 'Columns without explicit labels get a special CSS class to adjust the page design.');
        static::assertSelectorNotExists('.form-fieldset.fieldset-2 .form-fieldset-title-content', 'The "Fieldset 2" field defines its label as NULL, so its label element is not rendered.');
        static::assertStringContainsString('form-fieldset-no-header', $crawler->filter('.form-fieldset.fieldset-2')->attr('class'), 'Fieldsets without explicit labels get a special CSS class to adjust the page design.');
        static::assertSelectorTextSame('label[for="BlogPost_title"]', 'Title', 'The "title" field uses NULL as the value of the label, which tells EasyAdmin to generate a label automatically as the title-case value of the field name.');

        // fields with a FALSE label defined
        static::assertSelectorTextSame('ul.nav-tabs #tablist-tab-3', '', 'The "Tab 3" defines its label as FALSE, so its label is rendered as an empty string.');
        static::assertSame($crawler->filter('ul.nav-tabs #tablist-tab-3')->attr('href'), '#tab-3', 'Tabs without explicit labels get IDs generated automatically with autoincrement numbers.');
        static::assertSelectorNotExists('.form-column.column-3 .form-column-title-content', 'The "Column 3" field defines its label as NULL, so its label element is not rendered.');
        static::assertStringContainsString('form-column-no-header', $crawler->filter('.form-column.column-3')->attr('class'), 'Columns without explicit labels get a special CSS class to adjust the page design.');
        static::assertSelectorNotExists('.form-fieldset.fieldset-3 .form-fieldset-title-content', 'The "Fieldset 3" field defines its label as FALSE, so its label element is not rendered.');
        static::assertStringContainsString('form-fieldset-no-header', $crawler->filter('.form-fieldset.fieldset-3')->attr('class'), 'Fieldsets without explicit labels get a special CSS class to adjust the page design.');
        static::assertSelectorNotExists('.form-group.field-slug label', 'The "slug" field uses FALSE as the value of the label, which means that no <label> element should be rendered for that field.');

        // fields with a label defined as an empty string
        static::assertSelectorTextSame('ul.nav-tabs #tablist-tab-4', '', 'The "Tab 4" defines its label as an empty string, so its label is rendered as an empty string.');
        static::assertSame($crawler->filter('ul.nav-tabs #tablist-tab-4')->attr('href'), '#tab-4', 'Tabs without explicit labels get IDs generated automatically with autoincrement numbers.');
        static::assertSelectorNotExists('.form-column.column-4 .form-column-title-content', 'The "Column 4" field defines its label as an empty string, so its label element is not rendered.');
        static::assertStringContainsString('form-column-no-header', $crawler->filter('.form-column.column-4')->attr('class'), 'Columns without explicit labels get a special CSS class to adjust the page design.');
        static::assertSelectorNotExists('.form-fieldset.fieldset-4 .form-fieldset-title-content', 'The "Fieldset 4" field defines its label as an empty string, so its label element is not rendered.');
        static::assertStringContainsString('form-fieldset-no-header', $crawler->filter('.form-fieldset.fieldset-4')->attr('class'), 'Fieldsets without explicit labels get a special CSS class to adjust the page design.');
        static::assertSelectorTextSame('label[for="BlogPost_content"]', '', 'The "content" field defines an empty string as the label, so it renders a <label> element to keep the design layout but without any contents inside.');

        // fields with a label defined as a text string
        static::assertSelectorTextSame('ul.nav-tabs #tablist-tab-tab-5', 'Tab 5', 'The "Tab 5" defines its label as a text string.');
        static::assertSame('#tab-tab-5', $crawler->filter('ul.nav-tabs #tablist-tab-tab-5')->attr('href'), 'Tabs with labels get IDs generated as the slugs of their labels.');
        static::assertSelectorTextSame('.form-column.column-5 .form-column-title-content', 'Column 5', 'The "Column 5" field defines its label as a text string.');
        static::assertSelectorNotExists('.form-column.column-5.form-column-no-header');
        static::assertSelectorTextSame('.form-fieldset.fieldset-5 .form-fieldset-title-content', 'Fieldset 5', 'The "Fieldset 5" field defines its label as text string.');
        static::assertSelectorNotExists('.form-fieldset.fieldset-5.form-fieldset-no-header');
        static::assertSelectorTextSame('label[for="BlogPost_createdAt"]', 'Lorem Ipsum 1', 'The "createdAt" field defines a regular text string as its label (no HTML contents).');

        // fields with a label defined as a translatable string
        static::assertSelectorTextSame('ul.nav-tabs #tablist-tab-tab-6', 'Tab 6', 'The "Tab 6" defines its label as a translatable string.');
        static::assertSame('#tab-tab-6', $crawler->filter('ul.nav-tabs #tablist-tab-tab-6')->attr('href'), 'Tabs with labels get IDs generated as the slugs of their labels.');
        static::assertSelectorTextSame('.form-column.column-6 .form-column-title-content', 'Column 6', 'The "Column 6" field defines its label as a translatable string.');
        static::assertSelectorNotExists('.form-column.column-6.form-column-no-header');
        static::assertSelectorTextSame('.form-fieldset.fieldset-6 .form-fieldset-title-content', 'Fieldset 6', 'The "Fieldset 6" field defines its label as translatable string.');
        static::assertSelectorNotExists('.form-fieldset.fieldset-6.form-fieldset-no-header');
        static::assertSelectorTextSame('label[for="BlogPost_publishedAt"]', 'PublishedAt Lorem Ipsum', 'The "publishedAt" field defines its label as a translatable string with regular text (no HTML contents).');

        // fields with a label defined as a string with HTML contents
        static::assertSame('<span class="text-danger">Tab</span> <b>7</b>', trim($crawler->filter('ul.nav-tabs #tablist-tab-tab-7')->html()), 'The "Tab 7" defines its label as a text string with HTML contents, which must be rendered instead of escaped.');
        static::assertSame('#tab-tab-7', $crawler->filter('ul.nav-tabs #tablist-tab-tab-7')->attr('href'), 'Tabs with labels get IDs generated as the slugs of their labels but HTML tags must be removed.');
        static::assertSame('<a href="https://example.com">Column</a> <b>7</b>', trim($crawler->filter('.form-column.column-7 .form-column-title-content')->html()), 'The "Column 7" defines its label as a text string with HTML contents, which must be rendered instead of escaped.');
        static::assertSelectorNotExists('.form-column.column-7.form-column-no-header');
        static::assertSame('<a href="https://example.com">Fieldset</a> <b>7</b>', trim($crawler->filter('.form-fieldset.fieldset-7 .form-fieldset-title-content')->html()), 'The "Fieldset 7" defines its label as a text string with HTML contents, which must be rendered instead of escaped.');
        static::assertSelectorNotExists('.form-fieldset.fieldset-7.form-fieldset-no-header');
        static::assertSame($crawler->filter('label[for="BlogPost_author"]')->html(), '<a href="https://example.com">Author</a> <b>Lorem</b> Ipsum', 'The "author" field defines its label as regular string with HTML contents, which must be rendered instead of escaped (HTML is allowed in field labels).');

        // fields with a label defined as a translatable string with HTML contents
        static::assertSame('<span class="text-danger">Tab</span> <b>8</b>', trim($crawler->filter('ul.nav-tabs #tablist-tab-tab-8')->html()), 'The "Tab 8" defines its label as a translatable string with HTML contents, which must be rendered instead of escaped.');
        static::assertSame('#tab-tab-8', $crawler->filter('ul.nav-tabs #tablist-tab-tab-8')->attr('href'), 'Tabs with labels get IDs generated as the slugs of their labels but HTML tags must be removed.');
        static::assertSame('<a href="https://example.com">Column</a> <b>8</b>', trim($crawler->filter('.form-column.column-8 .form-column-title-content')->html()), 'The "Column 8" defines its label as a translatable string with HTML contents, which must be rendered instead of escaped.');
        static::assertSelectorNotExists('.form-column.column-8.form-column-no-header');
        static::assertSame('<a href="https://example.com">Fieldset</a> <b>8</b>', trim($crawler->filter('.form-fieldset.fieldset-8 .form-fieldset-title-content')->html()), 'The "Fieldset 8" defines its label as a text string with HTML contents, which must be rendered instead of escaped.');
        static::assertSelectorNotExists('.form-fieldset.fieldset-8.form-fieldset-no-header');
        static::assertSame($crawler->filter('label[for="BlogPost_publisher"]')->html(), '<a href="https://example.com">Publisher</a> <b>Lorem</b> Ipsum', 'The "publisher" field defines its label as translatable string with HTML contents, which must be rendered instead of escaped (HTML is allowed in field labels).');
    }

    public function testFieldsLabelsOnDetailPage(): void
    {
        $blogPost = $this->blogPosts->findOneBy([]);
        $crawler = $this->client->request('GET', $this->generateDetailUrl($blogPost->getId()));

        // fields with no label defined
        static::assertSelectorTextSame('ul.nav-tabs #tablist-tab-1', '', 'The "Tab 1" does not define any label, so its label is rendered as an empty string.');
        static::assertSame($crawler->filter('ul.nav-tabs #tablist-tab-1')->attr('href'), '#tab-1', 'Tabs without explicit labels get IDs generated automatically with autoincrement numbers.');
        static::assertSelectorNotExists('.form-column.column-1 .form-column-title-content', 'The "Column 1" field does not define any label, so its label element is not rendered.');
        static::assertStringContainsString('form-column-no-header', $crawler->filter('.form-column.column-1')->attr('class'), 'Columns without explicit labels get a special CSS class to adjust the page design.');
        static::assertSelectorNotExists('.form-fieldset.fieldset-1 .form-fieldset-title-content', 'The "Fieldset 1" field does not define any label, so its label element is not rendered.');
        static::assertStringContainsString('form-fieldset-no-header', $crawler->filter('.form-fieldset.fieldset-1')->attr('class'), 'Fieldsets without explicit labels get a special CSS class to adjust the page design.');
        static::assertSelectorTextSame('.field-group.field-id .field-label > div', 'ID', 'The "id" field does not define a label explicitly, so it is generated automatically based on the field name. Edge-case: fields named "id" are labeled as uppercase "ID" instead of the default title-case "Id" of the rest of the fields.');

        // fields with a NULL label defined
        static::assertSelectorTextSame('ul.nav-tabs #tablist-tab-2', '', 'The "Tab 2" defines its label as NULL, so its label is rendered as an empty string.');
        static::assertSame($crawler->filter('ul.nav-tabs #tablist-tab-2')->attr('href'), '#tab-2', 'Tabs without explicit labels get IDs generated automatically with autoincrement numbers.');
        static::assertSelectorNotExists('.form-column.column-2 .form-column-title-content', 'The "Column 2" field defines its label as NULL, so its label element is not rendered.');
        static::assertStringContainsString('form-column-no-header', $crawler->filter('.form-column.column-2')->attr('class'), 'Columns without explicit labels get a special CSS class to adjust the page design.');
        static::assertSelectorNotExists('.form-fieldset.fieldset-2 .form-fieldset-title-content', 'The "Fieldset 2" field defines its label as NULL, so its label element is not rendered.');
        static::assertStringContainsString('form-fieldset-no-header', $crawler->filter('.form-fieldset.fieldset-2')->attr('class'), 'Fieldsets without explicit labels get a special CSS class to adjust the page design.');
        static::assertSelectorTextSame('.field-group.field-title .field-label > div', 'Title', 'The "title" field uses NULL as the value of the label, which tells EasyAdmin to generate a label automatically as the title-case value of the field name.');

        // fields with a FALSE label defined
        static::assertSelectorTextSame('ul.nav-tabs #tablist-tab-3', '', 'The "Tab 3" defines its label as FALSE, so its label is rendered as an empty string.');
        static::assertSame($crawler->filter('ul.nav-tabs #tablist-tab-3')->attr('href'), '#tab-3', 'Tabs without explicit labels get IDs generated automatically with autoincrement numbers.');
        static::assertSelectorNotExists('.form-column.column-3 .form-column-title-content', 'The "Column 3" field defines its label as NULL, so its label element is not rendered.');
        static::assertStringContainsString('form-column-no-header', $crawler->filter('.form-column.column-3')->attr('class'), 'Columns without explicit labels get a special CSS class to adjust the page design.');
        static::assertSelectorNotExists('.form-fieldset.fieldset-3 .form-fieldset-title-content', 'The "Fieldset 3" field defines its label as FALSE, so its label element is not rendered.');
        static::assertStringContainsString('form-fieldset-no-header', $crawler->filter('.form-fieldset.fieldset-3')->attr('class'), 'Fieldsets without explicit labels get a special CSS class to adjust the page design.');
        static::assertSelectorNotExists('.field-group.field-slug .field-label', 'The "slug" field uses FALSE as the value of the label, which means that no <label> element should be rendered for that field.');

        // fields with a label defined as an empty string
        static::assertSelectorTextSame('ul.nav-tabs #tablist-tab-4', '', 'The "Tab 4" defines its label as an empty string, so its label is rendered as an empty string.');
        static::assertSame($crawler->filter('ul.nav-tabs #tablist-tab-4')->attr('href'), '#tab-4', 'Tabs without explicit labels get IDs generated automatically with autoincrement numbers.');
        static::assertSelectorNotExists('.form-column.column-4 .form-column-title-content', 'The "Column 4" field defines its label as an empty string, so its label element is not rendered.');
        static::assertStringContainsString('form-column-no-header', $crawler->filter('.form-column.column-4')->attr('class'), 'Columns without explicit labels get a special CSS class to adjust the page design.');
        static::assertSelectorNotExists('.form-fieldset.fieldset-4 .form-fieldset-title-content', 'The "Fieldset 4" field defines its label as an empty string, so its label element is not rendered.');
        static::assertStringContainsString('form-fieldset-no-header', $crawler->filter('.form-fieldset.fieldset-4')->attr('class'), 'Fieldsets without explicit labels get a special CSS class to adjust the page design.');
        static::assertSelectorTextSame('.field-group.field-content .field-label > div', '', 'The "content" field defines an empty string as the label, so it renders a <label> element to keep the design layout but without any contents inside.');

        // fields with a label defined as a text string
        static::assertSelectorTextSame('ul.nav-tabs #tablist-tab-tab-5', 'Tab 5', 'The "Tab 5" defines its label as a text string.');
        static::assertSame('#tab-tab-5', $crawler->filter('ul.nav-tabs #tablist-tab-tab-5')->attr('href'), 'Tabs with labels get IDs generated as the slugs of their labels.');
        static::assertSelectorTextSame('.form-column.column-5 .form-column-title-content', 'Column 5', 'The "Column 5" field defines its label as a text string.');
        static::assertSelectorNotExists('.form-column.column-5.form-column-no-header');
        static::assertSelectorTextSame('.form-fieldset.fieldset-5 .form-fieldset-title-content', 'Fieldset 5', 'The "Fieldset 5" field defines its label as text string.');
        static::assertSelectorNotExists('.form-fieldset.fieldset-5.form-fieldset-no-header');
        static::assertSelectorTextSame('.field-group.field-created-at .field-label > div', 'Lorem Ipsum 1', 'The "createdAt" field defines a regular text string as its label (no HTML contents).');

        // fields with a label defined as a translatable string
        static::assertSelectorTextSame('ul.nav-tabs #tablist-tab-tab-6', 'Tab 6', 'The "Tab 6" defines its label as a translatable string.');
        static::assertSame('#tab-tab-6', $crawler->filter('ul.nav-tabs #tablist-tab-tab-6')->attr('href'), 'Tabs with labels get IDs generated as the slugs of their labels.');
        static::assertSelectorTextSame('.form-column.column-6 .form-column-title-content', 'Column 6', 'The "Column 6" field defines its label as a translatable string.');
        static::assertSelectorNotExists('.form-column.column-6.form-column-no-header');
        static::assertSelectorTextSame('.form-fieldset.fieldset-6 .form-fieldset-title-content', 'Fieldset 6', 'The "Fieldset 6" field defines its label as translatable string.');
        static::assertSelectorNotExists('.form-fieldset.fieldset-6.form-fieldset-no-header');
        static::assertSelectorTextSame('.field-group.field-published-at .field-label > div', 'PublishedAt Lorem Ipsum', 'The "publishedAt" field defines its label as a translatable string with regular text (no HTML contents).');

        // fields with a label defined as a string with HTML contents
        static::assertSame('<span class="text-danger">Tab</span> <b>7</b>', trim($crawler->filter('ul.nav-tabs #tablist-tab-tab-7')->html()), 'The "Tab 7" defines its label as a text string with HTML contents, which must be rendered instead of escaped.');
        static::assertSame('#tab-tab-7', $crawler->filter('ul.nav-tabs #tablist-tab-tab-7')->attr('href'), 'Tabs with labels get IDs generated as the slugs of their labels but HTML tags must be removed.');
        static::assertSame('<a href="https://example.com">Column</a> <b>7</b>', trim($crawler->filter('.form-column.column-7 .form-column-title-content')->html()), 'The "Column 7" defines its label as a text string with HTML contents, which must be rendered instead of escaped.');
        static::assertSelectorNotExists('.form-column.column-7.form-column-no-header');
        static::assertSame('<a href="https://example.com">Fieldset</a> <b>7</b>', trim($crawler->filter('.form-fieldset.fieldset-7 .form-fieldset-title-content')->html()), 'The "Fieldset 7" defines its label as a text string with HTML contents, which must be rendered instead of escaped.');
        static::assertSelectorNotExists('.form-fieldset.fieldset-7.form-fieldset-no-header');
        static::assertSame(trim($crawler->filter('.field-group.field-author .field-label > div')->html()), '<a href="https://example.com">Author</a> <b>Lorem</b> Ipsum', 'The "author" field defines its label as regular string with HTML contents, which must be rendered instead of escaped (HTML is allowed in field labels).');

        // fields with a label defined as a translatable string with HTML contents
        static::assertSame('<span class="text-danger">Tab</span> <b>8</b>', trim($crawler->filter('ul.nav-tabs #tablist-tab-tab-8')->html()), 'The "Tab 8" defines its label as a translatable string with HTML contents, which must be rendered instead of escaped.');
        static::assertSame('#tab-tab-8', $crawler->filter('ul.nav-tabs #tablist-tab-tab-8')->attr('href'), 'Tabs with labels get IDs generated as the slugs of their labels but HTML tags must be removed.');
        static::assertSame('<a href="https://example.com">Column</a> <b>8</b>', trim($crawler->filter('.form-column.column-8 .form-column-title-content')->html()), 'The "Column 8" defines its label as a translatable string with HTML contents, which must be rendered instead of escaped.');
        static::assertSelectorNotExists('.form-column.column-8.form-column-no-header');
        static::assertSame('<a href="https://example.com">Fieldset</a> <b>8</b>', trim($crawler->filter('.form-fieldset.fieldset-8 .form-fieldset-title-content')->html()), 'The "Fieldset 8" defines its label as a text string with HTML contents, which must be rendered instead of escaped.');
        static::assertSelectorNotExists('.form-fieldset.fieldset-8.form-fieldset-no-header');
        static::assertSame(trim($crawler->filter('.field-group.field-publisher .field-label > div')->html()), '<a href="https://example.com">Publisher</a> <b>Lorem</b> Ipsum', 'The "publisher" field defines its label as translatable string with HTML contents, which must be rendered instead of escaped (HTML is allowed in field labels).');
    }
}
