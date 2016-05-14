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

class CustomizedBackendTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'customized_backend'));
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
            'action' => 'search',
            'entity' => 'Category',
            'sortField' => 'id',
            'sortDirection' => 'DESC',
        );

        $this->assertEquals('Look for Categories', trim($crawler->filter('.action-search button[type=submit]')->text()));
        $this->assertContains('custom_class_search', $crawler->filter('.action-search')->attr('class'));

        $i = 0;
        foreach ($hiddenParameters as $name => $value) {
            $this->assertEquals($name, $crawler->filter('.action-search input[type=hidden]')->eq($i)->attr('name'));
            $this->assertEquals($value, $crawler->filter('.action-search input[type=hidden]')->eq($i)->attr('value'));

            ++$i;
        }
    }

    public function testListViewNewAction()
    {
        $crawler = $this->requestListView();

        $this->assertEquals('New Category', trim($crawler->filter('.global-actions a.action-new')->text()));
        $this->assertEquals('custom_class_new action-new', $crawler->filter('.global-actions a.action-new')->attr('class'));
        $this->assertEquals('fa fa-plus-circle', $crawler->filter('.global-actions a.action-new i')->attr('class'));
        $this->assertStringStartsWith('/admin/?action=new&entity=Category&sortField=id&sortDirection=DESC&page=1', $crawler->filter('.global-actions a.action-new')->attr('href'));
    }

    public function testListViewItemActions()
    {
        $crawler = $this->requestListView();

        $this->assertEquals('Show', trim($crawler->filter('#main .table td.actions a')->eq(0)->text()));
        $this->assertEquals('Edit', trim($crawler->filter('#main .table td.actions a')->eq(1)->text()));
        $this->assertEquals('Delete', trim($crawler->filter('#main .table td.actions a')->eq(2)->text()));
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

    public function testListViewFieldClasses()
    {
        $crawler = $this->requestListView();

        $this->assertEquals('string custom_class_list', trim($crawler->filter('.table thead tr th[data-property-name="name"]')->eq(0)->attr('class')));
        $this->assertEquals('string custom_class_list', trim($crawler->filter('.table tbody tr td[data-label="Label"]')->eq(0)->attr('class')));
    }

    public function testListViewPagination()
    {
        $crawler = $this->requestListView();

        $this->assertContains('1 - 15 of 200', $crawler->filter('.list-pagination')->text());

        $this->assertEquals('disabled', $crawler->filter('.list-pagination li:contains("First")')->attr('class'));
        $this->assertEquals('disabled', $crawler->filter('.list-pagination li:contains("Previous")')->attr('class'));

        $this->assertStringStartsWith('/admin/?action=list&entity=Category&sortField=id&sortDirection=DESC&page=2', $crawler->filter('.list-pagination li a:contains("Next")')->attr('href'));
        $this->assertStringStartsWith('/admin/?action=list&entity=Category&sortField=id&sortDirection=DESC&page=14', $crawler->filter('.list-pagination li a:contains("Last")')->attr('href'));
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
            $this->assertContains('field-'.$cssClass, trim($crawler->filter('#main .form-group')->eq($i)->attr('class')));
        }
    }

    public function testShowViewActions()
    {
        $crawler = $this->requestShowView();

        // edit action
        $this->assertContains('fa-pencil-square', trim($crawler->filter('.form-actions a:contains("Modify Category") i')->attr('class')));

        // delete action (removed in configuration file)
        $this->assertCount(0, $crawler->filter('.form-actions a:contains("Delete")'));

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
        );

        // 1. visit a specific 'list' view page
        $crawler = $this->getBackendPage($parameters);

        // 2. click on the 'Show' link of the first item
        $link = $crawler->filter('td.actions a:contains("Show")')->eq(0)->link();
        $crawler = $this->client->click($link);

        // 3. the 'referer' parameter should point to the exact same previous 'list' page
        $refererUrl = $crawler->filter('.form-actions a:contains("Back to Category listing")')->attr('href');
        $queryString = parse_url($refererUrl, PHP_URL_QUERY);
        parse_str($queryString, $refererParameters);

        $this->assertEquals($parameters, $refererParameters);
    }

    /**
     * The 'referer' parameter stores the original 'list' or 'search' page
     * from which the user browsed to other pages (edit, delete, show). When
     * visiting several consecutive pages, the 'referer' value should be kept
     * without changes.
     */
    public function testChainedReferer()
    {
        $parameters = array(
            'action' => 'list',
            'entity' => 'Category',
            'page' => '2',
            'sortDirection' => 'ASC',
            'sortField' => 'name',
        );

        // 1. visit a specific 'list' view page
        $crawler = $this->getBackendPage($parameters);

        // 2. click on the 'Show' link of the first item
        $link = $crawler->filter('td.actions a:contains("Show")')->eq(0)->link();
        $crawler = $this->client->click($link);

        // 3. click on the 'Edit' button
        $link = $crawler->filter('.form-actions a:contains("Modify Category")')->link();
        $crawler = $this->client->click($link);

        // 4. the 'referer' parameter should point to the exact same previous 'list' page
        $refererUrl = $crawler->filter('#form-actions-row a:contains("Return to listing")')->attr('href');
        $queryString = parse_url($refererUrl, PHP_URL_QUERY);
        parse_str($queryString, $refererParameters);

        $this->assertEquals($parameters, $refererParameters);
    }

    public function testEditViewPageTitle()
    {
        $crawler = $this->requestEditView();

        $this->assertEquals('Modify Category (200) details', trim($crawler->filter('head title')->text()));
        $this->assertEquals('Modify Category (200) details', trim($crawler->filter('h1.title')->text()));
    }

    public function testEditViewFormAttributes()
    {
        $crawler = $this->requestEditView();
        $form = $crawler->filter('#main form')->eq(0);

        $this->assertSame('edit', trim($form->attr('data-view')));
        $this->assertSame('Category', trim($form->attr('data-entity')));
        $this->assertSame('200', trim($form->attr('data-entity-id')));
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
        $fieldDefaultClasses = array('integer', 'text', 'entity');
        $fieldCustomClasses = array('integer', 'text', 'entity');

        foreach ($fieldDefaultClasses as $i => $cssClass) {
            $this->assertContains('field-'.$cssClass, trim($crawler->filter('#main .form-group')->eq($i)->attr('class')));
        }

        foreach ($fieldCustomClasses as $i => $cssClass) {
            $this->assertContains($cssClass, trim($crawler->filter('#main .form-group')->eq($i)->attr('class')));
        }
    }

    public function testEditViewActions()
    {
        $crawler = $this->requestEditView();

        // save action
        $this->assertContains('fa-save', trim($crawler->filter('#form-actions-row button:contains("Save changes") i')->attr('class')));

        // delete action
        $this->assertContains('fa-minus-circle', trim($crawler->filter('#form-actions-row a:contains("Remove") i')->attr('class')));

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
        );

        // 1. visit a specific 'list' view page
        $crawler = $this->getBackendPage($parameters);

        // 2. click on the 'Edit' link of the first item
        $link = $crawler->filter('td.actions a:contains("Edit")')->eq(0)->link();
        $crawler = $this->client->click($link);

        // 3. the 'referer' parameter should point to the exact same previous 'list' page
        $refererUrl = $crawler->filter('#form-actions-row a:contains("Return to listing")')->attr('href');
        $queryString = parse_url($refererUrl, PHP_URL_QUERY);
        parse_str($queryString, $refererParameters);

        $this->assertEquals($parameters, $refererParameters);
    }

    public function testEditViewCheckboxLabel()
    {
        $crawler = $this->requestEditView('Product', '1');

        $this->assertContains('Custom Label', trim($crawler->filter('#product_enabled')->parents()->filter('label')->text()));
    }

    public function testNewViewPageTitle()
    {
        $crawler = $this->requestNewView();

        $this->assertEquals('Add a new Category', trim($crawler->filter('head title')->text()));
        $this->assertEquals('Add a new Category', trim($crawler->filter('h1.title')->text()));
    }

    public function testNewViewFormAttributes()
    {
        $crawler = $this->requestNewView();
        $form = $crawler->filter('#main form')->eq(0);

        $this->assertSame('new', trim($form->attr('data-view')));
        $this->assertSame('Category', trim($form->attr('data-entity')));
        $this->assertEmpty($form->attr('data-entity-id'));
    }

    public function testNewViewFieldLabels()
    {
        $crawler = $this->requestNewView();
        $fieldLabels = array('ID', 'Label', 'Parent Category Label');

        foreach ($fieldLabels as $i => $label) {
            $this->assertEquals($label, trim($crawler->filter('#main .form-group label')->eq($i)->text()));
        }
    }

    public function testNewViewFieldClasses()
    {
        $crawler = $this->requestNewView();
        $fieldClasses = array('integer', 'text', 'entity');

        foreach ($fieldClasses as $i => $cssClass) {
            $this->assertContains('field-'.$cssClass, trim($crawler->filter('#main .form-group')->eq($i)->attr('class')));
        }
    }

    public function testNewViewActions()
    {
        $crawler = $this->requestNewView();

        // save action
        $this->assertContains('fa-save', trim($crawler->filter('#form-actions-row button:contains("Save changes") i')->attr('class')));

        // list action
        $this->assertContains('fa-list', trim($crawler->filter('#form-actions-row a:contains("Return to listing") i')->attr('class')));
    }

    public function testNewViewListActionReferer()
    {
        $parameters = array(
            'action' => 'list',
            'entity' => 'Category',
            'page' => '2',
            'sortDirection' => 'ASC',
            'sortField' => 'name',
        );

        // 1. visit a specific 'list' view page
        $crawler = $this->getBackendPage($parameters);

        // 2. click on the 'New' link to browse the 'new' view
        $link = $crawler->filter('.global-actions a:contains("New Category")')->link();
        $crawler = $this->client->click($link);

        // 3. the 'referer' parameter should point to the exact same previous 'list' page
        $refererUrl = $crawler->filter('#form-actions-row a:contains("Return to listing")')->attr('href');
        $queryString = parse_url($refererUrl, PHP_URL_QUERY);
        parse_str($queryString, $refererParameters);

        $this->assertEquals($parameters, $refererParameters);
    }

    public function testNewCustomFormOptions()
    {
        $this->client->enableProfiler();

        $crawler = $this->requestNewView();
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        // test 'novalidate' attribute
        $this->assertSame('novalidate', $crawler->filter('#new-category-form')->first()->attr('novalidate'));

        $form = $crawler->selectButton('Save changes')->form();
        $form->remove('form[name]');
        $crawler = $this->client->submit($form);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        // test validation groups
        $profile = $this->client->getProfile();
        try {
            $formData = $profile->getCollector('form')->getData();
            $categoryFields = $formData['forms']['form']['children'];
            $this->assertSame($categoryFields['name']['errors'][0]['message'], 'This value should not be null.');
        } catch (\Exception $e) {
            // TODO: remove this condition when support for Symfony 2.3 is dropped
            // In Symfony 2.3 FormDataCollector does not exist. Search in response content.
            $this->assertContains('This value should not be null.', $crawler->filter('.error-block')->first()->text());
        }
    }

    public function testNewViewCheckboxLabel()
    {
        $crawler = $this->requestNewView('Product');

        $this->assertContains('Custom Label', trim($crawler->filter('#product_enabled')->parents()->filter('label')->text()));
    }

    public function testSearchViewPageTitle()
    {
        $crawler = $this->requestSearchView();

        $this->assertEquals('200 results found', trim($crawler->filter('head title')->text()));
        $this->assertEquals('200 results found', trim($crawler->filter('h1.title')->text()));
    }

    public function testSearchViewTableIdColumn()
    {
        $crawler = $this->requestSearchView();

        $this->assertEquals('ID', trim($crawler->filter('table th[data-property-name="id"]')->text()),
            'The ID entity property is very special and we uppercase it automatically to improve its readability.'
        );
    }

    public function testSearchViewTableColumnLabels()
    {
        $crawler = $this->requestSearchView();
        $columnLabels = array('ID', 'Label', 'Parent category', 'Actions');

        foreach ($columnLabels as $i => $label) {
            $this->assertEquals($label, trim($crawler->filter('.table thead th')->eq($i)->text()));
        }
    }

    public function testSearchViewTableColumnAttributes()
    {
        $crawler = $this->requestSearchView();
        $columnAttributes = array('id', 'name', 'parent');

        foreach ($columnAttributes as $i => $attribute) {
            $this->assertEquals($attribute, trim($crawler->filter('.table thead th')->eq($i)->attr('data-property-name')));
        }
    }

    public function testSearchViewDefaultTableSorting()
    {
        $crawler = $this->requestSearchView();

        $this->assertCount(1, $crawler->filter('.table thead th[class*="sorted"]'), 'Table is sorted only by one column.');
        $this->assertEquals('ID', trim($crawler->filter('.table thead th[class*="sorted"]')->text()), 'By default, table is soreted by ID column.');
        $this->assertEquals('fa fa-caret-down', $crawler->filter('.table thead th[class*="sorted"] i')->attr('class'), 'The column used to sort results shows the right icon.');
    }

    public function testSearchViewTableContents()
    {
        $crawler = $this->requestSearchView();

        $this->assertCount(15, $crawler->filter('.table tbody tr'));
    }

    public function testSearchViewTableRowAttributes()
    {
        $crawler = $this->requestSearchView();
        $columnAttributes = array('ID', 'Label', 'Parent category');

        foreach ($columnAttributes as $i => $attribute) {
            $this->assertEquals($attribute, trim($crawler->filter('.table tbody tr td')->eq($i)->attr('data-label')));
        }
    }

    public function testSearchViewPagination()
    {
        $crawler = $this->requestSearchView();

        $this->assertContains('1 - 15 of 200', $crawler->filter('.list-pagination')->text());

        $this->assertEquals('disabled', $crawler->filter('.list-pagination li:contains("First")')->attr('class'));
        $this->assertEquals('disabled', $crawler->filter('.list-pagination li:contains("Previous")')->attr('class'));

        $this->assertStringStartsWith('/admin/?action=search&entity=Category&sortField=id&sortDirection=DESC&page=2', $crawler->filter('.list-pagination li a:contains("Next")')->attr('href'));
        $this->assertStringStartsWith('/admin/?action=search&entity=Category&sortField=id&sortDirection=DESC&page=14', $crawler->filter('.list-pagination li a:contains("Last")')->attr('href'));
    }

    public function testSearchViewItemActions()
    {
        $crawler = $this->requestSearchView();

        $this->assertEquals('Show', trim($crawler->filter('#main .table td.actions a')->eq(0)->text()));
        $this->assertEquals('Edit', trim($crawler->filter('#main .table td.actions a')->eq(1)->text()));
        $this->assertEquals('Delete', trim($crawler->filter('#main .table td.actions a')->eq(2)->text()));
    }

    public function testSearchViewShowActionReferer()
    {
        $parameters = array(
            'action' => 'search',
            'entity' => 'Category',
            'page' => '2',
            'query' => 'cat',
            'sortDirection' => 'ASC',
            'sortField' => 'name',
        );

        // 1. visit a specific 'search' view page
        $crawler = $this->getBackendPage($parameters);

        // 2. click on the 'Show' action of the first result
        $link = $crawler->filter('td.actions a:contains("Show")')->eq(0)->link();
        $crawler = $this->client->click($link);

        // 3. the 'referer' parameter should point to the previous specific 'search' view page
        $refererUrl = $crawler->filter('.form-actions a:contains("Back to Category listing")')->attr('href');
        $queryString = parse_url($refererUrl, PHP_URL_QUERY);
        parse_str($queryString, $refererParameters);

        $this->assertEquals($parameters, $refererParameters);
    }

    public function testListViewVirtualFields()
    {
        $crawler = $this->requestListView('Product');

        $this->assertCount(15, $crawler->filter('.table tbody td:contains("Inaccessible")'));

        $this->assertEquals('This field is virtual', $crawler->filter('.table tbody td:contains("Inaccessible")')->first()->attr('data-label'));

        $firstVirtualField = $crawler->filter('.table tbody td:contains("Inaccessible") span')->first();
        $this->assertEquals('label label-danger', $firstVirtualField->attr('class'));
        $this->assertEquals(
            'Getter method does not exist for this field or the property is not public',
            $firstVirtualField->attr('title')
        );
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

    /**
     * @return Crawler
     */
    private function requestShowView()
    {
        return $this->getBackendPage(array(
            'action' => 'show',
            'entity' => 'Category',
            'id' => '200',
        ));
    }

    /**
     * @return Crawler
     */
    private function requestEditView($entityName = 'Category', $entityId = '200')
    {
        return $this->getBackendPage(array(
            'action' => 'edit',
            'entity' => $entityName,
            'id' => $entityId,
        ));
    }

    /**
     * @return Crawler
     */
    private function requestNewView($entityName = 'Category')
    {
        return $this->getBackendPage(array(
            'action' => 'new',
            'entity' => $entityName,
        ));
    }

    /**
     * @return Crawler
     */
    private function requestSearchView()
    {
        return $this->getBackendPage(array(
            'action' => 'search',
            'entity' => 'Category',
            'query' => 'cat',
        ));
    }
}
