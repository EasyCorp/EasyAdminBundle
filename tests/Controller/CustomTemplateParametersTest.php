<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class CustomTemplateParametersTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'custom_template_parameters'));
    }

    public function testListViewCustomParameters()
    {
        $this->requestListView();

        $this->assertContains('My custom template parameter is "list"', $this->client->getResponse()->getContent());
    }

    public function testShowViewCustomParameters()
    {
        $this->requestShowView();

        $this->assertContains('My custom template parameter is "show"', $this->client->getResponse()->getContent());
    }

    public function testSearchViewCustomParameters()
    {
        $this->requestSearchView();

        $this->assertContains('My custom template parameter is "search"', $this->client->getResponse()->getContent());
    }

    public function testEditViewCustomParameters()
    {
        $this->requestEditView();

        $this->assertContains('My custom template parameter is "edit"', $this->client->getResponse()->getContent());
    }

    public function testNewViewCustomParameters()
    {
        $this->requestNewView();

        $this->assertContains('My custom template parameter is "new"', $this->client->getResponse()->getContent());
    }
}
