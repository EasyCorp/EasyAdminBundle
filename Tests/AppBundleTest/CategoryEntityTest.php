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

    /**
     * @return Crawler
     */
    private function requestEditView()
    {
        $client = static::createClient();

        return $client->request('GET', '/admin/?action=edit&view=list&entity=Category&id=200');
    }

    public function testListViewPageMainMenu()
    {
        $crawler = $this->requestShowView();

        $this->assertEquals('Categories', trim($crawler->filter('#header-menu li.active')->text()));
    }

    public function testListViewPageTitle()
    {
        $crawler = $this->requestListView();

        $this->assertEquals('Product Categories', trim($crawler->filter('head title')->text()));
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

        $this->assertEquals('New Category', trim($crawler->filter('#content-actions a.btn')->text()));
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

    public function testShowViewPageMainMenu()
    {
        $crawler = $this->requestShowView();

        $this->assertEquals('Categories', trim($crawler->filter('#header-menu li.active')->text()));
    }

    public function testShowViewPageTitle()
    {
        $crawler = $this->requestShowView();

        $this->assertEquals('Details for Category number 200', trim($crawler->filter('head title')->text()));
        $this->assertEquals('Details for Category number 200', trim($crawler->filter('h1.title')->text()));
    }

    public function testShowViewFieldLabels()
    {
        $crawler = $this->requestShowView();
        $fieldLabels = array('#', 'Label', 'Parent category');

        foreach ($fieldLabels as $i => $label) {
            $this->assertEquals($label, trim($crawler->filter('#main .form-group label')->eq($i)->text()));
        }
    }

    public function testShowViewFieldClasses()
    {
        $crawler = $this->requestShowView();
        $fieldClasses = array('integer', 'string', 'association');

        foreach ($fieldClasses as $i => $cssClass) {
            $this->assertContains('field_'.$cssClass, trim($crawler->filter('#main .form-group')->eq($i)->attr('class')));
        }
    }

    public function testShowViewActions()
    {
        $crawler = $this->requestShowView();

        // edit action
        $this->assertContains('fa-pencil-square', trim($crawler->filter('.form-actions a:contains("Modify Category") i')->attr('class')));

        // delete action (removed in configuration file)
        $this->assertCount(0, $crawler->filter('.form-actions button:contains("Delete")'));

        // list action
        $this->assertContains('fa-list', trim($crawler->filter('.form-actions a:contains("Back to Category listing") i')->attr('class')));
    }

    public function testShowViewListActionReferer()
    {
        $parameters = array(
            'action' => 'list',
            'entity' => 'Category',
            'page' => '2',
            'sortDirection' => 'ASC',
            'sortField' => 'name',
            'view' => 'list',
        );

        // 1. visit a specific 'list' view page
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/?'.http_build_query($parameters));

        // 2. click on the 'Show' link of the first item
        $link = $crawler->filter('td.actions a:contains("Show")')->eq(0)->link();
        $crawler = $client->click($link);

        // 3. the 'referer' parameter should point to the previous specific 'list' view page
        $refererUrl = $crawler->filter('.form-actions a:contains("Back to Category listing")')->attr('href');
        $queryString = parse_url($refererUrl, PHP_URL_QUERY);
        parse_str($queryString, $refererParameters);

        $this->assertEquals($parameters, $refererParameters);
    }

    /**
     * The 'referer' parameter stores the original 'list' or 'search' page
     * from which the user browsed to other pages (edit, delete, show). When
     * visiting several consecutive pages, the 'referer' value should be kept
     * without changes,
     */
    public function testChainedReferer()
    {
        $parameters = array(
            'action' => 'list',
            'entity' => 'Category',
            'page' => '2',
            'sortDirection' => 'ASC',
            'sortField' => 'name',
            'view' => 'list',
        );

        // 1. visit a specific 'list' view page
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/?'.http_build_query($parameters));

        // 2. click on the 'Show' link of the first item
        $link = $crawler->filter('td.actions a:contains("Show")')->eq(0)->link();
        $crawler = $client->click($link);

        // 3. click on the 'Edit' button
        $link = $crawler->filter('.form-actions a:contains("Modify Category")')->link();
        $crawler = $client->click($link);

        // 4. the 'referer' parameter should point to the previous specific 'list' view page
        $refererUrl = $crawler->filter('#form-actions-row a:contains("Return to listing")')->attr('href');
        $queryString = parse_url($refererUrl, PHP_URL_QUERY);
        parse_str($queryString, $refererParameters);

        $this->assertEquals($parameters, $refererParameters);
    }

    public function testEditViewPageMainMenu()
    {
        $crawler = $this->requestEditView();

        $this->assertEquals('Categories', trim($crawler->filter('#header-menu li.active')->text()));
    }

    public function testEditViewPageTitle()
    {
        $crawler = $this->requestEditView();

        $this->assertEquals('Modify Category (200) details', trim($crawler->filter('head title')->text()));
        $this->assertEquals('Modify Category (200) details', trim($crawler->filter('h1.title')->text()));
    }

    public function testEditViewFieldLabels()
    {
        $crawler = $this->requestEditView();
        $fieldLabels = array('ID', 'Label', 'Parent Category Label');

        foreach ($fieldLabels as $i => $label) {
            $this->assertEquals($label, trim($crawler->filter('#main .form-group label')->eq($i)->text()));
        }
    }

    public function testEditViewFieldClasses()
    {
        $crawler = $this->requestEditView();
        $fieldClasses = array('integer', 'text', 'default');

        foreach ($fieldClasses as $i => $cssClass) {
            $this->assertContains('field_'.$cssClass, trim($crawler->filter('#main .form-group')->eq($i)->attr('class')));
        }
    }

    public function testEditViewActions()
    {
        $crawler = $this->requestEditView();

        // save action
        $this->assertContains('fa-save', trim($crawler->filter('#form-actions-row button:contains("Save changes") i')->attr('class')));

        // delete action
        $this->assertContains('fa-minus-circle', trim($crawler->filter('#form-actions-row button:contains("Remove") i')->attr('class')));

        // list action
        $this->assertContains('fa-list', trim($crawler->filter('#form-actions-row a:contains("Return to listing") i')->attr('class')));
    }

    public function testEditViewListActionReferer()
    {
        $parameters = array(
            'action' => 'list',
            'entity' => 'Category',
            'page' => '2',
            'sortDirection' => 'ASC',
            'sortField' => 'name',
            'view' => 'list',
        );

        // 1. visit a specific 'list' view page
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/?'.http_build_query($parameters));

        // 2. click on the 'Edit' link of the first item
        $link = $crawler->filter('td.actions a:contains("Edit")')->eq(0)->link();
        $crawler = $client->click($link);

        // 3. the 'referer' parameter should point to the previous specific 'list' view page
        $refererUrl = $crawler->filter('#form-actions-row a:contains("Return to listing")')->attr('href');
        $queryString = parse_url($refererUrl, PHP_URL_QUERY);
        parse_str($queryString, $refererParameters);

        $this->assertEquals($parameters, $refererParameters);
    }
}
