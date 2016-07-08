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

use JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class CustomMenuTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'custom_menu'));
    }

    public function testCustomBackendHomepage()
    {
        $this->client->request('GET', '/admin/');

        $this->assertEquals(
            '/admin/?action=list&entity=Category&menuIndex=0&submenuIndex=3',
            $this->client->getResponse()->headers->get('location')
        );

        $crawler = $this->client->followRedirect();

        $this->assertEquals(
            'Products',
            trim($crawler->filter('.sidebar-menu li.active.submenu-active a')->text())
        );

        $this->assertEquals(
            'Categories',
            trim($crawler->filter('.sidebar-menu .treeview-menu li.active a')->text())
        );
    }

    public function testBackendHomepageConfig()
    {
        $this->getBackendHomepage();
        $backendConfig = $this->client->getContainer()->get('easyadmin.config.manager')->getBackendConfig();

        $this->assertArraySubset(array(
            'route' => 'easyadmin',
            'params' => array('action' => 'list', 'entity' => 'Category'),
        ), $backendConfig['homepage']);
    }

    public function testDefaultMenuItem()
    {
        $this->getBackendHomepage();
        $backendConfig = $this->client->getContainer()->get('easyadmin.config.manager')->getBackendConfig();

        $this->assertArraySubset(array(
            'label' => 'Categories',
            'entity' => 'Category',
            'type' => 'entity',
        ), $backendConfig['default_menu_item']);
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

        $this->assertEquals(
            'fa fa-shopping-basket',
            $crawler->filter('.sidebar-menu li:contains("Products") i')->attr('class'),
            'First level menu item with custom icon'
        );

        $this->assertEquals(
            'fa fa-chevron-circle-right',
            $crawler->filter('.sidebar-menu li:contains("Images") i')->attr('class'),
            'First level menu item with default icon'
        );

        $this->assertCount(
            0,
            $crawler->filter('.sidebar-menu li:contains("Purchases") i'),
            'First level menu item without icon'
        );

        $this->assertEquals(
            'fa fa-th-list',
            $crawler->filter('.sidebar-menu .treeview-menu li:contains("List Products") i')->attr('class'),
            'Second level menu item with custom icon'
        );

        $this->assertEquals(
            'fa fa-chevron-right',
            $crawler->filter('.sidebar-menu .treeview-menu li:contains("Add Product") i')->attr('class'),
            'Second level menu item with default icon'
        );

        $this->assertCount(
            0,
            $crawler->filter('.sidebar-menu .treeview-menu li:contains("Categories") i'),
            'Second level menu item without icon'
        );
    }

    public function testMenuTargets()
    {
        $crawler = $this->getBackendHomepage();

        $this->assertEquals(
            '_blank',
            $crawler->filter('.sidebar-menu li:contains("Project Home") a')->attr('target')
        );

        $this->assertEquals(
            '_self',
            $crawler->filter('.sidebar-menu li:contains("Documentation") a')->attr('target')
        );

        $this->assertEquals(
            'arbitrary_value',
            $crawler->filter('.sidebar-menu li:contains("Report Issues") a')->attr('target')
        );
    }

    public function testMenuUrls()
    {
        $crawler = $this->getBackendHomepage();

        $this->assertEquals(
            '#',
            $crawler->filter('.sidebar-menu li:contains("Products") a')->attr('href'),
            'First level menu, empty item'
        );

        $this->assertEquals(
            '/admin/?entity=Image&action=list&menuIndex=1&submenuIndex=-1',
            $crawler->filter('.sidebar-menu li:contains("Images") a')->attr('href'),
            'First level menu, default link'
        );

        $this->assertEquals(
            '/admin/?entity=Purchase&action=list&menuIndex=2&submenuIndex=-1&sortField=deliveryDate',
            $crawler->filter('.sidebar-menu li:contains("Purchases") a')->attr('href'),
            'First level menu, customized link'
        );

        $this->assertEquals(
            'https://github.com/javiereguiluz/EasyAdminBundle',
            $crawler->filter('.sidebar-menu li:contains("Project Home") a')->attr('href'),
            'First level menu, absolute URL'
        );
    }

    public function testMenuItemTypes()
    {
        $expectedTypesMainMenu = array('empty', 'entity', 'entity', 'divider', 'link', 'link', 'link', 'divider', 'route', 'route');
        $expectedTypesSubMenu = array('entity', 'entity', 'divider', 'entity', 'link');

        $this->getBackendHomepage();
        $backendConfig = $this->client->getContainer()->get('easyadmin.config.manager')->getBackendConfig();
        $menuConfig = $backendConfig['design']['menu'];

        foreach ($menuConfig as $i => $itemConfig) {
            $this->assertEquals($expectedTypesMainMenu[$i], $itemConfig['type']);
        }

        foreach ($menuConfig[0]['children'] as $i => $itemConfig) {
            $this->assertEquals($expectedTypesSubMenu[$i], $itemConfig['type']);
        }
    }

    public function testExternalRoutesDontIncludeIndexParameters()
    {
        $crawler = $this->getBackendHomepage();

        $this->assertEquals(
            '/custom-route?custom_parameter=Lorem+Ipsum',
            $crawler->filter('.sidebar-menu li:contains("Custom External Route") a')->attr('href')
        );

        $this->assertEquals(
            '/admin/?menuIndex=9&submenuIndex=-1',
            $crawler->filter('.sidebar-menu li:contains("Custom Internal Route") a')->attr('href')
        );
    }
}
