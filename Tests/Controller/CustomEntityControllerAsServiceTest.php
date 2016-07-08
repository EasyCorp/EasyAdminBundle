<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Controller;

use Symfony\Component\DomCrawler\Crawler;
use JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class CustomEntityControllerAsServiceTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'custom_entity_controller_service'));
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

    /**
     * @return Crawler
     */
    private function requestListView()
    {
        return $this->getBackendPage(array(
            'action' => 'list',
            'entity' => 'Category',
            'view' => 'list',
        ));
    }

    /**
     * @return Crawler
     */
    private function requestShowView()
    {
        return $this->getBackendPage(array(
            'action' => 'show',
            'entity' => 'Category',
            'id' => '200',
        ));
    }
}
