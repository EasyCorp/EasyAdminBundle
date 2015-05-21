<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Tests;

use Symfony\Component\DomCrawler\Crawler;
use JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class ProductEntityTest extends AbstractTestCase
{
    /**
     * @return Crawler
     */
    private function requestListView()
    {
        return $this->doGetRequest(array(
            'entity' => 'Product',
            'action' => 'list',
            'view' => 'list',
        ));
    }

    public function testListViewVirtualFields()
    {
        $crawler = $this->requestListView();

        $this->assertCount(15, $crawler->filter('.table tbody td:contains("inaccessible")'));

        $this->assertEquals('thisFieldIsVirtual', $crawler->filter('.table tbody td:contains("inaccessible")')->first()->attr('data-label'));

        $firstVirtualField = $crawler->filter('.table tbody td:contains("inaccessible") span')->first();
        $this->assertEquals('label label-danger', $firstVirtualField->attr('class'));
        $this->assertEquals(
            'Getter method does not exist for this field or the property is not public',
            $firstVirtualField->attr('title')
        );
    }
}
