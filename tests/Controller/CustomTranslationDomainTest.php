<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class CustomTranslationDomainTest extends AbstractTestCase
{
    protected static $options = ['environment' => 'custom_translation_domain'];

    public function testListView()
    {
        $crawler = $this->requestListView();

        $this->assertSame('__Category__', trim($crawler->filter('.sidebar-menu li')->first()->text(null, true)));
        $this->assertSame('__Category__', trim($crawler->filter('h1.title')->text(null, true)));
        $this->assertSame('__New__', trim($crawler->filter('.global-actions a.action-new')->text(null, true)));
        $this->assertSame('__Name__', trim($crawler->filter('th')->eq(1)->text(null, true)));
        $this->assertSame('__Products__', trim($crawler->filter('th')->eq(2)->text(null, true)));
        $this->assertSame('__Edit__', trim($crawler->filter('td.actions')->first()->filter('a.action-edit')->text(null, true)));
        $this->assertSame('__Delete__', trim($crawler->filter('td.actions')->first()->filter('a.action-delete')->text(null, true)));
    }

    public function testShowView()
    {
        $crawler = $this->requestShowView();

        $this->assertSame('__Category__', trim($crawler->filter('.sidebar-menu li')->first()->text(null, true)));
        $this->assertSame('__Category__ (#200)', trim($crawler->filter('h1.title')->text(null, true)));
        $this->assertSame('__ID__', trim($crawler->filter('.form-group .control-label')->eq(0)->text(null, true)));
        $this->assertSame('__Name__', trim($crawler->filter('.form-group .control-label')->eq(1)->text(null, true)));
        $this->assertSame('__Products__', trim($crawler->filter('.form-group .control-label')->eq(2)->text(null, true)));
        $this->assertSame('__Parent__', trim($crawler->filter('.form-group .control-label')->eq(3)->text(null, true)));
        $this->assertSame('__Edit__', trim($crawler->filter('.form-actions a.action-edit')->text(null, true)));
        $this->assertSame('__Delete__', trim($crawler->filter('.form-actions a.action-delete')->text(null, true)));
        $this->assertSame('__Back to list__', trim($crawler->filter('.form-actions a.action-list')->text(null, true)));
    }

    public function testEditView()
    {
        $crawler = $this->requestEditView();

        $this->assertSame('__Category__', trim($crawler->filter('.sidebar-menu li')->first()->text(null, true)));
        $this->assertSame('Edit __Category__ (#200)', trim($crawler->filter('h1.title')->text(null, true)));
        $this->assertSame('__Name__', trim($crawler->filter('form label')->eq(0)->text(null, true)));
        $this->assertSame('__Products__', trim($crawler->filter('form label')->eq(1)->text(null, true)));
        $this->assertSame('__Parent__', trim($crawler->filter('form label')->eq(2)->text(null, true)));
        $this->assertSame('__Save__', trim($crawler->filter('.form-actions button[type="submit"]')->text(null, true)));
        $this->assertSame('__Delete__', trim($crawler->filter('.form-actions .action-delete')->text(null, true)));
    }

    public function testNewView()
    {
        $crawler = $this->requestNewView();

        $this->assertSame('__Category__', trim($crawler->filter('.sidebar-menu li')->first()->text(null, true)));
        $this->assertSame('Create __Category__', trim($crawler->filter('h1.title')->text(null, true)));
        $this->assertSame('__Name__', trim($crawler->filter('form label')->eq(0)->text(null, true)));
        $this->assertSame('__Products__', trim($crawler->filter('form label')->eq(1)->text(null, true)));
        $this->assertSame('__Parent__', trim($crawler->filter('form label')->eq(2)->text(null, true)));
        $this->assertSame('__Save__', trim($crawler->filter('.form-actions button[type="submit"]')->text(null, true)));
        $this->assertSame('__Back to list__', trim($crawler->filter('.form-actions .action-list')->text(null, true)));
    }
}
