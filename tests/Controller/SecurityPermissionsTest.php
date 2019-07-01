<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class SecurityPermissionsTest extends AbstractTestCase
{
    protected static $options = ['environment' => 'security_permissions'];

    public function testListViewAsAnonymousUser()
    {
        $crawler = $this->requestListView();

        $this->assertCount(2, $crawler->filter('table.datagrid thead th'));
        $this->assertSame('ID', \trim($crawler->filter('table.datagrid thead th')->eq(0)->text()));
        $this->assertSame('Actions', \trim($crawler->filter('table.datagrid thead th')->eq(1)->text()));
    }

    public function testListViewAsLoggedUser()
    {
        static::$client->followRedirects();
        $crawler = static::$client->request('GET', '/admin', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'pa$$word',
        ]);

        $this->assertCount(3, $crawler->filter('table.datagrid thead th'));

        $this->assertSame('ID', \trim($crawler->filter('table.datagrid thead th')->eq(0)->text()));
        $this->assertSame('Name', \trim($crawler->filter('table.datagrid thead th')->eq(1)->text()));
        $this->assertSame('Actions', \trim($crawler->filter('table.datagrid thead th')->eq(2)->text()));
    }
}
