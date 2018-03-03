<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class BackendErrorsTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(['environment' => 'default_backend']);
    }

    public function testUndefinedEntityError()
    {
        $crawler = $this->getBackendPage([
            'entity' => 'InexistentEntity',
            'view' => 'list',
        ]);

        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
        $this->assertContains('The "InexistentEntity" entity is not defined in the configuration of your backend.', $crawler->filter('head title')->text());
    }
}
