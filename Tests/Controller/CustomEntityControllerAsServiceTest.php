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
use Symfony\Component\HttpKernel\Kernel;
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
        if (2 === Kernel::MAJOR_VERSION && 3 === Kernel::MINOR_VERSION) {
            $this->markTestSkipped('This test is not compatible with this Symfony 2.3 because it requires the RequestStack service.');
        }

        $this->requestListView();
        $this->assertContains('Overridden list action as a service.', $this->client->getResponse()->getContent());
    }

    public function testShowAction()
    {
        if (2 === Kernel::MAJOR_VERSION && 3 === Kernel::MINOR_VERSION) {
            $this->markTestSkipped('This test is not compatible with this Symfony 2.3 because it requires the RequestStack service.');
        }

        $this->requestShowView();
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
