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

class RawFieldTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'raw_field'));
    }

    public function testListViewRawField()
    {
        $crawler = $this->requestListView();

        $this->assertRegExp('/\s*<ul>\s*(<li>.*<\/li>\s*){2}\s*<\/ul>/', $crawler->filter('#main table td[data-label="Html features"]')->eq(0)->html());
    }

    public function testShowViewRawField()
    {
        $crawler = $this->requestShowView();

        $this->assertRegExp('/\s*<ul>\s*(<li>.*<\/li>\s*){2}\s*<\/ul>/', $crawler->filter('#main .form-control')->eq(0)->html());
    }

    /**
     * @return Crawler
     */
    private function requestListView()
    {
        return $this->getBackendPage(array(
            'action' => 'list',
            'entity' => 'Product',
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
            'entity' => 'Product',
            'id' => '50',
        ));
    }
}
