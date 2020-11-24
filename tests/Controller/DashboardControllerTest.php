<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DashboardControllerTest extends WebTestCase
{
    public function testDashboardAsAnonymousUser()
    {
        $client = static::createClient();
        $client->followRedirects();
        $client->request('GET', '/admin');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testDashboardAsLoggedUser()
    {
        $client = static::createClient();
        $client->followRedirects();
        $client->request('GET', '/admin', [], [], ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);

        $this->assertResponseIsSuccessful();
    }
}
