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

class ActionOverrideTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'action_override'));
    }

    public function testListViewActions()
    {
        $crawler = $this->requestListView();

        $this->assertCount(15, $crawler->filter('table a:contains("Edit")'));
    }

    public function testEditViewActions()
    {
        $crawler = $this->requestEditView();

        $this->assertCount(1, $crawler->filter('.form-actions a:contains("Back to listing")'));
    }

    public function testShowViewActions()
    {
        $crawler = $this->requestShowView();

        $this->assertCount(1, $crawler->filter('.form-actions a:contains("Delete")'));
    }

    public function testNewViewActions()
    {
        $crawler = $this->requestNewView();

        $this->assertCount(1, $crawler->filter('.form-actions a:contains("Back to listing")'));
    }
}
