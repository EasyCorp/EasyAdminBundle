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

use Doctrine\ORM\EntityManager;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

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

        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
        $this->assertSame(
            '/admin/?action=list&entity=Category',
            $this->client->getResponse()->getTargetUrl(),
            'The backend homepage redirects to the "list" view of the first configured entity ("Category").'
        );
    }

    public function testLanguageDefinedByLayout()
    {
        $crawler = $this->getBackendHomepage();

        $this->assertSame('en', trim($crawler->filter('html')->attr('lang')));
    }

    public function testRtlIsDisabledByDefault()
    {
        $crawler = $this->getBackendHomepage();

        $backendConfig = $this->client->getContainer()->get('easyadmin.config.manager')->getBackendConfig();
        $this->assertFalse($backendConfig['design']['rtl'], 'RTL is disabled by default.');

        $this->assertNotContains('bootstrap-rtl.min.css', $crawler->filter('head')->text());
        $this->assertNotContains('adminlte-rtl.min.css', $crawler->filter('head')->text());
    }

    public function testDefaultCssStylesAreLinked()
    {
        $crawler = $this->getBackendHomepage();

        $this->assertSame(
            '/bundles/easyadmin/stylesheet/easyadmin-all.min.css',
            $crawler->filter('link[rel="stylesheet"]')->eq(0)->attr('href')
        );
    }

    public function testDefaultJsScriptsAreLinked()
    {
        $crawler = $this->getBackendHomepage();

        $this->assertSame(
            '/bundles/easyadmin/javascript/easyadmin-all.min.js',
            $crawler->filter('script')->eq(1)->attr('src')
        );
    }

    public function testLogo()
    {
        $crawler = $this->getBackendHomepage();

        $this->assertSame('EasyAdmin', trim($crawler->filter('#header-logo a')->text()));
        $this->assertSame('/admin/', $crawler->filter('#header-logo a')->attr('href'));
    }

    public function testMainMenuItems()
    {
        $menuItems = array(
            'Category' => '/admin/?entity=Category&action=list&menuIndex=0&submenuIndex=-1',
            'Image' => '/admin/?entity=Image&action=list&menuIndex=1&submenuIndex=-1',
            'Purchase' => '/admin/?entity=Purchase&action=list&menuIndex=2&submenuIndex=-1',
            'PurchaseItem' => '/admin/?entity=PurchaseItem&action=list&menuIndex=3&submenuIndex=-1',
            'Product' => '/admin/?entity=Product&action=list&menuIndex=4&submenuIndex=-1',
        );

        $crawler = $this->getBackendHomepage();

        $i = 0;
        foreach ($menuItems as $label => $url) {
            $this->assertSame($label, trim($crawler->filter('.sidebar-menu li a')->eq($i)->text()));
            $this->assertSame($url, $crawler->filter('.sidebar-menu li a')->eq($i)->attr('href'));

            ++$i;
        }
    }

    public function testUserMenuForAnonymousUsers()
    {
        $crawler = $this->getBackendHomepage();

        $this->assertContains('Anonymous', $crawler->filter('header .user-menu')->text());
        $this->assertCount(0, $crawler->filter('header .user-menu .dropdown-menu'));
    }

    public function testCustomCssFile()
    {
        $this->getBackendHomepage();

        $this->assertSame(13, substr_count($this->client->getResponse()->getContent(), '#205081'), 'The admin.css file uses the default brand color.');
        // #222222 color is only used by the "dark" color scheme, not the "light" one
        $this->assertSame(7, substr_count($this->client->getResponse()->getContent(), '#222222'), 'The admin.css file uses the dark color scheme.');
    }

    public function testCustomCssProperty()
    {
        $this->getBackendHomepage();
        $customCssContent = $this->client->getContainer()->get('easyadmin.config.manager')->getBackendConfig();

        $this->assertSame(13, substr_count($customCssContent['_internal']['custom_css'], '#205081'), 'The generated custom CSS uses the default brand color.');
        // #222222 color is only used by the "dark" color scheme, not the "light" one
        $this->assertSame(7, substr_count($customCssContent['_internal']['custom_css'], '#222222'), 'The generated custom CSS uses the dark color scheme.');
    }

    public function testListViewTitle()
    {
        $crawler = $this->requestListView();

        $this->assertSame('Category', trim($crawler->filter('head title')->text()));
        $this->assertSame('Category', trim($crawler->filter('h1.title')->text()));
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

        $this->assertSame('Search', trim($crawler->filter('.action-search button[type=submit]')->text()));
        $this->assertContains('action-search', $crawler->filter('.global-actions > div')->first()->attr('class'));
        $this->assertSame('_self', $crawler->filter('.action-search button')->attr('formtarget'));

        $i = 0;
        foreach ($hiddenParameters as $name => $value) {
            $this->assertSame($name, $crawler->filter('.action-search input[type=hidden]')->eq($i)->attr('name'));
            $this->assertSame($value, $crawler->filter('.action-search input[type=hidden]')->eq($i)->attr('value'));

            ++$i;
        }
    }

    public function testListViewNewAction()
    {
        $crawler = $this->requestListView();

        $this->assertSame('Add Category', trim($crawler->filter('.global-actions a.btn')->text()));
        $this->assertContains('action-new', trim($crawler->filter('.global-actions a.btn')->attr('class')));
        $this->assertSame('_self', $crawler->filter('.global-actions a.btn')->attr('target'));
        $this->assertCount(0, $crawler->filter('.global-actions a.btn i'), 'The default "new" button shows no icon.');
        $this->assertStringStartsWith('/admin/?action=new&entity=Category&sortField=id&sortDirection=DESC&page=1', $crawler->filter('.global-actions a.btn')->attr('href'));
    }

    public function testListViewItemActions()
    {
        $crawler = $this->requestListView();

        $this->assertSame('Edit', trim($crawler->filter('#main .table td.actions a')->eq(0)->text()));
        $this->assertContains('action-edit', trim($crawler->filter('#main .table td.actions a')->eq(0)->attr('class')));
        $this->assertSame('_self', $crawler->filter('#main .table td.actions a')->eq(0)->attr('target'));
        $this->assertSame('Delete', trim($crawler->filter('#main .table td.actions a')->eq(1)->text()));
    }

    public function testListViewTableIdColumn()
    {
        $crawler = $this->requestListView();

        $this->assertSame('ID', trim($crawler->filter('table th[data-property-name="id"]')->text()),
            'The ID entity property is very special and we uppercase it automatically to improve its readability.'
        );
    }

    public function testListViewTableColumnLabels()
    {
        $crawler = $this->requestListView();
        $columnLabels = array('ID', 'Name', 'Products', 'Parent', 'Actions');

        foreach ($columnLabels as $i => $label) {
            $this->assertSame($label, trim($crawler->filter('.table thead th')->eq($i)->text()));
        }
    }

    public function testListViewTableColumnAttributes()
    {
        $crawler = $this->requestListView();
        $columnAttributes = array('id', 'name', 'products', 'parent');

        foreach ($columnAttributes as $i => $attribute) {
            $this->assertSame($attribute, trim($crawler->filter('.table thead th')->eq($i)->attr('data-property-name')));
        }
    }

    public function testListViewDefaultTableSorting()
    {
        $crawler = $this->requestListView();

        $this->assertCount(1, $crawler->filter('.table thead th[class*="sorted"]'), 'Table is sorted only by one column.');
        $this->assertSame('ID', trim($crawler->filter('.table thead th[class*="sorted"]')->text()), 'By default, table is soreted by ID column.');
        $this->assertSame('fa fa-caret-down', $crawler->filter('.table thead th[class*="sorted"] i')->attr('class'), 'The column used to sort results shows the right icon.');
    }

    public function testListViewColumnSortingResetsPaginator()
    {
        $crawler = $this->requestListView();

        // Click on the 'Next' link in the paginator
        $crawler = $this->client->click($crawler->selectLink('Next')->link());
        $this->assertSame('id', $this->client->getRequest()->query->get('sortField'));
        $this->assertSame(2, $this->client->getRequest()->query->getInt('page'));

        // 2. Click on the 'Name' table column to reorder the listing
        $crawler = $this->client->click($crawler->filter('thead')->selectLink('Name')->link());
        $this->assertSame('name', $this->client->getRequest()->query->get('sortField'));
        $this->assertSame(1, $this->client->getRequest()->query->getInt('page'), 'When the listing contents are reordered, the pagination is reset to the first page.');
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
            $this->assertSame($attribute, trim($crawler->filter('.table tbody tr td')->eq($i)->attr('data-label')));
        }
    }

    public function testListViewPagination()
    {
        $crawler = $this->requestListView();

        $this->assertContains('1 - 15 of 200', $crawler->filter('.list-pagination')->text());

        $this->assertSame('disabled', $crawler->filter('.list-pagination li:contains("First")')->attr('class'));
        $this->assertSame('disabled', $crawler->filter('.list-pagination li:contains("Previous")')->attr('class'));

        $this->assertStringStartsWith('/admin/?action=list&entity=Category&sortField=id&sortDirection=DESC&page=2', $crawler->filter('.list-pagination li a:contains("Next")')->attr('href'));
        $this->assertStringStartsWith('/admin/?action=list&entity=Category&sortField=id&sortDirection=DESC&page=14', $crawler->filter('.list-pagination li a:contains("Last")')->attr('href'));
    }

    public function testListViewDefaultFormats()
    {
        $crawler = $this->requestListView('Purchase');

        $this->assertRegExp('/\d{4}-\d{2}-\d{2}/', trim($crawler->filter('#main table tr')->eq(1)->filter('td.date')->text()));
        $this->assertRegExp('/\d{2}:\d{2}/', trim($crawler->filter('#main table tr')->eq(1)->filter('td.time')->text()));
    }

    public function testListViewBooleanToggles()
    {
        $crawler = $this->requestListView('Product');

        $this->assertCount(15, $crawler->filter('td[data-label="Enabled"].toggle'), 'Boolean properties are displayed with a toggle widget unless configured explicitly.');
        $this->assertCount(0, $crawler->filter('td[data-label="Enabled"].boolean'));
    }

    public function testShowViewPageTitle()
    {
        $crawler = $this->requestShowView();

        $this->assertSame('Category (#200)', trim($crawler->filter('head title')->text()));
        $this->assertSame('Category (#200)', trim($crawler->filter('h1.title')->text()));
    }

    public function testShowViewFieldLabels()
    {
        $crawler = $this->requestShowView();
        $fieldLabels = array('ID', 'Name', 'Products', 'Parent');

        foreach ($fieldLabels as $i => $label) {
            $this->assertSame($label, trim($crawler->filter('#main .form-group label')->eq($i)->text()));
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
        $this->assertContains('action-edit', trim($crawler->filter('.form-actions a:contains("Edit")')->attr('class')));
        $this->assertContains('fa-edit', trim($crawler->filter('.form-actions a:contains("Edit") i')->attr('class')));
        $this->assertSame('_self', $crawler->filter('.form-actions a:contains("Edit")')->attr('target'));

        // delete action
        $this->assertContains('action-delete', trim($crawler->filter('.form-actions a:contains("Delete")')->attr('class')));
        $this->assertContains('fa-trash', trim($crawler->filter('.form-actions a:contains("Delete") i')->attr('class')));

        // list action
        $this->assertContains('action-list', trim($crawler->filter('.form-actions a:contains("Back to listing")')->attr('class')));
        $this->assertSame('btn btn-secondary action-list', trim($crawler->filter('.form-actions a:contains("Back to listing")')->attr('class')));
        $this->assertSame('_self', $crawler->filter('.form-actions a:contains("Back to listing")')->attr('target'));
    }

    public function testShowViewReferer()
    {
        $parameters = array(
            'action' => 'list',
            'entity' => 'Category',
            'sortField' => 'name',
            'sortDirection' => 'ASC',
            'page' => '2',
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

        $this->assertSame($parameters, $refererParameters);
    }

    public function testEditViewTitle()
    {
        $crawler = $this->requestEditView();

        $this->assertSame('Edit Category (#200)', trim($crawler->filter('head title')->text()));
        $this->assertSame('Edit Category (#200)', trim($crawler->filter('h1.title')->text()));
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
        $fieldLabels = array('Name', 'Products', 'Parent');

        foreach ($fieldLabels as $i => $label) {
            $this->assertSame($label, trim($crawler->filter('#main .form-group label')->eq($i)->text()));
        }
    }

    public function testEditViewFieldClasses()
    {
        $crawler = $this->requestEditView();
        $fieldClasses = array('text', 'entity');

        foreach ($fieldClasses as $i => $cssClass) {
            $this->assertContains('field-'.$cssClass, trim($crawler->filter('#main .form-group')->eq($i)->attr('class')));
        }
    }

    public function testEditViewActions()
    {
        $crawler = $this->requestEditView();

        // save action
        $this->assertContains('action-save', trim($crawler->filter('#form-actions-row button:contains("Save changes")')->attr('class')));
        $this->assertContains('fa-save', trim($crawler->filter('#form-actions-row button:contains("Save changes") i')->attr('class')));

        // delete action
        $this->assertContains('action-delete', trim($crawler->filter('#form-actions-row a:contains("Delete")')->attr('class')));
        $this->assertContains('fa-trash', trim($crawler->filter('#form-actions-row a:contains("Delete") i')->attr('class')));

        // list action
        $this->assertSame('btn btn-secondary action-list', trim($crawler->filter('#form-actions-row a:contains("Back to listing")')->attr('class')));
        $this->assertSame('_self', $crawler->filter('#form-actions-row a:contains("Back to listing")')->attr('target'));
    }

    public function testEditViewReferer()
    {
        $parameters = array(
            'action' => 'list',
            'entity' => 'Category',
            'sortField' => 'name',
            'sortDirection' => 'ASC',
            'page' => '2',
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

        $this->assertSame($parameters, $refererParameters);
    }

    public function testEditViewEntityModification()
    {
        $crawler = $this->requestEditView();
        $this->client->followRedirects();

        $categoryName = sprintf('Modified Category %s', md5(mt_rand()));
        $form = $crawler->selectButton('Save changes')->form(array(
            'category[name]' => $categoryName,
        ));
        $crawler = $this->client->submit($form);

        $this->assertContains(
            $categoryName,
            $crawler->filter('#main table tr')->eq(1)->text(),
            'The modified category is displayed in the first data row of the "list" table.'
        );
    }

    public function testEntityModificationViaAjax()
    {
        /* @var EntityManager */
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $product = $em->getRepository('AppTestBundle\Entity\FunctionalTests\Product')->find(1);
        $this->assertTrue($product->isEnabled(), 'Initially the product is enabled.');

        $queryParameters = array('action' => 'edit', 'view' => 'list', 'entity' => 'Product', 'id' => '1', 'property' => 'enabled', 'newValue' => 'false');
        $this->client->request('GET', '/admin/?'.http_build_query($queryParameters), array(), array(), array('HTTP_X-Requested-With' => 'XMLHttpRequest'));

        $product = $em->getRepository('AppTestBundle\Entity\FunctionalTests\Product')->find(1);
        $this->assertFalse($product->isEnabled(), 'After editing it via Ajax, the product is not enabled.');
    }

    public function testWrongEntityModificationViaAjax()
    {
        $queryParameters = array('action' => 'edit', 'view' => 'list', 'entity' => 'Product', 'id' => '1', 'property' => 'this_property_does_not_exist', 'newValue' => 'false');
        $this->client->request('GET', '/admin/?'.http_build_query($queryParameters), array(), array(), array('HTTP_X-Requested-With' => 'XMLHttpRequest'));

        $this->assertSame(500, $this->client->getResponse()->getStatusCode(), 'Trying to modify a non-existent property via Ajax returns a 500 error');
        $this->assertContains('The type of the &quot;this_property_does_not_exist&quot; property is not &quot;toggle&quot;', $this->client->getResponse()->getContent());
    }

    public function testNewViewTitle()
    {
        $crawler = $this->requestNewView();

        $this->assertSame('Create Category', trim($crawler->filter('head title')->text()));
        $this->assertSame('Create Category', trim($crawler->filter('h1.title')->text()));
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
        $fieldLabels = array('Name', 'Products', 'Parent');

        foreach ($fieldLabels as $i => $label) {
            $this->assertSame($label, trim($crawler->filter('#main .form-group label')->eq($i)->text()));
        }
    }

    public function testNewViewFieldClasses()
    {
        $crawler = $this->requestNewView();
        $fieldClasses = array('text', 'entity');

        foreach ($fieldClasses as $i => $cssClass) {
            $this->assertContains('field-'.$cssClass, trim($crawler->filter('#main .form-group')->eq($i)->attr('class')));
        }
    }

    public function testNewViewActions()
    {
        $crawler = $this->requestNewView();

        // save action
        $this->assertContains('action-save', trim($crawler->filter('#form-actions-row button:contains("Save changes")')->attr('class')));
        $this->assertContains('fa-save', trim($crawler->filter('#form-actions-row button:contains("Save changes") i')->attr('class')));

        // list action
        $this->assertSame('btn btn-secondary action-list', trim($crawler->filter('#form-actions-row a:contains("Back to listing")')->attr('class')));
        $this->assertSame('_self', $crawler->filter('#form-actions-row a:contains("Back to listing")')->attr('target'));
    }

    public function testNewViewReferer()
    {
        $parameters = array(
            'action' => 'list',
            'entity' => 'Category',
            'sortField' => 'name',
            'sortDirection' => 'ASC',
            'page' => '2',
        );

        // 1. visit a specific 'list' view page
        $crawler = $this->getBackendPage($parameters);

        // 2. click on the 'New' link to browse the 'new' view
        $link = $crawler->filter('.global-actions a:contains("Add Category")')->link();
        $crawler = $this->client->click($link);

        // 3. the 'referer' parameter should point to the exact same previous 'list' page
        $refererUrl = $crawler->filter('#form-actions-row a:contains("Back to listing")')->attr('href');
        $queryString = parse_url($refererUrl, PHP_URL_QUERY);
        parse_str($queryString, $refererParameters);

        $this->assertSame($parameters, $refererParameters);
    }

    public function testNewViewEntityCreation()
    {
        $crawler = $this->requestNewView();
        $this->client->followRedirects();

        $categoryName = sprintf('The New Category %s', md5(mt_rand()));
        $form = $crawler->selectButton('Save changes')->form(array(
            'category[name]' => $categoryName,
        ));
        $crawler = $this->client->submit($form);

        $this->assertContains($categoryName, $crawler->filter('#main table tr')->eq(1)->text(), 'The newly created category is displayed in the first data row of the "list" table.');
    }

    public function testSearchViewTitle()
    {
        $crawler = $this->requestSearchView();

        $this->assertSame('200 results found', trim($crawler->filter('head title')->html()), 'The page title does not contain HTML tags.');
        $this->assertSame('<strong>200</strong> results found', trim($crawler->filter('h1.title')->html()), 'The visible content contains HTML tags.');
    }

    public function testSearchViewEmptyQuery()
    {
        foreach (array('', '    ') as $emptyQuery) {
            $this->getBackendPage(array(
                'action' => 'search',
                'entity' => 'Category',
                'query' => $emptyQuery,
            ));

            $this->assertSame(302, $this->client->getResponse()->getStatusCode());
            $this->assertSame('/admin/?action=list&entity=Category&sortField=id&sortDirection=DESC', $this->client->getResponse()->headers->get('location'), 'Empty queries redirect back to the list view.');
        }
    }

    public function testSearchViewTableIdColumn()
    {
        $crawler = $this->requestSearchView();

        $this->assertSame('ID', trim($crawler->filter('table th[data-property-name="id"]')->text()),
            'The ID entity property is very special and we uppercase it automatically to improve its readability.'
        );
    }

    public function testSearchViewTableColumnLabels()
    {
        $crawler = $this->requestSearchView();
        $columnLabels = array('ID', 'Name', 'Products', 'Parent', 'Actions');

        foreach ($columnLabels as $i => $label) {
            $this->assertSame($label, trim($crawler->filter('.table thead th')->eq($i)->text()));
        }
    }

    public function testSearchViewTableColumnAttributes()
    {
        $crawler = $this->requestSearchView();
        $columnAttributes = array('id', 'name', 'products', 'parent');

        foreach ($columnAttributes as $i => $attribute) {
            $this->assertSame($attribute, trim($crawler->filter('.table thead th')->eq($i)->attr('data-property-name')));
        }
    }

    public function testSearchViewDefaultTableSorting()
    {
        $crawler = $this->requestSearchView();

        $this->assertCount(1, $crawler->filter('.table thead th[class*="sorted"]'), 'Table is sorted only by one column.');
        $this->assertSame('ID', trim($crawler->filter('.table thead th[class*="sorted"]')->text()), 'By default, table is soreted by ID column.');
        $this->assertSame('fa fa-caret-down', $crawler->filter('.table thead th[class*="sorted"] i')->attr('class'), 'The column used to sort results shows the right icon.');
    }

    public function testSearchViewColumnSortingResetsPaginator()
    {
        $crawler = $this->requestSearchView();

        // Click on the 'Next' link in the paginator
        $crawler = $this->client->click($crawler->selectLink('Next')->link());
        $this->assertSame('id', $this->client->getRequest()->query->get('sortField'));
        $this->assertSame(2, $this->client->getRequest()->query->getInt('page'));

        // 2. Click on the 'Name' table column to reorder the search results
        $crawler = $this->client->click($crawler->filter('thead')->selectLink('Name')->link());
        $this->assertSame('name', $this->client->getRequest()->query->get('sortField'));
        $this->assertSame(1, $this->client->getRequest()->query->getInt('page'), 'When the search results are reordered, the pagination is reset to the first page.');
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
            $this->assertSame($attribute, trim($crawler->filter('.table tbody tr td')->eq($i)->attr('data-label')));
        }
    }

    public function testSearchViewPagination()
    {
        $crawler = $this->requestSearchView();

        $this->assertContains('1 - 15 of 200', $crawler->filter('.list-pagination')->text());

        $this->assertSame('disabled', $crawler->filter('.list-pagination li:contains("First")')->attr('class'));
        $this->assertSame('disabled', $crawler->filter('.list-pagination li:contains("Previous")')->attr('class'));

        $this->assertStringStartsWith('/admin/?action=search&entity=Category&sortField=id&sortDirection=DESC&page=2', $crawler->filter('.list-pagination li a:contains("Next")')->attr('href'));
        $this->assertStringStartsWith('/admin/?action=search&entity=Category&sortField=id&sortDirection=DESC&page=14', $crawler->filter('.list-pagination li a:contains("Last")')->attr('href'));
    }

    public function testSearchViewItemActions()
    {
        $crawler = $this->requestSearchView();

        $this->assertSame('Edit', trim($crawler->filter('#main .table td.actions a')->eq(0)->text()));
        $this->assertSame('_self', $crawler->filter('#main .table td.actions a')->eq(0)->attr('target'));
        $this->assertSame('Delete', trim($crawler->filter('#main .table td.actions a')->eq(1)->text()));
    }

    public function testSearchViewShowActionReferer()
    {
        $parameters = array(
            'action' => 'search',
            'entity' => 'Category',
            'sortField' => 'name',
            'sortDirection' => 'ASC',
            'page' => '2',
            'query' => 'cat',
        );

        // 1. visit a specific 'search' view page
        $crawler = $this->getBackendPage($parameters);

        // 2. click on the 'Edit' action of the first result
        $link = $crawler->filter('td.actions a:contains("Edit")')->eq(0)->link();
        $crawler = $this->client->click($link);

        // 3. the 'referer' parameter should point to the exact same previous 'list' page
        $refererUrl = $crawler->filter('#form-actions-row a:contains("Back to listing")')->attr('href');
        $queryString = parse_url($refererUrl, PHP_URL_QUERY);
        parse_str($queryString, $refererParameters);

        $this->assertSame($parameters, $refererParameters);
    }

    public function testEntityDeletion()
    {
        if (PHP_VERSION_ID < 50400) {
            $this->markTestSkipped('This test keeps failing on Travis CI when running PHP 5.3 for no apparent reason.');
        }

        /* @var EntityManager */
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $product = $em->getRepository('AppTestBundle\Entity\FunctionalTests\Product')->find(1);
        $this->assertNotNull($product, 'Initially the product exists.');

        $crawler = $this->requestEditView('Product', '1');
        $this->client->followRedirects();
        $form = $crawler->filter('#delete_form_submit')->form();
        $this->client->submit($form);

        $product = $em->getRepository('AppTestBundle\Entity\FunctionalTests\Product')->find(1);
        $this->assertNull($product, 'After removing it via the delete form, the product no longer exists.');
    }

    public function testEntityDeletionRequiresCsrfToken()
    {
        $queryParameters = array('action' => 'delete', 'entity' => 'Product', 'id' => '1');
        // Sending a 'DELETE' HTTP request is not enough (the delete form includes a CSRF token)
        $this->client->request('DELETE', '/admin/?'.http_build_query($queryParameters));

        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
        $this->assertContains('Redirecting to /admin/?action=list&amp;entity=Product', $this->client->getResponse()->getContent());
    }

    public function testEntityDeletionRequiresDeleteHttpMethod()
    {
        $queryParameters = array('action' => 'delete', 'entity' => 'Product', 'id' => '1');
        // 'POST' HTTP method is wrong for deleting entities ('DELETE' method is required)
        $this->client->request('POST', '/admin/?'.http_build_query($queryParameters));

        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
        $this->assertContains('Redirecting to /admin/?action=list&amp;entity=Product', $this->client->getResponse()->getContent());
    }

    public function testEntityDeletionForm()
    {
        $crawler = $this->requestEditView('Product', '1');

        $this->assertSame('1', $crawler->filter('#delete_form__easyadmin_delete_flag')->attr('value'), 'The delete form contains a special flag to prevent sending empty forms (see issue #1409 for details).');
    }
}
