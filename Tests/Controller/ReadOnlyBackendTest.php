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

use JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;
use JavierEguiluz\Bundle\EasyAdminBundle\Exception\NoEntitiesConfiguredException;

class ReadOnlyBackendTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'read_only_backend'));
    }

    public function testListViewContaisNoDisabledActions()
    {
        $crawler = $this->requestListView();

        $this->assertCount(0, $crawler->filter('#content-actions a.btn:contains("Add Category")'), '"new" action is disabled.');
        $this->assertCount(0, $crawler->filter('#main .table td.actions a:contains("Edit")'), '"edit" action is disabled.');
    }

    public function testSearchViewContaisNoDisabledActions()
    {
        $crawler = $this->requestSearchView();

        $this->assertCount(0, $crawler->filter('#content-actions a.btn:contains("Add Category")'), '"new" action is disabled.');
        $this->assertCount(0, $crawler->filter('#main .table td.actions a:contains("Edit")'), '"edit" action is disabled.');
    }

    public function testShowViewContaisNoDisabledActions()
    {
        $crawler = $this->requestShowView();

        $this->assertCount(0, $crawler->filter('#form-actions a.btn:contains("Edit")'), '"edit" action is disabled.');
        $this->assertCount(0, $crawler->filter('#form-actions button:contains("Delete")'), '"delete" action is disabled.');
    }

    public function testEditActionIsDisabled()
    {
        $crawler = $this->requestEditView();

        $this->assertEquals(500, $this->client->getResponse()->getStatusCode());
        $this->assertContains('The requested edit action is not allowed.', $this->client->getResponse()->getContent());
    }

    public function testNewActionIsDisabled()
    {
        $crawler = $this->requestNewView();

        $this->assertEquals(500, $this->client->getResponse()->getStatusCode());
        $this->assertContains('The requested new action is not allowed.', $this->client->getResponse()->getContent());
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
    private function requestShowView()
    {
        return $this->getBackendPage(array(
            'action' => 'show',
            'entity' => 'Category',
            'id' => '200',
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

    /**
     * @return Crawler
     */
    private function requestNewView()
    {
        return $this->getBackendPage(array(
            'action' => 'new',
            'entity' => 'Category',
        ));
    }

    /**
     * @return Crawler
     */
    private function requestSearchView()
    {
        return $this->getBackendPage(array(
            'action' => 'search',
            'entity' => 'Category',
            'query' => 'cat',
        ));
    }
}
