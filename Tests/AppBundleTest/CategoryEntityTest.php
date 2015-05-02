<?php

namespace AppBundle\Tests;

use JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class CategoryEntityTest extends AbstractTestCase
{
    public function testListViewPageTitle()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/admin/?entity=Category&action=list&view=list');

        $this->assertEquals('Product Categories', trim($crawler->filter('h1.title')->text()));
    }

    public function testListViewSearchAction()
    {
        $hiddenParameters = array(
            'view' => 'list',
            'action' => 'search',
            'entity' => 'Category',
            'sortField' => 'id',
            'sortDirection' => 'DESC',
        );

        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/?entity=Category&action=list&view=list');

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
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/?entity=Category&action=list&view=list');

        $this->assertEquals('New Categories', trim($crawler->filter('#content-actions a.btn')->text()));
        $this->assertEquals('fa fa-plus-circle', $crawler->filter('#content-actions a.btn i')->attr('class'));
        $this->assertEquals('/admin/?entity=Category&action=new&view=list', $crawler->filter('#content-actions a.btn')->attr('href'));
    }

    public function testListViewTableIdColumn()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/?entity=Category&action=list&view=list');

        $this->assertEquals('ID', trim($crawler->filter('table th[data-property-name="id"]')->text()),
            'The ID entity property is very special and we uppercase it automatically to improve its readability.'
        );
    }

    public function testListViewTableColumnLabels()
    {
        $columnLabels = array('ID', 'Label', 'Parent category', 'Actions');

        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/?entity=Category&action=list&view=list');

        foreach ($columnLabels as $i => $label) {
            $this->assertEquals($label, trim($crawler->filter('.table thead th')->eq($i)->text()));
        }
    }

    public function testListViewTableColumnAttributes()
    {
        $columnAttributes = array('id', 'name', 'parent');

        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/?entity=Category&action=list&view=list');

        foreach ($columnAttributes as $i => $attribute) {
            $this->assertEquals($attribute, trim($crawler->filter('.table thead th')->eq($i)->attr('data-property-name')));
        }
    }

    public function testListViewDefaultTableSorting()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/?entity=Category&action=list&view=list');

        $this->assertCount(1, $crawler->filter('.table thead th[class*="sorted"]'), 'Table is sorted only by one column.');

        $this->assertEquals('ID', trim($crawler->filter('.table thead th[class*="sorted"]')->text()), 'By default, table is soreted by ID column.');

        $this->assertEquals('fa fa-caret-down', $crawler->filter('.table thead th[class*="sorted"] i')->attr('class'), 'The column used to sort results shows the right icon.');
    }

    public function testListViewTableContents()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/?entity=Category&action=list&view=list');

        $this->assertCount(15, $crawler->filter('.table tbody tr'));
    }

    public function testListViewTableRowAttributes()
    {
        $columnAttributes = array('ID', 'Label', 'Parent category');

        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/?entity=Category&action=list&view=list');

        foreach ($columnAttributes as $i => $attribute) {
            $this->assertEquals($attribute, trim($crawler->filter('.table tbody tr td')->eq($i)->attr('data-label')));
        }
    }

    public function testListViewPagination()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/?entity=Category&action=list&view=list');

        $this->assertContains('1 - 15 of 200', $crawler->filter('.list-pagination')->text());

        $this->assertEquals('disabled', $crawler->filter('.list-pagination li:contains("First")')->attr('class'));
        $this->assertEquals('disabled', $crawler->filter('.list-pagination li:contains("Previous")')->attr('class'));

        $this->assertEquals('/admin/?view=list&action=list&entity=Category&sortField=id&sortDirection=DESC&page=2', $crawler->filter('.list-pagination li a:contains("Next")')->attr('href'));
        $this->assertEquals('/admin/?view=list&action=list&entity=Category&sortField=id&sortDirection=DESC&page=14', $crawler->filter('.list-pagination li a:contains("Last")')->attr('href'));
    }
}
