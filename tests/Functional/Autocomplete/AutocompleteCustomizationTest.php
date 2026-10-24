<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Autocomplete;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Autocomplete\BlogPostWithAutocompleteCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Autocomplete\BlogPostWithRenderAsHtmlCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;

class AutocompleteCustomizationTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return BlogPostWithAutocompleteCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testAutocompleteWithFieldLevelCallback(): void
    {
        $category = CategoryFactory::createOne(['name' => 'Category 1']);
        CategoryFactory::createOne(['name' => 'Category 2']);

        $autocompleteUrl = $this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController(CategoryCrudController::class)
            ->setAction('autocomplete')
            ->set('page', 1)
            ->set('query', 'Category 1')
            ->set('autocompleteContext', [
                'crudControllerFqcn' => BlogPostWithAutocompleteCrudController::class,
                'propertyName' => 'categories',
                'originatingPage' => 'new',
            ])
            ->generateUrl();

        $this->client->request('GET', $autocompleteUrl);
        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('results', $data);
        $this->assertCount(1, $data['results']);
        $this->assertSame(sprintf('Category: Category 1 (ID: %d)', $category->getId()), $data['results'][0]['entityAsString']);
    }

    public function testAutocompleteWithCrudLevelCallback(): void
    {
        $blogPost = BlogPostFactory::createOne(['title' => 'BlogPost 1']);

        // the 'title' field has no field-level autocomplete callback, so the
        // CRUD-level callback defined in configureCrud() is used
        $autocompleteUrl = $this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController(BlogPostWithAutocompleteCrudController::class)
            ->setAction('autocomplete')
            ->set('page', 1)
            ->set('autocompleteContext', [
                'crudControllerFqcn' => BlogPostWithAutocompleteCrudController::class,
                'propertyName' => 'title',
                'originatingPage' => 'index',
            ])
            ->generateUrl();

        $this->client->request('GET', $autocompleteUrl);
        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(1, $data['results']);
        $this->assertSame(sprintf('[%d] BlogPost 1', $blogPost->getId()), $data['results'][0]['entityAsString']);
    }

    public function testAutocompleteEscapesHtmlByDefault(): void
    {
        CategoryFactory::createOne(['name' => '<script>alert("XSS")</script>Malicious Category']);

        $autocompleteUrl = $this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController(CategoryCrudController::class)
            ->setAction('autocomplete')
            ->set('page', 1)
            ->set('query', 'Malicious')
            ->set('autocompleteContext', [
                'crudControllerFqcn' => BlogPostWithAutocompleteCrudController::class,
                'propertyName' => 'categories',
                'originatingPage' => 'new',
            ])
            ->generateUrl();

        $this->client->request('GET', $autocompleteUrl);
        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertCount(1, $data['results']);
        $firstResult = $data['results'][0]['entityAsString'];

        $this->assertStringContainsString('&lt;script&gt;', $firstResult);
        $this->assertStringNotContainsString('<script>', $firstResult);
    }

    public function testAutocompleteReturnsValidJsonStructure(): void
    {
        $category = CategoryFactory::createOne(['name' => 'Category 1']);

        $autocompleteUrl = $this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController(CategoryCrudController::class)
            ->setAction('autocomplete')
            ->set('page', 1)
            ->set('autocompleteContext', [
                'crudControllerFqcn' => BlogPostWithAutocompleteCrudController::class,
                'propertyName' => 'categories',
                'originatingPage' => 'new',
            ])
            ->generateUrl();

        $this->client->request('GET', $autocompleteUrl);
        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('results', $data);
        $this->assertArrayHasKey('next_page', $data);
        $this->assertCount(1, $data['results']);
        $this->assertSame((string) $category->getId(), $data['results'][0]['entityId']);
        $this->assertArrayHasKey('entityAsString', $data['results'][0]);
    }

    public function testAutocompleteWithCallbackAndRenderAsHtml(): void
    {
        CategoryFactory::createOne(['name' => 'Important Category']);

        // build autocomplete URL using the controller with renderAsHtml: true
        $autocompleteUrl = $this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController(CategoryCrudController::class)
            ->setAction('autocomplete')
            ->set('page', 1)
            ->set('query', 'Important')
            ->set('autocompleteContext', [
                'crudControllerFqcn' => BlogPostWithRenderAsHtmlCrudController::class,
                'propertyName' => 'categories',
                'originatingPage' => 'new',
            ])
            ->generateUrl();

        $this->client->request('GET', $autocompleteUrl);
        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('results', $data);
        $this->assertCount(1, $data['results']);

        $firstResult = $data['results'][0]['entityAsString'];

        // when renderAsHtml: true, HTML tags should NOT be escaped
        $this->assertStringContainsString('<strong>', $firstResult);
        $this->assertStringContainsString('</strong>', $firstResult);
        $this->assertStringNotContainsString('&lt;strong&gt;', $firstResult);
        $this->assertStringContainsString('Important Category', $firstResult);
    }

    public function testSelectedItemRenderedSameAsDropdownEntries(): void
    {
        $category = CategoryFactory::createOne(['name' => 'Category 1']);
        $blogPost = BlogPostFactory::createOne(['categories' => [$category]]);

        // open the edit form for the blog post
        $editUrl = $this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController(BlogPostWithAutocompleteCrudController::class)
            ->setAction('edit')
            ->setEntityId($blogPost->getId())
            ->generateUrl();

        $this->client->request('GET', $editUrl);
        $this->assertResponseIsSuccessful();

        // the selected option should use the same callback format as dropdown entries:
        // "Category: {name} (ID: {id})"
        $expectedLabel = sprintf('Category: Category 1 (ID: %s)', $category->getId());
        $this->assertSelectorExists(sprintf('option[selected]:contains("%s")', $expectedLabel));
    }

    public function testSelectedItemRenderedWithHtmlCallback(): void
    {
        $category = CategoryFactory::createOne(['name' => 'Category 1']);
        $blogPost = BlogPostFactory::createOne(['categories' => [$category]]);

        // open the edit form using the controller with renderAsHtml: true
        $editUrl = $this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController(BlogPostWithRenderAsHtmlCrudController::class)
            ->setAction('edit')
            ->setEntityId($blogPost->getId())
            ->generateUrl();

        $this->client->request('GET', $editUrl);
        $this->assertResponseIsSuccessful();

        // the selected option should contain the HTML-formatted label
        // the callback returns: "<strong>{name}</strong> (ID: {id})"
        $crawler = $this->client->getCrawler();
        $selectedOption = $crawler->filter('select[name*="categories"] option[selected]');

        $this->assertCount(1, $selectedOption);
        $optionText = $selectedOption->text();

        $this->assertStringContainsString('Category 1', $optionText);
        $this->assertStringContainsString(sprintf('(ID: %d)', $category->getId()), $optionText);
    }
}
