<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class CustomFieldTemplateTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(['environment' => 'custom_field_template']);
    }

    /**
     * @group legacy
     */
    public function testListViewCustomFieldTemplate()
    {
        $crawler = $this->requestListView();

        $this->assertContains('Custom template for "name" field in the "list" view.', $crawler->filter('#main .datagrid td.string')->eq(0)->text());
        $this->assertContains('The value of the custom option is "custom_list_value".', $crawler->filter('#main .datagrid td.string')->eq(0)->text());
        $this->assertContains('The custom template knows that the "this_property_does_no_exist" field is not accessible.', $crawler->filter('#main .datagrid td.text')->eq(0)->text());
    }

    /**
     * @group legacy
     */
    public function testShowViewCustomFieldTemplate()
    {
        $crawler = $this->requestShowView();

        $this->assertContains('Custom template for "name" field in the "show" view.', $crawler->filter('#main .form-control')->eq(0)->text());
        $this->assertContains('The value of the custom option is "custom_show_value".', $crawler->filter('#main .form-control')->eq(0)->text());
        $this->assertContains('The custom template knows that the "this_property_does_no_exist" field is not accessible.', $crawler->filter('#main .form-control')->eq(1)->text());
    }
}
