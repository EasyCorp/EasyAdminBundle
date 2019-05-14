<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class EmptyBackendTest extends AbstractTestCase
{
    public function testNoEntityHasBeenConfigured()
    {
        $this->initClient(['environment' => 'empty_backend']);
        static::$client->request('GET', '/admin/');

        $this->assertSame(500, static::$client->getResponse()->getStatusCode());
        $this->assertContains('NoEntitiesConfiguredException', static::$client->getResponse()->getContent());
    }
}
