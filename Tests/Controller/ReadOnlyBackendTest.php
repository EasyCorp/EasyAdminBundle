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

class ReadOnlyBackendTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'read_only_backend'));
    }

    public function testListViewContainsNoDisabledActions()
    {
        $crawler = $this->requestListView();

        $this->assertCount(0, $crawler->filter('#content-actions a.btn:contains("Add Category")'), '"new" action is disabled.');
        $this->assertCount(0, $crawler->filter('#main .table td.actions a:contains("Edit")'), '"edit" action is disabled.');
    }

    public function testSearchViewContainsNoDisabledActions()
    {
        $crawler = $this->requestSearchView();

        $this->assertCount(0, $crawler->filter('#content-actions a.btn:contains("Add Category")'), '"new" action is disabled.');
        $this->assertCount(0, $crawler->filter('#main .table td.actions a:contains("Edit")'), '"edit" action is disabled.');
    }

    public function testShowViewContainsNoDisabledActions()
    {
        $crawler = $this->requestShowView();

        $this->assertCount(0, $crawler->filter('#form-actions a.btn:contains("Edit")'), '"edit" action is disabled.');
        $this->assertCount(0, $crawler->filter('#form-actions button:contains("Delete")'), '"delete" action is disabled.');
    }

    public function testEditActionIsDisabled()
    {
        $this->requestEditView();

        $this->assertEquals(500, $this->client->getResponse()->getStatusCode());
        $this->assertContains('Error: The requested &quot;edit&quot; action is not allowed for the &quot;Category&quot; entity. Solution: Remove the &quot;edit&quot; action from the &quot;disabled_actions&quot; option, which can be configured globally for the entire backend or locally for the &quot;Category&quot; entity.', $this->client->getResponse()->getContent());
    }

    public function testNewActionIsDisabled()
    {
        $this->requestNewView();

        $this->assertEquals(500, $this->client->getResponse()->getStatusCode());
        $this->assertContains('Error: The requested &quot;new&quot; action is not allowed for the &quot;Category&quot; entity. Solution: Remove the &quot;new&quot; action from the &quot;disabled_actions&quot; option, which can be configured globally for the entire backend or locally for the &quot;Category&quot; entity.', $this->client->getResponse()->getContent());
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
