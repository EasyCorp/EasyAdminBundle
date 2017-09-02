<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class DqlFilterTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'dql_filter'));
    }

    public function testListDqlFilter()
    {
        $crawler = $this->requestListView();

        $this->assertCount(4, $crawler->filter('#main .table tbody tr'));
        $this->assertSame(
            array('54', '53', '52', '51'),
            $crawler->filter('#main .table tbody tr')->extract('data-id')
        );
    }

    public function testSearchDqlFilter()
    {
        $crawler = $this->requestSearchView();

        $this->assertCount(11, $crawler->filter('#main .table tbody tr'));
        $this->assertSame(
            array('29', '28', '27', '26', '25', '24', '23', '22', '21', '20', '2'),
            $crawler->filter('#main .table tbody tr')->extract('data-id')
        );
    }
}
