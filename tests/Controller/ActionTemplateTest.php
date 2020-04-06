<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class ActionTemplateTest extends AbstractTestCase
{
    protected static $options = ['environment' => 'action_template'];

    public function testListViewActions()
    {
        // List is sorted DESC by "action_template" configuration, to ensure Child Categories first
        $crawler = $this->requestListView();

        $this->assertCount(15, $crawler->filter('table .actions a:contains("Parent")'));
        $this->assertCount(0, $crawler->filter('table .actions:contains("No Parent")'));
    }

    public function testShowViewActionsWithChildCategory()
    {
        $crawler = $this->requestShowView('Category', 101);

        $this->assertCount(1, $crawler->filter('.form-actions a:contains("Show Parent")'));
        $this->assertCount(0, $crawler->filter('.form-actions span:contains("No Parent")'));
    }

    public function testShowViewActionsWithParentCategory()
    {
        $crawler = $this->requestShowView('Category', 1);

        $this->assertCount(0, $crawler->filter('.form-actions a:contains("Show Parent")'));
        $this->assertCount(1, $crawler->filter('.form-actions span:contains("No Parent")'));
    }

    public function testEditViewActionsWithChildCategory()
    {
        $crawler = $this->requestEditView('Category', 150);

        $this->assertCount(1, $crawler->filter('.form-actions a:contains("Go to Parent")'));
        $this->assertCount(0, $crawler->filter('.form-actions span:contains("No Parent")'));
    }

    public function testEditViewActionsWithParentCategory()
    {
        $crawler = $this->requestEditView('Category', 50);

        $this->assertCount(0, $crawler->filter('.form-actions a:contains("Go to Parent")'));
        $this->assertCount(1, $crawler->filter('.form-actions span:contains("No Parent")'));
    }
}
