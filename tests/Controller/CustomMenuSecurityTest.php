<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class CustomMenuSecurityTest extends AbstractTestCase
{
    protected static $options = ['environment' => 'custom_menu_security'];

    public function testMenuSecurityAsAnonymousUser()
    {
        $crawler = $this->requestListView();

        $this->assertCount(1, $crawler->filter('.sidebar-menu li'));
        $this->assertSame('Categories', trim($crawler->filter('.sidebar-menu li')->text(null, true)));
    }

    public function testMenuSecurityAsLoggedUser()
    {
        static::$client->followRedirects();
        $crawler = static::$client->request('GET', '/admin', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'pa$$word',
        ]);

        $this->assertCount(7, $crawler->filter('.sidebar-menu li'));
        $this->assertSame('Products', trim($crawler->filter('.sidebar-menu li')->eq(0)->filter('span')->eq(0)->text(null, true)));
        $this->assertSame('Product', trim($crawler->filter('.sidebar-menu li')->eq(1)->text(null, true)));
        $this->assertSame('Add Product', trim($crawler->filter('.sidebar-menu li')->eq(2)->text(null, true)));
        $this->assertSame('Additional Items', trim($crawler->filter('.sidebar-menu li')->eq(3)->text(null, true)));
        $this->assertSame('Absolute URL', trim($crawler->filter('.sidebar-menu li')->eq(4)->text(null, true)));
        $this->assertSame('Categories', trim($crawler->filter('.sidebar-menu li')->eq(5)->text(null, true)));
        $this->assertSame('About EasyAdmin', trim($crawler->filter('.sidebar-menu li')->eq(6)->text(null, true)));
    }
}
