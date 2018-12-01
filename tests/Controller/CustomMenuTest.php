<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class CustomMenuTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(['environment' => 'custom_menu']);
    }

    public function testCustomBackendHomepage()
    {
        $this->client->request('GET', '/admin/');

        $this->assertSame(
            '/admin/?action=list&entity=Category&menuIndex=0&submenuIndex=3',
            $this->client->getResponse()->headers->get('location')
        );

        $crawler = $this->client->followRedirect();

        $this->assertSame(
            'Products',
            \trim($crawler->filter('.sidebar-menu li.active.submenu-active a')->text())
        );

        $this->assertSame(
            'Categories',
            \trim($crawler->filter('.sidebar-menu .treeview-menu li.active a')->text())
        );
    }

    public function testBackendHomepageConfig()
    {
        $this->getBackendHomepage();
        $backendConfig = $this->client->getContainer()->get('easyadmin.config.manager')->getBackendConfig();

        $this->assertArraySubset([
            'route' => 'easyadmin',
            'params' => ['action' => 'list', 'entity' => 'Category'],
        ], $backendConfig['homepage']);
    }

    public function testDefaultMenuItem()
    {
        $this->getBackendHomepage();
        $backendConfig = $this->client->getContainer()->get('easyadmin.config.manager')->getBackendConfig();

        $this->assertArraySubset([
            'label' => 'Categories',
            'entity' => 'Category',
            'type' => 'entity',
        ], $backendConfig['default_menu_item']);
    }

    public function testMenuDividers()
    {
        $crawler = $this->getBackendHomepage();

        $this->assertContains(
            'header',
            $crawler->filter('.sidebar-menu li:contains("About EasyAdmin")')->attr('class')
        );

        $this->assertContains(
            'header',
            $crawler->filter('.sidebar-menu .treeview-menu li:contains("Additional Items")')->attr('class')
        );
    }

    public function testMenuIcons()
    {
        $crawler = $this->getBackendHomepage();

        $this->assertSame(
            'fa fa-shopping-basket',
            $crawler->filter('.sidebar-menu li:contains("Products") i')->attr('class'),
            'First level menu item with custom icon'
        );

        $this->assertSame(
            'fa fa-folder-open',
            $crawler->filter('.sidebar-menu li:contains("Images") i')->attr('class'),
            'First level menu item with default icon'
        );

        $this->assertCount(
            0,
            $crawler->filter('.sidebar-menu li:contains("Purchases") i'),
            'First level menu item without icon'
        );

        $this->assertSame(
            'fa fa-th-list',
            $crawler->filter('.sidebar-menu .treeview-menu li:contains("List Products") i')->attr('class'),
            'Second level menu item with custom icon'
        );

        $this->assertCount(
            0,
            $crawler->filter('.sidebar-menu .treeview-menu li:contains("Add Product") i'),
            'Second level menu items don\'t show any icon by default'
        );

        $this->assertCount(
            0,
            $crawler->filter('.sidebar-menu .treeview-menu li:contains("Categories") i'),
            'Second level menu item without icon'
        );
    }

    public function testMenuCssClasses()
    {
        $crawler = $this->getBackendHomepage();

        $this->assertSame(
            'label-custom-css-class',
            $crawler->filter('.sidebar-menu li:contains("Products") a')->attr('class'),
            'First level label menu item with custom CSS class'
        );

        $this->assertSame(
            'entity-custom-css-class',
            $crawler->filter('.sidebar-menu li:contains("Images") a')->attr('class'),
            'First level entity menu item with custom CSS class'
        );

        $this->assertSame(
            '',
            $crawler->filter('.sidebar-menu li:contains("Purchases") a')->attr('class'),
            'First level entity menu item without custom CSS class'
        );

        $this->assertSame(
            'route-custom-css-class',
            $crawler->filter('.sidebar-menu li:contains("Custom Internal Route") a')->attr('class'),
            'First level route menu item with custom CSS class'
        );

        $this->assertSame(
            'children-custom-css-class',
            $crawler->filter('.sidebar-menu .treeview-menu li:contains("Categories") a')->attr('class'),
            'Second level menu item with custom CSS class'
        );
    }

    public function testMenuTargets()
    {
        $crawler = $this->getBackendHomepage();

        $this->assertSame(
            '_blank',
            $crawler->filter('.sidebar-menu li:contains("Project Home") a')->attr('target')
        );

        $this->assertSame(
            '_self',
            $crawler->filter('.sidebar-menu li:contains("Documentation") a')->attr('target')
        );

        $this->assertSame(
            'arbitrary_value',
            $crawler->filter('.sidebar-menu li:contains("Report Issues") a')->attr('target')
        );
    }

    public function testMenuUrls()
    {
        $crawler = $this->getBackendHomepage();

        $this->assertSame(
            '#',
            $crawler->filter('.sidebar-menu li:contains("Products") a')->attr('href'),
            'First level menu, empty item'
        );

        $this->assertSame(
            '/admin/?entity=Image&action=list&menuIndex=1&submenuIndex=-1',
            $crawler->filter('.sidebar-menu li:contains("Images") a')->attr('href'),
            'First level menu, default link'
        );

        $this->assertSame(
            '/admin/?entity=Purchase&action=list&menuIndex=2&submenuIndex=-1&sortField=deliveryDate&customParameter=customValue',
            $crawler->filter('.sidebar-menu li:contains("Purchases") a')->attr('href'),
            'First level menu, customized link'
        );

        $this->assertSame(
            'https://github.com/javiereguiluz/EasyAdminBundle',
            $crawler->filter('.sidebar-menu li:contains("Project Home") a')->attr('href'),
            'First level menu, absolute URL'
        );
    }

    public function testLinkTypes()
    {
        $crawler = $this->getBackendHomepage();

        $this->assertSame(
            null,
            $crawler->filter('.sidebar-menu li:contains("Categories") a')->attr('rel'),
            'The "rel" attribute is not added by default to menu items.'
        );

        $this->assertSame(
            'noreferrer',
            $crawler->filter('.sidebar-menu li:contains("Project Home") a')->attr('rel'),
            'External URLs define a "rel=noreferrer" attribute by default'
        );

        $this->assertSame(
            'preconnect',
            $crawler->filter('.sidebar-menu li:contains("Documentation") a')->attr('rel'),
            'If a URL defines a custom "rel" attribute, then "noreferrer" is not added by default.'
        );

        $this->assertSame(
            'index dns-prefetch bookmark',
            $crawler->filter('.sidebar-menu li:contains("Custom Internal Route") a')->attr('rel'),
            'Items can define multiple values in the "rel" attribute'
        );
    }

    public function testMenuItemTypes()
    {
        $expectedTypesMainMenu = ['empty', 'entity', 'entity', 'divider', 'link', 'link', 'link', 'divider', 'route', 'route'];
        $expectedTypesSubMenu = ['entity', 'entity', 'divider', 'entity', 'link'];

        $this->getBackendHomepage();
        $backendConfig = $this->client->getContainer()->get('easyadmin.config.manager')->getBackendConfig();
        $menuConfig = $backendConfig['design']['menu'];

        foreach ($menuConfig as $i => $itemConfig) {
            $this->assertSame($expectedTypesMainMenu[$i], $itemConfig['type']);
        }

        foreach ($menuConfig[0]['children'] as $i => $itemConfig) {
            $this->assertSame($expectedTypesSubMenu[$i], $itemConfig['type']);
        }
    }

    public function testExternalRoutesDontIncludeIndexParameters()
    {
        $crawler = $this->getBackendHomepage();

        $this->assertSame(
            '/custom-route?custom_parameter=Lorem%20Ipsum',
            $crawler->filter('.sidebar-menu li:contains("Custom External Route") a')->attr('href')
        );

        $this->assertSame(
            '/admin/?menuIndex=9&submenuIndex=-1',
            $crawler->filter('.sidebar-menu li:contains("Custom Internal Route") a')->attr('href')
        );
    }

    public function testCustomQueryParametersAreMaintained()
    {
        // 1. visit the homepage and click on the menu entry with custom parameters
        $crawler = $this->getBackendHomepage();
        $link = $crawler->filter('.sidebar-menu li:contains("Purchases") a')->eq(0)->link();
        $crawler = $this->client->click($link);

        // 2. click on the 'Edit' link of the first item
        $link = $crawler->filter('td.actions a:contains("Edit")')->eq(0)->link();
        $crawler = $this->client->click($link);

        // 3. the 'referer' parameter should contain the custom query string param
        $refererUrl = $crawler->filter('.form-actions a:contains("Back to listing")')->attr('href');
        $queryString = \parse_url($refererUrl, PHP_URL_QUERY);
        \parse_str($queryString, $refererParameters);

        $this->assertSame('customValue', $refererParameters['customParameter']);
    }
}
