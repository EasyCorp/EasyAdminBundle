<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class OverrideEasyAdminControllerTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(['environment' => 'override_controller']);
    }

    public function testLayoutIsOverridden()
    {
        $crawler = $this->client->request('GET', '/override_layout');

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Layout is overridden.', \trim($crawler->filter('#main')->text()));
    }
}
