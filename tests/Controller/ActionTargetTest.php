<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class ActionTargetTest extends AbstractTestCase
{
    protected static $options = ['environment' => 'action_target'];

    public function testListViewActions()
    {
        $crawler = $this->requestListView();

        $this->assertSame('custom_target', $crawler->filter('.button-action a:contains("Add Category")')->attr('target'));

        $this->assertSame('_blank', $crawler->filter('table a:contains("Edit")')->attr('target'));
        $this->assertSame('_parent', $crawler->filter('table a:contains("Custom action")')->attr('target'));
        $this->assertSame('_self', $crawler->filter('table a:contains("Another custom action")')->attr('target'));
        $this->assertSame('_self', $crawler->filter('table a:contains("Delete")')->attr('target'));
        $this->assertSame('_self', $crawler->filter('#modal-delete-button')->attr('formtarget'));
    }

    public function testEditViewActions()
    {
        $crawler = $this->requestEditView();

        $this->assertSame('_parent', $crawler->filter('.form-actions a:contains("Back to listing")')->attr('target'));
        $this->assertSame('_blank', $crawler->filter('.form-actions a:contains("Custom action")')->attr('target'));
        $this->assertSame('_blank', $crawler->filter('.form-actions a:contains("Delete")')->attr('target'));
        $this->assertSame('_blank', $crawler->filter('#modal-delete-button')->attr('formtarget'));
    }

    public function testShowViewActions()
    {
        $crawler = $this->requestShowView();

        $this->assertSame('_self', $crawler->filter('.form-actions a:contains("Edit")')->attr('target'));
        $this->assertSame('_self', $crawler->filter('.form-actions a:contains("Back to listing")')->attr('target'));
        $this->assertSame('custom_target', $crawler->filter('.form-actions a:contains("Custom action")')->attr('target'));
        $this->assertSame('_self', $crawler->filter('.form-actions a:contains("Delete")')->attr('target'));
        $this->assertSame('_self', $crawler->filter('#modal-delete-button')->attr('formtarget'));
    }

    public function testNewViewActions()
    {
        $crawler = $this->requestNewView();

        $this->assertSame('_top', $crawler->filter('.form-actions a:contains("Back to listing")')->attr('target'));
        $this->assertSame('_parent', $crawler->filter('.form-actions a:contains("Custom action")')->attr('target'));
    }
}
