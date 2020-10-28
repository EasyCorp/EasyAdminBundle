<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class CustomizedBackendTest extends AbstractTestCase
{
    protected static $options = ['environment' => 'customized_backend'];

    public function testUserMenuForLoggedUsers()
    {
        static::$client->followRedirects();
        $crawler = static::$client->request('GET', '/admin', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'pa$$word',
        ]);

        $this->assertContains('admin', $crawler->filter('[data-toggle="popover"]')->attr('data-content'));

        if (class_exists('Symfony\\Component\\Security\\Http\\Logout\\LogoutUrlGenerator')) {
            $this->assertContains('Sign out', $crawler->filter('[data-toggle="popover"]')->attr('data-content'));
        } else {
            $this->assertNotContains('Sign out', $crawler->filter('[data-toggle="popover"]')->attr('data-content'));
        }
    }

    public function testListViewPageTitle()
    {
        $crawler = $this->requestListView();

        $this->assertSame('Product Categories', trim($crawler->filter('head title')->text(null, true)));
        $this->assertSame('Product Categories', trim($crawler->filter('h1.title')->text(null, true)));
    }

    public function testListViewHelp()
    {
        $crawler = $this->requestListView();

        $this->assertSame('Global help message for <b>categories</b>', trim($crawler->filter('.content-header-help')->html()));
    }

    public function testListViewSearchAction()
    {
        $crawler = $this->requestListView();

        $this->assertSame('Look for Categories', trim($crawler->filter('.form-action-search [type="search"]')->attr('placeholder')));
        $this->assertContains('custom_class_search', $crawler->filter('.action-search')->attr('class'));

        $this->assertSame('search', $crawler->filter('.action-search input[type="hidden"][name="action"]')->attr('value'));
        $this->assertSame('Category', $crawler->filter('.action-search input[type="hidden"][name="entity"]')->attr('value'));

        // the search form doesn't include sort config unless it's explicitly included in the
        // request URI because the user click on some column to sort results
        $this->assertCount(0, $crawler->filter('.action-search input[type="hidden"][name="sortField"]'));
        $this->assertCount(0, $crawler->filter('.action-search input[type="hidden"][name="sortDirection"]'));
    }

    public function testListViewNewAction()
    {
        $crawler = $this->requestListView();

        $this->assertSame('New Category', trim($crawler->filter('.global-actions a.action-new')->text(null, true)));
        $this->assertSame('custom_class_new action-new', $crawler->filter('.global-actions a.action-new')->attr('class'));
        $this->assertSame('fa fa-fw fa-plus-circle', $crawler->filter('.global-actions a.action-new i')->attr('class'));
        $this->assertStringStartsWith('/admin/?action=new&entity=Category&view=list&sortField=id&sortDirection=DESC&page=1', $crawler->filter('.global-actions a.action-new')->attr('href'));
    }

    public function testListViewItemActions()
    {
        $crawler = $this->requestListView();

        $this->assertCount(15, $crawler->filter('#main .table td.actions a:contains("Show")'));
        $this->assertCount(15, $crawler->filter('#main .table td.actions a:contains("Edit")'));
        $this->assertCount(15, $crawler->filter('#main .table td.actions a:contains("Delete")'));
        $this->assertCount(15, $crawler->filter('#main .table td.actions a[title="Custom Action 1"]'));
        $this->assertCount(15, $crawler->filter('#main .table td.actions a[title="Custom Action 2"]:contains("Action 2")'));
    }

    public function testListViewDropdownItemActions()
    {
        $crawler = $this->requestListView('Product');

        $this->assertCount(15, $crawler->filter('#main .table td.actions div.actions-dropdown a.dropdown-item:contains("Edit")'));
    }

    public function testListViewTableIdColumn()
    {
        $crawler = $this->requestListView();

        $this->assertSame('ID', trim($crawler->filter('table th')->first()->text(null, true)),
            'The ID entity property is very special and we uppercase it automatically to improve its readability.'
        );
    }

    public function testListViewTableColumnLabels()
    {
        $crawler = $this->requestListView();
        $columnLabels = ['ID', 'Label', 'Parent category', 'Actions'];

        foreach ($columnLabels as $i => $label) {
            $this->assertSame($label, trim($crawler->filter('.table thead th')->eq($i)->text(null, true)));
        }
    }

    public function testListViewDefaultTableSorting()
    {
        $crawler = $this->requestListView();

        $this->assertCount(1, $crawler->filter('.table thead th[class*="sorted"]'), 'Table is sorted only by one column.');
        $this->assertSame('ID', trim($crawler->filter('.table thead th[class*="sorted"]')->text(null, true)), 'By default, table is soreted by ID column.');
        $this->assertSame('fa fa-fw fa-arrow-down', $crawler->filter('.table thead th[class*="sorted"] i')->attr('class'), 'The column used to sort results shows the right icon.');

        // the search form doesn't include sort config unless it's explicitly included in the
        // request URI because the user click on some column to sort results
        $this->assertCount(0, $crawler->filter('.action-search input[type="hidden"][name="sortField"]'));
        $this->assertCount(0, $crawler->filter('.action-search input[type="hidden"][name="sortDirection"]'));
    }

    public function testListViewTableContents()
    {
        $crawler = $this->requestListView();

        $this->assertCount(15, $crawler->filter('.table tbody tr'));
    }

    public function testListViewFieldClasses()
    {
        $crawler = $this->requestListView();

        $this->assertSame('string custom_class_list', trim($crawler->filter('.table thead tr th')->eq(1)->attr('class')));
        $this->assertSame('string custom_class_list', trim($crawler->filter('.table tbody tr td')->eq(1)->attr('class')));
    }

    public function testListViewPagination()
    {
        $crawler = $this->requestListView();

        $this->assertContains('200 results', $crawler->filter('.list-pagination')->text(null, true));

        $this->assertSame('page-item disabled', $crawler->filter('.list-pagination li:contains("Previous")')->attr('class'));
        $this->assertSame('page-link', $crawler->filter('.list-pagination a:contains("Previous")')->attr('class'));

        $this->assertStringStartsWith('/admin/?action=list&entity=Category&view=list&sortField=id&sortDirection=DESC&page=2', $crawler->filter('.list-pagination a:contains("Next")')->attr('href'));
    }

    public function testListViewCustomFormats()
    {
        $crawler = $this->requestListView('Purchase');

        $this->assertRegExp('/\d{8}/', trim($crawler->filter('#main table tr')->eq(1)->filter('td.date')->text(null, true)));
        $this->assertRegExp('/\d{2}:\d{2}/', trim($crawler->filter('#main table tr')->eq(1)->filter('td.time')->text(null, true)));
    }

    public function testShowViewDefaultFormats()
    {
        // the ID of purchases is a randome value, so the way to show the first
        // purchase is to get the ID from the first row of the list view
        $crawler = $this->requestListView('Purchase');
        $purchaseId = trim($crawler->filter('#main table tr')->eq(1)->filter('td')->eq(0)->text(null, true));
        $crawler = $this->requestShowView('Purchase', $purchaseId);

        $this->assertRegExp('/\d{4}-\d{2}-\d{2}/', trim($crawler->filter('#main .form-group:contains("Delivery date")')->filter('.form-control')->text(null, true)));
        $this->assertRegExp('/\d{2}:\d{2}:\d{2}/', trim($crawler->filter('#main .form-group:contains("Delivery hour")')->filter('.form-control')->text(null, true)));
    }

    public function testShowViewPageTitle()
    {
        $crawler = $this->requestShowView();

        $this->assertSame('Details for Category number 200', trim($crawler->filter('head title')->text(null, true)));
        $this->assertSame('Details for Category number 200', trim($crawler->filter('h1.title')->text(null, true)));
    }

    public function testShowViewHelp()
    {
        $crawler = $this->requestShowView();

        $this->assertSame('Help message overridden for the show view of categories', trim($crawler->filter('.content-header-help')->text(null, true)));
    }

    public function testShowViewFieldLabels()
    {
        $crawler = $this->requestShowView();
        $fieldLabels = ['#', 'Label', 'Parent category'];

        foreach ($fieldLabels as $i => $label) {
            $this->assertSame($label, trim($crawler->filter('#main .form-group label')->eq($i)->text(null, true)));
        }
    }

    public function testShowViewFieldClasses()
    {
        $crawler = $this->requestShowView();
        $fieldClasses = ['integer', 'string', 'association'];

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
        $parameters = [
            'action' => 'list',
            'entity' => 'Category',
            'sortField' => 'name',
            'sortDirection' => 'ASC',
            'page' => '2',
        ];

        // 1. visit a specific 'list' view page
        $crawler = $this->getBackendPage($parameters);

        // 2. click on the 'Show' link of the first item
        $link = $crawler->filter('td.actions a:contains("Show")')->eq(0)->link();
        $crawler = static::$client->click($link);

        // 3. the 'referer' parameter should point to the exact same previous 'list' page
        $refererUrl = $crawler->filter('.form-actions a:contains("Back to Category listing")')->attr('href');
        $queryString = parse_url($refererUrl, PHP_URL_QUERY);
        parse_str($queryString, $refererParameters);

        $this->assertSame($parameters, $refererParameters);
    }

    /**
     * The 'referer' parameter stores the original 'list' or 'search' page
     * from which the user browsed to other pages (edit, delete, show). When
     * visiting several consecutive pages, the 'referer' value should be kept
     * without changes.
     */
    public function testChainedReferer()
    {
        $parameters = [
            'action' => 'list',
            'entity' => 'Category',
            'sortField' => 'name',
            'sortDirection' => 'ASC',
            'page' => '2',
        ];

        // 1. visit a specific 'list' view page
        $crawler = $this->getBackendPage($parameters);

        // 2. click on the 'Show' link of the first item
        $link = $crawler->filter('td.actions a:contains("Show")')->eq(0)->link();
        $crawler = static::$client->click($link);

        // 3. click on the 'Edit' button
        $link = $crawler->filter('.form-actions a:contains("Modify Category")')->link();
        $crawler = static::$client->click($link);

        // 4. the 'referer' parameter should point to the exact same previous 'list' page
        $refererUrl = $crawler->filter('.form-actions a:contains("Return to listing")')->attr('href');
        $queryString = parse_url($refererUrl, PHP_URL_QUERY);
        parse_str($queryString, $refererParameters);

        $this->assertSame($parameters, $refererParameters);
    }

    public function testEditViewPageTitle()
    {
        $crawler = $this->requestEditView();

        $this->assertSame('Modify Category (200) details', trim($crawler->filter('head title')->text(null, true)));
        $this->assertSame('Modify Category (200) details', trim($crawler->filter('h1.title')->text(null, true)));
    }

    public function testEditViewHelp()
    {
        $crawler = $this->requestEditView();

        $this->assertSame('Help message overridden for the edit view of categories', trim($crawler->filter('.content-header-help')->text(null, true)));
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
        $fieldLabels = ['ID', 'Label', 'Parent Category Label'];

        foreach ($fieldLabels as $i => $label) {
            $this->assertSame($label, trim($crawler->filter('#main .form-group label')->eq($i)->text(null, true)));
        }
    }

    public function testEditViewFieldClasses()
    {
        $crawler = $this->requestEditView();
        $fieldDefaultClasses = ['integer', 'text', 'entity'];
        $fieldCustomClasses = ['integer', 'text', 'entity'];

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

        // delete action
        $this->assertContains('fa-minus-circle', trim($crawler->filter('.form-actions a:contains("Remove") i')->attr('class')));

        // list action
        $this->assertContains('fa-list', trim($crawler->filter('.form-actions a:contains("Return to listing") i')->attr('class')));
    }

    public function testEditViewListActionReferer()
    {
        $parameters = [
            'action' => 'list',
            'entity' => 'Category',
            'sortField' => 'name',
            'sortDirection' => 'ASC',
            'page' => '2',
        ];

        // 1. visit a specific 'list' view page
        $crawler = $this->getBackendPage($parameters);

        // 2. click on the 'Edit' link of the first item
        $link = $crawler->filter('td.actions a:contains("Edit")')->eq(0)->link();
        $crawler = static::$client->click($link);

        // 3. the 'referer' parameter should point to the exact same previous 'list' page
        $refererUrl = $crawler->filter('.form-actions a:contains("Return to listing")')->attr('href');
        $queryString = parse_url($refererUrl, PHP_URL_QUERY);
        parse_str($queryString, $refererParameters);

        $this->assertSame($parameters, $refererParameters);
    }

    public function testEditViewCheckboxLabel()
    {
        $crawler = $this->requestEditView('Product', '1');

        $this->assertContains('Custom Label', trim($crawler->filter('#product_enabled')->parents()->filter('label')->text(null, true)));
    }

    public function testListViewAutocompleteField()
    {
        $crawler = $this->requestEditView('Product', '1');

        $this->assertSame('Custom help message', trim($crawler->filter('select#product_categories_autocomplete + .form-help')->text(null, true)), 'This fixes issue #1441');
    }

    public function testNewViewPageTitle()
    {
        $crawler = $this->requestNewView();

        $this->assertSame('Add a new Category', trim($crawler->filter('head title')->text(null, true)));
        $this->assertSame('Add a new Category', trim($crawler->filter('h1.title')->text(null, true)));
    }

    public function testNewViewHelp()
    {
        $crawler = $this->requestNewView();

        $this->assertCount(0, $crawler->filter('.content-header .help-entity'));
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
        $fieldLabels = ['ID', 'Label', 'Parent Category Label'];

        foreach ($fieldLabels as $i => $label) {
            $this->assertSame($label, trim($crawler->filter('#main .form-group label')->eq($i)->text(null, true)));
        }
    }

    public function testNewViewFieldClasses()
    {
        $crawler = $this->requestNewView();
        $fieldClasses = ['integer', 'text', 'entity'];

        foreach ($fieldClasses as $i => $cssClass) {
            $this->assertContains('field-'.$cssClass, trim($crawler->filter('#main .form-group')->eq($i)->attr('class')));
        }
    }

    public function testNewViewActions()
    {
        $crawler = $this->requestNewView();

        // list action
        $this->assertContains('fa-list', trim($crawler->filter('.form-actions a:contains("Return to listing") i')->attr('class')));
    }

    public function testNewViewListActionReferer()
    {
        $parameters = [
            'action' => 'list',
            'entity' => 'Category',
            'sortField' => 'name',
            'sortDirection' => 'ASC',
            'page' => '2',
        ];

        // 1. visit a specific 'list' view page
        $crawler = $this->getBackendPage($parameters);

        // 2. click on the 'New' link to browse the 'new' view
        $link = $crawler->filter('.global-actions a:contains("New Category")')->link();
        $crawler = static::$client->click($link);

        // 3. the 'referer' parameter should point to the exact same previous 'list' page
        $refererUrl = $crawler->filter('.form-actions a:contains("Return to listing")')->attr('href');
        $queryString = parse_url($refererUrl, PHP_URL_QUERY);
        parse_str($queryString, $refererParameters);

        $this->assertSame($parameters, $refererParameters);
    }

    public function testNewCustomFormOptions()
    {
        $this->markTestSkipped('TODO: check why it doesn\'t work in some Symfony versions.');

        static::$client->enableProfiler();

        $crawler = $this->requestNewView();
        $this->assertSame(200, static::$client->getResponse()->getStatusCode());

        // test 'novalidate' attribute
        $this->assertSame('novalidate', $crawler->filter('#new-category-form')->first()->attr('novalidate'));

        $form = $crawler->selectButton('Save changes')->form();
        $form->remove('form[name]');
        static::$client->submit($form);
        $this->assertSame(200, static::$client->getResponse()->getStatusCode());

        // test validation groups
        $profile = static::$client->getProfile();
        $formData = $profile->getCollector('form')->getData();
        $categoryFields = $formData['forms']['category']['children'];
        $this->assertSame($categoryFields['name']['errors'][0]['message'], 'This value should not be null.');
    }

    public function testNewViewCheckboxLabel()
    {
        $crawler = $this->requestNewView('Product');

        $this->assertContains('Custom Label', trim($crawler->filter('#product_enabled')->parents()->filter('label')->text(null, true)));
    }

    public function testSearchViewPageTitle()
    {
        $crawler = $this->requestSearchView();

        $this->assertSame('Global Title for Search in Plural', trim($crawler->filter('head title')->text(null, true)));
        $this->assertSame('Global Title for Search in Plural', trim($crawler->filter('h1.title')->text(null, true)));
    }

    public function testSearchViewTableIdColumn()
    {
        $crawler = $this->requestSearchView();

        $this->assertSame('ID', trim($crawler->filter('table th')->first()->text(null, true)),
            'The ID entity property is very special and we uppercase it automatically to improve its readability.'
        );
    }

    public function testSearchViewTableColumnLabels()
    {
        $crawler = $this->requestSearchView();
        $columnLabels = ['ID', 'Label', 'Parent category', 'Actions'];

        foreach ($columnLabels as $i => $label) {
            $this->assertSame($label, trim($crawler->filter('.table thead th')->eq($i)->text(null, true)));
        }
    }

    public function testSearchViewDefaultTableSorting()
    {
        $crawler = $this->requestSearchView();

        $this->assertCount(1, $crawler->filter('.table thead th[class*="sorted"]'), 'Table is sorted only by one column.');
        $this->assertSame('Label', trim($crawler->filter('.table thead th[class*="sorted"]')->text(null, true)), 'By default, table is soreted by "Label" column (which is the "name" property).');
        $this->assertSame('fa fa-fw fa-arrow-up', $crawler->filter('.table thead th[class*="sorted"] i')->attr('class'), 'The column used to sort results shows the right icon.');

        // the search form doesn't include sort config unless it's explicitly included in the
        // request URI because the user click on some column to sort results
        $this->assertCount(0, $crawler->filter('.action-search input[type="hidden"][name="sortField"]'));
        $this->assertCount(0, $crawler->filter('.action-search input[type="hidden"][name="sortDirection"]'));
    }

    public function testSearchViewTableContents()
    {
        $crawler = $this->requestSearchView();

        $this->assertCount(15, $crawler->filter('.table tbody tr'));
    }

    public function testSearchViewPagination()
    {
        $crawler = $this->requestSearchView();

        $this->assertContains('200 results', $crawler->filter('.list-pagination')->text(null, true));

        $this->assertSame('page-item disabled', $crawler->filter('.list-pagination li:contains("Previous")')->attr('class'));
        $this->assertSame('page-link', $crawler->filter('.list-pagination a:contains("Previous")')->attr('class'));

        $this->assertStringStartsWith('/admin/?action=search&entity=Category&query=cat&sortField=name&sortDirection=ASC&page=2', $crawler->filter('.list-pagination a:contains("Next")')->attr('href'));
    }

    public function testSearchViewItemActions()
    {
        $crawler = $this->requestSearchView();

        $this->assertCount(15, $crawler->filter('#main .table td.actions a:contains("Show")'));
        $this->assertCount(15, $crawler->filter('#main .table td.actions a:contains("Edit")'));
        $this->assertCount(15, $crawler->filter('#main .table td.actions a:contains("Delete")'));
        $this->assertCount(15, $crawler->filter('#main .table td.actions a[title="Custom Action 1"]'));
        $this->assertCount(15, $crawler->filter('#main .table td.actions a[title="Custom Action 2"]:contains("Action 2")'));
    }

    public function testSearchViewShowActionReferer()
    {
        $parameters = [
            'action' => 'search',
            'entity' => 'Category',
            'sortField' => 'name',
            'sortDirection' => 'ASC',
            'page' => '2',
            'query' => 'cat',
        ];

        // 1. visit a specific 'search' view page
        $crawler = $this->getBackendPage($parameters);

        // 2. click on the 'Show' action of the first result
        $link = $crawler->filter('td.actions a:contains("Show")')->eq(0)->link();
        $crawler = static::$client->click($link);

        // 3. the 'referer' parameter should point to the previous specific 'search' view page
        $refererUrl = $crawler->filter('.form-actions a:contains("Back to Category listing")')->attr('href');
        $queryString = parse_url($refererUrl, PHP_URL_QUERY);
        parse_str($queryString, $refererParameters);

        $this->assertSame($parameters, $refererParameters);
    }

    public function testSearchUsingAssociations()
    {
        $parameters = [
            'action' => 'search',
            'entity' => 'Purchase',
            'page' => '1',
            'query' => 'user9@example',
        ];

        $crawler = $this->getBackendPage($parameters);

        $this->assertSame('user9', trim($crawler->filter('.table tbody tr td.association')->eq(0)->text(null, true)));
        $this->assertContains('sorted', $crawler->filter('.table th.association')->eq(0)->attr('class'));

        // the search form doesn't include sort config unless it's explicitly included in the
        // request URI because the user click on some column to sort results
        $this->assertCount(0, $crawler->filter('.action-search input[type="hidden"][name="sortField"]'));
        $this->assertCount(0, $crawler->filter('.action-search input[type="hidden"][name="sortDirection"]'));
    }

    public function testListViewVirtualFields()
    {
        $crawler = $this->requestListView('Product');

        $this->assertCount(15, $crawler->filter('.table tbody td:contains("Inaccessible")'));

        $firstVirtualField = $crawler->filter('.table tbody td:contains("Inaccessible") span')->first();
        $this->assertSame('badge badge-danger', $firstVirtualField->attr('class'));
        $this->assertSame(
            'Getter method does not exist for this field or the property is not public',
            $firstVirtualField->attr('title')
        );
    }

    public function testListViewImmutableDates()
    {
        if (!class_exists('\DateTimeImmutable')) {
            $this->markTestSkipped('DateTimeImmutable class does not exist in this PHP version.');
        }

        $crawler = $this->requestListView('User');

        $this->assertSame('October 18, 2005 16:27', trim($crawler->filter('#main table tr')->eq(1)->filter('td.datetime_immutable')->text(null, true)));
        $this->assertSame('2005-10-18', trim($crawler->filter('#main table tr')->eq(1)->filter('td.date_immutable')->text(null, true)));
        $this->assertSame('16:27:36', trim($crawler->filter('#main table tr')->eq(1)->filter('td.time_immutable')->text(null, true)));
    }

    public function testInputGroups()
    {
        $crawler = $this->requestNewView();

        $this->assertSame('<span><b>foo</b></span>', $crawler->filter('form#new-category-form .form-group')->eq(0)->filter('.input-group-prepend .input-group-text')->html());
        $this->assertSame('<span><span>bar</span></span>', $crawler->filter('form#new-category-form .form-group')->eq(0)->filter('.input-group-append .input-group-text')->html());
        $this->assertSame('<i class="fa fa-fw fa-foo"></i>', $crawler->filter('form#new-category-form .form-group')->eq(1)->filter('.input-group-prepend .input-group-text')->html());
        $this->assertSame('<i class="fa fa-fw fa-bar"></i>', $crawler->filter('form#new-category-form .form-group')->eq(1)->filter('.input-group-append .input-group-text')->html());
        $this->assertSame('<i class="fa fa-fw fa-foo"></i><span><b>foo</b></span>', $crawler->filter('form#new-category-form .form-group')->eq(2)->filter('.input-group-prepend .input-group-text')->html());
        $this->assertSame('<i class="fa fa-fw fa-bar"></i><span><span>bar</span></span>', $crawler->filter('form#new-category-form .form-group')->eq(2)->filter('.input-group-append .input-group-text')->html());
    }

    public function testBatchActions()
    {
        $crawler = $this->requestListView('Product');

        $this->assertSame('Delete', $crawler->filter('button.batch-action-requires-confirmation')->text(null, true));
        $this->assertCount(1, $crawler->filter('input[type="hidden"]#batch_form__token'));
        $this->assertCount(15, $crawler->filter('input[type="checkbox"].form-batch-checkbox'));
    }
}
