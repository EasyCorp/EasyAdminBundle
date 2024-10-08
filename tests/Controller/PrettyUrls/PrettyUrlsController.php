<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller\PrettyUrls;

use EasyCorp\Bundle\EasyAdminBundle\Tests\PrettyUrlsTestApplication\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PrettyUrlsController extends WebTestCase
{
    public static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testGeneratedRoutes()
    {
        $expectedRoutes = [];
        $expectedRoutes['admin_pretty'] = '/admin/pretty/urls';
        $expectedRoutes['admin_pretty_blog_post_index'] = '/admin/pretty/urls/blog_post/';
        $expectedRoutes['admin_pretty_blog_post_detail'] = '/admin/pretty/urls/blog_post/{entityId}';
        $expectedRoutes['admin_pretty_blog_post_new'] = '/admin/pretty/urls/blog_post/new';
        $expectedRoutes['admin_pretty_blog_post_edit'] = '/admin/pretty/urls/blog_post/{entityId}/edit';
        $expectedRoutes['admin_pretty_blog_post_delete'] = '/admin/pretty/urls/blog_post/{entityId}/delete';
        $expectedRoutes['admin_pretty_blog_post_batchDelete'] = '/admin/pretty/urls/blog_post/batchDelete';
        $expectedRoutes['admin_pretty_blog_post_autocomplete'] = '/admin/pretty/urls/blog_post/autocomplete';
        $expectedRoutes['admin_pretty_category_index'] = '/admin/pretty/urls/category/';
        $expectedRoutes['admin_pretty_category_detail'] = '/admin/pretty/urls/category/{entityId}';
        $expectedRoutes['admin_pretty_category_new'] = '/admin/pretty/urls/category/new';
        $expectedRoutes['admin_pretty_category_edit'] = '/admin/pretty/urls/category/{entityId}/edit';
        $expectedRoutes['admin_pretty_category_delete'] = '/admin/pretty/urls/category/{entityId}/delete';
        $expectedRoutes['admin_pretty_category_batchDelete'] = '/admin/pretty/urls/category/batchDelete';
        $expectedRoutes['admin_pretty_category_autocomplete'] = '/admin/pretty/urls/category/autocomplete';

        self::bootKernel();
        $container = static::getContainer();
        $router = $container->get('router');
        $generatedRoutes = [];
        foreach ($router->getRouteCollection() as $name => $route) {
            $generatedRoutes[$name] = $route->getPath();
        }

        ksort($generatedRoutes);
        ksort($expectedRoutes);

        $this->assertEquals($expectedRoutes, $generatedRoutes);
    }

    public function testWelcomePage()
    {
        $client = static::createClient();
        $client->followRedirects();

        $client->request('GET', '/admin/pretty/urls');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Welcome to EasyAdmin 4');
    }

    public function testBlogPostController()
    {
        $client = static::createClient();
        $client->followRedirects();

        $client->request('GET', '/admin/pretty/urls/blog_post');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1.title', 'BlogPost');
    }

    public function testMainMenuUsesPrettyUrls()
    {
        $client = static::createClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/admin/pretty/urls/blog_post');

        $this->assertSame('Dashboard', $crawler->filter('li.menu-item a[href="http://localhost/admin/pretty/urls/blog_post/"]')->first()->text());
        $this->assertSame('Blog Posts', $crawler->filter('li.menu-item a[href="http://localhost/admin/pretty/urls/blog_post/"]')->last()->text());
        $this->assertSame('Categories', $crawler->filter('li.menu-item a[href="http://localhost/admin/pretty/urls/category/"]')->text());
    }

    public function testActionsUsePrettyUrls()
    {
        $client = static::createClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/admin/pretty/urls/blog_post');

        $this->assertSame('http://localhost/admin/pretty/urls/blog_post/?page=1', $crawler->filter('form.form-action-search')->attr('action'));
        $this->assertSame('Add BlogPost', $crawler->filter('.global-actions a.action-new[href="http://localhost/admin/pretty/urls/blog_post/new"]')->text());
        $this->assertMatchesRegularExpression('#http://localhost/admin/pretty/urls/blog_post/1/edit\?csrfToken=.*&fieldName=content#', $crawler->filter('td.field-boolean input[type="checkbox"]')->attr('data-toggle-url'));
        $this->assertSame('Edit', $crawler->filter('td a.action-edit[href="http://localhost/admin/pretty/urls/blog_post/1/edit"]')->text());
        $this->assertSame('Delete', $crawler->filter('td a.action-delete[href="http://localhost/admin/pretty/urls/blog_post/1/delete"]')->text());
    }

    public function testSortLinksUsePrettyUrls()
    {
        $client = static::createClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/admin/pretty/urls/blog_post');

        $this->assertSame('http://localhost/admin/pretty/urls/blog_post/?page=1&sort%5Bid%5D=DESC', $crawler->filter('th.searchable a')->eq(0)->attr('href'));
        $this->assertSame('http://localhost/admin/pretty/urls/blog_post/?page=1&sort%5Btitle%5D=DESC', $crawler->filter('th.searchable a')->eq(1)->attr('href'));
        $this->assertSame('http://localhost/admin/pretty/urls/blog_post/?page=1&sort%5Bslug%5D=DESC', $crawler->filter('th.searchable a')->eq(2)->attr('href'));
        $this->assertSame('http://localhost/admin/pretty/urls/blog_post/?page=1&sort%5Bcontent%5D=DESC', $crawler->filter('th.searchable a')->eq(3)->attr('href'));
        $this->assertSame('http://localhost/admin/pretty/urls/blog_post/?page=1&sort%5Bauthor%5D=DESC', $crawler->filter('th.searchable a')->eq(4)->attr('href'));
    }
}
