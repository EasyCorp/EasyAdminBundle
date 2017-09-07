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

class EntitySortingTest extends AbstractTestCase
{
    public function setUp()
    {
        // parent::setUp();

        $this->initClient(array('environment' => 'entity_sorting'));
    }

    public function testMainMenuSorting()
    {
        $crawler = $this->requestListView('Product');

        $this->assertContains('sortField=price', $crawler->filter('.sidebar-menu a:contains("Product 1")')->attr('href'));
        $this->assertNotContains('sortDirection', $crawler->filter('.sidebar-menu a:contains("Product 1")')->attr('href'));
        $this->assertContains('sortField=price', $crawler->filter('.sidebar-menu a:contains("Product 2")')->attr('href'));
        $this->assertContains('sortDirection=ASC', $crawler->filter('.sidebar-menu a:contains("Product 2")')->attr('href'));
        $this->assertContains('sortField=id', $crawler->filter('.sidebar-menu a:contains("Product 3")')->attr('href'));
        $this->assertNotContains('sortDirection', $crawler->filter('.sidebar-menu a:contains("Product 3")')->attr('href'));

        // click on any menu item to sort contents differently
        $link = $crawler->filter('.sidebar-menu a:contains("Product 2")')->link();
        $crawler = $this->client->click($link);
        $this->assertNotContains('sorted', $crawler->filter('th[data-property-name="name"]')->attr('class'));
        $this->assertContains('sorted', $crawler->filter('th[data-property-name="price"]')->attr('class'));
        $this->assertContains('fa-caret-up', $crawler->filter('th[data-property-name="price"] i')->attr('class'));
    }

    public function testListViewSorting()
    {
        $crawler = $this->requestListView('Product');

        // check the default sorting of the page
        $this->assertContains('sorted', $crawler->filter('th[data-property-name="name"]')->attr('class'));
        $this->assertContains('fa-caret-down', $crawler->filter('th[data-property-name="name"] i')->attr('class'));

        // click on any other table column to sort contents differently
        $link = $crawler->filter('th[data-property-name="price"] a')->link();
        $crawler = $this->client->click($link);
        $this->assertNotContains('sorted', $crawler->filter('th[data-property-name="name"]')->attr('class'));
        $this->assertContains('sorted', $crawler->filter('th[data-property-name="price"]')->attr('class'));
        $this->assertContains('fa-caret-down', $crawler->filter('th[data-property-name="price"] i')->attr('class'));
    }

    public function testSearchViewSorting()
    {
        $crawler = $this->requestSearchView('lorem', 'Product');

        // check the default sorting of the page
        $this->assertContains('sorted', $crawler->filter('th[data-property-name="createdAt"]')->attr('class'));
        $this->assertContains('fa-caret-up', $crawler->filter('th[data-property-name="createdAt"] i')->attr('class'));

        // click on any other table column to sort contents differently
        $link = $crawler->filter('th[data-property-name="name"] a')->link();
        $crawler = $this->client->click($link);
        $this->assertNotContains('sorted', $crawler->filter('th[data-property-name="createdAt"]')->attr('class'));
        $this->assertContains('sorted', $crawler->filter('th[data-property-name="name"]')->attr('class'));
        $this->assertContains('fa-caret-down', $crawler->filter('th[data-property-name="name"] i')->attr('class'));
    }
}
