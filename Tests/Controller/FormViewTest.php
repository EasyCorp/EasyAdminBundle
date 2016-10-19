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

use JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class FormViewTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'form_view'));
    }

    public function testNewView()
    {
        $crawler = $this->requestNewView('Product');

        $this->assertEquals('Group Label 1', trim($crawler->filter('form .box .box-title')->eq(0)->text()));

        $this->assertCount(1, $crawler->filter('form .box-body #product_ean'));
        $this->assertNull($crawler->filter('form .box-body input')->eq(0)->attr('required'));

        $this->assertEquals('Section Label 1', trim($crawler->filter('form .box-body .field-section h2')->eq(0)->text()));

        $this->assertCount(1, $crawler->filter('form .box-body #product_name'));

        $this->assertCount(1, $crawler->filter('form .box-body .field-divider'));

        $this->assertCount(0, $crawler->filter('form .box-body #product_description',
            'The description field defined in the form view is removed by the new view.')
        );
    }

    public function testEditView()
    {
        $crawler = $this->requestEditView('Product', 1);

        $this->assertEquals('Group Label 1', trim($crawler->filter('form .box .box-title')->eq(0)->text()));

        $this->assertCount(0, $crawler->filter('form .box-body #product_ean',
            'The EAN field defined in the form view is removed by the edit view.')
        );

        $this->assertEquals('Section Label 1', trim($crawler->filter('form .box-body .field-section h2')->eq(0)->text()));

        $this->assertCount(1, $crawler->filter('form .box-body #product_name'));
        $this->assertEquals('Edit Help', trim($crawler->filter('form .box-body input + .help-block')->eq(0)->text()));

        $this->assertCount(1, $crawler->filter('form .box-body .field-divider'));
        $this->assertCount(1, $crawler->filter('form .box-body #product_description'));
        $this->assertCount(1, $crawler->filter('form .box-body #product_price'));
    }

    /**
     * @return Crawler
     */
    private function requestEditView($entityName = 'Category', $entityId = '200')
    {
        return $this->getBackendPage(array(
            'action' => 'edit',
            'entity' => $entityName,
            'id' => $entityId,
        ));
    }

    /**
     * @return Crawler
     */
    private function requestNewView($entityName = 'Category')
    {
        return $this->getBackendPage(array(
            'action' => 'new',
            'entity' => $entityName,
        ));
    }
}
