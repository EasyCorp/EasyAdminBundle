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

class ActionTargetTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'action_target'));
    }

    public function testListViewActions()
    {
        $crawler = $this->requestListView();

        $this->assertEquals('_top', $crawler->filter('.global-actions form button[type="submit"]')->attr('formtarget'));
        $this->assertEquals('custom_target', $crawler->filter('.button-action a:contains("Add Category")')->attr('target'));

        $this->assertEquals('_blank', $crawler->filter('table a:contains("Edit")')->attr('target'));
        $this->assertEquals('_parent', $crawler->filter('table a:contains("Custom action")')->attr('target'));
        $this->assertEquals('_self', $crawler->filter('table a:contains("Another custom action")')->attr('target'));
        $this->assertEquals('_self', $crawler->filter('table a:contains("Delete")')->attr('target'));
    }

    public function testEditViewActions()
    {
        $crawler = $this->requestEditView();

        $this->assertEquals('_parent', $crawler->filter('.form-actions a:contains("Back to listing")')->attr('target'));
        $this->assertEquals('_blank', $crawler->filter('.form-actions a:contains("Custom action")')->attr('target'));
        $this->assertEquals('_blank', $crawler->filter('.form-actions a:contains("Delete")')->attr('target'));
        $this->assertEquals('_blank', $crawler->filter('#modal-delete-button')->attr('formtarget'));
    }

    public function testShowViewActions()
    {
        $crawler = $this->requestShowView();

        $this->assertEquals('_self', $crawler->filter('.form-actions a:contains("Edit")')->attr('target'));
        $this->assertEquals('_self', $crawler->filter('.form-actions a:contains("Back to listing")')->attr('target'));
        $this->assertEquals('custom_target', $crawler->filter('.form-actions a:contains("Custom action")')->attr('target'));
        $this->assertEquals('_self', $crawler->filter('.form-actions a:contains("Delete")')->attr('target'));
        $this->assertEquals('_self', $crawler->filter('#modal-delete-button')->attr('formtarget'));
    }

    public function testNewViewActions()
    {
        $crawler = $this->requestNewView();

        $this->assertEquals('_top', $crawler->filter('.form-actions a:contains("Back to listing")')->attr('target'));
        $this->assertEquals('_parent', $crawler->filter('.form-actions a:contains("Custom action")')->attr('target'));
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
    private function requestEditView($entityName = 'Category', $entityId = '200')
    {
        return $this->getBackendPage(array(
            'action' => 'edit',
            'entity' => $entityName,
            'id' => $entityId,
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
