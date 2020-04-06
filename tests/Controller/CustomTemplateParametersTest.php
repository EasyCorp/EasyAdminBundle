<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class CustomTemplateParametersTest extends AbstractTestCase
{
    protected static $options = ['environment' => 'custom_template_parameters'];

    public function testListViewCustomParameters()
    {
        $this->requestListView();

        $this->assertContains('My custom template parameter is "list"', static::$client->getResponse()->getContent());
    }

    public function testShowViewCustomParameters()
    {
        $this->requestShowView();

        $this->assertContains('My custom template parameter is "show"', static::$client->getResponse()->getContent());
    }

    public function testSearchViewCustomParameters()
    {
        $this->requestSearchView();

        $this->assertContains('My custom template parameter is "search"', static::$client->getResponse()->getContent());
    }

    public function testEditViewCustomParameters()
    {
        $this->requestEditView();

        $this->assertContains('My custom template parameter is "edit"', static::$client->getResponse()->getContent());
    }

    public function testNewViewCustomParameters()
    {
        $this->requestNewView();

        $this->assertContains('My custom template parameter is "new"', static::$client->getResponse()->getContent());
    }
}
