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
        $this->assertSame('ID', trim($crawler->filter('table.datagrid thead th')->eq(0)->text(null, true)));
        $this->assertSame('Actions', trim($crawler->filter('table.datagrid thead th')->eq(1)->text(null, true)));
    }

    public function testListViewAsLoggedUser()
    {
        $crawler = $this->requestListViewAsLoggedUser();

        $this->assertCount(3, $crawler->filter('table.datagrid thead th'));

        $this->assertSame('ID', trim($crawler->filter('table.datagrid thead th')->eq(0)->text(null, true)));
        $this->assertSame('Name', trim($crawler->filter('table.datagrid thead th')->eq(1)->text(null, true)));
        $this->assertSame('Actions', trim($crawler->filter('table.datagrid thead th')->eq(2)->text(null, true)));
    }

    public function testShowViewAsAnonymousUser()
    {
        $crawler = $this->requestShowView();

        $this->assertCount(1, $crawler->filter('.content-body .form-group'));
        $this->assertSame('ID', trim($crawler->filter('.content-body .form-group .control-label')->eq(0)->text(null, true)));
    }

    public function testShowViewAsLoggedUser()
    {
        $crawler = $this->requestShowViewAsLoggedUser();

        $this->assertCount(2, $crawler->filter('.content-body .form-group'));

        $this->assertSame('ID', trim($crawler->filter('.content-body .form-group .control-label')->eq(0)->text(null, true)));
        $this->assertSame('Name', trim($crawler->filter('.content-body .form-group .control-label')->eq(1)->text(null, true)));
    }

    public function testNewViewAsAnonymousUser()
    {
        $crawler = $this->requestNewView();

        $this->assertCount(1, $crawler->filter('.content-body form .form-group'));
        $this->assertSame('ID', trim($crawler->filter('.content-body form .form-group .form-control-label')->eq(0)->text(null, true)));
    }

    public function testNewViewAsLoggedUser()
    {
        $crawler = $this->requestNewViewAsLoggedUser();

        $this->assertCount(2, $crawler->filter('.content-body form .form-group'));

        $this->assertSame('ID', trim($crawler->filter('.content-body form .form-group .form-control-label')->eq(0)->text(null, true)));
        $this->assertSame('Name', trim($crawler->filter('.content-body form .form-group .form-control-label')->eq(1)->text(null, true)));
    }

    public function testEditViewAsAnonymousUser()
    {
        $crawler = $this->requestEditView();

        $this->assertCount(1, $crawler->filter('.content-body form .form-group'));
        $this->assertSame('ID', trim($crawler->filter('.content-body form .form-group .form-control-label')->eq(0)->text(null, true)));
    }

    public function testEditViewAsLoggedUser()
    {
        $crawler = $this->requestEditViewAsLoggedUser();

        $this->assertCount(2, $crawler->filter('.content-body form .form-group'));

        $this->assertSame('ID', trim($crawler->filter('.content-body form .form-group .form-control-label')->eq(0)->text(null, true)));
        $this->assertSame('Name', trim($crawler->filter('.content-body form .form-group .form-control-label')->eq(1)->text(null, true)));
    }
}
