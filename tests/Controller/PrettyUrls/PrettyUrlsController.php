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
}
