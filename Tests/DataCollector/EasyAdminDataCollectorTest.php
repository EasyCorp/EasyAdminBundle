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

class EasyAdminDataCollectorTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'default_backend'));
    }

    public function testCollectorIsEnabled()
    {
        $this->client->enableProfiler();
        $this->requestListView();

        $this->assertNotNull($this->client->getProfile()->getCollector('easyadmin'));
    }

    public function testCollectorInformationForListView()
    {
        $this->client->enableProfiler();
        $this->requestListView();
        $collector = $this->client->getProfile()->getCollector('easyadmin');

        $this->assertEquals(5, $collector->getNumEntities());

        $parameters = array(
            'action' => 'list',
            'entity' => 'Category',
            'id' => null,
            'sort_field' => 'id',
            'sort_direction' => 'DESC',
        );
        $this->assertEquals($parameters, $collector->getRequestParameters());

        $currentConfiguration = $collector->getCurrentEntityConfig();
        $this->assertEquals('Category', $currentConfiguration['name']);

        $backendConfig = $collector->getBackendConfig();
        $this->assertCount(5, $backendConfig['entities']);
    }

    public function testCollectorInformationForEditView()
    {
        $this->client->enableProfiler();
        $this->requestEditView();
        $collector = $this->client->getProfile()->getCollector('easyadmin');

        $this->assertEquals(5, $collector->getNumEntities());

        $parameters = array(
            'action' => 'edit',
            'entity' => 'Category',
            'id' => 200,
            'sort_field' => 'id',
            'sort_direction' => 'DESC',
        );
        $this->assertEquals($parameters, $collector->getRequestParameters());

        $currentConfiguration = $collector->getCurrentEntityConfig();
        $this->assertEquals('Category', $currentConfiguration['name']);

        $backendConfig = $collector->getBackendConfig();
        $this->assertCount(5, $backendConfig['entities']);
    }

    /**
     * @return Crawler
     */
    private function requestListView()
    {
        return $this->getBackendPage(array(
            'action' => 'list',
            'entity' => 'Category',
        ));
    }

    /**
     * @return Crawler
     */
    private function requestEditView()
    {
        return $this->getBackendPage(array(
            'action' => 'edit',
            'entity' => 'Category',
            'id' => '200',
        ));
    }
}
