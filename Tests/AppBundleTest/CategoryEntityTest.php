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

class CategoryEntityTest extends AbstractTestCase
{
    /**
     * @return Crawler
     */
    private function requestListView()
    {
        $client = static::createClient();

        return $client->request('GET', '/admin/?entity=Category&action=list&view=list');
    }

    /**
     * @return Crawler
     */
    private function requestShowView()
    {
        $client = static::createClient();

        return $client->request('GET', '/admin/?action=show&view=list&entity=Category&id=200');
    }

    public function testListViewPageTitle()
    {
        $crawler = $this->requestListView();

        $this->assertEquals('Product Categories', trim($crawler->filter('h1.title')->text()));
    }

    public function testListViewSearchAction()
    {
        $crawler = $this->requestListView();

        $hiddenParameters = array(
            'view' => 'list',
            'action' => 'search',
            'entity' => 'Category',
            'sortField' => 'id',
            'sortDirection' => 'DESC',
        );

        $this->assertEquals('Look for Categories', $crawler->filter('#content-search input[type=search]')->attr('placeholder'));

        $i = 0;
        foreach ($hiddenParameters as $name => $value) {
            $this->assertEquals($name, $crawler->filter('#content-search input[type=hidden]')->eq($i)->attr('name'));
            $this->assertEquals($value, $crawler->filter('#content-search input[type=hidden]')->eq($i)->attr('value'));

            $i++;
        }
    }

    public function testListViewNewAction()
    {
        $crawler = $this->requestListView();

        $this->assertEquals('New Categories', trim($crawler->filter('#content-actions a.btn')->text()));
        $this->assertEquals('fa fa-plus-circle', $crawler->filter('#content-actions a.btn i')->attr('class'));
        $this->assertEquals('/admin/?entity=Category&action=new&view=list', $crawler->filter('#content-actions a.btn')->attr('href'));
    }

    public function testListViewTableIdColumn()
    {
        $crawler = $this->requestListView();

        $this->assertEquals('ID', trim($crawler->filter('table th[data-property-name="id"]')->text()),
            'The ID entity property is very special and we uppercase it automatically to improve its readability.'
        );
    }

    public function testListViewTableColumnLabels()
    {
        $crawler = $this->requestListView();
        $columnLabels = array('ID', 'Label', 'Parent category', 'Actions');

        foreach ($columnLabels as $i => $label) {
            $this->assertEquals($label, trim($crawler->filter('.table thead th')->eq($i)->text()));
        }
    }

    public function testListViewTableColumnAttributes()
    {
        $crawler = $this->requestListView();
        $columnAttributes = array('id', 'name', 'parent');

        foreach ($columnAttributes as $i => $attribute) {
            $this->assertEquals($attribute, trim($crawler->filter('.table thead th')->eq($i)->attr('data-property-name')));
        }
    }

    public function testListViewDefaultTableSorting()
    {
        $crawler = $this->requestListView();

        $this->assertCount(1, $crawler->filter('.table thead th[class*="sorted"]'), 'Table is sorted only by one column.');
        $this->assertEquals('ID', trim($crawler->filter('.table thead th[class*="sorted"]')->text()), 'By default, table is soreted by ID column.');
        $this->assertEquals('fa fa-caret-down', $crawler->filter('.table thead th[class*="sorted"] i')->attr('class'), 'The column used to sort results shows the right icon.');
    }

    public function testListViewTableContents()
    {
        $crawler = $this->requestListView();

        $this->assertCount(15, $crawler->filter('.table tbody tr'));
    }

    public function testListViewTableRowAttributes()
    {
        $crawler = $this->requestListView();
        $columnAttributes = array('ID', 'Label', 'Parent category');

        foreach ($columnAttributes as $i => $attribute) {
            $this->assertEquals($attribute, trim($crawler->filter('.table tbody tr td')->eq($i)->attr('data-label')));
        }
    }

    public function testListViewPagination()
    {
        $crawler = $this->requestListView();

        $this->assertContains('1 - 15 of 200', $crawler->filter('.list-pagination')->text());

        $this->assertEquals('disabled', $crawler->filter('.list-pagination li:contains("First")')->attr('class'));
        $this->assertEquals('disabled', $crawler->filter('.list-pagination li:contains("Previous")')->attr('class'));

        $this->assertStringStartsWith('/admin/?view=list&action=list&entity=Category&sortField=id&sortDirection=DESC&page=2', $crawler->filter('.list-pagination li a:contains("Next")')->attr('href'));
        $this->assertStringStartsWith('/admin/?view=list&action=list&entity=Category&sortField=id&sortDirection=DESC&page=14', $crawler->filter('.list-pagination li a:contains("Last")')->attr('href'));
    }

    public function testShowViewPageTitle()
    {
        $crawler = $this->requestShowView();

        $this->assertEquals('Details for Categories number 200', trim($crawler->filter('h1.title')->text()));
    }

    public function testShowViewFieldLabels()
    {
        $crawler = $this->requestShowView();
        $fieldLabels = array('#', 'Label', 'Parent category');

        foreach ($fieldLabels as $i => $label) {
            $this->assertEquals($label, trim($crawler->filter('#main .form-group label')->eq($i)->text()));
        }
    }
}
