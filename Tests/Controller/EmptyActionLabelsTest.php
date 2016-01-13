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

class EmptyActionLabelsTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'empty_action_labels'));
    }

    public function testBuiltInActionLabels()
    {
        $crawler = $this->requestListView();

        // edit action
        $this->assertEquals('', trim($crawler->filter('#main table tr:first-child td.actions a')->eq(0)->text()));
        $this->assertEquals('fa fa-pencil', trim($crawler->filter('#main table tr:first-child td.actions a i')->eq(0)->attr('class')));

        // delete action
        $this->assertEquals('', trim($crawler->filter('#main table tr:first-child td.actions a')->eq(1)->text()));
        $this->assertEquals('fa fa-minus-circle', trim($crawler->filter('#main table tr:first-child td.actions a i')->eq(1)->attr('class')));
    }

    public function testCustomActionLabels()
    {
        $crawler = $this->requestListView();

        // custom action 1
        $this->assertEquals('', trim($crawler->filter('#main table tr:first-child td.actions a')->eq(2)->text()));
        $this->assertEquals('fa fa-icon1', trim($crawler->filter('#main table tr:first-child td.actions a i')->eq(2)->attr('class')));

        // custom action 2
        $this->assertEquals('', trim($crawler->filter('#main table tr:first-child td.actions a')->eq(3)->text()));
        $this->assertEquals('fa fa-icon2', trim($crawler->filter('#main table tr:first-child td.actions a i')->eq(3)->attr('class')));
    }

    public function testFalseActionLabels()
    {
        $crawler = $this->requestListView();

        // custom action with 'false' label used as a string instead of a boolean
        $this->assertEquals('false', trim($crawler->filter('#main table tr:first-child td.actions a')->eq(4)->text()));
        $this->assertEquals('fa fa-icon3', trim($crawler->filter('#main table tr:first-child td.actions a i')->eq(4)->attr('class')));
    }

    /**
     * @return Crawler
     */
    private function requestListView($entityName = 'Category')
    {
        return $this->getBackendPage(array(
            'action' => 'list',
            'entity' => $entityName,
            'view' => 'list',
        ));
    }
}
