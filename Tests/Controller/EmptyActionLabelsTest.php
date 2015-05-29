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

    public function testListViewActionLabels()
    {
        $crawler = $this->requestListView();

        // show action
        $this->assertEquals('', trim($crawler->filter('#main table tr:first-child td.actions a')->eq(0)->text()));
        $this->assertEquals('fa fa-search', trim($crawler->filter('#main table tr:first-child td.actions a i')->eq(0)->attr('class')));

        // edit action
        $this->assertEquals('', trim($crawler->filter('#main table tr:first-child td.actions a')->eq(1)->text()));
        $this->assertEquals('fa fa-pencil', trim($crawler->filter('#main table tr:first-child td.actions a i')->eq(1)->attr('class')));
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
