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

class CustomTranslationDomainTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'custom_translation_domain'));
    }

    public function testListView()
    {
        $crawler = $this->requestListView();

        $this->assertEquals('__Category__', trim($crawler->filter('.sidebar-menu li')->first()->text()));
        $this->assertEquals('__Category__', trim($crawler->filter('h1.title')->text()));
        $this->assertEquals('__Search__', trim($crawler->filter('.global-actions form button[type="submit"]')->text()));
        $this->assertEquals('__New__', trim($crawler->filter('.global-actions a.action-new')->text()));
        $this->assertEquals('__Name__', trim($crawler->filter('th[data-property-name="name"]')->text()));
        $this->assertEquals('__Products__', trim($crawler->filter('th[data-property-name="products"]')->text()));
        $this->assertEquals('__Edit__', trim($crawler->filter('td.actions')->first()->filter('a.action-edit')->text()));
        $this->assertEquals('__Delete__', trim($crawler->filter('td.actions')->first()->filter('a.action-delete')->text()));
    }

    public function testShowView()
    {
        $crawler = $this->requestShowView();

        $this->assertEquals('__Category__', trim($crawler->filter('.sidebar-menu li')->first()->text()));
        $this->assertEquals('__Category__ (#200)', trim($crawler->filter('h1.title')->text()));
        $this->assertEquals('__ID__', trim($crawler->filter('.form-group .control-label')->eq(0)->text()));
        $this->assertEquals('__Name__', trim($crawler->filter('.form-group .control-label')->eq(1)->text()));
        $this->assertEquals('__Products__', trim($crawler->filter('.form-group .control-label')->eq(2)->text()));
        $this->assertEquals('__Parent__', trim($crawler->filter('.form-group .control-label')->eq(3)->text()));
        $this->assertEquals('__Edit__', trim($crawler->filter('.form-actions a.action-edit')->text()));
        $this->assertEquals('__Delete__', trim($crawler->filter('.form-actions a.action-delete')->text()));
        $this->assertEquals('__Back to list__', trim($crawler->filter('.form-actions a.action-list')->text()));
    }

    public function testEditView()
    {
        $crawler = $this->requestEditView();

        $this->assertEquals('__Category__', trim($crawler->filter('.sidebar-menu li')->first()->text()));
        $this->assertEquals('Edit __Category__ (#200)', trim($crawler->filter('h1.title')->text()));
        $this->assertEquals('__Name__', trim($crawler->filter('form label')->eq(0)->text()));
        $this->assertEquals('__Products__', trim($crawler->filter('form label')->eq(1)->text()));
        $this->assertEquals('__Parent__', trim($crawler->filter('form label')->eq(2)->text()));
        $this->assertEquals('__Save__', trim($crawler->filter('.form-actions button[type="submit"]')->text()));
        $this->assertEquals('__Delete__', trim($crawler->filter('.form-actions .action-delete')->text()));
    }

    public function testNewView()
    {
        $crawler = $this->requestNewView();

        $this->assertEquals('__Category__', trim($crawler->filter('.sidebar-menu li')->first()->text()));
        $this->assertEquals('Create __Category__', trim($crawler->filter('h1.title')->text()));
        $this->assertEquals('__Name__', trim($crawler->filter('form label')->eq(0)->text()));
        $this->assertEquals('__Products__', trim($crawler->filter('form label')->eq(1)->text()));
        $this->assertEquals('__Parent__', trim($crawler->filter('form label')->eq(2)->text()));
        $this->assertEquals('__Save__', trim($crawler->filter('.form-actions button[type="submit"]')->text()));
        $this->assertEquals('__Back to list__', trim($crawler->filter('.form-actions .action-list')->text()));
    }
}
