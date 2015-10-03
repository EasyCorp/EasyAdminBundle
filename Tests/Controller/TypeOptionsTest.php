<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Controller;

use Symfony\Component\DomCrawler\Crawler;
use JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class TypeOptionsTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'type_options'));
    }

    public function testNewViewTypeOptions()
    {
        $crawler = $this->requestNewView();

        $this->assertEquals('readonly', $crawler->filter('#main form #form_name')->eq(0)->attr('readonly'));

        $this->assertCount(201, $crawler->filter('#main form #form_parent input[type=radio]'));
        $this->assertEquals('Select a category...', $crawler->filter('#main form #form_parent div.radio')->eq(0)->text());
    }

    public function testEditViewTypeOptions()
    {
        $crawler = $this->requestEditView();

        $this->assertContains('col-sm-6', $crawler->filter('#main form label[for=form_name]')->attr('class'));
        $this->assertContains('col-sm-6', $crawler->filter('#main form input#form_name')->attr('class'));

        $this->assertContains('col-sm-4', $crawler->filter('#main form label[for=form_parent]')->attr('class'));
        $this->assertContains('col-sm-10', $crawler->filter('#main form select#form_parent')->attr('class'));
    }

    /**
     * @return Crawler
     */
    private function requestNewView()
    {
        return $this->getBackendPage(array(
            'action' => 'new',
            'entity' => 'Category',
        ));
    }

    /**
     * @return Crawler
     */
    private function requestEditView()
    {
        return $this->getBackendPage(array(
            'action' => 'edit',
            'entity' => 'Category',
            'id' => '50',
        ));
    }
}
