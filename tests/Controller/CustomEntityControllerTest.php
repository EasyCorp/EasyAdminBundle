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

class CustomEntityControllerTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'custom_entity_controller'));
    }

    public function testListAction()
    {
        $this->requestListView();
        $this->assertContains('Overridden list action.', $this->client->getResponse()->getContent());
    }

    public function testShowAction()
    {
        $this->requestShowView();
        $this->assertContains('Overridden show action.', $this->client->getResponse()->getContent());
    }
}
