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

    public function testWelcomePage()
    {
        $client = static::createClient();
        $client->followRedirects();
        $client->request('GET', '/admin/pretty/urls/blog_post');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Welcome to EasyAdmin 4');
    }
}
