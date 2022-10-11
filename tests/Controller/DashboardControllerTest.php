<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardControllerTest extends WebTestCase
{
    public function testWelcomePage()
    {
        $client = static::createClient();
        $client->followRedirects();
        $client->request('GET', '/admin');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Welcome to EasyAdmin 3');
    }

    public function testDashboardAsLoggedUser()
    {
        $client = static::createClient();
        $client->followRedirects();
        $client->request('GET', '/secure_admin', [], [], ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Welcome to EasyAdmin 3');
    }
}
