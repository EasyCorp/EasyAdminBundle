<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class CustomEntityControllerAsServiceTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(['environment' => 'custom_entity_controller_service']);
    }

    public function testListAction()
    {
        $this->requestListView();

        if (!$this->client->getContainer()->has('request_stack')) {
            $this->markTestSkipped('This test is skipped because @request_stack service is not available.');
        }

        $this->assertContains('Overridden list action as a service.', $this->client->getResponse()->getContent());
    }

    public function testShowAction()
    {
        $this->requestShowView();

        if (!$this->client->getContainer()->has('request_stack')) {
            $this->markTestSkipped('This test is skipped because @request_stack service is not available.');
        }

        $this->assertContains('Overridden show action as a service.', $this->client->getResponse()->getContent());
    }
}
