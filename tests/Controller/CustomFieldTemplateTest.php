<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class CustomFieldTemplateTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'custom_field_template'));
    }

    public function testListViewCustomFieldTemplate()
    {
        $crawler = $this->requestListView();

        $this->assertContains('Custom template for "name" field in the "list" view.', $crawler->filter('#main table td[data-label="Name"]')->eq(0)->text());
        $this->assertContains('The value of the custom option is "custom_list_value".', $crawler->filter('#main table td[data-label="Name"]')->eq(0)->text());
        $this->assertContains('The custom template knows that the "this_property_does_no_exist" field is not accessible.', $crawler->filter('#main table td[data-label="This property does no exist"]')->eq(0)->text());
    }

    public function testShowViewCustomFieldTemplate()
    {
        $crawler = $this->requestShowView();

        $this->assertContains('Custom template for "name" field in the "show" view.', $crawler->filter('#main .form-control')->eq(0)->text());
        $this->assertContains('The value of the custom option is "custom_show_value".', $crawler->filter('#main .form-control')->eq(0)->text());
        $this->assertContains('The custom template knows that the "this_property_does_no_exist" field is not accessible.', $crawler->filter('#main .form-control')->eq(1)->text());
    }
}
