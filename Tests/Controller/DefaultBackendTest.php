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

class DefaultBackendTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'default_backend'));
    }

    public function testBackendHomepageRedirection()
    {
        $this->client->request('GET', '/admin/');

        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(
            '/admin/?action=list&entity=Category',
            $this->client->getResponse()->getTargetUrl(),
            'The backend homepage redirects to the "list" view of the first configured entity ("Category").'
        );
    }

    public function testLanguageDefinedByLayout()
    {
        $crawler = $this->getBackendHomepage();

        $this->assertEquals('en', trim($crawler->filter('html')->attr('lang')));
    }

    public function testDefaultCssFilesAreLinked()
    {
        $cssFiles = array(
            '/bundles/easyadmin/stylesheet/bootstrap.min.css',
            '/bundles/easyadmin/stylesheet/font-awesome.min.css',
            '/admin/_css/admin.css',
        );

        $crawler = $this->getBackendHomepage();

        foreach ($cssFiles as $i => $url) {
            $this->assertEquals($url, $crawler->filterXPath('//link[@rel="stylesheet"]')->eq($i)->attr('href'));
        }
    }

    public function testLogo()
    {
        $crawler = $this->getBackendHomepage();

        $this->assertEquals('Easy Admin', $crawler->filter('#header-logo a')->text());
        $this->assertEquals('/admin/', $crawler->filter('#header-logo a')->attr('href'));
        $this->assertEquals('short', $crawler->filter('#header-logo a')->attr('class'));
    }

    public function testMainMenuItems()
    {
        $menuItems = array(
            'Category' => '/admin/?entity=Category&action=list',
            'Image' => '/admin/?entity=Image&action=list',
            'Purchase' => '/admin/?entity=Purchase&action=list',
            'PurchaseItem' => '/admin/?entity=PurchaseItem&action=list',
            'Product' => '/admin/?entity=Product&action=list',
        );

        $crawler = $this->getBackendHomepage();

        $i = 0;
        foreach ($menuItems as $label => $url) {
            $this->assertEquals($label, $crawler->filter('#header-menu li a')->eq($i)->text());
            $this->assertEquals($url, $crawler->filter('#header-menu li a')->eq($i)->attr('href'));

            ++$i;
        }
    }

    public function testAdminCssFile()
    {
        $this->client->request('GET', '/admin/_css/admin.css');

        $this->assertEquals('text/css; charset=UTF-8', $this->client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals(20, substr_count($this->client->getResponse()->getContent(), '#E67E22'), 'The admin.css file uses the default brand color.');
        // #222222 color is only used by the "dark" color scheme, not the "light" one
        $this->assertEquals(16, substr_count($this->client->getResponse()->getContent(), '#222222'), 'The admin.css file uses the dark color scheme.');
    }

    public function testListViewMainMenu()
    {
        $crawler = $this->requestListView();

        $this->assertEquals('Category', trim($crawler->filter('#header-menu li.active')->text()));
    }

    public function testListViewTitle()
    {
        $crawler = $this->requestListView();

        $this->assertEquals('Category', trim($crawler->filter('head title')->text()));
        $this->assertEquals('Category', trim($crawler->filter('h1.title')->text()));
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

        $this->assertEquals('Search', $crawler->filter('#content-search input[type=search]')->attr('placeholder'));

        $i = 0;
        foreach ($hiddenParameters as $name => $value) {
            $this->assertEquals($name, $crawler->filter('#content-search input[type=hidden]')->eq($i)->attr('name'));
            $this->assertEquals($value, $crawler->filter('#content-search input[type=hidden]')->eq($i)->attr('value'));

            ++$i;
        }
    }

    public function testListViewNewAction()
    {
        $crawler = $this->requestListView();

        $this->assertEquals('Add Category', trim($crawler->filter('#content-actions a.btn')->text()));
        $this->assertCount(0, $crawler->filter('#content-actions a.btn i'), 'The default "new" button shows no icon.');
        $this->assertStringStartsWith('/admin/?action=new&entity=Category&sortField=id&sortDirection=DESC&page=1', $crawler->filter('#content-actions a.btn')->attr('href'));
    }

    public function testListViewItemActions()
    {
        $crawler = $this->requestListView();

        $this->assertEquals('Show', trim($crawler->filter('#main .table td.actions a')->eq(0)->text()));
        $this->assertEquals('Edit', trim($crawler->filter('#main .table td.actions a')->eq(1)->text()));
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
        $columnLabels = array('ID', 'Name', 'Products', 'Parent', 'Actions');

        foreach ($columnLabels as $i => $label) {
            $this->assertEquals($label, trim($crawler->filter('.table thead th')->eq($i)->text()));
        }
    }

    public function testListViewTableColumnAttributes()
    {
        $crawler = $this->requestListView();
        $columnAttributes = array('id', 'name', 'products', 'parent');

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
        $columnAttributes = array('ID', 'Name', 'Products', 'Parent');

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

        $this->assertStringStartsWith('/admin/?action=list&entity=Category&sortField=id&sortDirection=DESC&page=2', $crawler->filter('.list-pagination li a:contains("Next")')->attr('href'));
        $this->assertStringStartsWith('/admin/?action=list&entity=Category&sortField=id&sortDirection=DESC&page=14', $crawler->filter('.list-pagination li a:contains("Last")')->attr('href'));
    }

    public function testShowViewPageMainMenu()
    {
        $crawler = $this->requestShowView();

        $this->assertEquals('Category', trim($crawler->filter('#header-menu li.active')->text()));
    }

    public function testShowViewPageTitle()
    {
        $crawler = $this->requestShowView();

        $this->assertEquals('Category (#200)', trim($crawler->filter('head title')->text()));
        $this->assertEquals('Category (#200)', trim($crawler->filter('h1.title')->text()));
    }

    public function testShowViewFieldLabels()
    {
        $crawler = $this->requestShowView();
        $fieldLabels = array('ID', 'Name', 'Products', 'Parent');

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
        $this->assertContains('fa-edit', trim($crawler->filter('.form-actions a:contains("Edit") i')->attr('class')));

        // delete action
        $this->assertContains('fa-trash', trim($crawler->filter('.form-actions button:contains("Delete") i')->attr('class')));

        // list action
        $this->assertEquals('btn btn-secondary', trim($crawler->filter('.form-actions a:contains("Back to listing")')->attr('class')));
    }

    public function testShowViewReferer()
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
        $refererUrl = $crawler->filter('.form-actions a:contains("Back to listing")')->attr('href');
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
        $link = $crawler->filter('.form-actions a:contains("Edit")')->link();
        $crawler = $this->client->click($link);

        // 4. the 'referer' parameter should point to the exact same previous 'list' page
        $refererUrl = $crawler->filter('#form-actions-row a:contains("Back to listing")')->attr('href');
        $queryString = parse_url($refererUrl, PHP_URL_QUERY);
        parse_str($queryString, $refererParameters);

        $this->assertEquals($parameters, $refererParameters);
    }

    public function testEditViewMainMenu()
    {
        $crawler = $this->requestEditView();

        $this->assertEquals('Category', trim($crawler->filter('#header-menu li.active')->text()));
    }

    public function testEditViewTitle()
    {
        $crawler = $this->requestEditView();

        $this->assertEquals('Edit Category (#200)', trim($crawler->filter('head title')->text()));
        $this->assertEquals('Edit Category (#200)', trim($crawler->filter('h1.title')->text()));
    }

    public function testEditViewFormClasses()
    {
        $crawler = $this->requestEditView();
        $formClasses = array('theme-bootstrap_3_horizontal_layout', 'form-horizontal');

        foreach ($formClasses as $cssClass) {
            $this->assertContains($cssClass, trim($crawler->filter('#main form')->eq(0)->attr('class')));
        }
    }

    public function testEditViewFieldLabels()
    {
        $crawler = $this->requestEditView();
        $fieldLabels = array('Name', 'Products', 'Parent');

        foreach ($fieldLabels as $i => $label) {
            $this->assertEquals($label, trim($crawler->filter('#main .form-group label')->eq($i)->text()));
        }
    }

    public function testEditViewFieldClasses()
    {
        $crawler = $this->requestEditView();
        $fieldClasses = array('text', 'default');

        foreach ($fieldClasses as $i => $cssClass) {
            $this->assertContains('field-'.$cssClass, trim($crawler->filter('#main .form-group')->eq($i)->attr('class')));
        }
    }

    public function testEditViewActions()
    {
        $crawler = $this->requestEditView();

        // save action
        $this->assertContains('fa-save', trim($crawler->filter('#form-actions-row button:contains("Save changes") i')->attr('class')));

        // delete action
        $this->assertContains('fa-trash', trim($crawler->filter('#form-actions-row button:contains("Delete") i')->attr('class')));

        // list action
        $this->assertEquals('btn btn-secondary', trim($crawler->filter('#form-actions-row a:contains("Back to listing")')->attr('class')));
    }

    public function testEditViewReferer()
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
        $refererUrl = $crawler->filter('#form-actions-row a:contains("Back to listing")')->attr('href');
        $queryString = parse_url($refererUrl, PHP_URL_QUERY);
        parse_str($queryString, $refererParameters);

        $this->assertEquals($parameters, $refererParameters);
    }

    public function testNewViewMainMenu()
    {
        $crawler = $this->requestNewView();

        $this->assertEquals('Category', trim($crawler->filter('#header-menu li.active')->text()));
    }

    public function testNewViewTitle()
    {
        $crawler = $this->requestNewView();

        $this->assertEquals('Create Category', trim($crawler->filter('head title')->text()));
        $this->assertEquals('Create Category', trim($crawler->filter('h1.title')->text()));
    }

    public function testNewViewFormClasses()
    {
        $crawler = $this->requestNewView();
        $formClasses = array('theme-bootstrap_3_horizontal_layout', 'form-horizontal');

        foreach ($formClasses as $cssClass) {
            $this->assertContains($cssClass, trim($crawler->filter('#main form')->eq(0)->attr('class')));
        }
    }

    public function testNewViewFieldLabels()
    {
        $crawler = $this->requestNewView();
        $fieldLabels = array('Name', 'Products', 'Parent');

        foreach ($fieldLabels as $i => $label) {
            $this->assertEquals($label, trim($crawler->filter('#main .form-group label')->eq($i)->text()));
        }
    }

    public function testNewViewFieldClasses()
    {
        $crawler = $this->requestNewView();
        $fieldClasses = array('text', 'default');

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
        $this->assertEquals('btn btn-secondary', trim($crawler->filter('#form-actions-row a:contains("Back to listing")')->attr('class')));
    }

    public function testNewViewReferer()
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
        $link = $crawler->filter('#content-actions a:contains("Add Category")')->link();
        $crawler = $this->client->click($link);

        // 3. the 'referer' parameter should point to the exact same previous 'list' page
        $refererUrl = $crawler->filter('#form-actions-row a:contains("Back to listing")')->attr('href');
        $queryString = parse_url($refererUrl, PHP_URL_QUERY);
        parse_str($queryString, $refererParameters);

        $this->assertEquals($parameters, $refererParameters);
    }

    public function testSearchViewMainMenu()
    {
        $crawler = $this->requestSearchView();

        $this->assertEquals('Category', trim($crawler->filter('#header-menu li.active')->text()));
    }

    public function testSearchViewTitle()
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
        $columnLabels = array('ID', 'Name', 'Products', 'Parent', 'Actions');

        foreach ($columnLabels as $i => $label) {
            $this->assertEquals($label, trim($crawler->filter('.table thead th')->eq($i)->text()));
        }
    }

    public function testSearchViewTableColumnAttributes()
    {
        $crawler = $this->requestSearchView();
        $columnAttributes = array('id', 'name', 'products', 'parent');

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
        $columnAttributes = array('ID', 'Name', 'Products', 'Parent');

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

        // 3. the 'referer' parameter should point to the exact same previous 'list' page
        $refererUrl = $crawler->filter('.form-actions a:contains("Back to listing")')->attr('href');
        $queryString = parse_url($refererUrl, PHP_URL_QUERY);
        parse_str($queryString, $refererParameters);

        $this->assertEquals($parameters, $refererParameters);
    }

    /**
     * @return Crawler
     */
    private function requestListView()
    {
        return $this->getBackendPage(array(
            'action' => 'list',
            'entity' => 'Category',
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
    private function requestEditView()
    {
        return $this->getBackendPage(array(
            'action' => 'edit',
            'entity' => 'Category',
            'id' => '200',
        ));
    }

    /**
     * @return Crawler
     */
    private function requestNewView()
    {
        return $this->getBackendPage(array(
            'action' => 'new',
            'entity' => 'Category',
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
