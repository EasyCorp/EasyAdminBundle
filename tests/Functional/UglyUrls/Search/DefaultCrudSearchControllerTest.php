<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\UglyUrls\Search;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\UglyUrlsApp\Controller\BlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\UglyUrlsApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\UglyUrlsApp\Kernel;

/**
 * Tests search form behavior with ugly URLs (legacy URL format).
 * Specifically tests that hidden inputs are rendered in the search form.
 *
 * @group legacy
 */
class DefaultCrudSearchControllerTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return BlogPostCrudController::class;
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

    public function testSearchFormHasHiddenInputsWithUglyUrls(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $form = $crawler->filter('form.form-action-search');

        // with ugly URLs, hidden inputs ARE rendered in the form
        $this->assertSame('index', $form->filter('input[type="hidden"][name="crudAction"]')->attr('value'));
        $this->assertSame(BlogPostCrudController::class, $form->filter('input[type="hidden"][name="crudControllerFqcn"]')->attr('value'));
        $this->assertSame('1', $form->filter('input[type="hidden"][name="page"]')->attr('value'));

        // form action should be empty with ugly URLs
        $this->assertEmpty($form->attr('action'), 'Form action should be empty when using ugly URLs');

        $formSearchInput = $form->filter('input[name="query"]');
        $this->assertSame('', $formSearchInput->attr('value'));
        $this->assertSame('Search', $formSearchInput->attr('placeholder'));
    }

    public function testSearchFormAfterMakingAQuery(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl('blog post'));

        $form = $crawler->filter('form.form-action-search');

        // with ugly URLs, hidden inputs ARE rendered in the form
        $this->assertSame('index', $form->filter('input[type="hidden"][name="crudAction"]')->attr('value'));
        $this->assertSame(BlogPostCrudController::class, $form->filter('input[type="hidden"][name="crudControllerFqcn"]')->attr('value'));
        $this->assertSame('1', $form->filter('input[type="hidden"][name="page"]')->attr('value'));

        $formSearchInput = $form->filter('input[name="query"]');
        $this->assertSame('blog post', $formSearchInput->attr('value'));

        $this->assertSelectorExists('form.form-action-search .content-search-reset', 'After making a query, the search form should display the button to reset contents');
        $this->assertSame($this->generateIndexUrl(), $crawler->filter('form.form-action-search .content-search-reset')->attr('href'));
    }

    public function testPaginationHiddenInputAfterSubmittingSearch(): void
    {
        // browse the index page and make some query
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $form = $crawler->filter('form.form-action-search')->form();
        $crawler = $this->client->submit($form, ['query' => 'blog-post-0']);

        // with ugly URLs, the page hidden input should be present
        $form = $crawler->filter('form.form-action-search');
        $this->assertSame('1', $form->filter('input[type="hidden"][name="page"]')->attr('value'));
    }
}
