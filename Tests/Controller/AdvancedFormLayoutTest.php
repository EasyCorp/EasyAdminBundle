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

class AdvancedFormLayoutTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'advanced_form_layout'));
    }

    /**
     * This test checks that a complex form layout is properly generated for
     * both 'edit' and 'new' forms. Testing very specific CSS selectors and
     * HTML elements is needed to prevent regressions.
     */
    public function testFormLayout()
    {
        // a dataProvider can't be used because it can't create the Crawlers
        foreach (array('edit', 'new') as $view) {
            $queryParams = array_merge(
                array('action' => $view, 'entity' => 'Product'),
                'edit' === $view ? array('id' => 1) : array()
            );
            $crawler = $this->getBackendPage($queryParams);

            $this->assertSame(
                'product_name',
                $crawler->filter('form .field-group')->eq(0)->filter('.box .box-body input')->attr('id'),
                'The "name" field is displayed in a "group" created automatically to not have ungrouped form fields.'
            );
            $this->assertCount(
                1,
                $crawler->filter('form .field-group')->eq(0)->filter('.box .box-body input'),
                'The "group" created automatically to not have ungrouped form fields only contains one field.'
            );

            $this->assertSame(
                'field-group col-xs-12 col-sm-8',
                $crawler->filter('form .field-group')->eq(1)->attr('class')
            );
            $this->assertSame(
                'Basic information',
                trim($crawler->filter('form .field-group')->eq(1)->filter('.box .box-title')->text())
            );
            $this->assertSame(
                'fa fa-pencil',
                $crawler->filter('form .field-group')->eq(1)->filter('.box .box-title i')->attr('class')
            );
            $this->assertSame(
                'product_description',
                $crawler->filter('form .field-group')->eq(1)->filter('.box .box-body textarea')->attr('id')
            );
            $this->assertCount(
                1,
                $crawler->filter('form .field-group')->eq(1)->filter('.box .box-body .field-divider')
            );
            $this->assertSame(
                '<hr>',
                trim($crawler->filter('form .field-group')->eq(1)->filter('.box .box-body .field-divider')->html())
            );
            $this->assertSame(
                'product_categories',
                $crawler->filter('form .field-group')->eq(1)->filter('.box .box-body select')->attr('id')
            );

            $this->assertSame(
                'field-group col-xs-12 col-sm-4',
                $crawler->filter('form .field-group')->eq(2)->attr('class')
            );
            $this->assertSame(
                'Product Details',
                trim($crawler->filter('form .field-group')->eq(2)->filter('.box .box-title')->text())
            );
            $this->assertCount(
                0,
                $crawler->filter('form .field-group')->eq(2)->filter('.box .box-title i')
            );
            $this->assertSame(
                'product_ean',
                $crawler->filter('form .field-group')->eq(2)->filter('.box .box-body input')->eq(0)->attr('id')
            );
            $this->assertSame(
                'product_price',
                $crawler->filter('form .field-group')->eq(2)->filter('.box .box-body input')->eq(1)->attr('id')
            );
            $this->assertCount(
                1,
                $crawler->filter('form .field-group')->eq(2)->filter('.box .box-body .field-section')
            );
            $this->assertSame(
                'Advanced Settings',
                trim($crawler->filter('form .field-group')->eq(2)->filter('.box .box-body .field-section h2')->text())
            );
            $this->assertSame(
                'fa fa-warning',
                $crawler->filter('form .field-group')->eq(2)->filter('.box .box-body .field-section i')->attr('class')
            );
            $this->assertSame(
                'Reserved for administrators use',
                trim($crawler->filter('form .field-group')->eq(2)->filter('.box .box-body .field-section .help-block')->text())
            );
            $this->assertSame(
                'product_enabled',
                $crawler->filter('form .field-group')->eq(2)->filter('.box .box-body input')->eq(2)->attr('id')
            );
            $this->assertSame(
                'product_createdAt_date_month',
                $crawler->filter('form .field-group')->eq(2)->filter('.box .box-body select')->eq(0)->attr('id')
            );

            $this->assertSame(
                'field-group col-xs-12 col-sm-8 new-row',
                $crawler->filter('form .field-group')->eq(3)->attr('class')
            );
            $this->assertCount(
                0,
                $crawler->filter('form .field-group')->eq(3)->filter('.box .box-title')
            );
            $this->assertCount(
                1,
                $crawler->filter('form .field-group')->eq(3)->filter('.box .box-body div#product_features')
            );

            $this->assertSame(
                'field-group col-xs-12 col-sm-4',
                $crawler->filter('form .field-group')->eq(4)->attr('class')
            );
            $this->assertCount(
                0,
                $crawler->filter('form .field-group')->eq(4)->filter('.box .box-title')
            );
            $this->assertCount(
                1,
                $crawler->filter('form .field-group')->eq(4)->filter('.box .box-body div#product_tags')
            );

            $this->assertSame(
                'field-group col-xs-12 col-sm-4',
                $crawler->filter('form .field-group')->eq(5)->attr('class')
            );
            $this->assertSame(
                'Attachments',
                trim($crawler->filter('form .field-group')->eq(5)->filter('.box .box-title')->text())
            );
            $this->assertSame(
                'fa fa-paperclip',
                $crawler->filter('form .field-group')->eq(5)->filter('.box .box-title i')->attr('class')
            );
            $this->assertSame(
                'PNG format is preferred',
                trim($crawler->filter('form .field-group')->eq(5)->filter('.box .box-body .help-block')->text())
            );
            $this->assertSame(
                'product_image',
                $crawler->filter('form .field-group')->eq(5)->filter('.box .box-body input')->eq(0)->attr('id')
            );

            $this->assertSame(
                'Save changes',
                trim($crawler->filter('#form-actions-row button')->eq(0)->text())
            );
            $this->assertSame(
                'Back to listing',
                trim($crawler->filter('#form-actions-row a.action-list')->text())
            );
            if ('edit' === $view) {
                $this->assertSame(
                    'Delete',
                    trim($crawler->filter('#form-actions-row a.action-delete')->text())
                );
            }
        }
    }
}
