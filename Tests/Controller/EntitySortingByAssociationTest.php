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

class EntitySortingByAssociationTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'entity_sorting_by_association'));
    }

    public function testListViewSorting()
    {
        $crawler = $this->requestListView();

        $this->assertContains('sorted', $crawler->filter('th[data-property-name="parent"]')->attr('class'));
        $this->assertContains('fa-caret-down', $crawler->filter('th[data-property-name="parent"] i')->attr('class'));
    }

    public function testSearchViewSorting()
    {
        $crawler = $this->requestSearchView();

        $this->assertContains('sorted', $crawler->filter('th[data-property-name="parent"]')->attr('class'));
        $this->assertContains('fa-caret-up', $crawler->filter('th[data-property-name="parent"] i')->attr('class'));
    }
}
